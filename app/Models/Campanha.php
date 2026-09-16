<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Campanha
 * ==========================================================
 * Campanhas de arrecadação, eventos e pedidos de doação publicados
 * pelos parceiros (ONGs e empresas apoiadoras).
 *
 * Toda consulta pública passa por um JOIN com `parceiros` filtrando
 * status = 'aprovado': se a parceria for recusada ou inativada, as
 * campanhas dela somem do site na mesma hora, sem precisar mexer em
 * cada campanha uma por uma.
 */

require_once __DIR__ . '/../../config/database.php';

class Campanha
{
    private PDO $pdo;

    /** Rótulos e ícones de cada tipo de publicação */
    public const TIPOS = [
        'campanha' => ['rotulo' => 'Campanha', 'icone' => '🎯'],
        'evento'   => ['rotulo' => 'Evento',   'icone' => '📅'],
        'doacao'   => ['rotulo' => 'Doação',   'icone' => '🎁'],
    ];

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Lê em `configuracoes` (chave "moderacao_campanhas_ativa") se
     * publicações novas devem entrar como pendente, exigindo aprovação
     * de um administrador antes de aparecer no site.
     *
     * Se a migration_026 ainda não tiver sido aplicada ou a chave não
     * existir, assume desligada (false) — publica na hora, comportamento
     * de sempre. Mesmo padrão de `Pedido::taxaComissaoPercentual()`.
     */
    public function moderacaoAtiva(): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT valor_config FROM configuracoes WHERE chave_config = 'moderacao_campanhas_ativa' LIMIT 1"
            );
            $stmt->execute();
            $valor = $stmt->fetchColumn();
            return $valor !== false && (string) $valor === '1';
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Lista as publicações ativas de parceiros aprovados.
     *
     * @param string $tipo    'campanha', 'evento', 'doacao' ou '' para todos
     * @param int    $limite  0 = sem limite
     * @param int    $offset  quantos registros pular (para paginação)
     * @return array<int, array<string, mixed>>
     */
    public function listarAtivas(
        string $tipo = '',
        int $limite = 0,
        bool $apenasDestaques = false,
        int $offset = 0
    ): array {
        $sql = "
            SELECT c.*,
                   p.nome AS parceiro_nome,
                   p.tipo AS parceiro_tipo,
                   p.logo AS parceiro_logo,
                   p.cidade AS parceiro_cidade,
                   p.estado AS parceiro_estado
            FROM parceiro_campanhas c
            JOIN parceiros p ON p.id = c.parceiro_id
            WHERE c.status = 'ativa'
              AND c.status_moderacao = 'aprovada'
              AND p.status = 'aprovado'
              AND (c.data_fim IS NULL OR c.data_fim >= NOW())
        ";

        $parametros = [];

        if ($tipo !== '' && isset(self::TIPOS[$tipo])) {
            $sql .= " AND c.tipo = :tipo";
            $parametros[':tipo'] = $tipo;
        }

        if ($apenasDestaques) {
            $sql .= " AND c.destaque = 1";
        }

        // Destaques primeiro; depois o que termina mais cedo (mais urgente);
        // campanhas sem prazo ficam no fim do bloco.
        $sql .= "
            ORDER BY c.destaque DESC,
                     CASE WHEN c.data_fim IS NULL THEN 1 ELSE 0 END,
                     c.data_fim ASC,
                     c.criado_em DESC
        ";

        if ($limite > 0) {
            $limite = min($limite, 60);
            $offset = max(0, $offset);
            $sql .= " LIMIT {$limite} OFFSET {$offset}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Conta quantas publicações ativas existem com os mesmos filtros de
     * listarAtivas() — usado para montar a paginação do hub de parceiros.
     */
    public function contarAtivas(string $tipo = ''): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM parceiro_campanhas c
            JOIN parceiros p ON p.id = c.parceiro_id
            WHERE c.status = 'ativa'
              AND c.status_moderacao = 'aprovada'
              AND p.status = 'aprovado'
              AND (c.data_fim IS NULL OR c.data_fim >= NOW())
        ";

        $parametros = [];

        if ($tipo !== '' && isset(self::TIPOS[$tipo])) {
            $sql .= " AND c.tipo = :tipo";
            $parametros[':tipo'] = $tipo;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Publicações de um parceiro específico.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarPorParceiro(int $parceiroId, bool $apenasAtivas = true): array
    {
        $sql = "
            SELECT *
            FROM parceiro_campanhas
            WHERE parceiro_id = :parceiro_id
        ";

        if ($apenasAtivas) {
            $sql .= " AND status = 'ativa'";
        }

        $sql .= " ORDER BY destaque DESC, criado_em DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':parceiro_id' => $parceiroId]);

        return $stmt->fetchAll();
    }

    /**
     * Fila de moderação (admin_campanhas.php): publicações com dados do
     * parceiro dono, filtráveis por status_moderacao.
     *
     * @param string $status 'pendente', 'aprovada', 'recusada' ou '' para todas
     * @return array<int, array<string, mixed>>
     */
    public function listarParaModeracao(string $status = ''): array
    {
        $sql = "
            SELECT c.*, p.nome AS parceiro_nome, p.usuario_id AS parceiro_usuario_id
            FROM parceiro_campanhas c
            JOIN parceiros p ON p.id = c.parceiro_id
        ";

        $parametros = [];

        if ($status !== '' && in_array($status, ['pendente', 'aprovada', 'recusada'], true)) {
            $sql .= " WHERE c.status_moderacao = :status";
            $parametros[':status'] = $status;
        }

        // Pendentes primeiro: é o que exige ação de quem abriu a tela.
        $sql .= " ORDER BY (c.status_moderacao = 'pendente') DESC, c.criado_em DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Quantas publicações estão esperando revisão (contador do dashboard
     * e do menu do admin).
     */
    public function contarPendentesModeracao(): int
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) FROM parceiro_campanhas WHERE status_moderacao = 'pendente'"
            );
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Aprova ou recusa uma publicação na moderação. Recusada some do
     * site (mesmo filtro de listarAtivas/buscarPorId), mas continua
     * visível pro parceiro no painel dele, com o motivo.
     */
    public function alterarStatusModeracao(int $id, string $status, ?string $observacao = null): bool
    {
        if (!in_array($status, ['pendente', 'aprovada', 'recusada'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE parceiro_campanhas
               SET status_moderacao = :status, observacao_moderacao = :observacao
             WHERE id = :id
        ");

        return $stmt->execute([
            ':status'     => $status,
            ':observacao' => $observacao ?: null,
            ':id'         => $id,
        ]);
    }

    /**
     * Busca uma publicação com os dados do parceiro dono.
     */
    public function buscarPorId(int $id, bool $apenasPublicas = true): ?array
    {
        $sql = "
            SELECT c.*,
                   p.id AS parceiro_id,
                   p.nome AS parceiro_nome,
                   p.tipo AS parceiro_tipo,
                   p.logo AS parceiro_logo,
                   p.cidade AS parceiro_cidade,
                   p.estado AS parceiro_estado,
                   p.whatsapp AS parceiro_whatsapp,
                   p.email_contato AS parceiro_email,
                   p.chave_pix AS parceiro_pix,
                   p.link_doacao AS parceiro_link_doacao,
                   p.usuario_id AS parceiro_usuario_id,
                   p.status AS parceiro_status
            FROM parceiro_campanhas c
            JOIN parceiros p ON p.id = c.parceiro_id
            WHERE c.id = :id
        ";

        if ($apenasPublicas) {
            $sql .= " AND c.status = 'ativa' AND c.status_moderacao = 'aprovada' AND p.status = 'aprovado'";
        }

        $stmt = $this->pdo->prepare($sql . " LIMIT 1");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function criar(array $dados): int|false
    {
        // Se a moderação por campanha estiver ligada (configuracoes ->
        // moderacao_campanhas_ativa), toda publicação nova nasce
        // "pendente" e só entra no ar depois que um admin aprovar em
        // admin_campanhas.php. Desligada (padrão), nasce "aprovada" e
        // publica na hora, como sempre foi.
        $statusModeracao = $this->moderacaoAtiva() ? 'pendente' : 'aprovada';

        $sql = "
            INSERT INTO parceiro_campanhas
                (parceiro_id, tipo, titulo, resumo, descricao, imagem, local_evento,
                 data_inicio, data_fim, meta_valor, itens_desejados, chave_pix,
                 link_externo, status, status_moderacao)
            VALUES
                (:parceiro_id, :tipo, :titulo, :resumo, :descricao, :imagem, :local_evento,
                 :data_inicio, :data_fim, :meta_valor, :itens_desejados, :chave_pix,
                 :link_externo, :status, :status_moderacao)
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->parametrosComuns($dados) + [
                ':parceiro_id'      => $dados['parceiro_id'],
                ':imagem'           => $dados['imagem'] ?: null,
                ':status_moderacao' => $statusModeracao,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('Erro ao criar campanha: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param bool $reenviarModeracao Quando true (padrão — é o caso de um
     *                                parceiro editando o conteúdo pelo
     *                                campanha_form.php), e a moderação por
     *                                campanha estiver ligada, a edição
     *                                volta o status de moderação para
     *                                "pendente": senão, bastaria ter uma
     *                                campanha aprovada uma vez pra depois
     *                                trocar o conteúdo livremente sem
     *                                revisão nenhuma. admin_campanhas.php
     *                                usa false aqui, porque lá quem está
     *                                mudando é o próprio admin.
     */
    public function atualizar(int $id, array $dados, bool $reenviarModeracao = true): bool
    {
        $sql = "
            UPDATE parceiro_campanhas SET
                tipo = :tipo,
                titulo = :titulo,
                resumo = :resumo,
                descricao = :descricao,
                local_evento = :local_evento,
                data_inicio = :data_inicio,
                data_fim = :data_fim,
                meta_valor = :meta_valor,
                itens_desejados = :itens_desejados,
                chave_pix = :chave_pix,
                link_externo = :link_externo,
                status = :status
        ";

        $parametros = $this->parametrosComuns($dados) + [':id' => $id];

        if (!empty($dados['imagem'])) {
            $sql .= ", imagem = :imagem";
            $parametros[':imagem'] = $dados['imagem'];
        }

        if ($reenviarModeracao && $this->moderacaoAtiva()) {
            $sql .= ", status_moderacao = 'pendente', observacao_moderacao = NULL";
        }

        $sql .= " WHERE id = :id";

        try {
            return $this->pdo->prepare($sql)->execute($parametros);
        } catch (PDOException $e) {
            error_log('Erro ao atualizar campanha: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Campos que criar() e atualizar() têm em comum, já normalizados
     * (string vazia do formulário vira NULL no banco).
     *
     * @return array<string, mixed>
     */
    private function parametrosComuns(array $dados): array
    {
        $tipo = isset(self::TIPOS[$dados['tipo'] ?? '']) ? $dados['tipo'] : 'campanha';
        $status = in_array($dados['status'] ?? '', ['rascunho', 'ativa', 'encerrada'], true)
            ? $dados['status']
            : 'ativa';

        return [
            ':tipo'            => $tipo,
            ':titulo'          => $dados['titulo'],
            ':resumo'          => $dados['resumo'] ?: null,
            ':descricao'       => $dados['descricao'] ?: null,
            ':local_evento'    => $dados['local_evento'] ?: null,
            ':data_inicio'     => $dados['data_inicio'] ?: null,
            ':data_fim'        => $dados['data_fim'] ?: null,
            ':meta_valor'      => ($dados['meta_valor'] ?? '') !== '' ? $dados['meta_valor'] : null,
            ':itens_desejados' => $dados['itens_desejados'] ?: null,
            ':chave_pix'       => $dados['chave_pix'] ?: null,
            ':link_externo'    => $dados['link_externo'] ?: null,
            ':status'          => $status,
        ];
    }

    public function excluir(int $id, int $parceiroId): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM parceiro_campanhas WHERE id = :id AND parceiro_id = :parceiro_id
        ");

        return $stmt->execute([':id' => $id, ':parceiro_id' => $parceiroId]);
    }

    /**
     * Contador simples de visualizações (não conta o próprio dono).
     */
    public function registrarVisualizacao(int $id): void
    {
        try {
            $this->pdo->prepare("
                UPDATE parceiro_campanhas SET visualizacoes = visualizacoes + 1 WHERE id = :id
            ")->execute([':id' => $id]);
        } catch (PDOException $e) {
            // Métrica não pode derrubar a página: só registra no log.
            error_log('Erro ao registrar visualização de campanha: ' . $e->getMessage());
        }
    }

    /**
     * Registra alguém que quer doar, ser voluntário ou divulgar.
     * O valor informado NÃO é somado ao arrecadado — quem confirma o
     * recebimento é o parceiro, pelo painel.
     */
    public function registrarApoio(int $campanhaId, ?int $usuarioId, array $dados): bool
    {
        $tipo = in_array($dados['tipo'] ?? '', ['doacao', 'voluntariado', 'divulgacao', 'duvida'], true)
            ? $dados['tipo']
            : 'doacao';

        $stmt = $this->pdo->prepare("
            INSERT INTO campanha_apoios (campanha_id, usuario_id, tipo, valor, mensagem, contato)
            VALUES (:campanha_id, :usuario_id, :tipo, :valor, :mensagem, :contato)
        ");

        try {
            return $stmt->execute([
                ':campanha_id' => $campanhaId,
                ':usuario_id'  => $usuarioId,
                ':tipo'        => $tipo,
                ':valor'       => ($dados['valor'] ?? '') !== '' ? $dados['valor'] : null,
                ':mensagem'    => $dados['mensagem'] ?: null,
                ':contato'     => $dados['contato'] ?: null,
            ]);
        } catch (PDOException $e) {
            error_log('Erro ao registrar apoio: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Apoios recebidos por uma campanha (painel do parceiro).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarApoios(int $campanhaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.nome AS usuario_nome, u.email AS usuario_email
            FROM campanha_apoios a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            WHERE a.campanha_id = :campanha_id
            ORDER BY a.criado_em DESC
        ");
        $stmt->execute([':campanha_id' => $campanhaId]);

        return $stmt->fetchAll();
    }

    /**
     * Quantos apoios cada campanha de um parceiro já recebeu.
     *
     * @return array<int, int> [campanha_id => total]
     */
    public function contarApoiosPorParceiro(int $parceiroId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id, COUNT(a.id) AS total
            FROM parceiro_campanhas c
            LEFT JOIN campanha_apoios a ON a.campanha_id = c.id
            WHERE c.parceiro_id = :parceiro_id
            GROUP BY c.id
        ");
        $stmt->execute([':parceiro_id' => $parceiroId]);

        $totais = [];
        foreach ($stmt->fetchAll() as $linha) {
            $totais[(int) $linha['id']] = (int) $linha['total'];
        }

        return $totais;
    }

    /**
     * Atualiza o total já arrecadado (informado pelo parceiro).
     */
    public function atualizarArrecadado(int $id, int $parceiroId, float $valor): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE parceiro_campanhas
               SET valor_arrecadado = :valor
             WHERE id = :id AND parceiro_id = :parceiro_id
        ");

        return $stmt->execute([
            ':valor'       => max(0, $valor),
            ':id'          => $id,
            ':parceiro_id' => $parceiroId,
        ]);
    }

    /**
     * Percentual da meta atingido (0 a 100). Devolve null quando a
     * publicação não tem meta em dinheiro — aí a barra nem aparece.
     */
    public static function percentualMeta(?float $meta, ?float $arrecadado): ?int
    {
        if ($meta === null || $meta <= 0) {
            return null;
        }

        return (int) min(100, round((($arrecadado ?? 0) / $meta) * 100));
    }

    public static function rotuloTipo(?string $tipo): string
    {
        return self::TIPOS[$tipo ?? '']['rotulo'] ?? 'Publicação';
    }

    public static function iconeTipo(?string $tipo): string
    {
        return self::TIPOS[$tipo ?? '']['icone'] ?? '🐾';
    }
}
