<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Parceiro
 * ==========================================================
 * ONGs, protetores independentes e empresas que apoiam o PetFinder
 * (divulgação, patrocínio, parceria institucional).
 *
 * Um parceiro só aparece publicamente depois de aprovado por um
 * administrador — assim ninguém publica pedido de doação em nome
 * do projeto sem passar por uma triagem.
 */

require_once __DIR__ . '/../../config/database.php';

class Parceiro
{
    private PDO $pdo;

    /** Rótulos legíveis dos tipos de parceiro */
    public const TIPOS = [
        'ong'      => 'ONG / Protetor',
        'empresa'  => 'Empresa parceira',
        'apoiador' => 'Apoiador / Divulgador',
    ];

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Lista os parceiros aprovados, com filtros opcionais de tipo,
     * cidade e busca livre. Parceiros em destaque vêm primeiro.
     *
     * @param int $limite 0 = sem limite
     * @param int $offset quantos registros pular (para paginação)
     * @return array<int, array<string, mixed>>
     */
    public function listarAprovados(
        string $tipo = '',
        string $cidade = '',
        string $busca = '',
        int $limite = 0,
        int $offset = 0
    ): array {
        $sql = "
            SELECT p.*,
                   (SELECT COUNT(*)
                      FROM parceiro_campanhas c
                     WHERE c.parceiro_id = p.id
                       AND c.status = 'ativa') AS total_campanhas
            FROM parceiros p
            WHERE p.status = 'aprovado'
        ";

        $parametros = $this->parametrosFiltro($tipo, $cidade, $busca);

        foreach ($parametros as $chave => $valor) {
            $sql .= " AND " . $this->condicaoFiltro($chave);
        }

        $sql .= " ORDER BY p.destaque DESC, total_campanhas DESC, p.nome";

        if ($limite > 0) {
            $limite = min($limite, 60);
            $offset = max(0, $offset);
            $sql .= " LIMIT {$limite} OFFSET {$offset}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Conta quantos parceiros aprovados batem com os mesmos filtros de
     * listarAprovados() — usado para montar a paginação do hub.
     */
    public function contarAprovados(string $tipo = '', string $cidade = '', string $busca = ''): int
    {
        $sql = "SELECT COUNT(*) FROM parceiros p WHERE p.status = 'aprovado'";

        $parametros = $this->parametrosFiltro($tipo, $cidade, $busca);

        foreach ($parametros as $chave => $valor) {
            $sql .= " AND " . $this->condicaoFiltro($chave);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Monta o array de parâmetros nomeados (tipo/cidade/busca) usado tanto
     * por listarAprovados() quanto por contarAprovados(), pra não haver o
     * risco desses dois filtrarem de jeitos diferentes.
     *
     * @return array<string, string>
     */
    private function parametrosFiltro(string $tipo, string $cidade, string $busca): array
    {
        $parametros = [];

        if ($tipo !== '' && isset(self::TIPOS[$tipo])) {
            $parametros[':tipo'] = $tipo;
        }

        if ($cidade !== '') {
            $parametros[':cidade'] = '%' . $cidade . '%';
        }

        if ($busca !== '') {
            $parametros[':busca'] = '%' . $busca . '%';
        }

        return $parametros;
    }

    /**
     * Cláusula SQL correspondente a cada chave de parametrosFiltro().
     */
    private function condicaoFiltro(string $chave): string
    {
        return match ($chave) {
            ':tipo'   => 'p.tipo = :tipo',
            ':cidade' => 'p.cidade LIKE :cidade',
            ':busca'  => '(p.nome LIKE :busca OR p.descricao LIKE :busca)',
            default   => '1=1',
        };
    }

    /**
     * Busca um parceiro pelo ID. Por padrão só devolve os aprovados;
     * passe $apenasAprovados = false para o painel do próprio dono e
     * para a moderação do administrador.
     */
    public function buscarPorId(int $id, bool $apenasAprovados = true): ?array
    {
        $sql = "
            SELECT p.*, u.nome AS responsavel_nome, u.email AS responsavel_email
            FROM parceiros p
            JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.id = :id
        ";

        if ($apenasAprovados) {
            $sql .= " AND p.status = 'aprovado'";
        }

        $stmt = $this->pdo->prepare($sql . " LIMIT 1");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Parceiros administrados pelo usuário logado (usado no painel).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM parceiros
            WHERE usuario_id = :usuario_id
            ORDER BY criado_em DESC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Fila de moderação do administrador.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarParaModeracao(string $status = ''): array
    {
        $sql = "
            SELECT p.*, u.nome AS responsavel_nome, u.email AS responsavel_email
            FROM parceiros p
            JOIN usuarios u ON u.id = p.usuario_id
        ";

        $parametros = [];

        if ($status !== '') {
            $sql .= " WHERE p.status = :status";
            $parametros[':status'] = $status;
        }

        // Pendentes primeiro: é o que exige ação de quem abriu a tela.
        $sql .= " ORDER BY (p.status = 'pendente') DESC, p.criado_em DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Verifica se o usuário pode administrar este parceiro (é o
     * responsável ou é administrador global).
     */
    public function podeAdministrar(int $parceiroId, int $usuarioId, bool $ehAdministrador = false): bool
    {
        if ($ehAdministrador) {
            return true;
        }

        $stmt = $this->pdo->prepare("
            SELECT id FROM parceiros WHERE id = :id AND usuario_id = :usuario_id LIMIT 1
        ");
        $stmt->execute([':id' => $parceiroId, ':usuario_id' => $usuarioId]);

        return $stmt->fetch() !== false;
    }

    /**
     * Cadastra uma candidatura de parceria (entra como 'pendente').
     * Retorna o ID gerado ou false.
     */
    public function cadastrar(array $dados): int|false
    {
        $sql = "
            INSERT INTO parceiros
                (usuario_id, empresa_id, tipo, nome, documento, descricao, como_ajuda,
                 logo, cidade, estado, site, instagram, whatsapp, email_contato,
                 chave_pix, link_doacao, aceita_voluntarios, status)
            VALUES
                (:usuario_id, :empresa_id, :tipo, :nome, :documento, :descricao, :como_ajuda,
                 :logo, :cidade, :estado, :site, :instagram, :whatsapp, :email_contato,
                 :chave_pix, :link_doacao, :aceita_voluntarios, 'pendente')
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id'         => $dados['usuario_id'],
                ':empresa_id'         => $dados['empresa_id'] ?: null,
                ':tipo'               => $dados['tipo'],
                ':nome'               => $dados['nome'],
                ':documento'          => $dados['documento'] ?: null,
                ':descricao'          => $dados['descricao'] ?: null,
                ':como_ajuda'         => $dados['como_ajuda'] ?: null,
                ':logo'               => $dados['logo'] ?: null,
                ':cidade'             => $dados['cidade'] ?: null,
                ':estado'             => $dados['estado'] ?: null,
                ':site'               => $dados['site'] ?: null,
                ':instagram'          => $dados['instagram'] ?: null,
                ':whatsapp'           => $dados['whatsapp'] ?: null,
                ':email_contato'      => $dados['email_contato'] ?: null,
                ':chave_pix'          => $dados['chave_pix'] ?: null,
                ':link_doacao'        => $dados['link_doacao'] ?: null,
                ':aceita_voluntarios' => !empty($dados['aceita_voluntarios']) ? 1 : 0,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('Erro ao cadastrar parceiro: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza os dados editáveis pelo próprio parceiro.
     */
    public function atualizar(int $id, array $dados): bool
    {
        $sql = "
            UPDATE parceiros SET
                tipo = :tipo,
                nome = :nome,
                descricao = :descricao,
                como_ajuda = :como_ajuda,
                cidade = :cidade,
                estado = :estado,
                site = :site,
                instagram = :instagram,
                whatsapp = :whatsapp,
                email_contato = :email_contato,
                chave_pix = :chave_pix,
                link_doacao = :link_doacao,
                aceita_voluntarios = :aceita_voluntarios
        ";

        $parametros = [
            ':tipo'               => $dados['tipo'],
            ':nome'               => $dados['nome'],
            ':descricao'          => $dados['descricao'] ?: null,
            ':como_ajuda'         => $dados['como_ajuda'] ?: null,
            ':cidade'             => $dados['cidade'] ?: null,
            ':estado'             => $dados['estado'] ?: null,
            ':site'               => $dados['site'] ?: null,
            ':instagram'          => $dados['instagram'] ?: null,
            ':whatsapp'           => $dados['whatsapp'] ?: null,
            ':email_contato'      => $dados['email_contato'] ?: null,
            ':chave_pix'          => $dados['chave_pix'] ?: null,
            ':link_doacao'        => $dados['link_doacao'] ?: null,
            ':aceita_voluntarios' => !empty($dados['aceita_voluntarios']) ? 1 : 0,
            ':id'                 => $id,
        ];

        // A logo só é sobrescrita quando veio arquivo novo — senão a
        // edição de texto apagaria a imagem já enviada.
        if (!empty($dados['logo'])) {
            $sql .= ", logo = :logo";
            $parametros[':logo'] = $dados['logo'];
        }

        $sql .= " WHERE id = :id";

        try {
            return $this->pdo->prepare($sql)->execute($parametros);
        } catch (PDOException $e) {
            error_log('Erro ao atualizar parceiro: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Moderação: aprova, recusa, inativa ou devolve para pendente.
     */
    public function alterarStatus(int $id, string $status, ?string $observacao = null): bool
    {
        if (!in_array($status, ['pendente', 'aprovado', 'recusado', 'inativo'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE parceiros
               SET status = :status, observacao_admin = :observacao
             WHERE id = :id
        ");

        return $stmt->execute([
            ':status'     => $status,
            ':observacao' => $observacao ?: null,
            ':id'         => $id,
        ]);
    }

    /**
     * Liga/desliga o selo de destaque (quem aparece na home).
     */
    public function alternarDestaque(int $id, bool $destaque): bool
    {
        $stmt = $this->pdo->prepare("UPDATE parceiros SET destaque = :destaque WHERE id = :id");

        return $stmt->execute([':destaque' => $destaque ? 1 : 0, ':id' => $id]);
    }

    /**
     * Parceiros em destaque para a vitrine da home.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarDestaques(int $limite = 8): array
    {
        $limite = max(1, min($limite, 24));

        $sql = "
            SELECT id, nome, tipo, logo, cidade, estado, como_ajuda
            FROM parceiros
            WHERE status = 'aprovado'
            ORDER BY destaque DESC, criado_em DESC
            LIMIT {$limite}
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Números da área de parceiros (usados no topo da página).
     *
     * @return array{parceiros:int, ongs:int, campanhas:int, arrecadado:float}
     */
    public function resumo(): array
    {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM parceiros WHERE status = 'aprovado') AS parceiros,
                (SELECT COUNT(*) FROM parceiros WHERE status = 'aprovado' AND tipo = 'ong') AS ongs,
                (SELECT COUNT(*) FROM parceiro_campanhas WHERE status = 'ativa') AS campanhas,
                (SELECT COALESCE(SUM(valor_arrecadado), 0) FROM parceiro_campanhas) AS arrecadado
        ";

        $dados = $this->pdo->query($sql)->fetch() ?: [];

        return [
            'parceiros'  => (int) ($dados['parceiros'] ?? 0),
            'ongs'       => (int) ($dados['ongs'] ?? 0),
            'campanhas'  => (int) ($dados['campanhas'] ?? 0),
            'arrecadado' => (float) ($dados['arrecadado'] ?? 0),
        ];
    }

    /**
     * Quantos parceiros estão em determinado status (usado nos cards
     * do dashboard, principalmente para a fila de moderação).
     */
    public function contarPorStatus(string $status): int
    {
        if (!in_array($status, ['pendente', 'aprovado', 'recusado', 'inativo'], true)) {
            return 0;
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM parceiros WHERE status = :status");
        $stmt->execute([':status' => $status]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Rótulo legível do tipo (com fallback, caso o banco tenha valor novo).
     */
    public static function rotuloTipo(?string $tipo): string
    {
        return self::TIPOS[$tipo ?? ''] ?? 'Parceiro';
    }
}
