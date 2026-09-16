<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: ValidacaoSenha
 * ==========================================================
 * Regra: mínimo 8 caracteres, pelo menos 1 letra maiúscula
 * e pelo menos 1 número.
 */

class ValidacaoSenha
{
    /**
     * Valida a força da senha.
     * Retorna null se a senha for válida, ou uma mensagem de erro se não for.
     */
    public static function validar(string $senha): ?string
    {
        if (strlen($senha) < 8) {
            return "A senha deve ter pelo menos 8 caracteres.";
        }

        if (!preg_match('/[A-Z]/', $senha)) {
            return "A senha deve ter pelo menos 1 letra maiúscula.";
        }

        if (!preg_match('/[0-9]/', $senha)) {
            return "A senha deve ter pelo menos 1 número.";
        }

        return null;
    }

    /**
     * Texto explicando a regra, para exibir no formulário
     */
    public static function regraTexto(): string
    {
        return "Mínimo de 8 caracteres, com pelo menos 1 letra maiúscula e 1 número.";
    }
}
