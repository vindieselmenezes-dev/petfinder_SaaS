<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Favorito
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
            CREATE TABLE IF NOT EXISTS pet_favoritos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                pet_id INT NOT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_favorito (usuario_id, pet_id),
                FOREIGN KEY (usuario_id)
                    REFERENCES usuarios(id)
                    ON DELETE CASCADE,
                FOREIGN KEY (pet_id)
                    REFERENCES pets(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->pdo->exec($sql);
    }

    public function existe(int $usuarioId, int $petId): bool
    {
        $sql = "
            SELECT 1
            FROM favoritos
            WHERE usuario_id = :usuario_id
              AND pet_id = :pet_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':pet_id'     => $petId
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function adicionar(int $usuarioId, int $petId): bool
    {
        if ($this->existe($usuarioId, $petId)) {
            return true;
        }

        $sql = "
            INSERT INTO favoritos (usuario_id, pet_id)
            VALUES (:usuario_id, :pet_id)
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':pet_id'     => $petId
        ]);
    }

    public function remover(int $usuarioId, int $petId): bool
    {
        $sql = "
            DELETE FROM favoritos
            WHERE usuario_id = :usuario_id
              AND pet_id = :pet_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':pet_id'     => $petId
        ]);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "
            SELECT
                pf.id,
                p.id AS pet_id,
                p.nome,
                p.foto,
                p.status,
                e.nome AS especie,
                r.nome AS raca,
                pf.criado_em
            FROM pet_favoritos pf
            INNER JOIN pets p
                ON p.id = pf.pet_id
            INNER JOIN especies e
                ON e.id = p.especie_id
            INNER JOIN racas r
                ON r.id = p.raca_id
            WHERE pf.usuario_id = :usuario_id
            ORDER BY pf.criado_em DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }
}
