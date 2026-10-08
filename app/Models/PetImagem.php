<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: PetImagem
 * Imagens adicionais (galeria) dos pets.
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class PetImagem
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Garante que a tabela de imagens do pet exista.
     */
    private function garantirTabelaImagens(): void
    {
        try {
            $this->pdo->query("SELECT 1 FROM pet_imagens LIMIT 1");
            return;
        } catch (PDOException $e) {
            $mensagem = $e->getMessage();

            if (!str_contains($mensagem, '42S02') && !str_contains($mensagem, 'Base table or view not found')) {
                throw $e;
            }

            $this->pdo->exec(
                "
                CREATE TABLE IF NOT EXISTS pet_imagens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    pet_id INT NOT NULL,
                    arquivo VARCHAR(255) NOT NULL,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_pet_imagem_pet
                        FOREIGN KEY (pet_id)
                        REFERENCES pets(id)
                        ON DELETE CASCADE
                )
                "
            );
        }
    }

    /**
     * Salva as imagens adicionais de um pet
     */
    public function salvarImagens(int $petId, array $imagens): bool
    {
        try {
            $this->garantirTabelaImagens();

            if (empty($imagens)) {
                return true;
            }

            $sql = "
                INSERT INTO pet_imagens
                (
                    pet_id,
                    arquivo
                )
                VALUES
                (
                    :pet_id,
                    :arquivo
                )
            ";

            $stmt = $this->pdo->prepare($sql);

            foreach ($imagens as $imagem) {
                if (
                    !$stmt->execute([
                        ':pet_id' => $petId,
                        ':arquivo' => $imagem
                    ])
                ) {
                    return false;
                }
            }

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * Busca as imagens adicionais de um pet
     */
    public function buscarImagens(int $petId): array
    {
        try {
            $this->garantirTabelaImagens();

            $sql = "
                SELECT id, arquivo
                FROM pet_imagens
                WHERE pet_id = :pet_id
                ORDER BY id
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':pet_id' => $petId]);

            return $stmt->fetchAll();
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * Exclui uma imagem extra específica de um pet (a foto de perfil
     * não é afetada, é só a galeria adicional)
     */
    public function excluirImagem(int $imagemId, int $petId): bool
    {
        $sql = "
            DELETE FROM pet_imagens
            WHERE id = :id
              AND pet_id = :pet_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $imagemId,
            ':pet_id' => $petId,
        ]);
    }
}
