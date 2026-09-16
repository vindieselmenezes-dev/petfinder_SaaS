<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — sitemap.xml
 * ==========================================================
 * Gera o sitemap combinando:
 *   1) Páginas estáticas de conteúdo (home, sobre, contato, categorias...)
 *   2) Listagens dinâmicas do banco (pets, empresas, produtos,
 *      prestadores, parceiros, campanhas) — reaproveitando os métodos
 *      de listagem pública que os models já têm.
 *
 * Cacheado em storage/cache/sitemap.xml por CACHE_TTL_SEGUNDOS: gerar
 * isso do zero em toda visita de robô de busca seria uma consulta em
 * 6 tabelas por request, sem necessidade nenhuma — o conteúdo não muda
 * minuto a minuto.
 *
 * Também pode ser rodado pela linha de comando (php sitemap.php), por
 * exemplo num cron a cada poucas horas, só para manter o cache sempre
 * quente e a primeira visita de um robô nunca pagar o custo de gerar
 * na hora. Funciona nos dois casos porque o bootstrap já é CLI-safe.
 *
 * Limite do protocolo de sitemap: 50.000 URLs / 50MB por arquivo. Este
 * site está muito longe disso, mas cada listagem abaixo tem um teto de
 * segurança (LIMITE_POR_TIPO) só para nunca estourar sem querer; se
 * algum catálogo crescer além disso de verdade, aí é hora de dividir em
 * vários sitemaps com um sitemap-index (não implementado aqui).
 */

require_once __DIR__ . '/app/bootstrap.php';

const CACHE_TTL_SEGUNDOS = 6 * 3600; // 6 horas
const LIMITE_POR_TIPO = 5000;
const CACHE_ARQUIVO = __DIR__ . '/storage/cache/sitemap.xml';

// --- Serve do cache se ainda estiver fresco ---------------------------------
if (is_file(CACHE_ARQUIVO) && (time() - filemtime(CACHE_ARQUIVO)) < CACHE_TTL_SEGUNDOS) {
    header('Content-Type: application/xml; charset=UTF-8');
    readfile(CACHE_ARQUIVO);
    exit;
}

/**
 * URL absoluta, preferindo APP_URL (necessário para gerar corretamente
 * quando rodado via CLI/cron, onde não existe HTTP_HOST). Mesma
 * prioridade que Seo::tags() já usa.
 */
function sitemapUrlAbsoluta(string $caminhoRelativo): string
{
    $base = rtrim((string) (getenv('APP_URL') ?: ''), '/');

    if ($base !== '') {
        return $base . $caminhoRelativo;
    }

    return Url::absoluta($caminhoRelativo);
}

/** @var array<int, array{loc: string, lastmod?: string, changefreq: string, priority: string}> $urls */
$urls = [];

function adicionarUrl(array &$urls, string $caminhoRelativo, string $changefreq, string $priority, ?string $lastmod = null): void
{
    $entrada = [
        'loc'        => sitemapUrlAbsoluta($caminhoRelativo),
        'changefreq' => $changefreq,
        'priority'   => $priority,
    ];

    if ($lastmod !== null && $lastmod !== '') {
        $entrada['lastmod'] = substr($lastmod, 0, 10); // YYYY-MM-DD
    }

    $urls[] = $entrada;
}

// =============================================================
// 1) Páginas estáticas de conteúdo
// =============================================================
// [arquivo, changefreq, priority]
$paginasEstaticas = [
    // Home é o único caso servido fora de /public (index.html na raiz).
    ['__home__', 'weekly', '1.0'],

    ['sobre.php', 'monthly', '0.6'],
    ['contato.php', 'monthly', '0.5'],
    ['ajuda.php', 'monthly', '0.5'],
    ['blog.php', 'weekly', '0.6'],
    ['planos.php', 'monthly', '0.6'],
    ['privacidade.php', 'yearly', '0.2'],
    ['termos.php', 'yearly', '0.2'],

    // Vitrines/listagens de conteúdo (não os formulários de busca com
    // parâmetros, que geram conteúdo duplicado e não valem a pena indexar).
    ['pets_adocao.php', 'daily', '0.9'],
    ['pets_perdidos.php', 'daily', '0.7'],
    ['pets_encontrados.php', 'daily', '0.7'],
    ['empresas.php', 'daily', '0.8'],
    ['produtos.php', 'daily', '0.8'],
    ['ofertas.php', 'daily', '0.7'],
    ['vitrine.php', 'daily', '0.7'],
    ['prestadores.php', 'daily', '0.8'],

    // Landing pages de categoria de serviço.
    ['adestramento.php', 'monthly', '0.6'],
    ['banho_e_tosa.php', 'monthly', '0.6'],
    ['clinica_veterinaria.php', 'monthly', '0.6'],
    ['hotelzinho.php', 'monthly', '0.6'],

    // Hub de parceiros/ONGs.
    ['parceiros.php', 'weekly', '0.7'],
];

foreach ($paginasEstaticas as [$arquivo, $changefreq, $priority]) {
    $caminho = $arquivo === '__home__' ? Url::raiz('index.html') : Url::pagina($arquivo);
    adicionarUrl($urls, $caminho, $changefreq, $priority);
}

// =============================================================
// 2) Conteúdo dinâmico do banco
// =============================================================

// --- Pets (adoção, perdidos, encontrados) -----------------------------------
try {
    $petModel = new Pet();
    foreach (['Para Adoção', 'Perdido', 'Encontrado'] as $status) {
        $pets = array_slice($petModel->listarPorStatus($status), 0, LIMITE_POR_TIPO);
        foreach ($pets as $pet) {
            adicionarUrl(
                $urls,
                Url::pagina('pet.php') . '?id=' . (int) $pet['id'],
                'weekly',
                '0.6',
                $pet['criado_em'] ?? null
            );
        }
    }
} catch (Throwable $e) {
    error_log('sitemap.xml: falha ao listar pets — ' . $e->getMessage());
}

// --- Empresas ativas ---------------------------------------------------------
try {
    $empresaModel = new Empresa();
    $empresas = array_slice($empresaModel->listarAtivas(), 0, LIMITE_POR_TIPO);
    foreach ($empresas as $empresa) {
        adicionarUrl($urls, Url::pagina('empresa.php') . '?id=' . (int) $empresa['id'], 'weekly', '0.6');
    }
} catch (Throwable $e) {
    error_log('sitemap.xml: falha ao listar empresas — ' . $e->getMessage());
}

// --- Produtos ativos -----------------------------------------------------
try {
    $produtoModel = new Produto();
    $produtos = array_slice($produtoModel->listarAtivos(), 0, LIMITE_POR_TIPO);
    foreach ($produtos as $produto) {
        adicionarUrl($urls, Url::pagina('produto.php') . '?id=' . (int) $produto['id'], 'weekly', '0.5');
    }
} catch (Throwable $e) {
    error_log('sitemap.xml: falha ao listar produtos — ' . $e->getMessage());
}

// --- Prestadores de serviço ativos -------------------------------------------
try {
    $prestadorModel = new Prestador();
    $prestadores = array_slice($prestadorModel->listarAtivos(), 0, LIMITE_POR_TIPO);
    foreach ($prestadores as $prestador) {
        adicionarUrl(
            $urls,
            Url::pagina('prestador.php') . '?id=' . (int) $prestador['id'],
            'weekly',
            '0.6',
            $prestador['atualizado_em'] ?? null
        );
    }
} catch (Throwable $e) {
    error_log('sitemap.xml: falha ao listar prestadores — ' . $e->getMessage());
}

// --- Parceiros/ONGs aprovados -------------------------------------------
try {
    $parceiroModel = new Parceiro();
    $parceiros = array_slice($parceiroModel->listarAprovados(), 0, LIMITE_POR_TIPO);
    foreach ($parceiros as $parceiro) {
        adicionarUrl(
            $urls,
            Url::pagina('parceiro.php') . '?id=' . (int) $parceiro['id'],
            'weekly',
            '0.6',
            $parceiro['atualizado_em'] ?? null
        );
    }
} catch (Throwable $e) {
    error_log('sitemap.xml: falha ao listar parceiros — ' . $e->getMessage());
}

// --- Campanhas/eventos/doações ativos e aprovados ----------------------------
try {
    $campanhaModel = new Campanha();
    $campanhas = array_slice($campanhaModel->listarAtivas(), 0, LIMITE_POR_TIPO);
    foreach ($campanhas as $campanha) {
        adicionarUrl(
            $urls,
            Url::pagina('campanha.php') . '?id=' . (int) $campanha['id'],
            'weekly',
            '0.5',
            $campanha['criado_em'] ?? null
        );
    }
} catch (Throwable $e) {
    // Cobre também instalações onde a migration_026 (status_moderacao)
    // ainda não foi aplicada — o sitemap não pode quebrar por isso.
    error_log('sitemap.xml: falha ao listar campanhas — ' . $e->getMessage());
}

// =============================================================
// 3) Monta o XML
// =============================================================
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $entrada) {
    $xml .= "  <url>\n";
    $xml .= '    <loc>' . htmlspecialchars($entrada['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
    if (isset($entrada['lastmod'])) {
        $xml .= '    <lastmod>' . $entrada['lastmod'] . "</lastmod>\n";
    }
    $xml .= '    <changefreq>' . $entrada['changefreq'] . "</changefreq>\n";
    $xml .= '    <priority>' . $entrada['priority'] . "</priority>\n";
    $xml .= "  </url>\n";
}

$xml .= '</urlset>' . "\n";

// --- Atualiza o cache (best-effort: se a pasta não existir ou não tiver
// permissão de escrita, ainda assim serve o XML gerado agora) ---------------
$pastaCache = dirname(CACHE_ARQUIVO);
if (!is_dir($pastaCache)) {
    @mkdir($pastaCache, 0755, true);
}
@file_put_contents(CACHE_ARQUIVO, $xml);

if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/xml; charset=UTF-8');
    echo $xml;
} else {
    fwrite(STDOUT, 'sitemap.xml gerado com ' . count($urls) . " URLs.\n");
}
