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
            ':empresa_id'         => $dados['empresa_id'],
            ':categoria_id'       => $dados['categoria_id'],
            ':subcategoria_id'    => $dados['subcategoria_id'],
            ':marca_id'           => $dados['marca_id'],
            ':nome'               => $dados['nome'],
            ':descricao'          => $dados['descricao'],
            ':sku'                => $dados['sku'],
            ':codigo_barras'      => $dados['codigo_barras'],
            ':peso'               => $dados['peso'],
            ':altura'             => $dados['altura'],
            ':largura'            => $dados['largura'],
            ':comprimento'        => $dados['comprimento'],
            ':preco_custo'        => $dados['preco_custo'],
            ':preco_venda'        => $dados['preco_venda'],
            ':preco_promocional'  => $dados['preco_promocional'],
            ':destaque'           => $dados['destaque'],
            ':ativo'              => $dados['ativo']
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
                p.*,
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
            ORDER BY p.criado_em DESC
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
            ':subcategoria_id'   => $dados['subcategoria_id'],
            ':marca_id'          => $dados['marca_id'],
            ':nome'              => $dados['nome'],
            ':descricao'         => $dados['descricao'],
            ':sku'               => $dados['sku'],
            ':codigo_barras'     => $dados['codigo_barras'],
            ':peso'              => $dados['peso'],
            ':altura'            => $dados['altura'],
            ':largura'           => $dados['largura'],
            ':comprimento'       => $dados['comprimento'],
            ':preco_custo'       => $dados['preco_custo'],
            ':preco_venda'       => $dados['preco_venda'],
            ':preco_promocional' => $dados['preco_promocional'],
            ':destaque'          => $dados['destaque'],
            ':ativo'             => $dados['ativo'],
            ':id'                => $id,
            ':empresa_id'        => $dados['empresa_id']
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
            ':id'         => $id,
            ':empresa_id' => $empresaId
        ]);
    }

    /**
     * Lista produtos ativos para o marketplace público, com filtros
     */
    public function listarAtivos(
        string $busca = '',
        int $subcategoriaId = 0,
        int $marcaId = 0,
        float $precoMin = 0.0,
        float $precoMax = 0.0,
        string $ordem = 'recente',
        string $cidade = ''
    ): array {

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

        $params = [];

        if ($busca !== '') {
            $sql .= " AND p.nome LIKE :busca ";
            $params[':busca'] = "%{$busca}%";
        }

        if ($subcategoriaId > 0) {
            $sql .= " AND p.subcategoria_id = :subcategoria_id ";
            $params[':subcategoria_id'] = $subcategoriaId;
        }

        if ($marcaId > 0) {
            $sql .= " AND p.marca_id = :marca_id ";
            $params[':marca_id'] = $marcaId;
        }

        if ($precoMin > 0) {
            $sql .= " AND COALESCE(p.preco_promocional, p.preco_venda) >= :preco_min ";
            $params[':preco_min'] = $precoMin;
        }

        if ($precoMax > 0) {
            $sql .= " AND COALESCE(p.preco_promocional, p.preco_venda) <= :preco_max ";
            $params[':preco_max'] = $precoMax;
        }

        if ($cidade !== '') {
            $sql .= " AND e.cidade = :cidade ";
            $params[':cidade'] = $cidade;
        }

        switch ($ordem) {
            case 'menor_preco':
                $sql .= " ORDER BY COALESCE(p.preco_promocional, p.preco_venda) ASC ";
                break;
            case 'maior_preco':
                $sql .= " ORDER BY COALESCE(p.preco_promocional, p.preco_venda) DESC ";
                break;
            case 'nome':
                $sql .= " ORDER BY p.nome ASC ";
                break;
            default:
                $sql .= " ORDER BY p.destaque DESC, p.criado_em DESC ";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
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

    /*
    |--------------------------------------------------------------------------
    | ESTOQUE
    |--------------------------------------------------------------------------
    */

    public function buscarEstoque(int $produtoId): ?array
    {
        $sql = "SELECT * FROM estoque WHERE produto_id = :produto_id LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':produto_id' => $produtoId]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    public function atualizarEstoque(int $produtoId, int $quantidade, int $min, int $max): bool
    {
        $existente = $this->buscarEstoque($produtoId);

        if ($existente) {

            $sql = "
                UPDATE estoque
                SET quantidade = :quantidade,
                    estoque_minimo = :minimo,
                    estoque_maximo = :maximo
                WHERE produto_id = :produto_id
            ";

        } else {

            $sql = "
                INSERT INTO estoque (produto_id, quantidade, estoque_minimo, estoque_maximo)
                VALUES (:produto_id, :quantidade, :minimo, :maximo)
            ";

        }

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':produto_id' => $produtoId,
            ':quantidade' => $quantidade,
            ':minimo'     => $min,
            ':maximo'     => $max
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGENS
    |--------------------------------------------------------------------------
    */

    public function salvarImagens(int $produtoId, array $imagens): bool
    {
        if (empty($imagens)) {
            return true;
        }

        // Verifica se já existe alguma imagem principal
        $sqlVerifica = "SELECT COUNT(*) FROM produto_imagens WHERE produto_id = :produto_id AND principal = 1";
        $stmt = $this->pdo->prepare($sqlVerifica);
        $stmt->execute([':produto_id' => $produtoId]);
        $jaTemPrincipal = ((int) $stmt->fetchColumn()) > 0;

        $sql = "
            INSERT INTO produto_imagens (produto_id, imagem, principal, ordem)
            VALUES (:produto_id, :imagem, :principal, :ordem)
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($imagens as $ordem => $imagem) {

            $ehPrincipal = (!$jaTemPrincipal && $ordem === 0) ? 1 : 0;

            $stmt->execute([
                ':produto_id' => $produtoId,
                ':imagem'     => $imagem,
                ':principal'  => $ehPrincipal,
                ':ordem'      => $ordem + 1
            ]);

        }

        return true;
    }

    public function buscarImagens(int $produtoId): array
    {
        $sql = "
            SELECT id, imagem, principal, ordem
            FROM produto_imagens
            WHERE produto_id = :produto_id
            ORDER BY principal DESC, ordem ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':produto_id' => $produtoId]);

        return $stmt->fetchAll();
    }

    public function excluirImagem(int $imagemId, int $produtoId): bool
    {
        $sql = "
            DELETE FROM produto_imagens
            WHERE id = :id
              AND produto_id = :produto_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id'         => $imagemId,
            ':produto_id' => $produtoId
        ]);
    }
}
