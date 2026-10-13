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
     * O que vai para o banco: o SHA-256 do token, nunca o token em si.
     *
     * O token é uma senha temporária (quem o tem redefine a conta). Se o banco
     * vazar (backup, SQL injection, acesso indevido), tokens guardados em texto
     * puro virariam links válidos. Como o token já é aleatório de 256 bits, um
     * hash simples basta: não há senha fraca para adivinhar por força bruta.
     * O SHA-256 em hexadecimal tem 64 caracteres, o tamanho da coluna.
     */
    public static function hashDoToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Gera um novo token de redefinição pra um usuário, válido por 1 hora.
     * Devolve o token em si (para o link do e-mail); só o hash fica no banco.
     */
    public function gerarToken(int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32));

        // A validade é calculada pelo PRÓPRIO banco (NOW() + 60 min), o mesmo
        // relógio que validarToken() usa para comparar. Calculá-la no PHP
        // misturava dois fusos: o app usa America/Sao_Paulo e o banco pode
        // estar em UTC, e aí o link nascia vencido (ou durava horas a mais).
        $stmt = $this->pdo->prepare("
            INSERT INTO reset_senha_tokens (usuario_id, token, expira_em)
            VALUES (:usuario_id, :token, DATE_ADD(NOW(), INTERVAL " . self::VALIDADE_MINUTOS . " MINUTE))
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token'      => self::hashDoToken($token),
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

        $stmt->execute([':token' => self::hashDoToken($token)]);

        $resultado = $stmt->fetch();

        return $resultado ? (int) $resultado['usuario_id'] : null;
    }

    /**
     * Marca um token como usado, pra não poder ser reaproveitado
     */
    public function marcarComoUsado(string $token): bool
    {
        $stmt = $this->pdo->prepare("UPDATE reset_senha_tokens SET usado = 1 WHERE token = :token");

        return $stmt->execute([':token' => self::hashDoToken($token)]);
    }

    /**
     * Invalida TODOS os links de redefinição ainda abertos de um usuário.
     * Chamado depois que a senha é trocada: um e-mail antigo esquecido na
     * caixa de entrada (ou lido por outra pessoa) não pode mais trocá-la.
     */
    public function invalidarTodosDoUsuario(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare("UPDATE reset_senha_tokens SET usado = 1 WHERE usuario_id = :id AND usado = 0");
        $stmt->execute([':id' => $usuarioId]);
    }
}
