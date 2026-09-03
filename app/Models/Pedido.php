<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Pedido
 *
 * Responsável pela compra direta de produtos (estilo
 * marketplace): cria o pedido, os itens, "aprova" o
 * pagamento (simulado — não há gateway real integrado) e
 * deixa o gatilho `trg_atualiza_estoque` do banco cuidar de
 * descontar o estoque.
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/MetricaEmpresa.php';

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
        $itensValidados = [];
        $valorProdutos = 0.0;

        foreach ($itens as $item) {
            $produtoId = (int) $item['produto_id'];
            $quantidade = max(1, (int) $item['quantidade']);

            $stmtProduto = $this->pdo->prepare("
                SELECT id, nome, preco_venda, preco_promocional, ativo
                FROM produtos
                WHERE id = :id
            ");
            $stmtProduto->execute([':id' => $produtoId]);
            $produto = $stmtProduto->fetch();

            if (!$produto || !$produto['ativo']) {
                $nomeProduto = $produto['nome'] ?? ('produto #' . $produtoId);
                return ['sucesso' => false, 'erro' => "O produto \"{$nomeProduto}\" não está mais disponível."];
            }

            $stmtEstoque = $this->pdo->prepare("SELECT quantidade FROM estoque WHERE produto_id = :id");
            $stmtEstoque->execute([':id' => $produtoId]);
            $estoque = $stmtEstoque->fetch();
            $disponivel = $estoque ? (int) $estoque['quantidade'] : 0;

            if ($disponivel < $quantidade) {
                return [
                    'sucesso' => false,
                    'erro' => "Estoque insuficiente para \"{$produto['nome']}\" (disponível: {$disponivel}).",
                ];
            }

            $preco = !empty($produto['preco_promocional'])
                ? (float) $produto['preco_promocional']
                : (float) $produto['preco_venda'];

            $subtotal = $preco * $quantidade;
            $valorProdutos += $subtotal;

            $itensValidados[] = [
                'produto_id' => $produtoId,
                'quantidade' => $quantidade,
                'preco_unitario' => $preco,
                'subtotal' => $subtotal,
            ];
        }

        // Cupom (opcional)
        $valorDesconto = 0.0;
        $cupom = null;
        if (!empty($cupomCodigo)) {
            $cupom = $this->buscarCupomValido($cupomCodigo, $valorProdutos);
            if ($cupom === false) {
                return ['sucesso' => false, 'erro' => 'Cupom inválido, expirado ou o valor mínimo da compra não foi atingido.'];
            }
            if ($cupom !== null) {
                $valorDesconto = $cupom['tipo'] === 'Percentual'
                    ? round($valorProdutos * ((float) $cupom['valor'] / 100), 2)
                    : (float) $cupom['valor'];
                $valorDesconto = min($valorDesconto, $valorProdutos);
            }
        }

        $valorFrete = 0.0; // sem integração de frete real por enquanto
        $valorTotal = max(0, $valorProdutos - $valorDesconto + $valorFrete);
        $numeroPedido = $this->gerarNumeroPedido();
        $previsaoEntrega = $this->calcularPrevisaoEntrega();

        try {
            $this->pdo->beginTransaction();

            $stmtPedido = $this->pdo->prepare("
                INSERT INTO pedidos
                    (usuario_id, numero_pedido, valor_produtos, valor_frete, valor_desconto, valor_total, status, previsao_entrega, endereco_id)
                VALUES
                    (:usuario_id, :numero_pedido, :valor_produtos, :valor_frete, :valor_desconto, :valor_total, 'Pago', :previsao_entrega, :endereco_id)
            ");
            $stmtPedido->execute([
                ':usuario_id' => $usuarioId,
                ':numero_pedido' => $numeroPedido,
                ':valor_produtos' => $valorProdutos,
                ':valor_frete' => $valorFrete,
                ':valor_desconto' => $valorDesconto,
                ':valor_total' => $valorTotal,
                ':previsao_entrega' => $previsaoEntrega,
                ':endereco_id' => $enderecoId,
            ]);
            $pedidoId = (int) $this->pdo->lastInsertId();

            try {
                $stmtStatus = $this->pdo->prepare(
                    'INSERT INTO pedido_status_historico (pedido_id, status, observacao) VALUES (:pedido_id, :status, :observacao)'
                );
                $stmtStatus->execute([
                    ':pedido_id' => $pedidoId,
                    ':status' => 'Pago',
                    ':observacao' => 'Pagamento aprovado.',
                ]);
            } catch (Throwable $exception) {
                // Permite concluir compras durante a janela de aplicação da migration.
            }

            $stmtItem = $this->pdo->prepare("
                INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, subtotal)
                VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal)
            ");
            foreach ($itensValidados as $item) {
                $stmtItem->execute([
                    ':pedido_id' => $pedidoId,
                    ':produto_id' => $item['produto_id'],
                    ':quantidade' => $item['quantidade'],
                    ':preco_unitario' => $item['preco_unitario'],
                    ':subtotal' => $item['subtotal'],
                ]);
            }

            // Pagamento: não existe gateway real integrado ainda, então o
            // pagamento é simulado como aprovado na hora — dá pra trocar
            // essa parte por uma integração de verdade no futuro sem mexer
            // no resto do fluxo.
            $stmtPagamento = $this->pdo->prepare("
                INSERT INTO pagamentos (pedido_id, forma_pagamento_id, valor, status, codigo_transacao, data_pagamento)
                VALUES (:pedido_id, :forma_pagamento_id, :valor, 'Aprovado', :codigo_transacao, NOW())
            ");
            $stmtPagamento->execute([
                ':pedido_id' => $pedidoId,
                ':forma_pagamento_id' => $formaPagamentoId,
                ':valor' => $valorTotal,
                ':codigo_transacao' => 'SIMULADO-' . strtoupper(bin2hex(random_bytes(5))),
            ]);

            if ($cupom !== null) {
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

            $this->pdo->commit();

            foreach ($itensValidados as $item) {
                $stmtEmpresa = $this->pdo->prepare('SELECT empresa_id FROM produtos WHERE id = :id');
                $stmtEmpresa->execute([':id' => $item['produto_id']]);
                $empresaId = (int) $stmtEmpresa->fetchColumn();
                if ($empresaId > 0) {
                    (new MetricaEmpresa())->registrar($empresaId, 'conversao', 'checkout', $item['produto_id'], $usuarioId);
                }
            }

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
     * Busca um cupom válido pelo código.
     * Retorna null se nenhum código foi informado corretamente,
     * false se o código existe mas não é válido/aplicável.
     */
    private function buscarCupomValido(string $codigo, float $valorProdutos)
    {
        $stmt = $this->pdo->prepare("
            SELECT *
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
            SELECT p.*, fp.nome AS forma_pagamento_nome,
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
            SELECT pi.*, pr.nome AS produto_nome, e.nome_fantasia AS empresa_nome
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
            SELECT p.*,
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
}
