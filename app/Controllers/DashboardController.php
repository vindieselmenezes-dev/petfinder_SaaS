<?php

declare(strict_types=1);

require_once __DIR__ . "/../Models/Pet.php";
require_once __DIR__ . "/../Models/Usuario.php";

class DashboardController
{
    private Pet $pet;
    private Usuario $usuario;

    public function __construct()
    {
        $this->pet = new Pet();
        $this->usuario = new Usuario();
    }

    /**
     * Retorna todas as estatísticas do Dashboard
     */
    public function estatisticas(): array
    {
        return [

            'totalPets' => $this->pet->contarPets(),

            'totalUsuarios' => $this->usuario->contarUsuarios(),

            'petsPerdidos' => $this->pet->contarPorStatus('Perdido'),

            'petsEncontrados' => $this->pet->contarPorStatus('Encontrado')

        ];
    }
}