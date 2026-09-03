<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: Mailer
 *
 * Envia via mail() quando EMAIL_NOTIFICACOES estiver habilitado e mantém
 * o log local para desenvolvimento e auditoria operacional.
 * ==========================================================
 */
class Mailer
{
    public static function enviar(string $destinatario, string $assunto, string $corpoHtml): bool
    {
        $diretorioLogs = __DIR__ . '/../../logs';

        if (!is_dir($diretorioLogs)) {
            mkdir($diretorioLogs, 0777, true);
        }

        $linha = sprintf(
            "[%s] PARA: %s | ASSUNTO: %s\n%s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $destinatario,
            $assunto,
            str_repeat('-', 60),
            strip_tags($corpoHtml)
        );

        file_put_contents($diretorioLogs . '/emails.log', $linha, FILE_APPEND);

        if (getenv('EMAIL_NOTIFICACOES') !== '1') {
            return true;
        }

        $remetente = getenv('EMAIL_REMETENTE') ?: 'no-reply@petfinder.local';
        $cabecalhos = [
            'From: PetFinder Brasil <' . $remetente . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        return mail($destinatario, $assunto, $corpoHtml, implode("\r\n", $cabecalhos));
    }
}
