<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: Foto
 * ==========================================================
 * Antes, quase toda página que mostrava uma foto de pet, empresa ou
 * produto repetia a mesma lógica:
 *
 *   if (!empty($pet['foto']) && file_exists("../uploads/pets/" . $pet['foto'])) {
 *       $caminhoFoto = "../uploads/pets/" . $pet['foto'];
 *   } else {
 *       $caminhoFoto = "../assets/img/pets/sem-foto.png";
 *   }
 *
 * Agora isso é só:
 *
 *   Foto::url($pet['foto'], 'pets')
 *
 * E, quando a página precisa decidir entre mostrar a foto ou outra
 * coisa (um ícone, por exemplo) em vez de uma imagem padrão:
 *
 *   Foto::existe($pet['foto'], 'pets')
 */
final class Foto
{
    private function __construct()
    {
    }

    /**
     * Verifica se o arquivo de foto realmente existe em /uploads/{pasta}.
     */
    public static function existe(?string $arquivo, string $pasta): bool
    {
        if (empty($arquivo)) {
            return false;
        }

        $raizProjeto = dirname(__DIR__, 2);

        return file_exists($raizProjeto . '/uploads/' . $pasta . '/' . $arquivo);
    }

    /**
     * URL da foto, com fallback automático para uma imagem padrão
     * quando o arquivo não existe ou não foi informado.
     */
    public static function url(?string $arquivo, string $pasta, string $imagemPadrao = 'img/pets/sem-foto.png'): string
    {
        if (self::existe($arquivo, $pasta)) {
            return Url::upload($pasta . '/' . $arquivo);
        }

        return Url::asset($imagemPadrao);
    }
}
