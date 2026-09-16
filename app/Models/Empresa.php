<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Empresa
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class Empresa
{
    /**
     * Conexão com o banco
     */
    private PDO $pdo;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Lista todas as categorias ativas
     */
    public function listarCategorias(): array
    {
        $sql = "
            SELECT id, nome, descricao, icone
            FROM categorias
            WHERE ativo = 1
            ORDER BY nome
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Verifica se um CNPJ já está cadastrado
     */
    public function cnpjExiste(string $cnpj): bool
    {
        if ($cnpj === '') {
            return false;
        }

        $sql = "
            SELECT id
            FROM empresas
            WHERE cnpj = :cnpj
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cnpj' => $cnpj]);

        return $stmt->fetch() !== false;
    }

    /**
     * Cadastra uma nova empresa. Retorna o ID gerado, ou false em caso de erro.
     */
    public function cadastrar(array $dados): int|false
    {
        $planoId = $dados['plano_id'] ?? null;

        if (empty($planoId)) {
            $stmtPlano = $this->pdo->query("SELECT id FROM planos WHERE slug = 'gratis' LIMIT 1");
            $planoGratis = $stmtPlano->fetch();
            $planoId = $planoGratis ? (int) $planoGratis['id'] : null;
        }

        // Todo plano (inclusive o Grátis) tem prazo -- calcula a data de
        // expiração do período de teste já na hora do cadastro, pra
        // ninguém ficar de graça pra sempre.
        $diasTrial = 0;
        if ($planoId) {
            $stmtDias = $this->pdo->prepare("SELECT dias_trial FROM planos WHERE id = :id");
            $stmtDias->execute([':id' => $planoId]);
            $diasTrial = (int) $stmtDias->fetchColumn();
        }

        $sql = "
            INSERT INTO empresas
            (
                usuario_id,
                categoria_id,
                plano_id,
                plano_iniciado_em,
                plano_expira_em,
                nome_fantasia,
                razao_social,
                cnpj,
                descricao,
                telefone,
                whatsapp,
                email,
                site,
                logo,
                capa,
                endereco,
                numero,
                complemento,
                bairro,
                cidade,
                estado,
                cep
            )
            VALUES
            (
                :usuario_id,
                :categoria_id,
                :plano_id,
                CURDATE(),
                DATE_ADD(CURDATE(), INTERVAL :dias_trial DAY),
                :nome_fantasia,
                :razao_social,
                :cnpj,
                :descricao,
                :telefone,
                :whatsapp,
                :email,
                :site,
                :logo,
                :capa,
                :endereco,
                :numero,
                :complemento,
                :bairro,
                :cidade,
                :estado,
                :cep
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $sucesso = $stmt->execute([
            ':usuario_id' => $dados['usuario_id'],
            ':categoria_id' => $dados['categoria_id'],
            ':plano_id' => $planoId,
            ':dias_trial' => $diasTrial,
            ':nome_fantasia' => $dados['nome_fantasia'],
            ':razao_social' => $dados['razao_social'],
            ':cnpj' => $dados['cnpj'],
            ':descricao' => $dados['descricao'],
            ':telefone' => $dados['telefone'],
            ':whatsapp' => $dados['whatsapp'],
            ':email' => $dados['email'],
            ':site' => $dados['site'],
            ':logo' => $dados['logo'],
            ':capa' => $dados['capa'],
            ':endereco' => $dados['endereco'],
            ':numero' => $dados['numero'],
            ':complemento' => $dados['complemento'],
            ':bairro' => $dados['bairro'],
            ':cidade' => $dados['cidade'],
            ':estado' => $dados['estado'],
            ':cep' => $dados['cep']
        ]);

        if (!$sucesso) {
            return false;
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Busca uma empresa pelo ID, já com o nome da categoria
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                e.id, e.usuario_id, e.categoria_id, e.plano_id, e.nome_fantasia,
                e.razao_social, e.cnpj, e.descricao, e.telefone, e.whatsapp,
                e.email, e.site, e.logo, e.capa, e.endereco, e.numero,
                e.complemento, e.bairro, e.cidade, e.estado, e.cep,
                e.latitude, e.longitude, e.avaliacao, e.total_avaliacoes,
                e.verificada, e.ativo, e.status_pagamento, e.criado_em,
                e.atualizado_em, e.onboarding_concluido, e.onboarding_etapa,
                e.plano_iniciado_em, e.plano_expira_em,
                c.nome AS categoria_nome,
                c.icone AS categoria_icone,
                p.nome AS plano_nome,
                p.slug AS plano_slug,
                p.preco_mensal AS plano_preco_mensal,
                p.destaque AS plano_destaque,
                p.limite_produtos AS plano_limite_produtos
            FROM empresas e
            INNER JOIN categorias c
                ON c.id = e.categoria_id
            LEFT JOIN planos p
                ON p.id = e.plano_id
            WHERE e.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Lista as empresas cadastradas por um usuário
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        // Inclui tanto as empresas em que o usuário é o dono (empresas.usuario_id)
        // quanto aquelas em que ele é colaborador/administrador via empresa_equipe —
        // é isso que permite um usuário administrar várias empresas com papéis
        // diferentes em cada uma.
        $sql = "
            SELECT
                e.id,
                e.nome_fantasia,
                e.logo,
                e.cidade,
                e.estado,
                e.ativo,
                e.verificada,
                e.avaliacao,
                e.criado_em,
                c.nome AS categoria_nome,
                COALESCE(ee.papel, 'proprietario') AS meu_papel
            FROM empresas e
            INNER JOIN categorias c
                ON c.id = e.categoria_id
            LEFT JOIN empresa_equipe ee
                ON ee.empresa_id = e.id AND ee.usuario_id = :usuario_id_equipe AND ee.status = 'ativo'
            WHERE e.usuario_id = :usuario_id
               OR ee.usuario_id IS NOT NULL
            ORDER BY e.criado_em DESC, e.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':usuario_id_equipe' => $usuarioId,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Atualiza uma empresa (só se pertencer ao usuário)
     */
    public function atualizar(int $id, array $dados): bool
    {
        $sql = "
            UPDATE empresas
            SET
                categoria_id = :categoria_id,
                nome_fantasia = :nome_fantasia,
                razao_social = :razao_social,
                cnpj = :cnpj,
                descricao = :descricao,
                telefone = :telefone,
                whatsapp = :whatsapp,
                email = :email,
                site = :site,
                logo = :logo,
                capa = :capa,
                endereco = :endereco,
                numero = :numero,
                complemento = :complemento,
                bairro = :bairro,
                cidade = :cidade,
                estado = :estado,
                cep = :cep
            WHERE id = :id
              AND usuario_id = :usuario_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':categoria_id' => $dados['categoria_id'],
            ':nome_fantasia' => $dados['nome_fantasia'],
            ':razao_social' => $dados['razao_social'],
            ':cnpj' => $dados['cnpj'],
            ':descricao' => $dados['descricao'],
            ':telefone' => $dados['telefone'],
            ':whatsapp' => $dados['whatsapp'],
            ':email' => $dados['email'],
            ':site' => $dados['site'],
            ':logo' => $dados['logo'],
            ':capa' => $dados['capa'],
            ':endereco' => $dados['endereco'],
            ':numero' => $dados['numero'],
            ':complemento' => $dados['complemento'],
            ':bairro' => $dados['bairro'],
            ':cidade' => $dados['cidade'],
            ':estado' => $dados['estado'],
            ':cep' => $dados['cep'],
            ':id' => $id,
            ':usuario_id' => $dados['usuario_id']
        ]);
    }

    /**
     * Exclui uma empresa (só se pertencer ao usuário)
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        $sql = "
            DELETE FROM empresas
            WHERE id = :id
              AND usuario_id = :usuario_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuarioId
        ]);
    }

    /**
     * Lista empresas ativas para o diretório público, com filtro opcional
     * de categoria e cidade
     */
    public function listarAtivas(int $categoriaId = 0, string $cidade = '', string $busca = ''): array
    {
        $sql = "
            SELECT
                e.id,
                e.nome_fantasia,
                e.logo,
                e.capa,
                e.descricao,
                e.cidade,
                e.estado,
                e.avaliacao,
                e.total_avaliacoes,
                e.verificada,
                c.nome AS categoria_nome,
                c.icone AS categoria_icone,
                p.nome AS plano_nome,
                p.destaque AS plano_destaque
            FROM empresas e
            INNER JOIN categorias c
                ON c.id = e.categoria_id
            LEFT JOIN planos p
                ON p.id = e.plano_id
            WHERE e.ativo = 1
              AND e.status_pagamento = 'Ativo'
              AND (e.plano_expira_em IS NULL OR e.plano_expira_em >= CURDATE())
        ";

        $params = [];

        if ($categoriaId > 0) {
            $sql .= " AND e.categoria_id = :categoria_id ";
            $params[':categoria_id'] = $categoriaId;
        }

        if ($cidade !== '') {
            $sql .= " AND e.cidade = :cidade ";
            $params[':cidade'] = $cidade;
        }

        if ($busca !== '') {
            $sql .= " AND LOWER(e.nome_fantasia) LIKE LOWER(:busca) ";
            $params[':busca'] = '%' . $busca . '%';
        }

        $sql .= " ORDER BY COALESCE(p.destaque, 0) DESC, COALESCE(p.prioridade, 0) DESC, e.verificada DESC, e.avaliacao DESC, e.criado_em DESC, e.id DESC ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Verifica se o período de teste/plano da empresa já venceu sem
     * assinatura de um plano pago (usado pra travar o painel B2B).
     */
    public function trialVencido(int $empresaId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT plano_expira_em FROM empresas WHERE id = :id
        ");
        $stmt->execute([':id' => $empresaId]);
        $expiraEm = $stmt->fetchColumn();

        if (!$expiraEm) {
            return false;
        }

        return strtotime($expiraEm) < strtotime(date('Y-m-d'));
    }

    /**
     * Troca o plano da empresa (por enquanto, sem gateway real ligado --
     * ver public/simular_assinatura.php). Recalcula a data de expiração
     * com base nos dias de trial do plano escolhido.
     *
     * O plano Grátis só pode ser usado uma vez (no cadastro) -- pra
     * ninguém conseguir "renovar" o período de teste de graça pra
     * sempre, essa troca é bloqueada pro slug 'gratis' fora do cadastro
     * inicial (ver validação em public/simular_assinatura.php).
     */
    public function atualizarPlano(int $empresaId, int $planoId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE empresas e
            INNER JOIN planos p ON p.id = :plano_id
            SET
                e.plano_id = :plano_id_set,
                e.plano_iniciado_em = CURDATE(),
                e.plano_expira_em = DATE_ADD(CURDATE(), INTERVAL p.dias_trial DAY),
                e.status_pagamento = 'Ativo'
            WHERE e.id = :empresa_id
        ");

        return $stmt->execute([
            ':plano_id' => $planoId,
            ':plano_id_set' => $planoId,
            ':empresa_id' => $empresaId,
        ]);
    }

    public function listarDestaques(int $limite = 6): array
    {
        $limite = max(1, min($limite, 20));
        $sql = "
            SELECT e.id, e.nome_fantasia, e.logo, e.capa, e.descricao,
                   e.cidade, e.estado, e.avaliacao, e.total_avaliacoes,
                   c.nome AS categoria_nome, c.icone AS categoria_icone
            FROM empresas e
            INNER JOIN categorias c ON c.id = e.categoria_id
            WHERE e.ativo = 1
            ORDER BY e.avaliacao DESC, e.total_avaliacoes DESC, e.verificada DESC, e.id DESC
            LIMIT {$limite}
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function avaliar(int $empresaId, int $usuarioId, int $nota): bool
    {
        if ($empresaId <= 0 || $usuarioId <= 0 || $nota < 1 || $nota > 5) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM avaliacoes WHERE empresa_id = :empresa_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute([':empresa_id' => $empresaId, ':usuario_id' => $usuarioId]);

        if ($stmt->fetch()) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO avaliacoes (usuario_id, empresa_id, nota) VALUES (:usuario_id, :empresa_id, :nota)'
            );
            $stmt->execute([':usuario_id' => $usuarioId, ':empresa_id' => $empresaId, ':nota' => $nota]);

            $stmt = $this->pdo->prepare(
                'UPDATE empresas SET avaliacao = (SELECT ROUND(AVG(nota), 1) FROM avaliacoes WHERE empresa_id = :empresa_id), total_avaliacoes = (SELECT COUNT(*) FROM avaliacoes WHERE empresa_id = :empresa_id_count) WHERE id = :empresa_id_update'
            );
            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':empresa_id_count' => $empresaId,
                ':empresa_id_update' => $empresaId,
            ]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function listarAvaliacoes(int $empresaId, int $limite = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.nota, a.comentario, a.criado_em, u.nome AS usuario_nome
             FROM avaliacoes a JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.empresa_id = :empresa_id ORDER BY a.criado_em DESC LIMIT :limite'
        );
        $stmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', max(1, min($limite, 50)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Conta o total de empresas ativas
     */
    public function contarEmpresas(): int
    {
        $sql = "SELECT COUNT(*) FROM empresas WHERE ativo = 1";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | HORÁRIOS DE FUNCIONAMENTO
    |--------------------------------------------------------------------------
    */

    /**
     * Substitui todos os horários de uma empresa pelos novos informados
     */
    public function salvarHorarios(int $empresaId, array $horarios): bool
    {
        $sqlApagar = "DELETE FROM empresa_horarios WHERE empresa_id = :empresa_id";
        $stmt = $this->pdo->prepare($sqlApagar);
        $stmt->execute([':empresa_id' => $empresaId]);

        $sqlInserir = "
            INSERT INTO empresa_horarios
            (empresa_id, dia_semana, abertura, fechamento, fechado)
            VALUES
            (:empresa_id, :dia_semana, :abertura, :fechamento, :fechado)
        ";

        $stmt = $this->pdo->prepare($sqlInserir);

        foreach ($horarios as $horario) {

            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':dia_semana' => $horario['dia_semana'],
                ':abertura' => $horario['fechado'] ? null : $horario['abertura'],
                ':fechamento' => $horario['fechado'] ? null : $horario['fechamento'],
                ':fechado' => $horario['fechado'] ? 1 : 0
            ]);

        }

        return true;
    }

    /**
     * Busca os horários de uma empresa, na ordem dos dias da semana
     */
    public function buscarHorarios(int $empresaId): array
    {
        $sql = "
            SELECT dia_semana, abertura, fechamento, fechado
            FROM empresa_horarios
            WHERE empresa_id = :empresa_id
            ORDER BY FIELD(
                dia_semana,
                'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll();
    }

    /*
    |--------------------------------------------------------------------------
    | GALERIA DE FOTOS
    |--------------------------------------------------------------------------
    */

    /**
     * Adiciona imagens na galeria de uma empresa
     */
    public function salvarGaleria(int $empresaId, array $imagens): bool
    {
        if (empty($imagens)) {
            return true;
        }

        $sql = "
            INSERT INTO empresa_galeria (empresa_id, imagem, ordem)
            VALUES (:empresa_id, :imagem, :ordem)
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($imagens as $ordem => $imagem) {

            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':imagem' => $imagem,
                ':ordem' => $ordem + 1
            ]);

        }

        return true;
    }

    /**
     * Busca as imagens da galeria de uma empresa
     */
    public function buscarGaleria(int $empresaId): array
    {
        $sql = "
            SELECT id, imagem, legenda, ordem
            FROM empresa_galeria
            WHERE empresa_id = :empresa_id
            ORDER BY ordem ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll();
    }

    /**
     * Remove uma imagem específica da galeria (verifica se pertence à empresa
     * e se a empresa pertence ao usuário, feito no Controller)
     */
    public function excluirImagemGaleria(int $imagemId, int $empresaId): bool
    {
        $sql = "
            DELETE FROM empresa_galeria
            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $imagemId,
            ':empresa_id' => $empresaId
        ]);
    }
}
