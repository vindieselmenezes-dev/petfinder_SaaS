<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: EmpresaSolicitacao
 * ==========================================================
 * Pedido de contratação/orçamento que um tutor faz a uma empresa das
 * categorias que não têm um fluxo de agendamento próprio (Banho e Tosa,
 * Hotel para Pets, Creche Pet, Adestramento). Mesmo espírito de
 * "prestador_solicitacoes", só que apontando pra empresas.id em vez de
 * prestadores_servico.id.
 */

require_once __DIR__ . '/../../config/database.php';

class EmpresaSolicitacao
{
    private PDO $pdo;

    public const STATUS_VALIDOS = ['pendente', 'aceita', 'recusada', 'concluida', 'cancelada'];

    /** Categorias que usam este fluxo genérico (as que não têm agendamento próprio) */
    public const CATEGORIAS_SUPORTADAS = [4, 5, 6];

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function criar(array $dados): int|false
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO empresa_solicitacoes
                (empresa_id, usuario_id, pet_id, servico, data_desejada, periodo, mensagem, busca_em_casa, endereco_busca)
            VALUES
                (:empresa_id, :usuario_id, :pet_id, :servico, :data_desejada, :periodo, :mensagem, :busca_em_casa, :endereco_busca)
        ");

        $buscaEmCasa = !empty($dados['busca_em_casa']) ? 1 : 0;

        $sucesso = $stmt->execute([
            ':empresa_id' => $dados['empresa_id'],
            ':usuario_id' => $dados['usuario_id'],
            ':pet_id' => ($dados['pet_id'] ?? '') ?: null,
            ':servico' => ($dados['servico'] ?? '') ?: null,
            ':data_desejada' => ($dados['data_desejada'] ?? '') ?: null,
            ':periodo' => ($dados['periodo'] ?? '') ?: null,
            ':mensagem' => ($dados['mensagem'] ?? '') ?: null,
            ':busca_em_casa' => $buscaEmCasa,
            ':endereco_busca' => $buscaEmCasa ? (($dados['endereco_busca'] ?? '') ?: null) : null,
        ]);

        return $sucesso ? (int) $this->pdo->lastInsertId() : false;
    }

    /**
     * Busca a solicitação com dados da empresa (dono/equipe), do tutor e do
     * pet -- usado pra montar as notificações.
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id, s.empresa_id, s.usuario_id, s.pet_id, s.servico,
                s.data_desejada, s.periodo, s.mensagem, s.busca_em_casa,
                s.endereco_busca, s.status, s.profissional_responsavel,
                s.data_hora_confirmada, s.observacoes_empresa, s.criado_em,
                s.atualizado_em,
                e.nome_fantasia AS empresa_nome,
                e.usuario_id AS empresa_usuario_id,
                e.categoria_id AS empresa_categoria_id,
                u.nome AS tutor_nome,
                p.nome AS pet_nome
            FROM empresa_solicitacoes s
            INNER JOIN empresas e ON e.id = s.empresa_id
            INNER JOIN usuarios u ON u.id = s.usuario_id
            LEFT JOIN pets p ON p.id = s.pet_id
            WHERE s.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    public function listarPorTutor(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id, s.empresa_id, s.usuario_id, s.pet_id, s.servico,
                s.data_desejada, s.periodo, s.mensagem, s.busca_em_casa,
                s.endereco_busca, s.status, s.profissional_responsavel,
                s.data_hora_confirmada, s.observacoes_empresa, s.criado_em,
                s.atualizado_em,
                e.nome_fantasia AS empresa_nome, e.categoria_id AS empresa_categoria_id, p.nome AS pet_nome
            FROM empresa_solicitacoes s
            INNER JOIN empresas e ON e.id = s.empresa_id
            LEFT JOIN pets p ON p.id = s.pet_id
            WHERE s.usuario_id = :usuario_id
            ORDER BY s.criado_em DESC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function listarPorEmpresa(int $empresaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id, s.empresa_id, s.usuario_id, s.pet_id, s.servico,
                s.data_desejada, s.periodo, s.mensagem, s.busca_em_casa,
                s.endereco_busca, s.status, s.profissional_responsavel,
                s.data_hora_confirmada, s.observacoes_empresa, s.criado_em,
                s.atualizado_em,
                u.nome AS tutor_nome, u.telefone AS tutor_telefone, p.nome AS pet_nome
            FROM empresa_solicitacoes s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            LEFT JOIN pets p ON p.id = s.pet_id
            WHERE s.empresa_id = :empresa_id
            ORDER BY s.criado_em DESC
        ");
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll();
    }

    public function atualizarStatus(int $solicitacaoId, int $empresaId, string $status, array $confirmacao = []): bool
    {
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE empresa_solicitacoes
            SET status = :status,
                profissional_responsavel = COALESCE(:profissional_responsavel, profissional_responsavel),
                data_hora_confirmada = COALESCE(:data_hora_confirmada, data_hora_confirmada),
                observacoes_empresa = COALESCE(:observacoes_empresa, observacoes_empresa)
            WHERE id = :id AND empresa_id = :empresa_id
        ");

        return $stmt->execute([
            ':status' => $status,
            ':profissional_responsavel' => ($confirmacao['profissional_responsavel'] ?? '') ?: null,
            ':data_hora_confirmada' => ($confirmacao['data_hora_confirmada'] ?? '') ?: null,
            ':observacoes_empresa' => ($confirmacao['observacoes_empresa'] ?? '') ?: null,
            ':id' => $solicitacaoId,
            ':empresa_id' => $empresaId,
        ]);
    }

    /**
     * Cancelamento feito pelo próprio tutor (só se ainda não foi atendida)
     */
    public function cancelarPeloTutor(int $solicitacaoId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE empresa_solicitacoes
            SET status = 'cancelada'
            WHERE id = :id
              AND usuario_id = :usuario_id
              AND status IN ('pendente', 'aceita')
        ");

        $stmt->execute([':id' => $solicitacaoId, ':usuario_id' => $usuarioId]);

        return $stmt->rowCount() > 0;
    }
}
