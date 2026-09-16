<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Plano.php';

class PlanoController
{
    private Plano $plano;

    public function __construct()
    {
        $this->plano = new Plano();
    }

    public function listarAtivos(): array
    {
        return $this->plano->listarAtivos();
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->plano->buscarPorId($id);
    }

    public function buscarPorSlug(string $slug): ?array
    {
        return $this->plano->buscarPorSlug($slug);
    }

    public function buscarPlanoGratis(): ?array
    {
        return $this->plano->buscarPlanoGratis();
    }
}
