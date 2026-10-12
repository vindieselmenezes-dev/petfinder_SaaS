<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: ValidacaoSenha
 * ==========================================================
 * REGRA ÚNICA de senha do sistema. Todo lugar que cria ou troca
 * uma senha (cadastro, cadastro de empresa, alterar senha, link de
 * redefinição, redefinição feita pelo administrador) passa por aqui.
 *
 * Regra: de 8 a 72 caracteres, com pelo menos 1 letra maiúscula
 * e pelo menos 1 número.
 *
 * O teto de 72 bytes existe porque o bcrypt (PASSWORD_DEFAULT) só
 * enxerga os 72 primeiros bytes: o que vier depois seria ignorado em
 * silêncio, e uma senha gigante só serviria para pesar o servidor.
 */

class ValidacaoSenha
{
    public const MINIMO = 8;
    public const MAXIMO = 72;

    /**
     * Valida a força da senha.
     * Retorna null se a senha for válida, ou uma mensagem de erro se não for.
     */
    public static function validar(string $senha): ?string
    {
        // strlen() conta bytes, que é exatamente o que o bcrypt limita.
        $tamanho = strlen($senha);

        if ($tamanho < self::MINIMO) {
            return "A senha deve ter pelo menos " . self::MINIMO . " caracteres.";
        }

        if ($tamanho > self::MAXIMO) {
            return "A senha deve ter no máximo " . self::MAXIMO . " caracteres.";
        }

        if (!preg_match('/[A-Z]/', $senha)) {
            return "A senha deve ter pelo menos 1 letra maiúscula.";
        }

        if (!preg_match('/\d/', $senha)) {
            return "A senha deve ter pelo menos 1 número.";
        }

        return null;
    }

    /**
     * Texto explicando a regra, para exibir no formulário
     */
    public static function regraTexto(): string
    {
        return "Mínimo de " . self::MINIMO . " caracteres, com pelo menos 1 letra maiúscula e 1 número.";
    }
}
