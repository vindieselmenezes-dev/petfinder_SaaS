<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Notificacao
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class Notificacao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Cria uma nova notificação para um usuário
     */
    public function criar(int $usuarioId, string $titulo, string $mensagem, string $tipo = 'Sistema', ?string $link = null): bool
    {
        $sql = "
            INSERT INTO notificacoes (usuario_id, titulo, mensagem, link, tipo, lida)
            VALUES (:usuario_id, :titulo, :mensagem, :link, :tipo, 0)
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':titulo'     => $titulo,
            ':mensagem'   => $mensagem,
            ':link'       => $link,
            ':tipo'       => $tipo
        ]);
    }

    /**
     * Lista as notificações de um usuário, mais recentes primeiro
     */
    public function listarPorUsuario(int $usuarioId, int $limite = 50): array
    {
        $sql = "
            SELECT id, titulo, mensagem, link, tipo, lida, criado_em
            FROM notificacoes
            WHERE usuario_id = :usuario_id
            ORDER BY criado_em DESC, id DESC
            LIMIT :limite
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Conta quantas notificações não lidas um usuário tem
     */
    public function contarNaoLidas(int $usuarioId): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM notificacoes
            WHERE usuario_id = :usuario_id
              AND lida = 0
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca uma notificação específica como lida (só se for do usuário)
     */
    public function marcarComoLida(int $id, int $usuarioId): bool
    {
        $sql = "
            UPDATE notificacoes
            SET lida = 1
            WHERE id = :id
              AND usuario_id = :usuario_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id'         => $id,
            ':usuario_id' => $usuarioId
        ]);
    }

    /**
     * Marca todas as notificações de um usuário como lidas
     */
    public function marcarTodasComoLidas(int $usuarioId): bool
    {
        $sql = "
            UPDATE notificacoes
            SET lida = 1
            WHERE usuario_id = :usuario_id
              AND lida = 0
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([':usuario_id' => $usuarioId]);
    }

    /**
     * Exclui uma notificação (só se for do usuário)
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        $sql = "
            DELETE FROM notificacoes
            WHERE id = :id
              AND usuario_id = :usuario_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id'         => $id,
            ':usuario_id' => $usuarioId
        ]);
    }
}
