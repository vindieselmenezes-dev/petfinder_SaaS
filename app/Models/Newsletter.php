<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Newsletter
 * ==========================================================
 * Inscrição de e-mail na newsletter (formulário "Receba nossas
 * novidades" do rodapé da home). A tabela `newsletter` já existia
 * no banco, mas não tinha nenhum código por trás dela ainda.
 */

require_once __DIR__ . '/../../config/database.php';

class Newsletter
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Inscreve um e-mail na newsletter (ou reativa, se a pessoa já
     * tinha se descadastrado antes).
     *
     * @return array{sucesso: bool, jaInscrito: bool, mensagem: string}
     */
    public function inscrever(string $email): array
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'sucesso'    => false,
                'jaInscrito' => false,
                'mensagem'   => 'Informe um e-mail válido.',
            ];
        }

        $existente = $this->buscarPorEmail($email);

        if ($existente !== null && (int) $existente['ativo'] === 1) {
            return [
                'sucesso'    => true,
                'jaInscrito' => true,
                'mensagem'   => 'Esse e-mail já está inscrito. Obrigado por acompanhar o PetFinder!',
            ];
        }

        try {
            if ($existente !== null) {
                // Já esteve inscrito antes e cancelou — reativa em vez de
                // duplicar a linha (o e-mail é UNIQUE na tabela).
                $stmt = $this->pdo->prepare('UPDATE newsletter SET ativo = 1 WHERE email = :email');
                $sucesso = $stmt->execute([':email' => $email]);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO newsletter (email, ativo) VALUES (:email, 1)'
                );
                $sucesso = $stmt->execute([':email' => $email]);
            }
        } catch (PDOException $e) {
            error_log('Erro ao inscrever e-mail na newsletter: ' . $e->getMessage());
            $sucesso = false;
        }

        return [
            'sucesso'    => $sucesso,
            'jaInscrito' => false,
            'mensagem'   => $sucesso
                ? 'Inscrição confirmada! Você vai receber nossas novidades por e-mail.'
                : 'Não foi possível concluir sua inscrição agora. Tente novamente em instantes.',
        ];
    }

    /**
     * Remove a inscrição (mantém o histórico, só marca como inativo).
     */
    public function cancelar(string $email): bool
    {
        $stmt = $this->pdo->prepare('UPDATE newsletter SET ativo = 0 WHERE email = :email');

        return $stmt->execute([':email' => strtolower(trim($email))]);
    }

    /**
     * Gera o token que vai no link de descadastro de um e-mail.
     *
     * Não é salvo em tabela nenhuma: é uma assinatura HMAC do próprio
     * e-mail (com APP_KEY como chave secreta), então o mesmo e-mail
     * sempre reproduz o mesmo token. Isso permite validar o link de
     * descadastro de qualquer e-mail já enviado no passado sem precisar
     * de uma tabela de tokens nem lembrar quando ele foi gerado — o
     * mesmo esquema usado por praticamente todo provedor de e-mail
     * marketing (Mailchimp, SendGrid etc.) pro link "descadastrar-se"
     * no rodapé.
     */
    public function gerarTokenDescadastro(string $email): string
    {
        $email = strtolower(trim($email));

        return hash_hmac('sha256', $email, self::chaveAssinatura());
    }

    /**
     * Confere se o token de um link de descadastro é válido pra aquele
     * e-mail (hash_equals evita timing attack).
     */
    public function tokenDescadastroValido(string $email, string $token): bool
    {
        return $token !== '' && hash_equals($this->gerarTokenDescadastro($email), $token);
    }

    /**
     * Chave usada para assinar os tokens de descadastro. Vem de APP_KEY
     * no .env (mesma chave pode ser reaproveitada por outras assinaturas
     * no futuro). Se ninguém configurou ainda, cai num valor fixo — os
     * links continuam funcionando, só não resistem a alguém com acesso
     * ao código-fonte adivinhar o token de um e-mail, por isso o aviso
     * no log pra não passar despercebido em produção.
     */
    private static function chaveAssinatura(): string
    {
        $chave = getenv('APP_KEY');

        if ($chave === false || $chave === '') {
            error_log('APP_KEY não configurada no .env — usando chave padrão insegura para assinar links de descadastro da newsletter. Configure APP_KEY em produção.');
            return 'petfinder-brasil-chave-padrao-insegura';
        }

        return $chave;
    }

    private function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, ativo FROM newsletter WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);

        return $stmt->fetch() ?: null;
    }
}
