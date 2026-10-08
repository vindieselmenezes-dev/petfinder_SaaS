<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: ProdutoEstoque
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class ProdutoEstoque
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function buscarEstoque(int $produtoId): ?array
    {
        $sql = "
            SELECT id, produto_id, quantidade, estoque_minimo, estoque_maximo, ultima_atualizacao
            FROM estoque
            WHERE produto_id = :produto_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':produto_id' => $produtoId]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    public function atualizarEstoque(int $produtoId, int $quantidade, int $min, int $max): bool
    {
        $existente = $this->buscarEstoque($produtoId);

        if ($existente) {

            $sql = "
                UPDATE estoque
                SET quantidade = :quantidade,
                    estoque_minimo = :minimo,
                    estoque_maximo = :maximo
                WHERE produto_id = :produto_id
            ";

        } else {

            $sql = "
                INSERT INTO estoque (produto_id, quantidade, estoque_minimo, estoque_maximo)
                VALUES (:produto_id, :quantidade, :minimo, :maximo)
            ";

        }

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':produto_id' => $produtoId,
            ':quantidade' => $quantidade,
            ':minimo' => $min,
            ':maximo' => $max
        ]);
    }
}
