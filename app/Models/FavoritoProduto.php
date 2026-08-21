<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class FavoritoProduto
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
        $this->garantirTabelaFavoritos();
    }

    private function garantirTabelaFavoritos(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS produto_favoritos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                produto_id INT NOT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_prod_fav (usuario_id, produto_id),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->pdo->exec($sql);
    }

    public function existe(int $usuarioId, int $produtoId): bool
    {
        $sql = "
            SELECT 1
            FROM produto_favoritos
            WHERE usuario_id = :usuario_id
              AND produto_id = :produto_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':produto_id' => $produtoId
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function adicionar(int $usuarioId, int $produtoId): bool
    {
        if ($this->existe($usuarioId, $produtoId)) return true;

        $sql = "INSERT INTO produto_favoritos (usuario_id, produto_id) VALUES (:usuario_id, :produto_id)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':produto_id' => $produtoId
        ]);
    }

    public function remover(int $usuarioId, int $produtoId): bool
    {
        $sql = "DELETE FROM produto_favoritos WHERE usuario_id = :usuario_id AND produto_id = :produto_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':produto_id' => $produtoId
        ]);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "
            SELECT
                pf.id,
                p.id AS produto_id,
                p.nome,
                p.preco_venda,
                p.preco_promocional,
                p.ativo,
                (
                    SELECT imagem FROM produto_imagens
                    WHERE produto_id = p.id
                    ORDER BY principal DESC, ordem ASC
                    LIMIT 1
                ) AS imagem_principal,
                pf.criado_em
            FROM produto_favoritos pf
            INNER JOIN produtos p ON p.id = pf.produto_id
            WHERE pf.usuario_id = :usuario_id
            ORDER BY pf.criado_em DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }
}
