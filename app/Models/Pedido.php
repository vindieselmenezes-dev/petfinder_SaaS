<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Pedido
 *
 * Responsável pela compra direta de produtos (estilo
 * marketplace): cria o pedido, os itens, registra o
 * pagamento e deixa o gatilho `trg_atualiza_estoque` do
 * banco cuidar de descontar o estoque.
 *
 * Sem gateway real integrado, o pagamento só é "aprovado"
 * na hora no modo demonstração (ver PagamentoSimulado). Em
 * produção o pedido nasce "Aguardando Pagamento" e só vira
 * "Pago" por confirmação (admin / futura integração).
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/MetricaEmpresa.php';
require_once __DIR__ . '/Notificacao.php';
require_once __DIR__ . '/../Helpers/PagamentoSimulado.php';

class Pedido
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Cria um pedido a partir dos itens do carrinho.
     *
     * @param int   $usuarioId
     * @param array $itens Lista de ['produto_id' => int, 'quantidade' => int]
     * @param int   $formaPagamentoId
     * @param string|null $cupomCodigo
     *
     * @return array ['sucesso' => bool, 'pedido_id' => ?int, 'numero_pedido' => ?string, 'erro' => ?string]
     */
    public function criar(int $usuarioId, array $itens, int $formaPagamentoId, ?string $cupomCodigo = null, ?int $enderecoId = null): array
    {
        if (empty($itens)) {
            return ['sucesso' => false, 'erro' => 'O carrinho está vazio.'];
        }

        // Reconstrói cada item a partir do banco (nunca confia em preço
        // vindo do formulário) e confere estoque disponível.
        $validacao = $this->validarItens($itens);
        if (isset($validacao['erro'])) {
            return ['sucesso' => false, 'erro' => $validacao['erro']];
        }
        $itensValidados = $validacao['itens'];
        $valorProdutos = $validacao['valor_produtos'];

        // Cupom (opcional)
        $valorDesconto = 0.0;
        $cupom = null;
        if (!empty($cupomCodigo)) {
            $cupom = $this->buscarCupomValido($cupomCodigo, $valorProdutos);
            if ($cupom === false) {
                return ['sucesso' => false, 'erro' => 'Cupom inválido, expirado ou o valor mínimo da compra não foi atingido.'];
            }
            if ($cupom !== null) {
                $valorDesconto = $this->calcularDesconto($cupom, $valorProdutos);
            }
        }

        $valorFrete = 0.0; // sem integração de frete real por enquanto
        $valorTotal = max(0, $valorProdutos - $valorDesconto + $valorFrete);
        $valorComissaoTotal = round(array_sum(array_column($itensValidados, 'valor_comissao')), 2);
        $numeroPedido = $this->gerarNumeroPedido();
        $previsaoEntrega = $this->calcularPrevisaoEntrega();

        // Sem cobrança real, só aprova na hora no modo demonstração.
        $aprovarAgora = PagamentoSimulado::habilitado();
        $statusPedido = $aprovarAgora ? 'Pago' : 'Aguardando Pagamento';

        try {
            $this->pdo->beginTransaction();

            $stmtPedido = $this->pdo->prepare("
                INSERT INTO pedidos
                    (usuario_id, numero_pedido, valor_produtos, valor_frete, valor_desconto, valor_total, valor_comissao_total, status, previsao_entrega, endereco_id)
                VALUES
                    (:usuario_id, :numero_pedido, :valor_produtos, :valor_frete, :valor_desconto, :valor_total, :valor_comissao_total, :status_pedido, :previsao_entrega, :endereco_id)
            ");
            $stmtPedido->execute([
                ':usuario_id' => $usuarioId,
                ':numero_pedido' => $numeroPedido,
                ':valor_produtos' => $valorProdutos,
                ':valor_frete' => $valorFrete,
                ':valor_desconto' => $valorDesconto,
                ':valor_total' => $valorTotal,
                ':valor_comissao_total' => $valorComissaoTotal,
                ':status_pedido' => $statusPedido,
                ':previsao_entrega' => $previsaoEntrega,
                ':endereco_id' => $enderecoId,
            ]);
            $pedidoId = (int) $this->pdo->lastInsertId();

            $this->registrarHistoricoInicial($pedidoId, $aprovarAgora);
            $this->inserirItensPedido($pedidoId, $itensValidados);

            // Pagamento: não existe gateway real integrado ainda. No modo
            // demonstração ele é simulado como aprovado na hora; fora dele
            // fica "Pendente" até alguém confirmar — dá pra trocar essa parte
            // por uma integração de verdade sem mexer no resto do fluxo.
            $stmtPagamento = $this->pdo->prepare("
                INSERT INTO pagamentos (pedido_id, forma_pagamento_id, valor, status, codigo_transacao, data_pagamento)
                VALUES (:pedido_id, :forma_pagamento_id, :valor, :status_pagamento, :codigo_transacao, :data_pagamento)
            ");
            $stmtPagamento->execute([
                ':pedido_id' => $pedidoId,
                ':forma_pagamento_id' => $formaPagamentoId,
                ':valor' => $valorTotal,
                ':status_pagamento' => $aprovarAgora ? 'Aprovado' : 'Pendente',
                ':codigo_transacao' => ($aprovarAgora ? 'SIMULADO-' : 'PENDENTE-') . strtoupper(bin2hex(random_bytes(5))),
                ':data_pagamento' => $aprovarAgora ? date('Y-m-d H:i:s') : null,
            ]);

            if ($cupom !== null) {
                $this->registrarUsoCupom($cupom, $pedidoId, $usuarioId);
            }

            $this->pdo->commit();

            $this->registrarMetricasCheckout($itensValidados, $usuarioId);

            return [
                'sucesso' => true,
                'pedido_id' => $pedidoId,
                'numero_pedido' => $numeroPedido,
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Não foi possível concluir a compra. Tente novamente.'];
        }
    }

    /**
     * Confere cada item no banco (preço e estoque) e calcula comissão/repasse.
     * Retorna ['erro' => string] ou ['itens' => array, 'valor_produtos' => float].
     */
    private function validarItens(array $itens): array
    {
        $itensValidados = [];
        $valorProdutos = 0.0;
        $taxaComissao = $this->taxaComissaoPercentual();

        foreach ($itens as $item) {
            $produtoId = (int) $item['produto_id'];
            $quantidade = max(1, (int) $item['quantidade']);

            $stmtProduto = $this->pdo->prepare("
                SELECT id, nome, preco_venda, preco_promocional, ativo, empresa_id
                FROM produtos
                WHERE id = :id
            ");
            $stmtProduto->execute([':id' => $produtoId]);
            $produto = $stmtProduto->fetch();

            if (!$produto || !$produto['ativo']) {
                $nomeProduto = $produto['nome'] ?? ('produto #' . $produtoId);
                return ['erro' => "O produto \"{$nomeProduto}\" não está mais disponível."];
            }

            $stmtEstoque = $this->pdo->prepare("SELECT quantidade FROM estoque WHERE produto_id = :id");
            $stmtEstoque->execute([':id' => $produtoId]);
            $estoque = $stmtEstoque->fetch();
            $disponivel = $estoque ? (int) $estoque['quantidade'] : 0;

            if ($disponivel < $quantidade) {
                return ['erro' => "Estoque insuficiente para \"{$produto['nome']}\" (disponível: {$disponivel})."];
            }

            $preco = !empty($produto['preco_promocional'])
                ? (float) $produto['preco_promocional']
                : (float) $produto['preco_venda'];

            $subtotal = $preco * $quantidade;
            $valorProdutos += $subtotal;

            $valorComissaoItem = round($subtotal * ($taxaComissao / 100), 2);
            $valorRepasseItem = round($subtotal - $valorComissaoItem, 2);

            $itensValidados[] = [
                'produto_id' => $produtoId,
                'quantidade' => $quantidade,
                'preco_unitario' => $preco,
                'subtotal' => $subtotal,
                'valor_comissao' => $valorComissaoItem,
                'valor_repasse' => $valorRepasseItem,
                // Guardado aqui pra não precisar consultar de novo lá embaixo
                // (evita 1 query extra por item só pra pegar o empresa_id).
                'empresa_id' => (int) $produto['empresa_id'],
            ];
        }

        return ['itens' => $itensValidados, 'valor_produtos' => $valorProdutos];
    }

    private function calcularDesconto(array $cupom, float $valorProdutos): float
    {
        $valorDesconto = $cupom['tipo'] === 'Percentual'
            ? round($valorProdutos * ((float) $cupom['valor'] / 100), 2)
            : (float) $cupom['valor'];

        return min($valorDesconto, $valorProdutos);
    }

    private function registrarHistoricoInicial(int $pedidoId, bool $pagamentoAprovado): void
    {
        try {
            $stmtStatus = $this->pdo->prepare(
                'INSERT INTO pedido_status_historico (pedido_id, status, observacao) VALUES (:pedido_id, :status, :observacao)'
            );
            $stmtStatus->execute([
                ':pedido_id' => $pedidoId,
                ':status' => $pagamentoAprovado ? 'Pago' : 'Aguardando Pagamento',
                ':observacao' => $pagamentoAprovado ? 'Pagamento aprovado.' : 'Pedido registrado. Aguardando confirmação do pagamento.',
            ]);
        } catch (Throwable $exception) {
            // Permite concluir compras durante a janela de aplicação da migration.
        }
    }

    private function inserirItensPedido(int $pedidoId, array $itensValidados): void
    {
        $stmtItem = $this->pdo->prepare("
            INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, subtotal, valor_comissao, valor_repasse)
            VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal, :valor_comissao, :valor_repasse)
        ");
        foreach ($itensValidados as $item) {
            $stmtItem->execute([
                ':pedido_id' => $pedidoId,
                ':produto_id' => $item['produto_id'],
                ':quantidade' => $item['quantidade'],
                ':preco_unitario' => $item['preco_unitario'],
                ':subtotal' => $item['subtotal'],
                ':valor_comissao' => $item['valor_comissao'],
                ':valor_repasse' => $item['valor_repasse'],
            ]);
        }
    }

    private function registrarUsoCupom(array $cupom, int $pedidoId, int $usuarioId): void
    {
        $stmtCupomUso = $this->pdo->prepare("
            INSERT INTO cupons_utilizados (cupom_id, pedido_id, usuario_id)
            VALUES (:cupom_id, :pedido_id, :usuario_id)
        ");
        $stmtCupomUso->execute([
            ':cupom_id' => $cupom['id'],
            ':pedido_id' => $pedidoId,
            ':usuario_id' => $usuarioId,
        ]);

        $stmtCupomDecrementa = $this->pdo->prepare("
            UPDATE cupons SET quantidade = GREATEST(0, quantidade - 1) WHERE id = :id
        ");
        $stmtCupomDecrementa->execute([':id' => $cupom['id']]);
    }

    /**
     * Antes rodava 1 SELECT por item só pra descobrir o empresa_id (N+1);
     * reaproveita o valor já buscado na validação.
     */
    private function registrarMetricasCheckout(array $itensValidados, int $usuarioId): void
    {
        $metricaEmpresa = new MetricaEmpresa();
        foreach ($itensValidados as $item) {
            if ($item['empresa_id'] > 0) {
                $metricaEmpresa->registrar($item['empresa_id'], 'conversao', 'checkout', $item['produto_id'], $usuarioId);
            }
        }
    }

    /**
     * Busca um cupom válido pelo código.
     * Retorna null se nenhum código foi informado corretamente,
     * false se o código existe mas não é válido/aplicável.
     */
    private function buscarCupomValido(string $codigo, float $valorProdutos)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, codigo, descricao, tipo, valor, valor_minimo, data_inicio, data_fim, quantidade, ativo, criado_em
            FROM cupons
            WHERE codigo = :codigo
              AND ativo = 1
              AND quantidade > 0
              AND (data_inicio IS NULL OR data_inicio <= CURDATE())
              AND (data_fim IS NULL OR data_fim >= CURDATE())
            LIMIT 1
        ");
        $stmt->execute([':codigo' => $codigo]);
        $cupom = $stmt->fetch();

        if (!$cupom) {
            return false;
        }

        if (!empty($cupom['valor_minimo']) && $valorProdutos < (float) $cupom['valor_minimo']) {
            return false;
        }

        return $cupom;
    }

    /**
     * Lê a % de comissão do marketplace em `configuracoes` (chave
     * "comissao_marketplace_percentual"). Se a migration_021 ainda não
     * tiver sido aplicada ou a chave não existir, usa 10% como padrão —
     * assim o checkout nunca quebra por causa disso.
     */
    private function taxaComissaoPercentual(): float
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT valor_config FROM configuracoes WHERE chave_config = 'comissao_marketplace_percentual' LIMIT 1"
            );
            $stmt->execute();
            $valor = $stmt->fetchColumn();
            return $valor !== false ? (float) $valor : 10.0;
        } catch (Throwable $exception) {
            return 10.0;
        }
    }

    private function gerarNumeroPedido(): string
    {
        return 'PF' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    /**
     * Calcula uma previsão de entrega simulada (não há integração com
     * transportadora/frete real ainda): considera 5 dias úteis a partir
     * de hoje, pulando sábados e domingos.
     */
    public function calcularPrevisaoEntrega(): string
    {
        $data = new DateTime();
        $diasUteisRestantes = 5;

        while ($diasUteisRestantes > 0) {
            $data->modify('+1 day');
            $diaSemana = (int) $data->format('N'); // 6 = sábado, 7 = domingo
            if ($diaSemana < 6) {
                $diasUteisRestantes--;
            }
        }

        return $data->format('Y-m-d');
    }

    /**
     * Busca um pedido (com os itens) garantindo que pertence ao usuário.
     */
    public function buscarPorId(int $pedidoId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id, p.usuario_id, p.numero_pedido, p.valor_produtos,
                p.valor_frete, p.valor_desconto, p.valor_total,
                p.valor_comissao_total, p.status, p.previsao_entrega,
                p.endereco_id, p.observacoes, p.criado_em, p.atualizado_em,
                p.codigo_rastreio, p.transportadora, p.enviado_em, p.entregue_em,
                fp.nome AS forma_pagamento_nome,
                e.logradouro, e.numero, e.complemento, e.referencia,
                e.bairro, e.cidade, e.estado, e.cep
            FROM pedidos p
            LEFT JOIN pagamentos pg ON pg.pedido_id = p.id
            LEFT JOIN formas_pagamento fp ON fp.id = pg.forma_pagamento_id
            LEFT JOIN enderecos e ON e.id = p.endereco_id
            WHERE p.id = :id AND p.usuario_id = :usuario_id
            LIMIT 1
        ");
        $stmt->execute([':id' => $pedidoId, ':usuario_id' => $usuarioId]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            return null;
        }

        $stmtItens = $this->pdo->prepare("
            SELECT
                pi.id, pi.pedido_id, pi.produto_id, pi.quantidade,
                pi.preco_unitario, pi.subtotal, pi.valor_comissao, pi.valor_repasse,
                pr.nome AS produto_nome, e.nome_fantasia AS empresa_nome
            FROM pedido_itens pi
            JOIN produtos pr ON pr.id = pi.produto_id
            LEFT JOIN empresas e ON e.id = pr.empresa_id
            WHERE pi.pedido_id = :pedido_id
        ");
        $stmtItens->execute([':pedido_id' => $pedidoId]);
        $pedido['itens'] = $stmtItens->fetchAll();

        return $pedido;
    }

    /**
     * Lista os pedidos de um usuário, mais recentes primeiro.
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id, p.usuario_id, p.numero_pedido, p.valor_produtos,
                p.valor_frete, p.valor_desconto, p.valor_total,
                p.valor_comissao_total, p.status, p.previsao_entrega,
                p.endereco_id, p.observacoes, p.criado_em, p.atualizado_em,
                p.codigo_rastreio, p.transportadora, p.enviado_em, p.entregue_em,
                (SELECT COUNT(*) FROM pedido_itens WHERE pedido_id = p.id) AS total_itens
            FROM pedidos p
            WHERE p.usuario_id = :usuario_id
            ORDER BY p.criado_em DESC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function listarStatusHistorico(int $pedidoId, int $usuarioId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT h.status, h.observacao, h.criado_em
                 FROM pedido_status_historico h
                 JOIN pedidos p ON p.id = h.pedido_id
                 WHERE h.pedido_id = :pedido_id AND p.usuario_id = :usuario_id
                 ORDER BY h.criado_em ASC, h.id ASC'
            );
            $stmt->execute([':pedido_id' => $pedidoId, ':usuario_id' => $usuarioId]);
            return $stmt->fetchAll();
        } catch (Throwable $exception) {
            return [];
        }
    }

    /**
     * Lista as vendas de produtos de uma empresa (itens de pedidos que
     * contêm produtos dela), com o valor de comissão retida pela
     * plataforma e o valor de repasse — mais recentes primeiro.
     *
     * Um mesmo pedido pode ter produtos de várias empresas diferentes
     * (carrinho multi-loja); por isso a lista é por ITEM, não por pedido.
     */
    public function listarVendasPorEmpresa(int $empresaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                pi.id AS item_id,
                pi.quantidade,
                pi.preco_unitario,
                pi.subtotal,
                pi.valor_comissao,
                pi.valor_repasse,
                pr.nome AS produto_nome,
                p.id AS pedido_id,
                p.numero_pedido,
                p.status,
                p.criado_em
            FROM pedido_itens pi
            JOIN produtos pr ON pr.id = pi.produto_id
            JOIN pedidos p ON p.id = pi.pedido_id
            WHERE pr.empresa_id = :empresa_id
            ORDER BY p.criado_em DESC, pi.id DESC
        ");
        $stmt->execute([':empresa_id' => $empresaId]);
        return $stmt->fetchAll();
    }

    /**
     * Lista pedidos de todos os clientes para o administrador (confirmação de pagamento).
     *
     * @param string|null $status Filtra por status do pedido (null/'' = todos).
     */
    public function listarParaAdmin(?string $status = null, int $limite = 100): array
    {
        $limite = max(1, min($limite, 200));

        $sql = "
            SELECT
                p.id,
                p.numero_pedido,
                p.status,
                p.valor_total,
                p.criado_em,
                u.nome AS cliente_nome,
                u.sobrenome AS cliente_sobrenome,
                u.email AS cliente_email,
                (SELECT fp.nome
                   FROM pagamentos pg
                   INNER JOIN formas_pagamento fp ON fp.id = pg.forma_pagamento_id
                  WHERE pg.pedido_id = p.id
                  ORDER BY pg.id DESC LIMIT 1) AS forma_pagamento_nome,
                (SELECT pg2.status FROM pagamentos pg2 WHERE pg2.pedido_id = p.id ORDER BY pg2.id DESC LIMIT 1) AS pagamento_status,
                (SELECT COALESCE(SUM(pi.quantidade), 0) FROM pedido_itens pi WHERE pi.pedido_id = p.id) AS total_itens
            FROM pedidos p
            INNER JOIN usuarios u ON u.id = p.usuario_id
        ";

        $parametros = [];
        if ($status !== null && $status !== '') {
            $sql .= ' WHERE p.status = :status';
            $parametros[':status'] = $status;
        }

        $sql .= " ORDER BY p.criado_em DESC, p.id DESC LIMIT {$limite}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Quantos pedidos esperam confirmação de pagamento e quanto dinheiro isso representa.
     *
     * @return array{quantidade: int, valor: float}
     */
    public function resumoAguardandoPagamento(): array
    {
        $linha = $this->pdo->query("
            SELECT COUNT(*) AS quantidade, COALESCE(SUM(valor_total), 0) AS valor
            FROM pedidos
            WHERE status = 'Aguardando Pagamento'
        ")->fetch();

        return [
            'quantidade' => (int) ($linha['quantidade'] ?? 0),
            'valor' => (float) ($linha['valor'] ?? 0),
        ];
    }

    public function statusAtual(int $pedidoId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT status FROM pedidos WHERE id = :id');
        $stmt->execute([':id' => $pedidoId]);
        $status = $stmt->fetchColumn();

        return $status === false ? null : (string) $status;
    }

    public function atualizarStatus(int $pedidoId, string $status, string $observacao = ''): bool
    {
        $validos = ['Aguardando Pagamento', 'Pago', 'Separação', 'Enviado', 'Entregue', 'Cancelado'];
        if (!in_array($status, $validos, true)) {
            return false;
        }
        $stmt = $this->pdo->prepare('SELECT usuario_id, status FROM pedidos WHERE id = :id');
        $stmt->execute([':id' => $pedidoId]);
        $pedido = $stmt->fetch();
        if (!$pedido || $pedido['status'] === $status) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('UPDATE pedidos SET status = :status, enviado_em = IF(:status_envio = "Enviado", NOW(), enviado_em), entregue_em = IF(:status_entregue = "Entregue", NOW(), entregue_em) WHERE id = :id')->execute([
                ':status' => $status,
                ':status_envio' => $status,
                ':status_entregue' => $status,
                ':id' => $pedidoId,
            ]);
            $this->pdo->prepare('INSERT INTO pedido_status_historico (pedido_id, status, observacao) VALUES (:pedido_id, :status, :observacao)')->execute([
                ':pedido_id' => $pedidoId,
                ':status' => $status,
                ':observacao' => $observacao ?: 'Status atualizado.',
            ]);
            // Cancelar um pedido que ainda NÃO foi pago: devolve o estoque (o gatilho do
            // banco desconta ao inserir os itens) e recusa o pagamento pendente.
            if ($status === 'Cancelado' && $pedido['status'] === 'Aguardando Pagamento') {
                $this->pdo->prepare('
                    UPDATE estoque e
                    INNER JOIN (
                        SELECT produto_id, SUM(quantidade) AS quantidade
                        FROM pedido_itens
                        WHERE pedido_id = :id
                        GROUP BY produto_id
                    ) itens ON itens.produto_id = e.produto_id
                    SET e.quantidade = e.quantidade + itens.quantidade
                ')->execute([':id' => $pedidoId]);
                $this->pdo->prepare("UPDATE pagamentos SET status = 'Recusado' WHERE pedido_id = :id AND status = 'Pendente'")->execute([':id' => $pedidoId]);
            }
            // Confirmar o pedido como "Pago" também aprova o pagamento que estava pendente.
            if ($status === 'Pago') {
                $this->pdo->prepare("UPDATE pagamentos SET status = 'Aprovado', data_pagamento = NOW() WHERE pedido_id = :id AND status = 'Pendente'")->execute([':id' => $pedidoId]);
            }
            $this->pdo->commit();
            (new Notificacao())->criar((int) $pedido['usuario_id'], 'Pedido atualizado', 'O pedido #' . $pedidoId . ' agora está: ' . $status . '.', 'Pedido', 'pedido_confirmado.php?id=' . $pedidoId, enviarEmail: true);
            return true;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
