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
     * Apaga o contador dessa ação/origem (ex.: depois de um login ou de
     * um código 2FA correto, para o histórico de erros não pesar contra
     * quem acertou).
     */
    public function zerar(string $acao, ?string $identificador = null): void
    {
        $identificador = $identificador ?? self::ip();

        $stmt = $this->pdo->prepare("
            DELETE FROM limite_requisicoes
             WHERE acao = :acao AND identificador = :identificador
        ");
        $stmt->execute([':acao' => $acao, ':identificador' => $identificador]);
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
     * IP de quem está fazendo a requisição.
     *
     * O cabeçalho X-Forwarded-For é escrito por QUEM ENVIA a requisição:
     * se o site responde direto para a internet, qualquer pessoa pode
     * mandar um valor diferente a cada tentativa e nunca cair no limite.
     * Por isso o cabeçalho só é considerado quando a conexão vem de um
     * proxy/CDN que você declarou em TRUSTED_PROXIES (no .env), uma lista
     * separada por vírgulas de IPs ou faixas CIDR. Exemplo:
     *
     *   TRUSTED_PROXIES=10.0.0.5,172.16.0.0/12
     *
     * Sem TRUSTED_PROXIES, vale sempre o REMOTE_ADDR (o IP real da
     * conexão). Se o site estiver atrás de proxy/CDN e a variável não
     * for definida, todos os visitantes parecem ter o IP do proxy.
     *
     * Quando a conexão vem de um proxy confiável, lê o cabeçalho da
     * direita para a esquerda e usa o primeiro IP que não seja de proxy
     * confiável: os valores à esquerda podem ter sido forjados pelo
     * cliente, o da direita foi anotado pelo seu próprio proxy.
     */
    public static function ip(): string
    {
        $remoto = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
        $confiaveis = self::proxiesConfiaveis();

        if ($confiaveis === [] || !self::ipEmLista($remoto, $confiaveis)) {
            return $remoto;
        }

        $encaminhado = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($encaminhado === '') {
            return $remoto;
        }

        foreach (array_reverse(explode(',', $encaminhado)) as $candidato) {
            $candidato = trim($candidato);

            if (filter_var($candidato, FILTER_VALIDATE_IP) === false) {
                continue;
            }

            if (!self::ipEmLista($candidato, $confiaveis)) {
                return $candidato;
            }
        }

        return $remoto;
    }

    /**
     * Lista de proxies confiáveis (IPs ou CIDRs) vinda de TRUSTED_PROXIES.
     *
     * @return string[]
     */
    private static function proxiesConfiaveis(): array
    {
        $valor = getenv('TRUSTED_PROXIES');

        if ($valor === false || trim($valor) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $valor)), 'strlen'));
    }

    /**
     * $ip está em alguma das regras (IP exato ou CIDR, IPv4 ou IPv6)?
     *
     * @param string[] $regras
     */
    private static function ipEmLista(string $ip, array $regras): bool
    {
        $binarioIp = @inet_pton($ip);

        if ($binarioIp === false) {
            return false;
        }

        foreach ($regras as $regra) {
            [$rede, $bits] = array_pad(explode('/', $regra, 2), 2, null);
            $binarioRede = @inet_pton($rede);

            // Famílias diferentes (IPv4 x IPv6) nunca casam.
            if ($binarioRede === false || strlen($binarioRede) !== strlen($binarioIp)) {
                continue;
            }

            $total = strlen($binarioIp) * 8;
            $bits = ($bits === null || !ctype_digit($bits)) ? $total : min((int) $bits, $total);

            $bytesInteiros = intdiv($bits, 8);
            $restante = $bits % 8;

            if ($bytesInteiros > 0 && substr($binarioIp, 0, $bytesInteiros) !== substr($binarioRede, 0, $bytesInteiros)) {
                continue;
            }

            if ($restante > 0) {
                $mascara = (0xFF << (8 - $restante)) & 0xFF;
                if ((ord($binarioIp[$bytesInteiros]) & $mascara) !== (ord($binarioRede[$bytesInteiros]) & $mascara)) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }
}
