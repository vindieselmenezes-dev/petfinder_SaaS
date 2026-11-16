<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: PetAcesso
 * Regra única de quem pode VER um pet.
 *
 *  - Perfil (pet.php): o tutor, o administrador e qualquer pessoa
 *    quando o pet está em um status público (STATUS_PUBLICOS).
 *  - Dados privados (histórico, vacinas): só o tutor e o administrador.
 *
 * Os métodos *Para() são puros (fáceis de testar). Os demais
 * usam o usuário logado.
 * ==========================================================
 */

class PetAcesso
{
    /**
     * Status em que o perfil é público. "Para Adoção" é a regra pedida;
     * "Perdido" e "Encontrado" ficam públicos porque o alerta de pet
     * perdido só funciona se qualquer pessoa puder ver o pet.
     * Para deixar só adoção pública, remova os dois últimos.
     */
    public const STATUS_PUBLICOS = ['Para Adoção', 'Perdido', 'Encontrado'];

    public static function ehDonoPara(array $pet, ?int $usuarioId): bool
    {
        return $usuarioId !== null && $usuarioId > 0 && (int) ($pet['usuario_id'] ?? 0) === $usuarioId;
    }

    public static function podeVerPerfilPara(array $pet, ?int $usuarioId, bool $admin): bool
    {
        return $admin
            || self::ehDonoPara($pet, $usuarioId)
            || in_array($pet['status'] ?? '', self::STATUS_PUBLICOS, true);
    }

    public static function podeVerPrivadoPara(array $pet, ?int $usuarioId, bool $admin): bool
    {
        return $admin || self::ehDonoPara($pet, $usuarioId);
    }

    public static function podeVerPerfil(?array $pet): bool
    {
        return $pet !== null && self::podeVerPerfilPara($pet, self::usuarioAtual(), self::admin());
    }

    public static function podeVerPrivado(?array $pet): bool
    {
        return $pet !== null && self::podeVerPrivadoPara($pet, self::usuarioAtual(), self::admin());
    }

    private static function usuarioAtual(): ?int
    {
        return Auth::check() ? Auth::id() : null;
    }

    private static function admin(): bool
    {
        return Auth::check() && Auth::ehAdministrador();
    }
}
