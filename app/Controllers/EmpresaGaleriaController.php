<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: EmpresaGaleriaController
 *
 * Upload de imagens (logo, capa e galeria) e operações da galeria de
 * uma empresa. Separado do EmpresaController para manter cada classe
 * com uma responsabilidade só.
 * ==========================================================
 */

require_once __DIR__ . '/../Models/Empresa.php';

class EmpresaGaleriaController
{
    private const TAMANHO_MAX_IMAGEM = 5 * 1024 * 1024;

    private const MIME_PERMITIDOS = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    private Empresa $empresa;

    public function __construct()
    {
        $this->empresa = new Empresa();
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
     * Processa o upload de uma única imagem (logo ou capa).
     * Retorna o nome do novo arquivo, ou null se não houver upload válido.
     */
    public function processarImagemUnica(array $arquivo): ?string
    {
        if (empty($arquivo['name']) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $permitidas = ["jpg", "jpeg", "png", "webp"];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, $permitidas, true)) {
            return null;
        }

        if (!$this->arquivoEhImagemValida($arquivo['tmp_name'], (int) ($arquivo['size'] ?? 0))) {
            return null;
        }

        $diretorio = dirname(__DIR__, 2) . "/uploads/empresas";

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        $novoNome = uniqid("empresa_", true) . "." . $extensao;
        $destino = $diretorio . "/" . $novoNome;

        if (!ImagemUpload::salvar($arquivo['tmp_name'], $destino)) {
            return null;
        }

        return $novoNome;
    }

    /**
     * Processa upload de múltiplas imagens para a galeria
     */
    public function processarGaleria(array $arquivos): array
    {
        $permitidas = ["jpg", "jpeg", "png", "webp"];
        $imagensSalvas = [];

        if (empty($arquivos['name']) || !is_array($arquivos['name'])) {
            return [];
        }

        $diretorio = dirname(__DIR__, 2) . "/uploads/empresas";

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

            $novoNome = uniqid("empresa_galeria_", true) . "." . $extensao;
            $destino = $diretorio . "/" . $novoNome;

            if (ImagemUpload::salvar($tmpName, $destino)) {
                $imagensSalvas[] = $novoNome;
            }

        }

        return $imagensSalvas;
    }

    public function salvarGaleria(int $empresaId, array $imagens): bool
    {
        return $this->empresa->salvarGaleria($empresaId, $imagens);
    }

    public function buscarGaleria(int $empresaId): array
    {
        return $this->empresa->buscarGaleria($empresaId);
    }

    public function excluirImagemGaleria(int $imagemId, int $empresaId): bool
    {
        return $this->empresa->excluirImagemGaleria($imagemId, $empresaId);
    }
}
