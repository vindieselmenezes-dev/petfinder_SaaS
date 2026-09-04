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
    private const TAMANHO_MAX_IMAGEM = 5 * 1024 * 1024;

    private const MIME_PERMITIDOS = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    private Produto $produto;

    public function __construct()
    {
        $this->produto = new Produto();
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
     * Verifica se um arquivo enviado é realmente uma imagem válida
     */
    private function arquivoEhImagemValida(string $caminhoTemporario, int $tamanho): bool
    {
        if ($tamanho <= 0 || $tamanho > self::TAMANHO_MAX_IMAGEM) {
            return false;
        }

        $infoImagem = @getimagesize($caminhoTemporario);

        if ($infoImagem === false) {
            return false;
        }

        return in_array($infoImagem['mime'] ?? '', self::MIME_PERMITIDOS, true);
    }

    /**
     * Processa upload de múltiplas imagens de produto
     */
    public function processarImagens(array $arquivos): array
    {
        $permitidas = ["jpg", "jpeg", "png", "webp"];
        $imagensSalvas = [];

        if (empty($arquivos['name']) || !is_array($arquivos['name'])) {
            return [];
        }

        $diretorio = dirname(__DIR__, 2) . "/uploads/produtos";

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        foreach ($arquivos['name'] as $index => $nomeArquivo) {

            if (($arquivos['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));

            if (!in_array($extensao, $permitidas, true)) {
                continue;
            }

            $tmpName = $arquivos['tmp_name'][$index] ?? '';
            $tamanho = (int) ($arquivos['size'][$index] ?? 0);

            if (!$this->arquivoEhImagemValida($tmpName, $tamanho)) {
                continue;
            }

            $novoNome = uniqid("produto_", true) . "." . $extensao;
            $destino = $diretorio . "/" . $novoNome;

            if (move_uploaded_file($tmpName, $destino)) {
                $imagensSalvas[] = $novoNome;
            }

        }

        return $imagensSalvas;
    }

    /**
     * Cadastra um novo produto
     */
    public function cadastrar(array $dados): int|false
    {
        if (empty($dados['empresa_id'])) {
            return false;
        }

        if (empty($dados['nome'])) {
            return false;
        }

        if (empty($dados['preco_venda']) || (float) $dados['preco_venda'] <= 0) {
            return false;
        }

        $dados['categoria_id'] = 9; // Marketplace (fixa)
        $dados['subcategoria_id'] = !empty($dados['subcategoria_id']) ? (int) $dados['subcategoria_id'] : null;
        $dados['marca_id'] = !empty($dados['marca_id']) ? (int) $dados['marca_id'] : null;
        $dados['descricao'] = $dados['descricao'] ?? '';
        $dados['sku'] = !empty($dados['sku']) ? $dados['sku'] : null;
        $dados['codigo_barras'] = !empty($dados['codigo_barras']) ? $dados['codigo_barras'] : null;
        $dados['peso'] = $dados['peso'] !== '' ? $dados['peso'] : null;
        $dados['altura'] = $dados['altura'] !== '' ? $dados['altura'] : null;
        $dados['largura'] = $dados['largura'] !== '' ? $dados['largura'] : null;
        $dados['comprimento'] = $dados['comprimento'] !== '' ? $dados['comprimento'] : null;
        $dados['preco_custo'] = $dados['preco_custo'] !== '' ? $dados['preco_custo'] : null;
        $dados['preco_promocional'] = !empty($dados['preco_promocional']) ? $dados['preco_promocional'] : null;
        $dados['destaque'] = !empty($dados['destaque']) ? 1 : 0;
        $dados['ativo'] = 1;

        return $this->produto->cadastrar($dados);
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
        if (empty($dados['empresa_id'])) {
            return false;
        }

        if (empty($dados['nome'])) {
            return false;
        }

        if (empty($dados['preco_venda']) || (float) $dados['preco_venda'] <= 0) {
            return false;
        }

        $dados['subcategoria_id'] = !empty($dados['subcategoria_id']) ? (int) $dados['subcategoria_id'] : null;
        $dados['marca_id'] = !empty($dados['marca_id']) ? (int) $dados['marca_id'] : null;
        $dados['descricao'] = $dados['descricao'] ?? '';
        $dados['sku'] = !empty($dados['sku']) ? $dados['sku'] : null;
        $dados['codigo_barras'] = !empty($dados['codigo_barras']) ? $dados['codigo_barras'] : null;
        $dados['peso'] = $dados['peso'] !== '' ? $dados['peso'] : null;
        $dados['altura'] = $dados['altura'] !== '' ? $dados['altura'] : null;
        $dados['largura'] = $dados['largura'] !== '' ? $dados['largura'] : null;
        $dados['comprimento'] = $dados['comprimento'] !== '' ? $dados['comprimento'] : null;
        $dados['preco_custo'] = $dados['preco_custo'] !== '' ? $dados['preco_custo'] : null;
        $dados['preco_promocional'] = !empty($dados['preco_promocional']) ? $dados['preco_promocional'] : null;
        $dados['destaque'] = !empty($dados['destaque']) ? 1 : 0;
        $dados['ativo'] = !empty($dados['ativo']) ? 1 : 0;

        return $this->produto->atualizar($id, $dados);
    }

    public function excluir(int $id, int $empresaId): bool
    {
        return $this->produto->excluir($id, $empresaId);
    }

    public function listarAtivos(
        string $busca = '',
        int $subcategoriaId = 0,
        int $marcaId = 0,
        float $precoMin = 0.0,
        float $precoMax = 0.0,
        string $ordem = 'recente',
        string $cidade = '',
        int $categoriaId = 0
    ): array {
        return $this->produto->listarAtivos(trim($busca), $subcategoriaId, $marcaId, $precoMin, $precoMax, $ordem, trim($cidade), $categoriaId);
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

    public function buscarEstoque(int $produtoId): ?array
    {
        return $this->produto->buscarEstoque($produtoId);
    }

    public function atualizarEstoque(int $produtoId, int $quantidade, int $min = 0, int $max = 0): bool
    {
        return $this->produto->atualizarEstoque($produtoId, $quantidade, $min, $max);
    }

    public function salvarImagens(int $produtoId, array $imagens): bool
    {
        return $this->produto->salvarImagens($produtoId, $imagens);
    }

    public function buscarImagens(int $produtoId): array
    {
        return $this->produto->buscarImagens($produtoId);
    }

    public function excluirImagem(int $imagemId, int $produtoId): bool
    {
        return $this->produto->excluirImagem($imagemId, $produtoId);
    }
}
