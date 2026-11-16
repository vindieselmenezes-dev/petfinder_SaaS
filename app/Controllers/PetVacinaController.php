<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: PetVacinaController
 * ==========================================================
 */

require_once __DIR__ . '/../Models/PetVacina.php';

class PetVacinaController
{
    private PetVacina $vacinas;

    public function __construct()
    {
        $this->vacinas = new PetVacina();
    }

    public function listarCatalogo(): array
    {
        return $this->vacinas->listarCatalogo();
    }

    public function montarQuadro(int $petId): array
    {
        return $this->vacinas->montarQuadro($petId);
    }

    public function listarPorPet(int $petId): array
    {
        return $this->vacinas->listarPorPet($petId);
    }

    /**
     * Valida e registra uma dose. Retorna null se deu certo, ou a mensagem de erro.
     */
    public function registrar(int $petId, array $post, ?int $usuarioId, ?int $empresaId = null): ?string
    {
        $vacinaId = (int) ($post['vacina_id'] ?? 0);
        $aplicacao = trim((string) ($post['data_aplicacao'] ?? ''));
        $proxima = trim((string) ($post['proxima_dose'] ?? ''));

        $catalogo = array_column($this->vacinas->listarCatalogo(), 'nome', 'id');
        if (!isset($catalogo[$vacinaId])) {
            return 'Escolha uma vacina válida.';
        }
        if (!$this->dataValida($aplicacao) || $aplicacao > date('Y-m-d')) {
            return 'Informe uma data de aplicação válida (não pode ser futura).';
        }
        if ($proxima !== '' && (!$this->dataValida($proxima) || $proxima < $aplicacao)) {
            return 'A próxima dose precisa ser uma data válida, igual ou posterior à aplicação.';
        }

        $dados = [
            'vacina_id' => $vacinaId,
            'data_aplicacao' => $aplicacao,
            'proxima_dose' => $proxima,
            'veterinario' => mb_substr(trim((string) ($post['veterinario'] ?? '')), 0, 150),
            'lote' => mb_substr(trim((string) ($post['lote'] ?? '')), 0, 100),
            'observacoes' => trim((string) ($post['observacoes'] ?? '')),
            'empresa_id' => $empresaId,
            'registrado_por' => $usuarioId,
        ];

        if ($this->vacinas->registrar($petId, $dados) === false) {
            return 'Não foi possível registrar a vacina.';
        }

        (new PetHistorico())->registrarEventoHistorico(
            $petId,
            'Vacina',
            $catalogo[$vacinaId] . ' aplicada',
            $dados['lote'] !== '' ? 'Lote ' . $dados['lote'] : null,
            $aplicacao . ' 00:00:00',
            $usuarioId
        );

        return null;
    }

    /**
     * Registro feito pela equipe de uma clínica. Exige que o pet tenha sido
     * atendido pela empresa e avisa o tutor. O acesso à empresa (papel da
     * equipe) é checado pela página, antes de chamar este método.
     */
    public function registrarPelaClinica(array $pet, int $empresaId, array $post, int $usuarioId, string $nomeClinica): ?string
    {
        $petId = (int) $pet['id'];

        if (!$this->vacinas->petAtendidoPelaEmpresa($petId, $empresaId)) {
            return 'Este pet não tem consulta registrada nesta clínica.';
        }

        $erro = $this->registrar($petId, $post, $usuarioId, $empresaId);
        if ($erro !== null) {
            return $erro;
        }

        (new NotificacaoController())->criar(
            (int) $pet['usuario_id'],
            'Nova vacina registrada',
            $nomeClinica . ' registrou uma vacina na carteira de ' . $pet['nome'] . '.',
            'Sistema',
            Url::pagina('vacinas_pet.php') . '?id=' . $petId
        );

        return null;
    }

    public function excluir(int $doseId, int $petId): bool
    {
        return $this->vacinas->excluir($doseId, $petId);
    }

    private function dataValida(string $data): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $data);

        return $d !== false && $d->format('Y-m-d') === $data;
    }
}
