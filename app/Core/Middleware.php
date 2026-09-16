<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Núcleo: Middleware
 * ==========================================================
 * Guardas de acesso reutilizáveis. Substitui o padrão repetido em
 * dezenas de páginas:
 *
 *   if (!isset($_SESSION['usuario_id'])) {
 *       header('Location: login.php');
 *       exit;
 *   }
 *
 * Uso no topo de uma página protegida:
 *   Middleware::exigirLogin();
 *   Middleware::exigirTipo(Auth::TIPO_EMPRESA);
 */
final class Middleware
{
    private function __construct()
    {
    }

    /**
     * Exige que exista um usuário logado. Caso contrário, redireciona
     * para o login já preparado para voltar à página de origem.
     */
    public static function exigirLogin(?string $paginaLogin = null): void
    {
        if (Auth::check()) {
            return;
        }

        $paginaLogin ??= Url::pagina('login.php');
        $voltar = $_SERVER['REQUEST_URI'] ?? null;
        $destino = $paginaLogin . ($voltar ? '?voltar=' . urlencode($voltar) : '');

        header('Location: ' . $destino);
        exit;
    }

    /**
     * Exige login E que o usuário tenha um dos tipos de perfil informados.
     * Quem estiver logado mas com o tipo errado recebe 403.
     */
    public static function exigirTipo(string ...$tipos): void
    {
        self::exigirLogin();

        if (!Auth::tipoEhUmDe(...$tipos)) {
            http_response_code(403);
            echo '<h1>403 - Acesso não autorizado</h1>';
            echo '<p>Seu perfil não tem permissão para acessar esta página.</p>';
            echo '<p><a href="' . htmlspecialchars(Url::pagina('dashboard.php')) . '">Voltar ao painel</a></p>';
            exit;
        }
    }

    /**
     * Valida o token CSRF de um POST. Encerra a requisição se inválido.
     */
    public static function exigirCsrfValido(): void
    {
        $token = $_POST['csrf_token'] ?? null;

        if (!Csrf::validar($token)) {
            http_response_code(419);
            echo '<h1>Sessão expirada</h1>';
            echo '<p>Recarregue a página e tente novamente.</p>';
            exit;
        }
    }
}
