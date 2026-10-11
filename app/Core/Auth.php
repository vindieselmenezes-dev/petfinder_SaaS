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

    // Modo suporte: o administrador entra no painel de uma empresa e precisa
    // poder voltar a ser administrador sem sair e entrar de novo.
    private const CHAVE_SUPORTE         = 'is_impersonating';
    private const CHAVE_SUPORTE_POR     = 'impersonated_by';
    private const CHAVE_SUPORTE_EMPRESA = 'impersonated_empresa_id';
    private const CHAVE_SUPORTE_NOME    = 'impersonated_empresa_nome';
    private const CHAVE_TIPO_ORIGINAL   = 'perfil_tipo_original';

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

    /**
     * Entra no modo suporte: o administrador passa a ver o painel da empresa
     * (o tipo da sessão vira "empresa"), guardando o tipo original para
     * poder voltar. Só um administrador, fora do modo suporte, consegue iniciar.
     */
    public static function iniciarModoSuporte(int $empresaId, string $empresaNome): bool
    {
        if (!self::check() || !self::ehAdministrador() || self::emModoSuporte()) {
            return false;
        }

        $_SESSION[self::CHAVE_TIPO_ORIGINAL]   = self::TIPO_ADMINISTRADOR;
        $_SESSION[self::CHAVE_SUPORTE_POR]     = self::id();
        $_SESSION[self::CHAVE_SUPORTE]         = true;
        $_SESSION[self::CHAVE_SUPORTE_EMPRESA] = $empresaId;
        $_SESSION[self::CHAVE_SUPORTE_NOME]    = $empresaNome;
        $_SESSION[self::CHAVE_TIPO]            = self::TIPO_EMPRESA;

        return true;
    }

    /**
     * Verdadeiro só quando a sessão foi realmente colocada em modo suporte por
     * um administrador: as marcas precisam bater com o usuário logado.
     */
    public static function emModoSuporte(): bool
    {
        return ($_SESSION[self::CHAVE_SUPORTE] ?? false) === true
            && (int) ($_SESSION[self::CHAVE_SUPORTE_POR] ?? 0) === (int) (self::id() ?? 0)
            && ($_SESSION[self::CHAVE_TIPO_ORIGINAL] ?? '') === self::TIPO_ADMINISTRADOR;
    }

    public static function nomeEmpresaSuporte(): string
    {
        return (string) ($_SESSION[self::CHAVE_SUPORTE_NOME] ?? 'Empresa');
    }

    /**
     * Sai do modo suporte e devolve o tipo de administrador. Se a sessão não
     * estiver em modo suporte válido, só limpa as marcas e NÃO promove ninguém.
     */
    public static function encerrarModoSuporte(): bool
    {
        $valido = self::emModoSuporte();

        if ($valido) {
            $_SESSION[self::CHAVE_TIPO] = self::TIPO_ADMINISTRADOR;
        }

        unset(
            $_SESSION[self::CHAVE_SUPORTE],
            $_SESSION[self::CHAVE_SUPORTE_POR],
            $_SESSION[self::CHAVE_SUPORTE_EMPRESA],
            $_SESSION[self::CHAVE_SUPORTE_NOME],
            $_SESSION[self::CHAVE_TIPO_ORIGINAL]
        );

        return $valido;
    }

    /**
     * Tipos de conta que o próprio visitante pode escolher no cadastro público.
     * "administrador" NUNCA entra aqui: contas de administrador são promovidas
     * por outro administrador ou direto no banco de dados.
     */
    public const TIPOS_CADASTRO_PUBLICO = ['tutor', 'empresa'];

    /**
     * Valida o tipo de conta pedido no cadastro público. Qualquer valor fora
     * da lista permitida (inclusive "administrador") vira "tutor".
     */
    public static function tipoDeCadastroPublico(?string $pedido): string
    {
        return in_array($pedido, self::TIPOS_CADASTRO_PUBLICO, true) ? $pedido : 'tutor';
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
