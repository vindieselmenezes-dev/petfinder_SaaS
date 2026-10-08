<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: PetBuscaController
 * Busca pública de pets para adoção.
 * ==========================================================
 */

require_once __DIR__ . '/../Models/PetBuscaAdocao.php';

class PetBuscaController
{
    private PetBuscaAdocao $busca;

    public function __construct()
    {
        $this->busca = new PetBuscaAdocao();
    }

    /**
     * Busca pública de pets para adoção (sem exigir login).
     * Aceita as mesmas chaves de Pet::buscarAdocaoPublico().
     */
    public function buscarAdocaoPublico(array $criterios = []): array
    {
        return $this->busca->buscarAdocaoPublico($this->aparar($criterios));
    }

    /**
     * Conta quantos pets batem com os mesmos filtros de buscarAdocaoPublico()
     * (usado para montar a paginação nas telas públicas).
     */
    public function contarAdocaoPublico(array $criterios = []): int
    {
        return $this->busca->contarAdocaoPublico($this->aparar($criterios));
    }

    /**
     * Remove espaços das pontas dos filtros de texto da busca de adoção.
     */
    private function aparar(array $criterios): array
    {
        foreach (['busca', 'cidade', 'sexo', 'cor'] as $chave) {
            if (isset($criterios[$chave])) {
                $criterios[$chave] = trim((string) $criterios[$chave]);
            }
        }

        return $criterios;
    }
}
