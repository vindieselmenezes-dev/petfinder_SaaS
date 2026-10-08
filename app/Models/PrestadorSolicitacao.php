<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: PrestadorSolicitacao
 * Solicitações de serviço feitas a prestadores.
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class PrestadorSolicitacao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function criarSolicitacao(array $dados): int|false
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prestador_solicitacoes
            (prestador_id, usuario_id, pet_id, data_desejada, periodo, mensagem)
            VALUES
            (:prestador_id, :usuario_id, :pet_id, :data_desejada, :periodo, :mensagem)
        ");

        $sucesso = $stmt->execute([
            ':prestador_id' => $dados['prestador_id'],
            ':usuario_id' => $dados['usuario_id'],
            ':pet_id' => $this->textoOuNulo($dados, 'pet_id'),
            ':data_desejada' => $this->textoOuNulo($dados, 'data_desejada'),
            ':periodo' => $this->textoOuNulo($dados, 'periodo'),
            ':mensagem' => $this->textoOuNulo($dados, 'mensagem'),
        ]);

        return $sucesso ? (int) $this->pdo->lastInsertId() : false;
    }

    public function listarSolicitacoesRecebidas(int $prestadorId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id, s.prestador_id, s.usuario_id, s.pet_id, s.data_desejada,
                s.periodo, s.mensagem, s.status, s.criado_em, s.atualizado_em,
                u.nome AS tutor_nome, u.telefone AS tutor_telefone, p.nome AS pet_nome
            FROM prestador_solicitacoes s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            LEFT JOIN pets p ON p.id = s.pet_id
            WHERE s.prestador_id = :prestador_id
            ORDER BY s.criado_em DESC
        ");
        $stmt->execute([':prestador_id' => $prestadorId]);

        return $stmt->fetchAll();
    }

    /**
     * Busca uma solicitação com dados do tutor, do prestador (usuario_id
     * dono do perfil) e do pet -- usado pra montar as notificações.
     */
    public function buscarSolicitacaoPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id, s.prestador_id, s.usuario_id, s.pet_id, s.data_desejada,
                s.periodo, s.mensagem, s.status, s.criado_em, s.atualizado_em,
                pr.usuario_id AS prestador_usuario_id,
                pr.tipo AS prestador_tipo,
                u.nome AS tutor_nome,
                p.nome AS pet_nome
            FROM prestador_solicitacoes s
            INNER JOIN prestadores_servico pr ON pr.id = s.prestador_id
            INNER JOIN usuarios u ON u.id = s.usuario_id
            LEFT JOIN pets p ON p.id = s.pet_id
            WHERE s.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    public function listarSolicitacoesFeitas(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id, s.prestador_id, s.usuario_id, s.pet_id, s.data_desejada,
                s.periodo, s.mensagem, s.status, s.criado_em, s.atualizado_em,
                pr.tipo, u.nome AS prestador_nome
            FROM prestador_solicitacoes s
            INNER JOIN prestadores_servico pr ON pr.id = s.prestador_id
            INNER JOIN usuarios u ON u.id = pr.usuario_id
            WHERE s.usuario_id = :usuario_id
            ORDER BY s.criado_em DESC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function atualizarStatusSolicitacao(int $solicitacaoId, int $prestadorId, string $status): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE prestador_solicitacoes
            SET status = :status
            WHERE id = :id AND prestador_id = :prestador_id
        ");

        return $stmt->execute([
            ':status' => $status,
            ':id' => $solicitacaoId,
            ':prestador_id' => $prestadorId
        ]);
    }

    /** Texto do formulário: vazio vira null. */
    private function textoOuNulo(array $dados, string $campo): mixed
    {
        return ($dados[$campo] ?? '') ?: null;
    }
}
