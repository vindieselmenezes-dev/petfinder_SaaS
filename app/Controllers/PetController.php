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
    private const STATUS_COM_TUTOR = 'Com Tutor';
    private const STATUS_PARA_ADOCAO = 'Para Adoção';

    /**
     * Status válidos para um pet
     */
    private const STATUS_VALIDOS = [
        self::STATUS_COM_TUTOR,
        "Perdido",
        "Encontrado",
        self::STATUS_PARA_ADOCAO,
        "Adotado"
    ];

    /**
     * Model
     */
    private Pet $pet;
    private PetImagem $imagem;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->pet = new Pet();
        $this->imagem = new PetImagem();
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

        $dados["cor"] = $dados["cor"] ?? "";
        $dados["status"] = in_array($dados["status"] ?? "", self::STATUS_VALIDOS, true)
            ? $dados["status"]
            : self::STATUS_COM_TUTOR;
        $dados["peso"] = $dados["peso"] ?? null;
        $dados["altura"] = $dados["altura"] ?? null;
        $dados["data_nascimento"] = $dados["data_nascimento"] ?? null;
        $dados["microchip"] = $dados["microchip"] ?? null;
        $dados["castrado"] = $dados["castrado"] ?? 0;
        $dados["observacoes"] = $dados["observacoes"] ?? "";
        $dados["foto"] = $dados["foto"] ?? "sem-foto.png";

        /*
        |--------------------------------------------------------------
        | Salva no banco
        |--------------------------------------------------------------
        */
        $petId = $this->pet->cadastrar($dados);

        if ($petId === false) {
            return false;
        }

        if (!empty($imagens) && !$this->imagem->salvarImagens($petId, array_values($imagens))) {
            return false;
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

    public function buscarPorToken(string $token): ?array
    {
        return $this->pet->buscarPorToken($token);
    }

    public function garantirTokenIdentidade(int $petId): string
    {
        return $this->pet->garantirTokenIdentidade($petId);
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

        $dados["cor"] = $dados["cor"] ?? "";
        $dados["status"] = in_array($dados["status"] ?? "", self::STATUS_VALIDOS, true)
            ? $dados["status"]
            : self::STATUS_COM_TUTOR;
        $dados["peso"] = $dados["peso"] ?? null;
        $dados["altura"] = $dados["altura"] ?? null;
        $dados["data_nascimento"] = $dados["data_nascimento"] ?? null;
        $dados["microchip"] = $dados["microchip"] ?? null;
        $dados["castrado"] = $dados["castrado"] ?? 0;
        $dados["observacoes"] = $dados["observacoes"] ?? "";
        $dados["foto"] = $dados["foto"] ?? "sem-foto.png";

        $atualizado = $this->pet->atualizar($id, $dados);

        if (!$atualizado) {
            return false;
        }

        if (!empty($imagens)) {
            return $this->imagem->salvarImagens($id, $imagens);
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
     * Lista pets da plataforma por status (para telas públicas)
     */
    public function listarPorStatus(string $status, int $pagina = 1, int $porPagina = 0): array
    {
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            return [];
        }

        return $this->pet->listarPorStatus($status, $pagina, $porPagina);
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
