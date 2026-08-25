<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: PetController
 * ==========================================================
 */

require_once __DIR__ . '/../Models/Pet.php';

class PetController
{
    /**
     * Status válidos para um pet
     */
    private const STATUS_VALIDOS = [
        "Com Tutor",
        "Perdido",
        "Encontrado",
        "Para Adoção",
        "Adotado"
    ];

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

    /**
     * Model
     */
    private Pet $pet;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->pet = new Pet();
    }

    /**
     * Lista todas as espécies
     */
    public function listarEspecies(): array
    {
        return $this->pet->listarEspecies();
    }

    public function listarCidadesComPets(): array
    {
        return $this->pet->listarCidadesComPets();
    }

    /**
     * Lista as raças de uma espécie
     */
    public function listarRacas(int $especieId): array
    {
        return $this->pet->listarRacas($especieId);
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

        $arquivosParaProcessar = [];

        if (isset($arquivos["name"])) {
            if (is_array($arquivos["name"])) {
                foreach ($arquivos["name"] as $index => $nomeArquivo) {
                    if (($arquivos["error"][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $arquivosParaProcessar[] = [
                        "name" => $nomeArquivo,
                        "tmp_name" => $arquivos["tmp_name"][$index] ?? "",
                        "error" => $arquivos["error"][$index] ?? UPLOAD_ERR_NO_FILE,
                        "size" => (int) ($arquivos["size"][$index] ?? 0),
                    ];
                }
            } elseif (($arquivos["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $arquivosParaProcessar[] = [
                    "name" => $arquivos["name"] ?? "",
                    "tmp_name" => $arquivos["tmp_name"] ?? "",
                    "error" => $arquivos["error"] ?? UPLOAD_ERR_NO_FILE,
                    "size" => (int) ($arquivos["size"] ?? 0),
                ];
            }
        }

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

            if (!move_uploaded_file($arquivo["tmp_name"], $destino)) {
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
     * Cadastra um pet
     */
    public function cadastrar(array $dados, array $imagens = []): bool
    {
        /*
        |--------------------------------------------------------------
        | Validações obrigatórias
        |--------------------------------------------------------------
        */

        if (empty($dados["nome"])) {
            return false;
        }

        if (empty($dados["especie_id"])) {
            return false;
        }

        if (empty($dados["raca_id"])) {
            return false;
        }

        if (empty($dados["sexo"])) {
            return false;
        }

        /*
        |--------------------------------------------------------------
        | Valores padrão
        |--------------------------------------------------------------
        */

        $dados["cor"]             = $dados["cor"] ?? "";
        $dados["status"]          = in_array($dados["status"] ?? "", self::STATUS_VALIDOS, true)
            ? $dados["status"]
            : "Com Tutor";
        $dados["peso"]            = $dados["peso"] ?? null;
        $dados["altura"]          = $dados["altura"] ?? null;
        $dados["data_nascimento"] = $dados["data_nascimento"] ?? null;
        $dados["microchip"]       = $dados["microchip"] ?? null;
        $dados["castrado"]        = $dados["castrado"] ?? 0;
        $dados["observacoes"]     = $dados["observacoes"] ?? "";
        $dados["foto"]            = $dados["foto"] ?? "sem-foto.png";

        /*
        |--------------------------------------------------------------
        | Salva no banco
        |--------------------------------------------------------------
        */
        $petId = $this->pet->cadastrar($dados);

        if ($petId === false) {
            return false;
        }

        if (!empty($imagens)) {
            if (!$this->pet->salvarImagens($petId, array_values($imagens))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Lista os pets do usuário
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->pet->listarPorUsuario($usuarioId);
    }

    /**
     * Conta todos os pets
     */
    public function contarPets(): int
    {
        return $this->pet->contarPets();
    }

    /**
     * Lista todos os pets cadastrados
     */
    public function listarTodos(): array
    {
        return $this->pet->listarTodos();
    }

    /**
     * Exclui um pet por ID (admin)
     */
    public function excluirPorId(int $id): bool
    {
        return $this->pet->excluirPorId($id);
    }

    /**
     * Busca um pet pelo ID
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->pet->buscarPorId($id);
    }

    /**
     * Atualiza um pet existente
     */
    public function atualizar(int $id, array $dados, array $imagens = []): bool
    {
        if (empty($dados["nome"])) {
            return false;
        }

        if (empty($dados["especie_id"])) {
            return false;
        }

        if (empty($dados["raca_id"])) {
            return false;
        }

        if (empty($dados["sexo"])) {
            return false;
        }

        $dados["cor"]             = $dados["cor"] ?? "";
        $dados["status"]          = in_array($dados["status"] ?? "", self::STATUS_VALIDOS, true)
            ? $dados["status"]
            : "Com Tutor";
        $dados["peso"]            = $dados["peso"] ?? null;
        $dados["altura"]          = $dados["altura"] ?? null;
        $dados["data_nascimento"] = $dados["data_nascimento"] ?? null;
        $dados["microchip"]       = $dados["microchip"] ?? null;
        $dados["castrado"]        = $dados["castrado"] ?? 0;
        $dados["observacoes"]     = $dados["observacoes"] ?? "";
        $dados["foto"]            = $dados["foto"] ?? "sem-foto.png";

        $atualizado = $this->pet->atualizar($id, $dados);

        if (!$atualizado) {
            return false;
        }

        if (!empty($imagens)) {
            return $this->pet->salvarImagens($id, $imagens);
        }

        return true;
    }

    /**
     * Exclui um pet (verifica se pertence ao usuário)
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        return $this->pet->excluir($id, $usuarioId);
    }

    /**
     * Busca pública de pets para adoção (sem exigir login)
     */
    public function buscarAdocaoPublico(
        string $busca = '',
        string $cidade = '',
        int $especieId = 0,
        int $racaId = 0,
        string $sexo = '',
        string $cor = '',
        int $castrado = -1,
        int $idadeMin = 0,
        int $idadeMax = 0,
        float $pesoMin = 0.0,
        float $pesoMax = 0.0,
        float $alturaMin = 0.0,
        float $alturaMax = 0.0,
        string $status = 'Para Adoção',
        string $ordem = 'criado_em',
        string $direcao = 'DESC'
    ): array {
        return $this->pet->buscarAdocaoPublico(
            trim($busca),
            trim($cidade),
            $especieId,
            $racaId,
            trim($sexo),
            trim($cor),
            $castrado,
            $idadeMin,
            $idadeMax,
            $pesoMin,
            $pesoMax,
            $alturaMin,
            $alturaMax,
            $status,
            $ordem,
            $direcao
        );
    }

    /**
     * Busca imagens adicionais de um pet
     */
    public function buscarImagens(int $petId): array
    {
        return $this->pet->buscarImagens($petId);
    }

    public function excluirImagem(int $imagemId, int $petId): bool
    {
        return $this->pet->excluirImagem($imagemId, $petId);
    }

    /**
     * Lista pets da plataforma por status (para telas públicas)
     */
    public function listarPorStatus(string $status): array
    {
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            return [];
        }

        return $this->pet->listarPorStatus($status);
    }

    /**
     * Conta pets por status
     */
    public function contarPorStatus(string $status): int
    {
        return $this->pet->contarPorStatus($status);
    }

    /**
     * Atualiza o status de um pet (ex: marcar como Perdido)
     */
    public function atualizarStatus(int $petId, string $status, ?int $usuarioId = null, ?string $motivo = null): bool
    {
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            return false;
        }

        return $this->pet->atualizarStatus($petId, $status, $usuarioId, $motivo);
    }

    public function buscarHistoricoStatus(int $petId): array
    {
        return $this->pet->buscarHistoricoStatus($petId);
    }

    public function transferirTutor(int $petId, int $novoTutorId): bool
    {
        return $this->pet->transferirTutor($petId, $novoTutorId);
    }

    /**
     * Retorna a lista de status válidos (para preencher um <select>)
     */
    public function statusValidos(): array
    {
        return self::STATUS_VALIDOS;
    }
}
