<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Produto
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class Produto
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Lista as subcategorias de produto (categoria "Marketplace")
     */
    public function listarSubcategorias(int $categoriaId = 9): array
    {
        $sql = "
            SELECT id, nome, descricao
            FROM subcategorias
            WHERE categoria_id = :categoria_id
              AND ativo = 1
            ORDER BY nome
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':categoria_id' => $categoriaId]);

        return $stmt->fetchAll();
    }

    /**
     * Lista marcas ativas
     */
    public function listarMarcas(): array
    {
        $sql = "
            SELECT id, nome
            FROM marcas
            WHERE ativo = 1
            ORDER BY nome
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Cadastra um novo produto. Retorna o ID gerado, ou false em erro.
     */
    public function cadastrar(array $dados): int|false
    {
        $sql = "
            INSERT INTO produtos
            (
                empresa_id,
                categoria_id,
                subcategoria_id,
                marca_id,
                nome,
                descricao,
                sku,
                codigo_barras,
                peso,
                altura,
                largura,
                comprimento,
                preco_custo,
                preco_venda,
                preco_promocional,
                destaque,
                ativo
            )
            VALUES
            (
                :empresa_id,
                :categoria_id,
                :subcategoria_id,
                :marca_id,
                :nome,
                :descricao,
                :sku,
                :codigo_barras,
                :peso,
                :altura,
                :largura,
                :comprimento,
                :preco_custo,
                :preco_venda,
                :preco_promocional,
                :destaque,
                :ativo
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $sucesso = $stmt->execute([
            ':empresa_id' => $dados['empresa_id'],
            ':categoria_id' => $dados['categoria_id'],
            ':subcategoria_id' => $dados['subcategoria_id'],
            ':marca_id' => $dados['marca_id'],
            ':nome' => $dados['nome'],
            ':descricao' => $dados['descricao'],
            ':sku' => $dados['sku'],
            ':codigo_barras' => $dados['codigo_barras'],
            ':peso' => $dados['peso'],
            ':altura' => $dados['altura'],
            ':largura' => $dados['largura'],
            ':comprimento' => $dados['comprimento'],
            ':preco_custo' => $dados['preco_custo'],
            ':preco_venda' => $dados['preco_venda'],
            ':preco_promocional' => $dados['preco_promocional'],
            ':destaque' => $dados['destaque'],
            ':ativo' => $dados['ativo']
        ]);

        if (!$sucesso) {
            return false;
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Busca um produto pelo ID, com dados de subcategoria, marca e empresa
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                p.id, p.categoria_id, p.subcategoria_id, p.marca_id,
                p.fornecedor_id, p.empresa_id, p.nome, p.descricao, p.sku,
                p.codigo_barras, p.peso, p.altura, p.largura, p.comprimento,
                p.preco_custo, p.preco_venda, p.preco_promocional, p.destaque,
                p.ativo, p.criado_em, p.atualizado_em,
                s.nome AS subcategoria_nome,
                m.nome AS marca_nome,
                e.nome_fantasia AS empresa_nome,
                e.usuario_id AS empresa_usuario_id,
                e.whatsapp AS empresa_whatsapp,
                e.cidade AS empresa_cidade,
                e.estado AS empresa_estado
            FROM produtos p
            LEFT JOIN subcategorias s ON s.id = p.subcategoria_id
            LEFT JOIN marcas m ON m.id = p.marca_id
            INNER JOIN empresas e ON e.id = p.empresa_id
            WHERE p.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Lista os produtos de uma empresa
     */
    public function listarPorEmpresa(int $empresaId): array
    {
        $sql = "
            SELECT
                p.id,
                p.nome,
                p.preco_venda,
                p.preco_promocional,
                p.ativo,
                p.destaque,
                p.criado_em,
                s.nome AS subcategoria_nome,
                COALESCE(est.quantidade, 0) AS estoque_quantidade,
                (
                    SELECT imagem FROM produto_imagens
                    WHERE produto_id = p.id
                    ORDER BY principal DESC, ordem ASC
                    LIMIT 1
                ) AS imagem_principal
            FROM produtos p
            LEFT JOIN subcategorias s ON s.id = p.subcategoria_id
            LEFT JOIN estoque est ON est.produto_id = p.id
            WHERE p.empresa_id = :empresa_id
            ORDER BY p.criado_em DESC, p.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll();
    }

    /**
     * Atualiza um produto
     */
    public function atualizar(int $id, array $dados): bool
    {
        $sql = "
            UPDATE produtos
            SET
                subcategoria_id = :subcategoria_id,
                marca_id = :marca_id,
                nome = :nome,
                descricao = :descricao,
                sku = :sku,
                codigo_barras = :codigo_barras,
                peso = :peso,
                altura = :altura,
                largura = :largura,
                comprimento = :comprimento,
                preco_custo = :preco_custo,
                preco_venda = :preco_venda,
                preco_promocional = :preco_promocional,
                destaque = :destaque,
                ativo = :ativo
            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':subcategoria_id' => $dados['subcategoria_id'],
            ':marca_id' => $dados['marca_id'],
            ':nome' => $dados['nome'],
            ':descricao' => $dados['descricao'],
            ':sku' => $dados['sku'],
            ':codigo_barras' => $dados['codigo_barras'],
            ':peso' => $dados['peso'],
            ':altura' => $dados['altura'],
            ':largura' => $dados['largura'],
            ':comprimento' => $dados['comprimento'],
            ':preco_custo' => $dados['preco_custo'],
            ':preco_venda' => $dados['preco_venda'],
            ':preco_promocional' => $dados['preco_promocional'],
            ':destaque' => $dados['destaque'],
            ':ativo' => $dados['ativo'],
            ':id' => $id,
            ':empresa_id' => $dados['empresa_id']
        ]);
    }

    /**
     * Exclui um produto (só se pertencer à empresa informada)
     */
    public function excluir(int $id, int $empresaId): bool
    {
        $sql = "
            DELETE FROM produtos
            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);
    }

    /**
     * Lista produtos ativos para o marketplace público, com filtros.
     *
     * Chaves aceitas em $criterios (todas opcionais):
     *   busca, subcategoria_id, marca_id, preco_min, preco_max, ordem
     *   ('recente', 'menor_preco', 'maior_preco' ou 'nome'), cidade,
     *   categoria_id, empresa, apenas_promocao, subcategorias (lista de ids)
     *   e avaliacao_minima.
     */
    public function listarAtivos(array $criterios = []): array
    {
        $busca = (string) ($criterios['busca'] ?? '');
        $subcategoriaId = (int) ($criterios['subcategoria_id'] ?? 0);
        $marcaId = (int) ($criterios['marca_id'] ?? 0);
        $precoMin = (float) ($criterios['preco_min'] ?? 0.0);
        $precoMax = (float) ($criterios['preco_max'] ?? 0.0);
        $ordem = (string) ($criterios['ordem'] ?? 'recente');
        $cidade = (string) ($criterios['cidade'] ?? '');
        $categoriaId = (int) ($criterios['categoria_id'] ?? 0);
        $empresa = (string) ($criterios['empresa'] ?? '');
        $apenasPromocao = (bool) ($criterios['apenas_promocao'] ?? false);
        $subcategoriasSelecionadas = (array) ($criterios['subcategorias'] ?? []);
        $avaliacaoMinima = (float) ($criterios['avaliacao_minima'] ?? 0.0);

        $sql = "
            SELECT
                p.id,
                p.nome,
                p.preco_venda,
                p.preco_promocional,
                p.destaque,
                e.nome_fantasia AS empresa_nome,
                e.cidade AS empresa_cidade,
                e.estado AS empresa_estado,
                s.nome AS subcategoria_nome,
                m.nome AS marca_nome,
                (
                    SELECT imagem FROM produto_imagens
                    WHERE produto_id = p.id
                    ORDER BY principal DESC, ordem ASC
                    LIMIT 1
                ) AS imagem_principal,
                COALESCE(est.quantidade, 0) AS estoque_quantidade
            FROM produtos p
            INNER JOIN empresas e ON e.id = p.empresa_id
            LEFT JOIN subcategorias s ON s.id = p.subcategoria_id
            LEFT JOIN marcas m ON m.id = p.marca_id
            LEFT JOIN estoque est ON est.produto_id = p.id
            WHERE p.ativo = 1
              AND e.ativo = 1
        ";

        $termoBusca = "%{$busca}%";
        [$sqlSubcategorias, $paramsSubcategorias] = $this->filtroSubcategorias($subcategoriasSelecionadas);

        // Cada filtro: [está ativo?, trecho do SQL, parâmetros]
        $filtros = [
            [$busca !== '', " AND (
                p.nome LIKE :busca_nome
                OR s.nome LIKE :busca_subcategoria
                OR m.nome LIKE :busca_marca
                OR e.nome_fantasia LIKE :busca_empresa
            ) ", [
                ':busca_nome' => $termoBusca,
                ':busca_subcategoria' => $termoBusca,
                ':busca_marca' => $termoBusca,
                ':busca_empresa' => $termoBusca,
            ]],
            [$subcategoriaId > 0, " AND p.subcategoria_id = :subcategoria_id ", [':subcategoria_id' => $subcategoriaId]],
            [$marcaId > 0, " AND p.marca_id = :marca_id ", [':marca_id' => $marcaId]],
            [$precoMin > 0, " AND COALESCE(p.preco_promocional, p.preco_venda) >= :preco_min ", [':preco_min' => $precoMin]],
            [$precoMax > 0, " AND COALESCE(p.preco_promocional, p.preco_venda) <= :preco_max ", [':preco_max' => $precoMax]],
            [$cidade !== '', " AND (e.cidade LIKE :cidade OR e.estado LIKE :estado) ", [
                ':cidade' => "%{$cidade}%",
                ':estado' => "%{$cidade}%",
            ]],
            [$empresa !== '', " AND e.nome_fantasia LIKE :empresa ", [':empresa' => "%{$empresa}%"]],
            [$apenasPromocao, " AND p.preco_promocional IS NOT NULL AND p.preco_promocional > 0 AND p.preco_promocional < p.preco_venda ", []],
            [$avaliacaoMinima > 0, " AND e.avaliacao >= :avaliacao_minima ", [':avaliacao_minima' => $avaliacaoMinima]],
            [$sqlSubcategorias !== '', $sqlSubcategorias, $paramsSubcategorias],
            [$categoriaId > 0, " AND p.categoria_id = :categoria_id ", [':categoria_id' => $categoriaId]],
        ];

        $params = [];

        foreach ($filtros as [$ativo, $trechoSql, $parametros]) {
            if ($ativo) {
                $sql .= $trechoSql;
                $params += $parametros;
            }
        }

        $sql .= $this->clausulaOrdenacao($ordem);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Monta o filtro "subcategoria IN (...)" a partir da seleção do usuário,
     * ignorando valores inválidos e repetidos.
     *
     * @return array{0: string, 1: array<string, int>}
     */
    private function filtroSubcategorias(array $selecionadas): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $selecionadas),
            static fn(int $valor): bool => $valor > 0
        )));

        if ($ids === []) {
            return ['', []];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $index => $valor) {
            $placeholders[] = ':subcategoria_' . $index;
            $params[':subcategoria_' . $index] = $valor;
        }

        return [" AND p.subcategoria_id IN (" . implode(', ', $placeholders) . ") ", $params];
    }

    private function clausulaOrdenacao(string $ordem): string
    {
        return match ($ordem) {
            'menor_preco' => " ORDER BY COALESCE(p.preco_promocional, p.preco_venda) ASC ",
            'maior_preco' => " ORDER BY COALESCE(p.preco_promocional, p.preco_venda) DESC ",
            'nome' => " ORDER BY p.nome ASC ",
            default => " ORDER BY p.destaque DESC, p.criado_em DESC, p.id DESC ",
        };
    }

    public function listarDestaques(int $limite = 4): array
    {
        $limite = max(1, min($limite, 12));
        $sql = "
            SELECT p.id, p.nome, p.preco_venda, p.preco_promocional, p.destaque,
                   e.id AS empresa_id, e.nome_fantasia AS empresa_nome,
                   s.nome AS subcategoria_nome,
                   (SELECT imagem FROM produto_imagens WHERE produto_id = p.id
                    ORDER BY principal DESC, ordem ASC LIMIT 1) AS imagem_principal
            FROM produtos p
            INNER JOIN empresas e ON e.id = p.empresa_id
            LEFT JOIN subcategorias s ON s.id = p.subcategoria_id
            WHERE p.ativo = 1 AND p.destaque = 1 AND e.ativo = 1
            ORDER BY p.atualizado_em DESC, p.id DESC
            LIMIT {$limite}
        ";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function listarCategorias(): array
    {
        return $this->pdo->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
    }

    public function definirDestaque(int $produtoId, int $empresaId, bool $destaque): bool
    {
        $stmt = $this->pdo->prepare('UPDATE produtos SET destaque = :destaque WHERE id = :id AND empresa_id = :empresa_id');
        return $stmt->execute([':destaque' => $destaque ? 1 : 0, ':id' => $produtoId, ':empresa_id' => $empresaId]);
    }

    /**
     * Lista produtos em oferta (com preço promocional ativo),
     * ordenados pelo maior percentual de desconto
     */
    public function listarOfertas(int $limite = 24): array
    {
        $sql = "
            SELECT
                p.id,
                p.nome,
                p.preco_venda,
                p.preco_promocional,
                e.nome_fantasia AS empresa_nome,
                e.cidade AS empresa_cidade,
                e.estado AS empresa_estado,
                s.nome AS subcategoria_nome,
                (
                    SELECT imagem FROM produto_imagens
                    WHERE produto_id = p.id
                    ORDER BY principal DESC, ordem ASC
                    LIMIT 1
                ) AS imagem_principal,
                ROUND(((p.preco_venda - p.preco_promocional) / p.preco_venda) * 100) AS percentual_desconto
            FROM produtos p
            INNER JOIN empresas e ON e.id = p.empresa_id
            LEFT JOIN subcategorias s ON s.id = p.subcategoria_id
            WHERE p.ativo = 1
              AND e.ativo = 1
              AND p.preco_promocional IS NOT NULL
              AND p.preco_promocional > 0
              AND p.preco_promocional < p.preco_venda
            ORDER BY percentual_desconto DESC
            LIMIT :limite
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Conta o total de produtos ativos
     */
    public function contarProdutos(): int
    {
        $sql = "SELECT COUNT(*) FROM produtos WHERE ativo = 1";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Conta quantos produtos uma empresa específica já tem cadastrados
     * (ativos ou não) -- usado pra aplicar o limite do plano dela.
     */
    public function contarProdutosPorEmpresa(int $empresaId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produtos WHERE empresa_id = :empresa_id");
        $stmt->execute([':empresa_id' => $empresaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Verifica se a empresa já bateu o limite de produtos do plano dela.
     * limite_produtos = NULL no plano significa "ilimitado" (nunca bate o teto).
     */
    /**
     * Verifica se a empresa já bateu o limite de produtos do plano dela
     * OU se o período de teste do plano já venceu -- nos dois casos,
     * novo produto não pode ser cadastrado.
     */
    public function limiteAtingido(int $empresaId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT p.limite_produtos, e.plano_expira_em
            FROM empresas e
            LEFT JOIN planos p ON p.id = e.plano_id
            WHERE e.id = :empresa_id
        ");
        $stmt->execute([':empresa_id' => $empresaId]);
        $linha = $stmt->fetch();

        if (!$linha) {
            return false;
        }

        if (!empty($linha['plano_expira_em']) && strtotime($linha['plano_expira_em']) < strtotime(date('Y-m-d'))) {
            return true;
        }

        if ($linha['limite_produtos'] === null) {
            return false;
        }

        return $this->contarProdutosPorEmpresa($empresaId) >= (int) $linha['limite_produtos'];
    }
}
