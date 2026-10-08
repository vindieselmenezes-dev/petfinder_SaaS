<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: PetHistoricoController
 * Histórico de status e eventos dos pets.
 * ==========================================================
 */

require_once __DIR__ . '/../Models/PetHistorico.php';

class PetHistoricoController
{
    private PetHistorico $historico;

    public function __construct()
    {
        $this->historico = new PetHistorico();
    }

    public function buscarHistoricoStatus(int $petId): array
    {
        return $this->historico->buscarHistoricoStatus($petId);
    }

    public function registrarEventoHistorico(int $petId, string $tipo, string $descricao, ?string $detalhes = null, ?string $dataEvento = null, ?int $usuarioId = null): bool
    {
        return $this->historico->registrarEventoHistorico($petId, $tipo, $descricao, $detalhes, $dataEvento, $usuarioId);
    }

    public function buscarHistoricoCompleto(int $petId): array
    {
        return $this->historico->buscarHistoricoCompleto($petId);
    }
}
