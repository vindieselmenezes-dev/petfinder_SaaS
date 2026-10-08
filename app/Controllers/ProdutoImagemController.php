<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: ProdutoImagemController
 *
 * Upload e galeria de imagens de produto. Separado do
 * ProdutoController para manter cada classe com uma
 * responsabilidade só.
 * ==========================================================
 */

require_once __DIR__ . '/../Models/ProdutoImagem.php';

class ProdutoImagemController
{
    private const TAMANHO_MAX_IMAGEM = 5 * 1024 * 1024;

    private const MIME_PERMITIDOS = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    private ProdutoImagem $imagem;

    public function __construct()
    {
        $this->imagem = new ProdutoImagem();
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

            if (ImagemUpload::salvar($tmpName, $destino)) {
                $imagensSalvas[] = $novoNome;
            }

        }

        return $imagensSalvas;
    }

    public function salvarImagens(int $produtoId, array $imagens): bool
    {
        return $this->imagem->salvarImagens($produtoId, $imagens);
    }

    public function buscarImagens(int $produtoId): array
    {
        return $this->imagem->buscarImagens($produtoId);
    }

    public function excluirImagem(int $imagemId, int $produtoId): bool
    {
        return $this->imagem->excluirImagem($imagemId, $produtoId);
    }
}
