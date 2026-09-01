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
