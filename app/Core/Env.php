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

    /** @var array<string,string> Valores lidos do .env (a primeira ocorrência vale). */
    private static array $valores = [];

    private function __construct()
    {
        // Classe utilitária (apenas métodos estáticos): não deve ser instanciada.
    }

    /**
     * Lê uma variável de configuração. Mesma semântica do getenv(): devolve a
     * string (pode ser vazia) ou false se não existir. O ambiente real do
     * servidor vence; depois vale o .env (primeira ocorrência). Use isto no
     * lugar de getenv() para funcionar mesmo com putenv() desativado.
     *
     * @return string|false
     */
    public static function get(string $chave)
    {
        $real = getenv($chave);
        if ($real !== false) {
            return $real;
        }

        return self::$valores[$chave] ?? false;
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

            // Guarda sempre: hospedagens gratuitas (ex.: InfinityFree) desativam
            // putenv(), e sem isto o .env seria lido e nunca chegaria ao sistema.
            if (!array_key_exists($chave, self::$valores)) {
                self::$valores[$chave] = $valor;
            }

            if (function_exists('putenv') && getenv($chave) === false) {
                @putenv("{$chave}={$valor}");
            }
        }

        self::$carregado = true;
    }
}
