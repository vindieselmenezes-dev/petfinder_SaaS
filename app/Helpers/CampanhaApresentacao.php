<?php

declare(strict_types=1);

/**
 * Textos e números usados só para exibir publicações de parceiros
 * (rótulo, ícone e percentual da meta). Sem acesso a banco.
 */
final class CampanhaApresentacao
{
    private function __construct()
    {
        // Classe utilitária (apenas métodos estáticos): não deve ser instanciada.
    }

    /**
     * Percentual da meta atingido (0 a 100). Devolve null quando a
     * publicação não tem meta em dinheiro — aí a barra nem aparece.
     */
    public static function percentualMeta(?float $meta, ?float $arrecadado): ?int
    {
        if ($meta === null || $meta <= 0) {
            return null;
        }

        return (int) min(100, round((($arrecadado ?? 0) / $meta) * 100));
    }

    public static function rotuloTipo(?string $tipo): string
    {
        return Campanha::TIPOS[$tipo ?? '']['rotulo'] ?? 'Publicação';
    }

    public static function iconeTipo(?string $tipo): string
    {
        return Campanha::TIPOS[$tipo ?? '']['icone'] ?? '🐾';
    }
}
