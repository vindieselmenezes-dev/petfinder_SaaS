<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Mensagem
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function enviar(int $conversaId, int $remetenteId, string $texto): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO mensagens (conversa_id, remetente_id, mensagem, lida)
            VALUES (:conversa_id, :remetente_id, :mensagem, 0)
        ");
        $stmt->execute([
            ':conversa_id'  => $conversaId,
            ':remetente_id' => $remetenteId,
            ':mensagem'     => $texto,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listarPorConversa(int $conversaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT m.*, u.nome AS remetente_nome
            FROM mensagens m
            INNER JOIN usuarios u ON u.id = m.remetente_id
            WHERE m.conversa_id = :conversa_id
            ORDER BY m.enviado_em ASC, m.id ASC
        ");
        $stmt->execute([':conversa_id' => $conversaId]);

        return $stmt->fetchAll();
    }

    /**
     * Marca como lidas todas as mensagens de uma conversa que não
     * foram enviadas pelo próprio usuário (ou seja, as que ele recebeu)
     */
    public function marcarComoLidas(int $conversaId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE mensagens
            SET lida = 1
            WHERE conversa_id = :conversa_id
              AND remetente_id != :usuario_id
        ");

        return $stmt->execute([':conversa_id' => $conversaId, ':usuario_id' => $usuarioId]);
    }
}
