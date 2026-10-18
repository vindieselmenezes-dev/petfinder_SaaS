<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Helpers/Mailer.php';

/**
 * Confirmação de e-mail no cadastro.
 *
 * Segue o mesmo desenho do ResetSenha: o token (256 bits aleatórios) vai no
 * link do e-mail e só o SHA-256 dele fica no banco; a validade é calculada
 * pelo próprio banco (NOW()), para não misturar fusos entre PHP e MySQL.
 */
class ConfirmacaoEmail
{
    private const VALIDADE_HORAS = 24;

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Se o login deve ser barrado até a confirmação do e-mail.
     * Desligue com EXIGIR_CONFIRMACAO_EMAIL=0 no .env (por exemplo, enquanto
     * o envio de e-mail do servidor ainda não estiver configurado).
     */
    public static function exigido(): bool
    {
        // Sem envio de e-mail disponível, exigir confirmação trancaria todo
        // cadastro novo fora da conta: nesse caso a exigência fica desligada.
        return Env::get('EXIGIR_CONFIRMACAO_EMAIL') !== '0' && Mailer::disponivel();
    }

    /**
     * Esta conta precisa confirmar o e-mail antes de entrar?
     * Se a coluna ainda não existe (migração 029 não rodou), não barra ninguém.
     *
     * @param array<string,mixed> $usuario Linha de usuarios (precisa vir com email_verificado_em).
     */
    public static function usuarioPrecisaConfirmar(array $usuario): bool
    {
        return self::exigido()
            && array_key_exists('email_verificado_em', $usuario)
            && empty($usuario['email_verificado_em']);
    }

    public static function hashDoToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Gera um novo link para o usuário, válido por 24 horas, e invalida os
     * anteriores ainda abertos (só o link mais recente funciona).
     */
    public function gerarToken(int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32));

        $this->pdo->prepare(
            'UPDATE confirmacao_email_tokens SET usado = 1 WHERE usuario_id = :id AND usado = 0'
        )->execute([':id' => $usuarioId]);

        $this->pdo->prepare(
            'INSERT INTO confirmacao_email_tokens (usuario_id, token, expira_em)
             VALUES (:usuario_id, :token, DATE_ADD(NOW(), INTERVAL ' . self::VALIDADE_HORAS . ' HOUR))'
        )->execute([
            ':usuario_id' => $usuarioId,
            ':token'      => self::hashDoToken($token),
        ]);

        return $token;
    }

    /**
     * Token existe, não expirou e não foi usado? Devolve o usuario_id ou null.
     */
    public function validarToken(string $token): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT usuario_id
               FROM confirmacao_email_tokens
              WHERE token = :token AND usado = 0 AND expira_em >= NOW()
              LIMIT 1'
        );
        $stmt->execute([':token' => self::hashDoToken($token)]);

        $linha = $stmt->fetch();

        return $linha ? (int) $linha['usuario_id'] : null;
    }

    /**
     * Confirma o e-mail: marca a conta como verificada e gasta o token,
     * tudo numa transação. Devolve false se o token for inválido/expirado.
     */
    public function confirmar(string $token): bool
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, usuario_id
                   FROM confirmacao_email_tokens
                  WHERE token = :token AND usado = 0 AND expira_em >= NOW()
                  LIMIT 1
                  FOR UPDATE'
            );
            $stmt->execute([':token' => self::hashDoToken($token)]);
            $linha = $stmt->fetch();

            if (!$linha) {
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->prepare(
                'UPDATE usuarios
                    SET email_verificado_em = COALESCE(email_verificado_em, NOW())
                  WHERE id = :id'
            )->execute([':id' => (int) $linha['usuario_id']]);

            $this->pdo->prepare(
                'UPDATE confirmacao_email_tokens SET usado = 1 WHERE usuario_id = :id'
            )->execute([':id' => (int) $linha['usuario_id']]);

            $this->pdo->commit();

            return true;
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('[ConfirmacaoEmail] Falha ao confirmar: ' . $erro->getMessage());

            return false;
        }
    }

    /**
     * Marca o e-mail como confirmado sem token. Usado quando a pessoa já
     * provou ter acesso à caixa de entrada por outro caminho (link de
     * redefinição de senha). Não derruba o fluxo se a migração 029 não rodou.
     */
    public function marcarComoConfirmado(int $usuarioId): void
    {
        try {
            $this->pdo->prepare(
                'UPDATE usuarios
                    SET email_verificado_em = COALESCE(email_verificado_em, NOW())
                  WHERE id = :id'
            )->execute([':id' => $usuarioId]);
        } catch (Throwable $erro) {
            error_log('[ConfirmacaoEmail] Não foi possível marcar como confirmado: ' . $erro->getMessage());
        }
    }

    /**
     * Busca a conta pelo e-mail já com o estado da confirmação.
     *
     * @return array<string,mixed>|null
     */
    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, email_verificado_em FROM usuarios WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    /**
     * Gera o token e envia o e-mail com o link de confirmação.
     * Devolve o link (para o modo teste local) ou null se não havia o que enviar.
     *
     * @param array<string,mixed> $usuario Precisa de id, nome e email.
     */
    public function enviarLink(array $usuario): ?string
    {
        $token = $this->gerarToken((int) $usuario['id']);
        $link  = Url::absoluta(Url::pagina('confirmar_email.php') . '?' . http_build_query(['token' => $token]));

        $nome = htmlspecialchars((string) $usuario['nome'], ENT_QUOTES, 'UTF-8');

        Mailer::enviar(
            (string) $usuario['email'],
            'Confirme seu e-mail - EcoSistemPet',
            "Olá, {$nome}!<br><br>Falta só um passo para ativar sua conta no EcoSistemPet. "
            . "Clique no link abaixo para confirmar seu e-mail (válido por " . self::VALIDADE_HORAS . " horas):<br>"
            . "<a href=\"{$link}\">{$link}</a><br><br>Se você não criou uma conta, pode ignorar este e-mail."
        );

        return $link;
    }
}
