<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: PetVacina
 * Quadro de vacinas (carteira de vacinação) dos pets.
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class PetVacina
{
    /** Quantos dias antes do vencimento a vacina passa a "vence em breve". */
    public const DIAS_ALERTA = 30;

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /** Catálogo de vacinas disponíveis para registrar. */
    public function listarCatalogo(): array
    {
        return $this->pdo
            ->query('SELECT id, nome, periodicidade, periodicidade_dias, obrigatoria FROM vacinas ORDER BY obrigatoria DESC, nome')
            ->fetchAll();
    }

    /** Todas as doses registradas do pet, da mais recente para a mais antiga. */
    public function listarPorPet(int $petId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT pv.id, pv.pet_id, pv.vacina_id, pv.data_aplicacao, pv.proxima_dose,
                   pv.veterinario, pv.lote, pv.observacoes,
                   pv.empresa_id, e.nome_fantasia AS clinica,
                   v.nome AS vacina, v.obrigatoria
            FROM pet_vacinas pv
            INNER JOIN vacinas v ON v.id = pv.vacina_id
            LEFT JOIN empresas e ON e.id = pv.empresa_id
            WHERE pv.pet_id = :pet_id
            ORDER BY pv.data_aplicacao DESC, pv.id DESC
        ');
        $stmt->execute([':pet_id' => $petId]);

        return $stmt->fetchAll();
    }

    /**
     * Monta o quadro: uma linha por vacina (a dose mais recente de cada uma)
     * com a situação calculada. Vacinas obrigatórias nunca aplicadas aparecem
     * como "Pendente".
     *
     * @return array<int,array<string,mixed>>
     */
    public function montarQuadro(int $petId, ?string $hoje = null): array
    {
        $hoje ??= date('Y-m-d');
        $ultimaPorVacina = [];

        foreach ($this->listarPorPet($petId) as $dose) {
            $ultimaPorVacina[(int) $dose['vacina_id']] ??= $dose; // já vem da mais recente
        }

        $quadro = [];
        foreach ($this->listarCatalogo() as $vacina) {
            $dose = $ultimaPorVacina[(int) $vacina['id']] ?? null;
            if ($dose === null && !(int) $vacina['obrigatoria']) {
                continue;
            }
            $quadro[] = [
                'vacina_id' => (int) $vacina['id'],
                'vacina' => $vacina['nome'],
                'obrigatoria' => (int) $vacina['obrigatoria'] === 1,
                'dose' => $dose,
                'situacao' => self::situacao($dose['proxima_dose'] ?? null, $dose !== null, $hoje),
            ];
        }

        return $quadro;
    }

    /** Em dia | Vence em breve | Atrasada | Pendente. */
    public static function situacao(?string $proximaDose, bool $aplicada, string $hoje): string
    {
        if (!$aplicada) {
            return 'Pendente';
        }
        if ($proximaDose === null || $proximaDose === '') {
            return 'Em dia';
        }
        if ($proximaDose < $hoje) {
            return 'Atrasada';
        }
        $limite = date('Y-m-d', strtotime($hoje . ' +' . self::DIAS_ALERTA . ' days'));

        return $proximaDose <= $limite ? 'Vence em breve' : 'Em dia';
    }

    /**
     * Resumo para a carteirinha pública: só vacina, data e situação.
     * Veterinário, lote e observações nunca saem daqui.
     *
     * @return array<int,array{vacina:string,data_aplicacao:string,situacao:string}>
     */
    public function resumoPublico(int $petId, ?string $hoje = null): array
    {
        $hoje ??= date('Y-m-d');
        $resumo = [];

        foreach ($this->montarQuadro($petId, $hoje) as $linha) {
            if ($linha['dose'] === null) {
                continue;
            }
            $resumo[] = [
                'vacina' => $linha['vacina'],
                'data_aplicacao' => $linha['dose']['data_aplicacao'],
                'situacao' => $linha['situacao'],
            ];
        }

        return $resumo;
    }

    /** A clínica só mexe em pets que ela atendeu (consulta não cancelada). */
    public function petAtendidoPelaEmpresa(int $petId, int $empresaId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM consultas
            WHERE pet_id = :pet_id AND empresa_id = :empresa_id AND status <> 'Cancelada'
            LIMIT 1
        ");
        $stmt->execute([':pet_id' => $petId, ':empresa_id' => $empresaId]);

        return (bool) $stmt->fetchColumn();
    }

    public function registrar(int $petId, array $dados): int|false
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO pet_vacinas (pet_id, vacina_id, data_aplicacao, proxima_dose, veterinario, lote, observacoes, empresa_id, registrado_por)
            VALUES (:pet_id, :vacina_id, :data_aplicacao, :proxima_dose, :veterinario, :lote, :observacoes, :empresa_id, :registrado_por)
        ');
        $ok = $stmt->execute([
            ':pet_id' => $petId,
            ':vacina_id' => (int) $dados['vacina_id'],
            ':data_aplicacao' => $dados['data_aplicacao'],
            ':proxima_dose' => $dados['proxima_dose'] ?: null,
            ':veterinario' => $dados['veterinario'] ?: null,
            ':lote' => $dados['lote'] ?: null,
            ':observacoes' => $dados['observacoes'] ?: null,
            ':empresa_id' => $dados['empresa_id'] ?? null,
            ':registrado_por' => $dados['registrado_por'] ?? null,
        ]);

        return $ok ? (int) $this->pdo->lastInsertId() : false;
    }

    /** Remove uma dose, só se ela pertencer ao pet informado. */
    public function excluir(int $doseId, int $petId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM pet_vacinas WHERE id = :id AND pet_id = :pet_id');
        $stmt->execute([':id' => $doseId, ':pet_id' => $petId]);

        return $stmt->rowCount() > 0;
    }
}
