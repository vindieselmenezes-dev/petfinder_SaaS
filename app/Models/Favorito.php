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
        // A tabela real e oficial do sistema é `favoritos` — mais genérica,
        // já preparada pra favoritar empresa/produto/veterinário além de pet.
        // (Havia uma tabela paralela `pet_favoritos` sendo criada aqui por
        // engano, onde nada nunca era escrito — listarPorUsuario() lia dela
        // e por isso "Meus Favoritos" sempre aparecia vazio.)
        $sql = "
            CREATE TABLE IF NOT EXISTS favoritos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                pet_id INT NOT NULL,
                empresa_id INT NULL,
                produto_id INT NULL,
                veterinario_id INT NULL,
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
                f.id,
                p.id AS pet_id,
                p.nome,
                p.foto,
                p.status,
                e.nome AS especie,
                r.nome AS raca,
                (
                    SELECT cidade FROM enderecos
                    WHERE usuario_id = p.usuario_id
                    ORDER BY principal DESC, id ASC
                    LIMIT 1
                ) AS cidade,
                u.nome AS tutor_nome,
                u.telefone AS tutor_telefone,
                f.criado_em
            FROM favoritos f
            INNER JOIN pets p
                ON p.id = f.pet_id
            INNER JOIN especies e
                ON e.id = p.especie_id
            INNER JOIN racas r
                ON r.id = p.raca_id
            INNER JOIN usuarios u
                ON u.id = p.usuario_id
            WHERE f.usuario_id = :usuario_id
            ORDER BY f.criado_em DESC, f.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }
}
