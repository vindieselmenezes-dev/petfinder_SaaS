<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: RateLimiter
 * ==========================================================
 * Limita quantas vezes uma mesma origem (IP, por padrão) pode enviar
 * um formulário público em um intervalo de tempo — pensado para
 * endpoints sem sessão e sem CSRF útil (contato, inscrição/descadastro
 * da newsletter), onde o honeypot sozinho barra bots simples mas não
 * segura alguém disparando requisições de propósito.
 *
 * Mesmo espírito do `LimiteLogin` (que já existia só para tentativas de
 * senha errada), só que genérico por "ação" e com janela deslizante em
 * vez de "N erros seguidos": aqui o que importa é volume por período,
 * não erro de credencial.
 *
 * Uso típico:
 *   $limite = new RateLimiter();
 *   if (!$limite->permitido('contato', 5, 10, 30)) {
 *       // bloqueado: 5 envios por 10 minutos, bloqueia por 30 minutos
 *   }
 */

require_once __DIR__ . '/../../config/database.php';

class RateLimiter
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
        $this->garantirTabela();
    }

    private function garantirTabela(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS limite_requisicoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                acao VARCHAR(60) NOT NULL,
                identificador VARCHAR(120) NOT NULL,
                tentativas INT NOT NULL DEFAULT 0,
                janela_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                bloqueado_ate TIMESTAMP NULL,
                UNIQUE KEY uq_acao_identificador (acao, identificador)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->pdo->exec($sql);
    }

    /**
     * Registra mais uma tentativa de `$acao` vinda de `$identificador`
     * (o IP de quem está enviando, se omitido) e diz se ela pode
     * prosseguir.
     *
     * Janela deslizante simples: se a última janela já "venceu"
     * ($janelaMinutos atrás), zera a contagem e abre uma nova. Dentro da
     * janela, incrementa; ao passar de $maxPorJanela, bloqueia por
     * $bloqueioMinutos (e continua bloqueado até o bloqueio acabar,
     * mesmo que a janela em si já tenha vencido).
     *
     * @param string $acao            Identifica o formulário: 'contato', 'newsletter_inscrever' etc.
     * @param int    $maxPorJanela    Quantos envios são permitidos dentro da janela.
     * @param int    $janelaMinutos   Tamanho da janela, em minutos.
     * @param int    $bloqueioMinutos Por quanto tempo bloquear ao estourar o limite.
     * @param string|null $identificador Por padrão, o IP de quem está fazendo a requisição.
     */
    public function permitido(
        string $acao,
        int $maxPorJanela,
        int $janelaMinutos,
        int $bloqueioMinutos,
        ?string $identificador = null
    ): bool {
        $identificador = $identificador ?? self::ip();

        // Importante: a comparação de "venceu a janela?" e "ainda está
        // bloqueado?" é feita inteiramente em SQL (contra o NOW() do
        // próprio MySQL), nunca trazendo o horário pro PHP pra comparar
        // com strtotime()/time(). Se o fuso horário do PHP for diferente
        // do fuso do servidor MySQL (bem comum: PHP em America/Sao_Paulo,
        // MySQL em UTC), strtotime() interpretaria o horário do banco no
        // fuso errado, gerando uma diferença de horas — beeem maior que
        // qualquer janela de poucos minutos — e a janela pareceria
        // "sempre vencida", nunca bloqueando ninguém de verdade.
        $stmt = $this->pdo->prepare("
            SELECT
                tentativas,
                (bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()) AS ainda_bloqueado,
                (janela_inicio <= NOW() - INTERVAL :janela MINUTE) AS janela_vencida
            FROM limite_requisicoes
            WHERE acao = :acao AND identificador = :identificador
        ");
        $stmt->execute([
            ':janela'        => $janelaMinutos,
            ':acao'          => $acao,
            ':identificador' => $identificador,
        ]);
        $linha = $stmt->fetch();

        // Já está bloqueado de uma estourada anterior.
        if ($linha && (bool) $linha['ainda_bloqueado']) {
            return false;
        }

        $janelaVencida = !$linha || (bool) $linha['janela_vencida'];

        if ($janelaVencida) {
            // Nova janela: começa (ou reinicia) a contagem do zero.
            $sql = "
                INSERT INTO limite_requisicoes (acao, identificador, tentativas, janela_inicio, bloqueado_ate)
                VALUES (:acao, :identificador, 1, NOW(), NULL)
                ON DUPLICATE KEY UPDATE
                    tentativas = 1,
                    janela_inicio = NOW(),
                    bloqueado_ate = NULL
            ";
            $this->pdo->prepare($sql)->execute([':acao' => $acao, ':identificador' => $identificador]);

            return true;
        }

        $tentativas = (int) $linha['tentativas'] + 1;

        if ($tentativas > $maxPorJanela) {
            $stmt = $this->pdo->prepare("
                UPDATE limite_requisicoes
                   SET tentativas = :tentativas, bloqueado_ate = DATE_ADD(NOW(), INTERVAL :minutos MINUTE)
                 WHERE acao = :acao AND identificador = :identificador
            ");
            $stmt->execute([
                ':tentativas'    => $tentativas,
                ':minutos'       => $bloqueioMinutos,
                ':acao'          => $acao,
                ':identificador' => $identificador,
            ]);

            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE limite_requisicoes
               SET tentativas = :tentativas
             WHERE acao = :acao AND identificador = :identificador
        ");
        $stmt->execute([
            ':tentativas'    => $tentativas,
            ':acao'          => $acao,
            ':identificador' => $identificador,
        ]);

        // Limpeza automática, sem precisar de cron: 1 chance em 200 (~0,5%)
        // de rodar a cada chamada. Assim a tabela nunca cresce pra sempre
        // com IPs que só apareceram uma vez, e não depende de nenhuma
        // tarefa agendada configurada por fora.
        if (random_int(1, 200) === 1) {
            $this->limparAntigas();
        }

        return true;
    }

    /**
     * Remove linhas que não servem mais pra nada: não estão bloqueadas
     * agora e a janela delas já venceu há muito tempo (bem mais que
     * qualquer $janelaMinutos/$bloqueioMinutos usado na prática — é só
     * uma margem de segurança generosa, não precisa ser exata).
     *
     * Roda sozinha, probabilisticamente, a cada permitido() (ver acima).
     * Também pode ser chamada manualmente ou por um cron, se preferir
     * um controle mais previsível do que "de vez em quando".
     */
    public function limparAntigas(int $diasParaConsiderarObsoleto = 2): int
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM limite_requisicoes
             WHERE (bloqueado_ate IS NULL OR bloqueado_ate < NOW())
               AND janela_inicio < NOW() - INTERVAL :dias DAY
        ");
        $stmt->execute([':dias' => $diasParaConsiderarObsoleto]);

        return $stmt->rowCount();
    }

    /**
     * Minutos restantes de bloqueio para essa ação/origem (0 = livre).
     * Útil para mostrar "tente novamente em X minutos" na tela.
     */
    public function minutosRestantes(string $acao, ?string $identificador = null): int
    {
        $identificador = $identificador ?? self::ip();

        $stmt = $this->pdo->prepare("
            SELECT TIMESTAMPDIFF(SECOND, NOW(), bloqueado_ate) AS segundos
            FROM limite_requisicoes
            WHERE acao = :acao AND identificador = :identificador
              AND bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()
        ");
        $stmt->execute([':acao' => $acao, ':identificador' => $identificador]);
        $linha = $stmt->fetch();

        if (!$linha) {
            return 0;
        }

        return (int) max(1, ceil(((int) $linha['segundos']) / 60));
    }

    /**
     * IP de quem está fazendo a requisição. Confia no primeiro endereço
     * de X-Forwarded-For quando presente (site atrás de proxy/CDN),
     * senão usa REMOTE_ADDR — mesmo nível de confiança que o projeto já
     * usa em SegurancaHttp::forcarHttpsSeNecessario() para X-Forwarded-Proto,
     * sem lista de proxies confiáveis. Suficiente para conter flood, não
     * para identificar quem está por trás de um IP com certeza.
     */
    public static function ip(): string
    {
        $encaminhado = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

        if ($encaminhado !== '') {
            $primeiro = trim(explode(',', $encaminhado)[0]);
            if ($primeiro !== '') {
                return $primeiro;
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    }
}
