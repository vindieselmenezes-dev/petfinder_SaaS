<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: ProdutoController
 * ==========================================================
 */

require_once __DIR__ . '/../Models/Produto.php';

class ProdutoController
{
    private Produto $produto;
    private ProdutoEstoque $estoque;

    public function __construct()
    {
        $this->produto = new Produto();
        $this->estoque = new ProdutoEstoque();
    }

    public function listarSubcategorias(): array
    {
        return $this->produto->listarSubcategorias();
    }

    public function listarMarcas(): array
    {
        return $this->produto->listarMarcas();
    }

    /**
     * Cadastra um novo produto
     */
    public function cadastrar(array $dados): int|false
    {
        if (!$this->dadosBasicosValidos($dados)) {
            return false;
        }

        if ($this->produto->limiteAtingido((int) $dados['empresa_id'])) {
            return false;
        }

        $dados['categoria_id'] = 9; // Marketplace (fixa)
        $dados = $this->normalizarCampos($dados);
        $dados['ativo'] = 1;

        return $this->produto->cadastrar($dados);
    }

    /**
     * Campos obrigatórios comuns ao cadastro e à atualização.
     */
    private function dadosBasicosValidos(array $dados): bool
    {
        if (empty($dados['empresa_id']) || empty($dados['nome'])) {
            return false;
        }

        return !empty($dados['preco_venda']) && (float) $dados['preco_venda'] > 0;
    }

    /**
     * Normaliza os campos opcionais (vazio vira null) do produto.
     */
    private function normalizarCampos(array $dados): array
    {
        $dados['subcategoria_id'] = !empty($dados['subcategoria_id']) ? (int) $dados['subcategoria_id'] : null;
        $dados['marca_id'] = !empty($dados['marca_id']) ? (int) $dados['marca_id'] : null;
        $dados['descricao'] = $dados['descricao'] ?? '';
        $dados['sku'] = !empty($dados['sku']) ? $dados['sku'] : null;
        $dados['codigo_barras'] = !empty($dados['codigo_barras']) ? $dados['codigo_barras'] : null;
        $dados['peso'] = ($dados['peso'] ?? '') !== '' ? $dados['peso'] : null;
        $dados['altura'] = ($dados['altura'] ?? '') !== '' ? $dados['altura'] : null;
        $dados['largura'] = ($dados['largura'] ?? '') !== '' ? $dados['largura'] : null;
        $dados['comprimento'] = ($dados['comprimento'] ?? '') !== '' ? $dados['comprimento'] : null;
        $dados['preco_custo'] = ($dados['preco_custo'] ?? '') !== '' ? $dados['preco_custo'] : null;
        $dados['preco_promocional'] = !empty($dados['preco_promocional']) ? $dados['preco_promocional'] : null;
        $dados['destaque'] = !empty($dados['destaque']) ? 1 : 0;

        return $dados;
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->produto->buscarPorId($id);
    }

    public function listarPorEmpresa(int $empresaId): array
    {
        return $this->produto->listarPorEmpresa($empresaId);
    }

    /**
     * Atualiza um produto (verifica propriedade via empresa_id)
     */
    public function atualizar(int $id, array $dados): bool
    {
        if (!$this->dadosBasicosValidos($dados)) {
            return false;
        }

        $dados = $this->normalizarCampos($dados);
        $dados['ativo'] = !empty($dados['ativo']) ? 1 : 0;

        return $this->produto->atualizar($id, $dados);
    }

    public function excluir(int $id, int $empresaId): bool
    {
        return $this->produto->excluir($id, $empresaId);
    }

    /**
     * Lista produtos ativos do marketplace. Aceita as mesmas chaves de
     * Produto::listarAtivos(); busca, cidade e empresa são aparadas.
     */
    public function listarAtivos(array $criterios = []): array
    {
        foreach (['busca', 'cidade', 'empresa'] as $chave) {
            if (isset($criterios[$chave])) {
                $criterios[$chave] = trim((string) $criterios[$chave]);
            }
        }

        return $this->produto->listarAtivos($criterios);
    }

    public function listarDestaques(int $limite = 4): array
    {
        return $this->produto->listarDestaques($limite);
    }

    public function listarCategorias(): array
    {
        return $this->produto->listarCategorias();
    }

    public function definirDestaque(int $produtoId, int $empresaId, bool $destaque): bool
    {
        return $this->produto->definirDestaque($produtoId, $empresaId, $destaque);
    }

    public function listarOfertas(int $limite = 24): array
    {
        return $this->produto->listarOfertas($limite);
    }

    public function contarProdutos(): int
    {
        return $this->produto->contarProdutos();
    }

    public function contarProdutosPorEmpresa(int $empresaId): int
    {
        return $this->produto->contarProdutosPorEmpresa($empresaId);
    }

    public function limiteAtingido(int $empresaId): bool
    {
        return $this->produto->limiteAtingido($empresaId);
    }

    public function buscarEstoque(int $produtoId): ?array
    {
        return $this->estoque->buscarEstoque($produtoId);
    }

    public function atualizarEstoque(int $produtoId, int $quantidade, int $min = 0, int $max = 0): bool
    {
        return $this->estoque->atualizarEstoque($produtoId, $quantidade, $min, $max);
    }
}
