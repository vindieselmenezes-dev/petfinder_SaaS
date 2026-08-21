<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: Mailer
 *
 * TODO: hoje isso só registra o e-mail em log (app/logs/emails.log),
 * não envia de verdade — o XAMPP local não tem servidor de e-mail
 * configurado. Quando for hora de enviar de verdade, trocar o
 * corpo do método enviar() por PHPMailer + SMTP (Gmail, SendGrid,
 * etc.), mantendo a mesma assinatura — nada mais no sistema
 * precisa mudar, todo mundo já chama Mailer::enviar().
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

        // Sempre retorna true (modo simulado) — quando plugar SMTP de
        // verdade, retornar o resultado real do envio aqui.
        return true;
    }
}
