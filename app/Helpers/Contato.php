<?php

declare(strict_types=1);

require_once __DIR__ . '/../Core/Env.php';

/**
 * Canais públicos de contato do site, lidos do .env.
 *
 * Nada aqui é escrito direto no código: os endereços que o site mostra aos
 * visitantes precisam ser caixas que alguém realmente lê. O que não estiver
 * configurado simplesmente não aparece (em vez de mostrar um canal falso).
 *
 *   EMAIL_CONTATO       caixa que recebe as mensagens do formulário de contato
 *   EMAIL_PRIVACIDADE   canal de dados pessoais (LGPD); se vazio, usa EMAIL_CONTATO
 *   WHATSAPP_CONTATO    número com DDD (ex.: 31999998888); o 55 é acrescentado
 *   WHATSAPP_HORARIO    texto do horário de atendimento (opcional)
 */
final class Contato
{
    private static function emailValido(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '' || !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // Endereços de exemplo/desenvolvimento nunca devem ser mostrados ao público.
        if (preg_match('/@petfinder\.(local|test)$/i', $valor)) {
            return null;
        }

        return $valor;
    }

    public static function email(): ?string
    {
        return self::emailValido(Env::get('EMAIL_CONTATO') ?: null);
    }

    public static function emailPrivacidade(): ?string
    {
        return self::emailValido(Env::get('EMAIL_PRIVACIDADE') ?: null) ?? self::email();
    }

    /** Só dígitos, com o código do país (55). Null se não estiver configurado ou for inválido. */
    public static function whatsappNumero(): ?string
    {
        return self::normalizarTelefone((string) (Env::get('WHATSAPP_CONTATO') ?: ''));
    }

    public static function whatsappLink(?string $texto = null): ?string
    {
        $numero = self::whatsappNumero();

        if ($numero === null) {
            return null;
        }

        return 'https://wa.me/' . $numero . ($texto ? '?text=' . rawurlencode($texto) : '');
    }

    public static function horario(): string
    {
        $horario = trim((string) (Env::get('WHATSAPP_HORARIO') ?: ''));

        return $horario !== '' ? $horario : 'segunda a sexta, das 9h às 18h';
    }

    /** Telefone brasileiro (com ou sem 55): 10 ou 11 dígitos de DDD + número. */
    public static function telefoneValido(string $telefone): bool
    {
        return self::normalizarTelefone($telefone) !== null;
    }

    /** Devolve só dígitos com 55 na frente, ou null se não parecer um telefone brasileiro. */
    public static function normalizarTelefone(string $telefone): ?string
    {
        $digitos = preg_replace('/\D/', '', $telefone) ?? '';

        if (strlen($digitos) === 10 || strlen($digitos) === 11) {
            $digitos = '55' . $digitos;
        }

        return (strlen($digitos) === 12 || strlen($digitos) === 13) && str_starts_with($digitos, '55')
            ? $digitos
            : null;
    }
}
