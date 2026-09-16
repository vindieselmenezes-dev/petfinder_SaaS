<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class ResetSenha
{
    private PDO $pdo;

    private const VALIDADE_MINUTOS = 60;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Gera um novo token de redefinição pra um usuário, válido por 1 hora
     */
    public function gerarToken(int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiraEm = date('Y-m-d H:i:s', strtotime('+' . self::VALIDADE_MINUTOS . ' minutes'));

        $stmt = $this->pdo->prepare("
            INSERT INTO reset_senha_tokens (usuario_id, token, expira_em)
            VALUES (:usuario_id, :token, :expira_em)
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token'      => $token,
            ':expira_em'  => $expiraEm,
        ]);

        return $token;
    }

    /**
     * Valida um token: existe, não expirou, ainda não foi usado.
     * Retorna o usuario_id se válido, ou null se inválido/expirado.
     */
    public function validarToken(string $token): ?int
    {
        $stmt = $this->pdo->prepare("
            SELECT usuario_id
            FROM reset_senha_tokens
            WHERE token = :token
              AND usado = 0
              AND expira_em >= NOW()
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $resultado = $stmt->fetch();

        return $resultado ? (int) $resultado['usuario_id'] : null;
    }

    /**
     * Marca um token como usado, pra não poder ser reaproveitado
     */
    public function marcarComoUsado(string $token): bool
    {
        $stmt = $this->pdo->prepare("UPDATE reset_senha_tokens SET usado = 1 WHERE token = :token");

        return $stmt->execute([':token' => $token]);
    }
}
