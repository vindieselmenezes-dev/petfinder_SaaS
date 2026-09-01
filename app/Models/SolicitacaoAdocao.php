<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class SolicitacaoAdocao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Já existe uma solicitação PENDENTE desse usuário pra esse pet?
     */
    public function existePendente(int $petId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM solicitacoes_adocao
            WHERE pet_id = :pet_id AND usuario_solicitante_id = :usuario_id AND status = 'Pendente'
            LIMIT 1
        ");
        $stmt->execute([':pet_id' => $petId, ':usuario_id' => $usuarioId]);

        return (bool) $stmt->fetch();
    }

    public function criar(int $petId, int $usuarioId, ?int $conversaId, string $mensagem): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO solicitacoes_adocao (pet_id, usuario_solicitante_id, conversa_id, mensagem, status)
            VALUES (:pet_id, :usuario_id, :conversa_id, :mensagem, 'Pendente')
        ");
        $stmt->execute([
            ':pet_id'      => $petId,
            ':usuario_id'  => $usuarioId,
            ':conversa_id' => $conversaId,
            ':mensagem'    => $mensagem,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT sa.*, p.nome AS pet_nome, p.usuario_id AS pet_dono_id,
                   u.nome AS solicitante_nome, u.email AS solicitante_email
            FROM solicitacoes_adocao sa
            INNER JOIN pets p ON p.id = sa.pet_id
            INNER JOIN usuarios u ON u.id = sa.usuario_solicitante_id
            WHERE sa.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Solicitações que EU enviei (sou o interessado)
     */
    public function listarEnviadas(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT sa.*, p.nome AS pet_nome, p.foto AS pet_foto, p.status AS pet_status
            FROM solicitacoes_adocao sa
            INNER JOIN pets p ON p.id = sa.pet_id
            WHERE sa.usuario_solicitante_id = :usuario_id
            ORDER BY sa.criado_em DESC, sa.id DESC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Solicitações que EU recebi (sou dono dos pets envolvidos)
     */
    public function listarRecebidas(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT sa.*, p.nome AS pet_nome, p.foto AS pet_foto, p.status AS pet_status,
                   u.nome AS solicitante_nome
            FROM solicitacoes_adocao sa
            INNER JOIN pets p ON p.id = sa.pet_id
            INNER JOIN usuarios u ON u.id = sa.usuario_solicitante_id
            WHERE p.usuario_id = :usuario_id
            ORDER BY
                FIELD(sa.status, 'Pendente', 'Aprovada', 'Rejeitada', 'Cancelada'),
                sa.criado_em DESC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function atualizarStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE solicitacoes_adocao SET status = :status WHERE id = :id");

        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * Quando uma solicitação é aprovada, todas as outras solicitações
     * pendentes pro mesmo pet são automaticamente rejeitadas (o pet
     * já foi pra outra pessoa)
     */
    public function rejeitarDemaisPendentes(int $petId, int $idAprovada): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE solicitacoes_adocao
            SET status = 'Rejeitada'
            WHERE pet_id = :pet_id AND id != :id_aprovada AND status = 'Pendente'
        ");

        return $stmt->execute([':pet_id' => $petId, ':id_aprovada' => $idAprovada]);
    }
}
