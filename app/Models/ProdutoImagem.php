<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: ProdutoImagem
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class ProdutoImagem
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function salvarImagens(int $produtoId, array $imagens): bool
    {
        if (empty($imagens)) {
            return true;
        }

        // Verifica se já existe alguma imagem principal
        $sqlVerifica = "SELECT COUNT(*) FROM produto_imagens WHERE produto_id = :produto_id AND principal = 1";
        $stmt = $this->pdo->prepare($sqlVerifica);
        $stmt->execute([':produto_id' => $produtoId]);
        $jaTemPrincipal = ((int) $stmt->fetchColumn()) > 0;

        $sql = "
            INSERT INTO produto_imagens (produto_id, imagem, principal, ordem)
            VALUES (:produto_id, :imagem, :principal, :ordem)
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($imagens as $ordem => $imagem) {

            $ehPrincipal = (!$jaTemPrincipal && $ordem === 0) ? 1 : 0;

            $stmt->execute([
                ':produto_id' => $produtoId,
                ':imagem' => $imagem,
                ':principal' => $ehPrincipal,
                ':ordem' => $ordem + 1
            ]);

        }

        return true;
    }

    public function buscarImagens(int $produtoId): array
    {
        $sql = "
            SELECT id, imagem, principal, ordem
            FROM produto_imagens
            WHERE produto_id = :produto_id
            ORDER BY principal DESC, ordem ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':produto_id' => $produtoId]);

        return $stmt->fetchAll();
    }

    public function excluirImagem(int $imagemId, int $produtoId): bool
    {
        $sql = "
            DELETE FROM produto_imagens
            WHERE id = :id
              AND produto_id = :produto_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $imagemId,
            ':produto_id' => $produtoId
        ]);
    }
}
