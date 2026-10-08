<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: PetImagemController
 * Upload e galeria de imagens dos pets.
 * ==========================================================
 */

require_once __DIR__ . '/../Models/PetImagem.php';

class PetImagemController
{
    /**
     * Tamanho máximo permitido por imagem (em bytes) - 5 MB
     */
    private const TAMANHO_MAX_IMAGEM = 5 * 1024 * 1024;

    /**
     * Tipos MIME realmente aceitos (confirmados pelo conteúdo do arquivo,
     * não pela extensão do nome)
     */
    private const MIME_PERMITIDOS = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    private PetImagem $imagem;

    public function __construct()
    {
        $this->imagem = new PetImagem();
    }

    /**
     * Verifica se um arquivo enviado é realmente uma imagem válida,
     * lendo o conteúdo real do arquivo (não confiando no nome/extensão)
     */
    private function arquivoEhImagemValida(string $caminhoTemporario, int $tamanho): bool
    {
        if ($tamanho <= 0 || $tamanho > self::TAMANHO_MAX_IMAGEM) {
            return false;
        }

        // getimagesize() lê o cabeçalho real do arquivo - se não for uma
        // imagem de verdade (mesmo que tenha nome "foto.jpg"), retorna false
        $infoImagem = @getimagesize($caminhoTemporario);

        if ($infoImagem === false) {
            return false;
        }

        $mimeReal = $infoImagem['mime'] ?? '';

        if (!in_array($mimeReal, self::MIME_PERMITIDOS, true)) {
            return false;
        }

        return true;
    }

    /**
     * Normaliza o $_FILES de um ou vários arquivos numa lista simples,
     * descartando os que tiveram erro no upload.
     */
    private function listarArquivosEnviados(array $arquivos): array
    {
        if (!isset($arquivos["name"])) {
            return [];
        }

        if (is_array($arquivos["name"])) {
            return $this->listarUploadMultiplo($arquivos);
        }

        if (($arquivos["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [];
        }

        return [[
            "name" => $arquivos["name"] ?? "",
            "tmp_name" => $arquivos["tmp_name"] ?? "",
            "error" => $arquivos["error"] ?? UPLOAD_ERR_NO_FILE,
            "size" => (int) ($arquivos["size"] ?? 0),
        ]];
    }

    private function listarUploadMultiplo(array $arquivos): array
    {
        $lista = [];

        foreach ($arquivos["name"] as $index => $nomeArquivo) {
            if (($arquivos["error"][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $lista[] = [
                "name" => $nomeArquivo,
                "tmp_name" => $arquivos["tmp_name"][$index] ?? "",
                "error" => $arquivos["error"][$index] ?? UPLOAD_ERR_NO_FILE,
                "size" => (int) ($arquivos["size"][$index] ?? 0),
            ];
        }

        return $lista;
    }

    /**
     * Processa uploads de múltiplas fotos de um pet.
     * A primeira imagem válida vira a foto principal do pet,
     * enquanto as demais são armazenadas na galeria adicional.
     */
    public function processarImagensUpload(array $arquivos, string $fotoAtual = "sem-foto.png"): array
    {
        $permitidas = ["jpg", "jpeg", "png", "webp"];
        $imagensExtras = [];
        $foto = $fotoAtual !== "" ? $fotoAtual : "sem-foto.png";

        $diretorio = dirname(__DIR__, 2) . "/uploads/pets";

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        $arquivosParaProcessar = $this->listarArquivosEnviados($arquivos);

        $fotoPrincipalDefinida = ($foto !== "sem-foto.png" && $foto !== "" && $foto !== null);

        foreach ($arquivosParaProcessar as $arquivo) {
            $extensao = strtolower(pathinfo($arquivo["name"], PATHINFO_EXTENSION));

            // 1ª camada: extensão do nome (filtro rápido, mas não confiável sozinho)
            if (!in_array($extensao, $permitidas, true)) {
                continue;
            }

            // 2ª e 3ª camadas: tamanho real + conteúdo real do arquivo
            // (garante que não é um arquivo malicioso disfarçado de imagem)
            if (!$this->arquivoEhImagemValida($arquivo["tmp_name"], $arquivo["size"])) {
                continue;
            }

            $novoNome = uniqid("pet_", true) . "." . $extensao;
            $destino = $diretorio . "/" . $novoNome;

            if (!ImagemUpload::salvar($arquivo["tmp_name"], $destino)) {
                continue;
            }

            if (!$fotoPrincipalDefinida) {
                $foto = $novoNome;
                $fotoPrincipalDefinida = true;
            } else {
                $imagensExtras[] = $novoNome;
            }
        }

        return [
            "foto" => $foto,
            "imagens" => $imagensExtras
        ];
    }

    /**
     * Busca imagens adicionais de um pet
     */
    public function buscarImagens(int $petId): array
    {
        return $this->imagem->buscarImagens($petId);
    }

    public function excluirImagem(int $imagemId, int $petId): bool
    {
        return $this->imagem->excluirImagem($imagemId, $petId);
    }
}
