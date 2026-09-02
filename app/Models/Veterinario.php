<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Veterinario
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Busca o registro de veterinário de um usuário, se existir
     */
    public function buscarPorUsuario(int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM veterinarios WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    public function listarProximos(int $usuarioId, int $limite = 8): array
    {
        $limite = max(1, min($limite, 20));
        $sql = "
            SELECT v.id, v.usuario_id, v.crmv, v.biografia, v.experiencia,
                   v.valor_consulta, v.atendimento_domicilio, v.atendimento_online,
                   v.foto, v.avaliacao, v.total_avaliacoes,
                   CONCAT(u.nome, ' ', u.sobrenome) AS nome,
                   u.telefone, u.latitude, u.longitude,
                   (6371 * ACOS(
                       COS(RADIANS(origin.latitude)) * COS(RADIANS(u.latitude)) *
                       COS(RADIANS(u.longitude) - RADIANS(origin.longitude)) +
                       SIN(RADIANS(origin.latitude)) * SIN(RADIANS(u.latitude))
                   )) AS distancia_km
            FROM veterinarios v
            INNER JOIN usuarios u ON u.id = v.usuario_id AND u.status = 'ativo'
            INNER JOIN usuarios origin ON origin.id = :usuario_id
            WHERE v.ativo = 1
              AND origin.latitude IS NOT NULL AND origin.longitude IS NOT NULL
              AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL
            ORDER BY distancia_km ASC, v.avaliacao DESC, v.total_avaliacoes DESC
            LIMIT {$limite}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Cadastra um usuário como veterinário (CRMV obrigatório, é o
     * registro profissional oficial)
     */
    public function cadastrar(int $usuarioId, string $crmv): int|false
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO veterinarios (usuario_id, crmv, ativo)
            VALUES (?, ?, 1)
        ");

        if (!$stmt->execute([$usuarioId, $crmv])) {
            return false;
        }

        return (int) $this->pdo->lastInsertId();
    }
}
