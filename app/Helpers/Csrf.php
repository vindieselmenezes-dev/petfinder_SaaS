<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: Csrf
 * ==========================================================
 * Protege formulários contra ataques CSRF (Cross-Site Request
 * Forgery) - impede que outro site consiga enviar um formulário
 * "em nome" do usuário logado, sem ele saber.
 */

class Csrf
{
    private const NOME_CAMPO = 'csrf_token';

    /**
     * Gera (ou reaproveita) o token da sessão atual
     */
    public static function gerarToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Retorna o HTML pronto do campo escondido, pra colar dentro do <form>
     */
    public static function campoHtml(): string
    {
        $token = self::gerarToken();

        return '<input type="hidden" name="' . self::NOME_CAMPO . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Verifica se o token recebido do formulário bate com o da sessão
     */
    public static function validar(?string $tokenRecebido): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($tokenRecebido)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $tokenRecebido);
    }
}
