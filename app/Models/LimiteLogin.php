<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: LimiteLogin
 * ==========================================================
 * Protege contra tentativas repetidas de login (força bruta).
 * Após MAX_TENTATIVAS erradas dentro de JANELA_MINUTOS, bloqueia por
 * TEMPO_BLOQUEIO_MINUTOS minutos. Erros antigos (fora da janela) não
 * contam: quem erra a senha de vez em quando não acaba bloqueado.
 *
 * Use LimiteLogin::chave($email) como identificador: ela normaliza a
 * caixa (Foo@x.com e foo@x.com são a mesma conta) e limita o tamanho,
 * então vale também para e-mails que não existem, sem revelar quais
 * existem.
 */

require_once __DIR__ . '/../../config/database.php';

class LimiteLogin
{
    private const MAX_TENTATIVAS = 5;
    private const TEMPO_BLOQUEIO_MINUTOS = 15;
    private const JANELA_MINUTOS = 15;

    /**
     * Identificador estável para um e-mail digitado no login: minúsculas,
     * sem espaços nas pontas e com tamanho fixo (cabe na coluna mesmo que
     * alguém envie um texto enorme).
     */
    public static function chave(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
        $this->garantirTabela();
    }

    private function garantirTabela(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS tentativas_login (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(180) NOT NULL,
                tentativas INT NOT NULL DEFAULT 0,
                ultima_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                bloqueado_ate TIMESTAMP NULL,
                UNIQUE KEY uq_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->pdo->exec($sql);
    }

    /**
     * Verifica se esse e-mail está bloqueado no momento
     */
    public function estaBloqueado(string $email): bool
    {
        $sql = "
            SELECT bloqueado_ate
            FROM tentativas_login
            WHERE email = :email
              AND bloqueado_ate IS NOT NULL
              AND bloqueado_ate > NOW()
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch() !== false;
    }

    /**
     * Retorna quantos minutos faltam para o bloqueio acabar
     * (0 se não estiver bloqueado)
     */
    public function minutosRestantes(string $email): int
    {
        $sql = "
            SELECT TIMESTAMPDIFF(SECOND, NOW(), bloqueado_ate) AS segundos
            FROM tentativas_login
            WHERE email = :email
              AND bloqueado_ate IS NOT NULL
              AND bloqueado_ate > NOW()
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        $resultado = $stmt->fetch();

        if (!$resultado) {
            return 0;
        }

        return (int) ceil(((int) $resultado['segundos']) / 60);
    }

    /**
     * Registra uma tentativa de login incorreta.
     * Bloqueia automaticamente se atingir o limite.
     */
    public function registrarFalha(string $email): void
    {
        $sql = "
            INSERT INTO tentativas_login (email, tentativas, ultima_tentativa)
            VALUES (:email, 1, NOW())
            ON DUPLICATE KEY UPDATE
                tentativas = IF(ultima_tentativa < NOW() - INTERVAL :janela MINUTE, 1, tentativas + 1),
                ultima_tentativa = NOW()
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email, ':janela' => self::JANELA_MINUTOS]);

        $sqlVerifica = "
            SELECT tentativas
            FROM tentativas_login
            WHERE email = :email
        ";

        $stmt = $this->pdo->prepare($sqlVerifica);
        $stmt->execute([':email' => $email]);
        $linha = $stmt->fetch();

        if ($linha && (int) $linha['tentativas'] >= self::MAX_TENTATIVAS) {

            $sqlBloquear = "
                UPDATE tentativas_login
                SET bloqueado_ate = DATE_ADD(NOW(), INTERVAL :minutos MINUTE),
                    tentativas = 0
                WHERE email = :email
            ";

            $stmt = $this->pdo->prepare($sqlBloquear);
            $stmt->execute([
                ':minutos' => self::TEMPO_BLOQUEIO_MINUTOS,
                ':email'   => $email
            ]);

        }

        // Limpeza sem cron: ~0,5% das falhas aproveitam para varrer a tabela.
        if (random_int(1, 200) === 1) {
            $this->limparAntigas();
        }
    }

    /**
     * Remove linhas que não servem mais: sem bloqueio ativo e sem
     * nenhuma tentativa há mais de $dias dia(s). Evita que a tabela
     * cresça sem fim com e-mails sorteados por quem testa senhas.
     */
    public function limparAntigas(int $dias = 1): int
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM tentativas_login
             WHERE (bloqueado_ate IS NULL OR bloqueado_ate < NOW())
               AND ultima_tentativa < NOW() - INTERVAL :dias DAY
        ");
        $stmt->execute([':dias' => $dias]);

        return $stmt->rowCount();
    }

    /**
     * Limpa o histórico de tentativas após um login bem-sucedido
     */
    public function registrarSucesso(string $email): void
    {
        $sql = "DELETE FROM tentativas_login WHERE email = :email";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
    }

    /**
     * Quantas tentativas erradas ainda restam antes do bloqueio
     */
    public function tentativasRestantes(string $email): int
    {
        $sql = "
            SELECT tentativas
            FROM tentativas_login
            WHERE email = :email
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $linha = $stmt->fetch();

        $usadas = $linha ? (int) $linha['tentativas'] : 0;

        return max(0, self::MAX_TENTATIVAS - $usadas);
    }
}
