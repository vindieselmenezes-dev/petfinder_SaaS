<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * TestKit: mini framework de testes (sem dependências externas)
 * ==========================================================
 */

class TestKit
{
    private static int $total = 0;
    private static int $sucesso = 0;
    private static array $falhas = [];

    /**
     * Executa um teste nomeado, capturando qualquer erro/exceção
     */
    public static function run(string $nome, callable $funcao): void
    {
        self::$total++;

        try {
            $funcao();
            self::$sucesso++;
            echo "[OK]      {$nome}\n";
        } catch (Throwable $e) {
            self::$falhas[] = $nome . ' -> ' . $e->getMessage();
            echo "[FALHOU]  {$nome}\n";
            echo "          Motivo: " . $e->getMessage() . "\n";
        }
    }

    public static function assertTrue(bool $condicao, string $mensagem = 'Esperava true, obteve false'): void
    {
        if (!$condicao) {
            throw new Exception($mensagem);
        }
    }

    public static function assertFalse(bool $condicao, string $mensagem = 'Esperava false, obteve true'): void
    {
        if ($condicao) {
            throw new Exception($mensagem);
        }
    }

    public static function assertEquals(mixed $esperado, mixed $obtido, string $mensagem = ''): void
    {
        if ($esperado != $obtido) {
            $msg = $mensagem !== ''
                ? $mensagem
                : "Esperava '" . print_r($esperado, true) . "', obteve '" . print_r($obtido, true) . "'";
            throw new Exception($msg);
        }
    }

    public static function assertNotNull(mixed $valor, string $mensagem = 'Esperava valor não nulo'): void
    {
        if ($valor === null) {
            throw new Exception($mensagem);
        }
    }

    public static function assertNull(mixed $valor, string $mensagem = 'Esperava valor nulo'): void
    {
        if ($valor !== null) {
            throw new Exception($mensagem);
        }
    }

    /**
     * Imprime o resumo final e retorna se houve alguma falha
     */
    public static function resumo(): void
    {
        echo "\n==============================\n";
        echo "Total:   " . self::$total . "\n";
        echo "Sucesso: " . self::$sucesso . "\n";
        echo "Falhas:  " . count(self::$falhas) . "\n";
        echo "==============================\n";

        if (count(self::$falhas) > 0) {
            echo "\nDetalhes das falhas:\n";
            foreach (self::$falhas as $falha) {
                echo " - {$falha}\n";
            }
            echo "\n";
        }
    }

    public static function houveFalha(): bool
    {
        return count(self::$falhas) > 0;
    }
}
