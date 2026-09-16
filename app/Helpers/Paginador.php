<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: Paginador
 * ==========================================================
 * Gera o HTML dos controles de paginação (Anterior / números /
 * Próxima) usado nas listagens públicas de pets.
 *
 * Preserva automaticamente todos os filtros que já estão na URL
 * (busca, cidade, status, etc.) — só troca o parâmetro "pagina".
 *
 * Uso:
 *   echo Paginador::renderizar($paginaAtual, $totalPaginas);
 */
final class Paginador
{
    private function __construct()
    {
    }

    /**
     * Quantos registros pular no SQL (LIMIT/OFFSET) para chegar na
     * página informada.
     */
    public static function offset(int $paginaAtual, int $porPagina): int
    {
        $paginaAtual = max(1, $paginaAtual);

        return ($paginaAtual - 1) * max(0, $porPagina);
    }

    /**
     * Monta a URL da página $numero, preservando o restante da querystring
     * atual (filtros de busca, ordenação, etc.).
     *
     * $parametro permite ter mais de uma paginação independente na mesma
     * página (ex: "campanhas" e "parceiros" na mesma tela), sem que uma
     * pise no parâmetro da outra.
     */
    private static function url(int $numero, string $parametro = 'pagina'): string
    {
        $query = $_GET;
        $query[$parametro] = $numero;

        return '?' . http_build_query($query);
    }

    /**
     * Renderiza o bloco de paginação.
     * Retorna string vazia quando só existe uma página (nada a paginar).
     *
     * @param int    $paginaAtual  Página atualmente exibida (1-based)
     * @param int    $totalPaginas Total de páginas disponíveis
     * @param int    $janela       Quantos números mostrar de cada lado da página atual
     * @param string $parametro    Nome do parâmetro de página na URL (padrão: "pagina")
     */
    public static function renderizar(
        int $paginaAtual,
        int $totalPaginas,
        int $janela = 2,
        string $parametro = 'pagina'
    ): string {
        if ($totalPaginas <= 1) {
            return '';
        }

        $paginaAtual = max(1, min($paginaAtual, $totalPaginas));

        $estiloBase = 'display:inline-block; padding:6px 12px; margin:2px; border-radius:4px; '
            . 'font-family:sans-serif; font-size:13px; font-weight:bold; text-decoration:none;';
        $estiloInativo = $estiloBase . ' background:#f1f2f6; color:#95a5a6; pointer-events:none;';
        $estiloLink = $estiloBase . ' background:#f1f2f6; color:#34495e;';
        $estiloAtual = $estiloBase . ' background:#3498db; color:#fff;';

        $html = '<nav style="margin-top:20px; text-align:center;" aria-label="Paginação">';

        // Botão "Anterior"
        if ($paginaAtual > 1) {
            $html .= '<a href="' . htmlspecialchars(self::url($paginaAtual - 1, $parametro)) . '" style="' . $estiloLink . '">&laquo; Anterior</a>';
        } else {
            $html .= '<span style="' . $estiloInativo . '">&laquo; Anterior</span>';
        }

        // Números de página (com janela ao redor da página atual)
        $inicio = max(1, $paginaAtual - $janela);
        $fim = min($totalPaginas, $paginaAtual + $janela);

        if ($inicio > 1) {
            $html .= '<a href="' . htmlspecialchars(self::url(1, $parametro)) . '" style="' . $estiloLink . '">1</a>';
            if ($inicio > 2) {
                $html .= '<span style="' . $estiloBase . ' color:#bbb;">&hellip;</span>';
            }
        }

        for ($numero = $inicio; $numero <= $fim; $numero++) {
            if ($numero === $paginaAtual) {
                $html .= '<span style="' . $estiloAtual . '">' . $numero . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars(self::url($numero, $parametro)) . '" style="' . $estiloLink . '">' . $numero . '</a>';
            }
        }

        if ($fim < $totalPaginas) {
            if ($fim < $totalPaginas - 1) {
                $html .= '<span style="' . $estiloBase . ' color:#bbb;">&hellip;</span>';
            }
            $html .= '<a href="' . htmlspecialchars(self::url($totalPaginas, $parametro)) . '" style="' . $estiloLink . '">' . $totalPaginas . '</a>';
        }

        // Botão "Próxima"
        if ($paginaAtual < $totalPaginas) {
            $html .= '<a href="' . htmlspecialchars(self::url($paginaAtual + 1, $parametro)) . '" style="' . $estiloLink . '">Próxima &raquo;</a>';
        } else {
            $html .= '<span style="' . $estiloInativo . '">Próxima &raquo;</span>';
        }

        $html .= '</nav>';

        return $html;
    }
}
