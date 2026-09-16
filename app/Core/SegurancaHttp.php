<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Núcleo: SegurancaHttp
 * ==========================================================
 * Cabeçalhos de segurança HTTP e redirecionamento para HTTPS.
 * Aplicado uma vez, no bootstrap, para todo o site.
 */
final class SegurancaHttp
{
    private function __construct()
    {
    }

    public static function aplicar(): void
    {
        if (self::estaAtrasDeCliOuTestes()) {
            return; // evita "headers already sent" ao rodar comandos de linha de comando
        }

        self::forcarHttpsSeNecessario();
        self::enviarCabecalhos();
    }

    private static function estaAtrasDeCliOuTestes(): bool
    {
        return PHP_SAPI === 'cli' || headers_sent();
    }

    /**
     * Redireciona para HTTPS em produção. Em ambiente local (APP_ENV=local)
     * não força nada, pra não quebrar quem está testando em
     * http://localhost sem certificado.
     */
    private static function forcarHttpsSeNecessario(): void
    {
        $ambiente = getenv('APP_ENV') ?: 'local';
        if ($ambiente === 'local') {
            return;
        }

        $https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
        $atrasDeProxyHttps = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        if ($https || $atrasDeProxyHttps) {
            return;
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        if ($host === '') {
            return; // sem host conhecido, não tem pra onde redirecionar
        }

        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }

    private static function enviarCabecalhos(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Geolocalização é usada de verdade (recursos de pet perdido); os
        // demais sensores não têm uso conhecido no site.
        header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');

        if (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off') {
            // HSTS: só faz sentido anunciar depois que o site já está em HTTPS.
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        header("Content-Security-Policy: " . self::politicaConteudo());
    }

    /**
     * CSP pragmática: restringe a origens conhecidas, mas ainda permite
     * 'unsafe-inline' em script/style porque o site tem dezenas de
     * <script> e style="" inline espalhados pelas páginas — removê-los
     * exigiria mover cada um pra arquivo externo ou usar nonce por
     * requisição, o que é um projeto à parte (fica anotado no changelog
     * da Fase 6 como próximo passo de segurança).
     */
    private static function politicaConteudo(): string
    {
        $diretivas = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' fonts.googleapis.com cdn.jsdelivr.net",
            "font-src 'self' fonts.gstatic.com",
            "img-src 'self' data: api.qrserver.com",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $diretivas);
    }
}
