<?php
/**
 * ==========================================================
 * PETFINDER BRASIL
 * Arquivo: app/Models/BlogShare.php
 * ==========================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class BlogShare
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
        $this->criarTabelaSeNaoExiste();
    }

    private function criarTabelaSeNaoExiste(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS blog_shares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id TINYINT NOT NULL,
            usuario_id INT NOT NULL,
            rede_social VARCHAR(60) NOT NULL,
            compartilhado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_blog_share_usuario
                FOREIGN KEY (usuario_id)
                REFERENCES usuarios(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->pdo->exec($sql);
    }

    public function salvar(int $postId, int $usuarioId, string $redeSocial): bool
    {
        $sql = "INSERT INTO blog_shares (post_id, usuario_id, rede_social)
                VALUES (:post_id, :usuario_id, :rede_social)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':post_id' => $postId,
            ':usuario_id' => $usuarioId,
            ':rede_social' => $redeSocial
        ]);
    }
}
