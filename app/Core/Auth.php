<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Núcleo: Auth
 * ==========================================================
 * Ponto único de verdade sobre "quem está logado".
 *
 * Antes, cada página fazia sua própria checagem, com pequenas
 * variações (`$_SESSION['perfil_tipo'] ?? $_SESSION['user_role']`,
 * `isset($_SESSION['usuario_id'])`, etc). Isso gerava duplicidade
 * e inconsistência. Agora tudo passa por aqui.
 */
final class Auth
{
    private const CHAVE_ID     = 'usuario_id';
    private const CHAVE_NOME   = 'usuario_nome';
    private const CHAVE_EMAIL  = 'usuario_email';
    private const CHAVE_TIPO   = 'perfil_tipo';

    /**
     * Tipos de perfil reconhecidos pelo sistema
     */
    public const TIPO_TUTOR         = 'cliente';
    public const TIPO_EMPRESA       = 'empresa';
    public const TIPO_ADMINISTRADOR = 'administrador';

    private function __construct()
    {
        // Classe estática — não deve ser instanciada
    }

    /**
     * Abre a sessão para o usuário autenticado.
     * Sempre regenera o ID de sessão (evita session fixation).
     */
    public static function login(array $usuario): void
    {
        session_regenerate_id(true);

        $_SESSION[self::CHAVE_ID]    = (int) $usuario['id'];
        $_SESSION[self::CHAVE_NOME]  = $usuario['nome'] ?? 'Usuário';
        $_SESSION[self::CHAVE_EMAIL] = $usuario['email'] ?? '';
        $_SESSION[self::CHAVE_TIPO]  = $usuario['perfil_tipo']
            ?? $usuario['tipo_usuario']
            ?? self::TIPO_TUTOR;
    }

    /**
     * Encerra a sessão por completo
     */
    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametros['path'],
                $parametros['domain'],
                $parametros['secure'],
                $parametros['httponly']
            );
        }

        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::CHAVE_ID]);
    }

    public static function id(): ?int
    {
        return isset($_SESSION[self::CHAVE_ID]) ? (int) $_SESSION[self::CHAVE_ID] : null;
    }

    public static function nome(): string
    {
        return $_SESSION[self::CHAVE_NOME] ?? 'Usuário';
    }

    public static function email(): string
    {
        return $_SESSION[self::CHAVE_EMAIL] ?? '';
    }

    public static function tipo(): string
    {
        return $_SESSION[self::CHAVE_TIPO] ?? self::TIPO_TUTOR;
    }

    /**
     * Verifica se o usuário logado tem um dos tipos informados.
     * Ex: Auth::tipoEhUmDe(Auth::TIPO_EMPRESA, Auth::TIPO_ADMINISTRADOR)
     */
    public static function tipoEhUmDe(string ...$tipos): bool
    {
        return in_array(self::tipo(), $tipos, true);
    }

    public static function ehAdministrador(): bool
    {
        return self::tipo() === self::TIPO_ADMINISTRADOR;
    }

    public static function ehEmpresa(): bool
    {
        return self::tipo() === self::TIPO_EMPRESA;
    }

    /**
     * Retorna os dados básicos do usuário logado (ou null)
     */
    public static function usuario(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id'    => self::id(),
            'nome'  => self::nome(),
            'email' => self::email(),
            'tipo'  => self::tipo(),
        ];
    }
}
