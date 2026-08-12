<?php
/**
 * ==========================================================
 * PETFINDER BRASIL
 * Arquivo: app/Models/BlogComment.php
 * ==========================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class BlogComment
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
        $this->criarTabelaSeNaoExiste();
    }

    private function criarTabelaSeNaoExiste(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS blog_comentarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id TINYINT NOT NULL,
            usuario_id INT NOT NULL,
            comentario TEXT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_blog_comentario_usuario
                FOREIGN KEY (usuario_id)
                REFERENCES usuarios(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->pdo->exec($sql);
    }

    public function salvar(int $postId, int $usuarioId, string $comentario): bool
    {
        $sql = "INSERT INTO blog_comentarios (post_id, usuario_id, comentario)
                VALUES (:post_id, :usuario_id, :comentario)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':post_id' => $postId,
            ':usuario_id' => $usuarioId,
            ':comentario' => $comentario
        ]);
    }

    public function listarPorPostId(int $postId): array
    {
        $sql = "SELECT bc.id,
                       bc.post_id,
                       bc.comentario,
                       bc.criado_em,
                       u.nome,
                       u.sobrenome
                FROM blog_comentarios bc
                JOIN usuarios u ON u.id = bc.usuario_id
                WHERE bc.post_id = :post_id
                ORDER BY bc.criado_em DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':post_id' => $postId]);

        return $stmt->fetchAll();
    }
}
