<?php

declare(strict_types=1);

require_once __DIR__ . '/../Core/Env.php';

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: Mailer
 *
 * Envia o e-mail de verdade quando EMAIL_ENVIO=1 (ou, por compatibilidade,
 * EMAIL_NOTIFICACOES=1) e mantém o log local para desenvolvimento e auditoria.
 *
 * Duas chaves independentes no .env:
 *  - EMAIL_ENVIO=1         liga o envio real (recuperação de senha, 2FA,
 *                          contato, confirmação de e-mail).
 *  - EMAIL_NOTIFICACOES=1  além disso, faz cada notificação do sistema
 *                          virar e-mail (quem decide isso é Notificacao::criar).
 * ==========================================================
 */
class Mailer
{
    /**
     * O site consegue entregar e-mail agora? Verdadeiro com o envio real ligado
     * (EMAIL_ENVIO=1 ou EMAIL_NOTIFICACOES=1) ou em ambiente local, onde os
     * links aparecem na própria tela para teste. Telas que dependem de e-mail
     * (esqueci a senha, 2FA, confirmação) usam isto para não prometer o que
     * não vão cumprir.
     */
    public static function disponivel(): bool
    {
        return Env::get('EMAIL_ENVIO') === '1'
            || Env::get('EMAIL_NOTIFICACOES') === '1'
            || Env::get('APP_ENV') === 'local';
    }

    public static function enviar(string $destinatario, string $assunto, string $corpoHtml): bool
    {
        self::registrarLog($destinatario, $assunto, $corpoHtml);

        if (Env::get('EMAIL_ENVIO') !== '1' && Env::get('EMAIL_NOTIFICACOES') !== '1') {
            return true;
        }

        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (is_file($autoload) && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            require_once $autoload;
            return self::enviarViaSmtp($destinatario, $assunto, $corpoHtml);
        }

        return self::enviarViaMail($destinatario, $assunto, $corpoHtml);
    }

    private static function registrarLog(string $destinatario, string $assunto, string $corpoHtml): void
    {
        $diretorioLogs = __DIR__ . '/../../logs';

        if (!is_dir($diretorioLogs)) {
            mkdir($diretorioLogs, 0777, true);
        }

        // Defesa extra: mesmo se o .htaccess da raiz não valer (outro servidor),
        // a própria pasta recusa acesso pela web.
        $protecao = $diretorioLogs . '/.htaccess';
        if (!is_file($protecao)) {
            @file_put_contents($protecao, "Require all denied\nDeny from all\n");
        }

        // O corpo do e-mail tem links com token (redefinição de senha, confirmação
        // de e-mail). Fora do ambiente local, esses links NÃO ficam gravados em
        // arquivo: quem lesse o log poderia tomar contas.
        $corpoParaLog = Env::get('APP_ENV') === 'local'
            ? strip_tags($corpoHtml)
            : '[conteúdo omitido fora do ambiente local]';

        $linha = sprintf(
            "[%s] PARA: %s | ASSUNTO: %s\n%s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $destinatario,
            $assunto,
            str_repeat('-', 60),
            $corpoParaLog
        );

        file_put_contents($diretorioLogs . '/emails.log', $linha, FILE_APPEND);
    }

    private static function enviarViaSmtp(string $destinatario, string $assunto, string $corpoHtml): bool
    {
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host = Env::get('SMTP_HOST') ?: 'localhost';
            $mailer->Port = (int) (Env::get('SMTP_PORT') ?: 587);
            $mailer->SMTPAuth = Env::get('SMTP_AUTH') === '1';
            $mailer->Username = Env::get('SMTP_USERNAME') ?: '';
            $mailer->Password = Env::get('SMTP_PASSWORD') ?: '';
            $mailer->SMTPSecure = Env::get('SMTP_ENCRYPTION') ?: PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            // Sem isto o PHPMailer espera até 300 s se a conexão for bloqueada: a página travaria.
            $mailer->Timeout = 15;
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom(Env::get('EMAIL_REMETENTE') ?: 'no-reply@petfinder.local', 'EcoSistemPet');
            $mailer->addAddress($destinatario);
            $mailer->isHTML(true);
            $mailer->Subject = $assunto;
            $mailer->Body = $corpoHtml;
            return $mailer->send();
        } catch (Throwable $exception) {
            error_log('Falha SMTP EcoSistemPet: ' . $exception->getMessage());
            return false;
        }
    }

    private static function enviarViaMail(string $destinatario, string $assunto, string $corpoHtml): bool
    {
        $remetente = Env::get('EMAIL_REMETENTE') ?: 'no-reply@petfinder.local';
        $cabecalhos = [
            'From: EcoSistemPet <' . $remetente . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        return mail($destinatario, $assunto, $corpoHtml, implode("\r\n", $cabecalhos));
    }
}
