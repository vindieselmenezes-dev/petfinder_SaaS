<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Núcleo: Env
 * ==========================================================
 * Carregador simples de arquivo .env (sem depender de pacote
 * externo). Lê "CHAVE=valor" por linha e injeta com putenv(),
 * para que getenv() funcione no resto da aplicação.
 */
final class Env
{
    private static bool $carregado = false;

    private function __construct()
    {
    }

    public static function carregar(string $caminhoArquivo): void
    {
        if (self::$carregado || !is_readable($caminhoArquivo)) {
            self::$carregado = true;
            return;
        }

        $linhas = file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }

            [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
            $valor = trim($valor, "\"'");

            if (getenv($chave) === false) {
                putenv("{$chave}={$valor}");
            }
        }

        self::$carregado = true;
    }
}
