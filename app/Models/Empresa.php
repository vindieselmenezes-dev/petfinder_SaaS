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
        $sql = "
            INSERT INTO empresas
            (
                usuario_id,
                categoria_id,
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
                e.*,
                c.nome AS categoria_nome,
                c.icone AS categoria_icone
            FROM empresas e
            INNER JOIN categorias c
                ON c.id = e.categoria_id
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
                c.icone AS categoria_icone
            FROM empresas e
            INNER JOIN categorias c
                ON c.id = e.categoria_id
            WHERE e.ativo = 1
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

        $sql .= " ORDER BY e.verificada DESC, e.avaliacao DESC, e.criado_em DESC, e.id DESC ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

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
