<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Área de Parceiros
 * ==========================================================
 * Substitui a antiga faixa estática "Empresas Parceiras" da home.
 * Aqui ficam as ONGs, protetores e empresas que ajudam a divulgar e
 * crescer o PetFinder — com destaque para o que realmente precisa de
 * gente: campanhas, eventos e doações.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

$parceiroModel = new Parceiro();
$campanhaModel = new Campanha();

// --- Filtros da vitrine de campanhas -------------------------------------
$tipoCampanha = $_GET['tipo'] ?? '';
if (!isset(Campanha::TIPOS[$tipoCampanha])) {
    $tipoCampanha = '';
}

// --- Filtros da lista de parceiros ---------------------------------------
$tipoParceiro = $_GET['parceiro'] ?? '';
if (!isset(Parceiro::TIPOS[$tipoParceiro])) {
    $tipoParceiro = '';
}

$cidade = trim($_GET['cidade'] ?? '');
$busca  = trim($_GET['q'] ?? '');

$resumo = $parceiroModel->resumo();

// --- Paginação das campanhas ("pagina") -----------------------------------
$porPaginaCampanhas = 6;
$paginaCampanhas = max(1, (int) ($_GET['pagina'] ?? 1));
$totalCampanhas = $campanhaModel->contarAtivas($tipoCampanha);
$totalPaginasCampanhas = (int) ceil(max(1, $totalCampanhas) / $porPaginaCampanhas);
$paginaCampanhas = min($paginaCampanhas, $totalPaginasCampanhas);

$campanhas = $campanhaModel->listarAtivas(
    $tipoCampanha,
    $porPaginaCampanhas,
    false,
    Paginador::offset($paginaCampanhas, $porPaginaCampanhas)
);

// --- Paginação dos parceiros ("pagina_parceiros") --------------------------
// Parâmetro com nome diferente do de cima: são duas listas paginadas
// independentes na mesma tela, e cada uma precisa da sua própria página.
$porPaginaParceiros = 9;
$paginaParceiros = max(1, (int) ($_GET['pagina_parceiros'] ?? 1));
$totalParceiros = $parceiroModel->contarAprovados($tipoParceiro, $cidade, $busca);
$totalPaginasParceiros = (int) ceil(max(1, $totalParceiros) / $porPaginaParceiros);
$paginaParceiros = min($paginaParceiros, $totalPaginasParceiros);

$parceiros = $parceiroModel->listarAprovados(
    $tipoParceiro,
    $cidade,
    $busca,
    $porPaginaParceiros,
    Paginador::offset($paginaParceiros, $porPaginaParceiros)
);

$tituloPagina = 'Parceiros, campanhas e doações';

/**
 * Formata o período de um evento/campanha em uma linha só.
 */
function periodoCampanha(?string $inicio, ?string $fim): string
{
    if (!$inicio && !$fim) {
        return 'Sem prazo definido';
    }

    if ($inicio && $fim) {
        return date('d/m/Y', strtotime($inicio)) . ' até ' . date('d/m/Y', strtotime($fim));
    }

    return $inicio
        ? 'A partir de ' . date('d/m/Y H:i', strtotime($inicio))
        : 'Até ' . date('d/m/Y', strtotime($fim));
}

/**
 * Link de cada aba "Todas / Campanha / Evento / Doação": troca só o tipo
 * de campanha e zera a paginação das campanhas, preservando os demais
 * filtros (cidade, busca, tipo de parceiro) e a página da lista de baixo.
 */
function urlFiltroTipoCampanha(string $tipo): string
{
    $query = $_GET;
    $query['tipo'] = $tipo;
    unset($query['pagina']);

    return '?' . http_build_query($query) . '#campanhas';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($tituloPagina) ?> - PetFinder Brasil</title>

    <meta name="description"
        content="ONGs, protetores e empresas parceiras do PetFinder Brasil. Veja campanhas, eventos e pedidos de doação e descubra como ajudar.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= Url::asset('css/style.css') ?>">

</head>

<body style="padding-top:38px;">

    <div
        style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;">
        <button type="button"
            onclick="if(window.history.length>1){history.back();}else{window.location.href='<?= Url::raiz('index.html') ?>';}"
            style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;"
            aria-label="Voltar para a página anterior">← Voltar</button>
    </div>

    <header class="border-bottom py-3">

        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <a href="<?= Url::raiz('index.html') ?>" class="d-flex align-items-center text-decoration-none">
                <img src="<?= Url::asset('img/logo.png') ?>" alt="PetFinder Brasil" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>

            <a href="<?= Url::pagina('cadastrar_parceiro.php') ?>" class="btn btn-success">
                <i class="bi bi-heart-fill"></i>
                Quero ser parceiro
            </a>

        </div>

    </header>

    <!-- ========================================================= -->
    <!-- CAPA -->
    <!-- ========================================================= -->

    <section class="py-5 text-white" style="background:linear-gradient(135deg,#015C1E,#1B365D);">

        <div class="container">

            <div class="row align-items-center g-4">

                <div class="col-lg-7">

                    <h1 class="fw-bold mb-3">🤝 Parceiros que fazem o PetFinder crescer</h1>

                    <p class="lead mb-4">
                        ONGs, protetores independentes e empresas que abraçam a causa animal e ajudam
                        a levar a plataforma para mais gente. Apoie uma campanha, participe de um
                        evento ou doe direto para quem cuida.
                    </p>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="#campanhas" class="btn btn-warning btn-lg fw-bold">
                            <i class="bi bi-megaphone-fill"></i> Ver campanhas
                        </a>
                        <a href="<?= Url::pagina('cadastrar_parceiro.php') ?>" class="btn btn-outline-light btn-lg">
                            Cadastrar minha ONG / empresa
                        </a>
                    </div>

                </div>

                <div class="col-lg-5">

                    <div class="row g-3 text-center">

                        <div class="col-6">
                            <div class="bg-white text-dark rounded-3 p-3 h-100">
                                <div class="fs-3 fw-bold"><?= $resumo['parceiros'] ?></div>
                                <small class="text-muted">parceiros ativos</small>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="bg-white text-dark rounded-3 p-3 h-100">
                                <div class="fs-3 fw-bold"><?= $resumo['ongs'] ?></div>
                                <small class="text-muted">ONGs e protetores</small>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="bg-white text-dark rounded-3 p-3 h-100">
                                <div class="fs-3 fw-bold"><?= $resumo['campanhas'] ?></div>
                                <small class="text-muted">ações em andamento</small>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="bg-white text-dark rounded-3 p-3 h-100">
                                <div class="fs-3 fw-bold">
                                    R$ <?= number_format($resumo['arrecadado'], 0, ',', '.') ?>
                                </div>
                                <small class="text-muted">já arrecadados</small>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <main class="container my-5">

        <!-- ========================================================= -->
        <!-- CAMPANHAS / EVENTOS / DOAÇÕES -->
        <!-- ========================================================= -->

        <section id="campanhas" class="mb-5">

            <div class="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-4">

                <div>
                    <h2 class="fw-bold mb-1">📣 Campanhas, eventos e doações</h2>
                    <p class="text-muted mb-0">
                        Publicações ativas dos nossos parceiros. Toda ajuda conta — inclusive compartilhar.
                    </p>
                </div>

                <div class="btn-group" role="group" aria-label="Filtrar publicações por tipo">
                    <a href="<?= htmlspecialchars(urlFiltroTipoCampanha('')) ?>"
                        class="btn btn-<?= $tipoCampanha === '' ? 'primary' : 'outline-primary' ?>">
                        Todas
                    </a>
                    <?php foreach (Campanha::TIPOS as $chave => $config): ?>
                        <a href="<?= htmlspecialchars(urlFiltroTipoCampanha($chave)) ?>"
                            class="btn btn-<?= $tipoCampanha === $chave ? 'primary' : 'outline-primary' ?>">
                            <?= $config['icone'] ?> <?= htmlspecialchars($config['rotulo']) ?>s
                        </a>
                    <?php endforeach; ?>
                </div>

            </div>

            <div class="row g-4">

                <?php if (count($campanhas) === 0): ?>

                    <div class="col-12">
                        <div class="text-center text-muted border rounded-3 py-5">
                            <div class="fs-1">🐾</div>
                            <p class="mb-1">Nenhuma ação publicada por aqui ainda.</p>
                            <p class="mb-0">
                                É de uma ONG ou empresa?
                                <a href="<?= Url::pagina('cadastrar_parceiro.php') ?>">Cadastre-se e publique a primeira.</a>
                            </p>
                        </div>
                    </div>

                <?php else: ?>

                    <?php foreach ($campanhas as $campanha): ?>

                        <?php
                        $imagem = Foto::url(
                            $campanha['imagem'] ?? null,
                            'campanhas',
                            'img/parceiros/parceiro01.jpg'
                        );
                        $percentual = Campanha::percentualMeta(
                            $campanha['meta_valor'] !== null ? (float) $campanha['meta_valor'] : null,
                            (float) $campanha['valor_arrecadado']
                        );
                        $urlCampanhaCard = Url::absoluta(
                            Url::pagina('campanha.php') . '?id=' . (int) $campanha['id']
                        );
                        ?>

                        <div class="col-lg-4 col-md-6">

                            <div class="card h-100 shadow-sm <?= !empty($campanha['destaque']) ? 'border-warning border-2' : '' ?>">

                                <a href="<?= Url::pagina('campanha.php') ?>?id=<?= (int) $campanha['id'] ?>"
                                    class="position-relative d-block">

                                    <img src="<?= htmlspecialchars($imagem) ?>" class="card-img-top"
                                        style="height:190px; object-fit:cover;"
                                        alt="<?= htmlspecialchars($campanha['titulo']) ?>">

                                    <span class="badge bg-dark position-absolute top-0 start-0 m-2">
                                        <?= Campanha::iconeTipo($campanha['tipo']) ?>
                                        <?= htmlspecialchars(Campanha::rotuloTipo($campanha['tipo'])) ?>
                                    </span>

                                    <?php if (!empty($campanha['destaque'])): ?>
                                        <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
                                            ⭐ Destaque
                                        </span>
                                    <?php endif; ?>

                                </a>

                                <div class="card-body d-flex flex-column">

                                    <small class="text-muted d-block mb-2">
                                        <i class="bi bi-building"></i>
                                        <?= htmlspecialchars($campanha['parceiro_nome']) ?>
                                        <?php if (!empty($campanha['parceiro_cidade'])): ?>
                                            · <?= htmlspecialchars($campanha['parceiro_cidade']) ?><?= !empty($campanha['parceiro_estado']) ? '/' . htmlspecialchars($campanha['parceiro_estado']) : '' ?>
                                        <?php endif; ?>
                                    </small>

                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <h5 class="card-title fw-bold mb-0">
                                            <?= htmlspecialchars($campanha['titulo']) ?>
                                        </h5>
                                        <?= Compartilhamento::renderizarDropdown(
                                            $campanha['titulo'],
                                            $urlCampanhaCard,
                                            'compartilhar-campanha-' . (int) $campanha['id']
                                        ) ?>
                                    </div>

                                    <?php if (!empty($campanha['resumo'])): ?>
                                        <p class="card-text text-muted small">
                                            <?= htmlspecialchars($campanha['resumo']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($campanha['tipo'] === 'evento' && !empty($campanha['local_evento'])): ?>
                                        <p class="small mb-1">
                                            <i class="bi bi-geo-alt"></i>
                                            <?= htmlspecialchars($campanha['local_evento']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <p class="small text-muted mb-3">
                                        <i class="bi bi-calendar-event"></i>
                                        <?= htmlspecialchars(periodoCampanha($campanha['data_inicio'], $campanha['data_fim'])) ?>
                                    </p>

                                    <?php if ($percentual !== null): ?>
                                        <div class="mb-2">
                                            <div class="progress" style="height:8px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: <?= $percentual ?>%;"
                                                    aria-valuenow="<?= $percentual ?>" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted">
                                                R$ <?= number_format((float) $campanha['valor_arrecadado'], 2, ',', '.') ?>
                                                de R$ <?= number_format((float) $campanha['meta_valor'], 2, ',', '.') ?>
                                                (<?= $percentual ?>%)
                                            </small>
                                        </div>
                                    <?php elseif (!empty($campanha['itens_desejados'])): ?>
                                        <p class="small mb-2">
                                            <i class="bi bi-box-seam"></i>
                                            Precisa de: <?= htmlspecialchars($campanha['itens_desejados']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <a href="<?= Url::pagina('campanha.php') ?>?id=<?= (int) $campanha['id'] ?>"
                                        class="btn btn-success w-100 mt-auto">
                                        <i class="bi bi-heart-fill"></i> Quero ajudar
                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <?php if ($totalCampanhas > 0): ?>
                <div class="mt-4">
                    <?= Paginador::renderizar($paginaCampanhas, $totalPaginasCampanhas, 2, 'pagina') ?>
                    <p class="text-center text-muted small mt-2 mb-0">
                        Página <?= $paginaCampanhas ?> de <?= $totalPaginasCampanhas ?>
                        · <?= $totalCampanhas ?> ação<?= $totalCampanhas === 1 ? '' : 'ões' ?> no total
                    </p>
                </div>
            <?php endif; ?>

        </section>

        <!-- ========================================================= -->
        <!-- PARCEIROS -->
        <!-- ========================================================= -->

        <section id="lista-parceiros">

            <h2 class="fw-bold mb-1">🏳️ Quem caminha com a gente</h2>
            <p class="text-muted">
                ONGs, protetores e empresas que divulgam o PetFinder e apoiam a causa animal.
            </p>

            <form method="GET" class="row g-2 mb-4 align-items-end bg-light p-3 rounded-3">

                <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoCampanha) ?>">

                <div class="col-md-4">
                    <label class="form-label small">Tipo de parceiro</label>
                    <select name="parceiro" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (Parceiro::TIPOS as $chave => $rotulo): ?>
                            <option value="<?= $chave ?>" <?= $tipoParceiro === $chave ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rotulo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">Cidade</label>
                    <input type="text" name="cidade" class="form-control" placeholder="Ex: Belo Horizonte"
                        value="<?= htmlspecialchars($cidade) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label small">Buscar</label>
                    <input type="text" name="q" class="form-control" placeholder="Nome da ONG ou empresa"
                        value="<?= htmlspecialchars($busca) ?>">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>

            </form>

            <div class="row g-4">

                <?php if (count($parceiros) === 0): ?>

                    <div class="col-12 text-center text-muted py-5">
                        Nenhum parceiro encontrado com esses filtros.
                    </div>

                <?php else: ?>

                    <?php foreach ($parceiros as $parceiro): ?>

                        <?php $logo = Foto::url($parceiro['logo'] ?? null, 'parceiros', 'img/logo.png'); ?>

                        <div class="col-lg-4 col-md-6">

                            <div class="card h-100 shadow-sm <?= !empty($parceiro['destaque']) ? 'border-warning border-2' : '' ?>">

                                <div class="card-body d-flex flex-column">

                                    <div class="d-flex align-items-center gap-3 mb-3">

                                        <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($parceiro['nome']) ?>"
                                            class="rounded-circle border"
                                            style="width:64px; height:64px; object-fit:cover;">

                                        <div>
                                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($parceiro['nome']) ?></h5>
                                            <span class="badge bg-primary">
                                                <?= htmlspecialchars(Parceiro::rotuloTipo($parceiro['tipo'])) ?>
                                            </span>
                                            <?php if (!empty($parceiro['destaque'])): ?>
                                                <span class="badge bg-warning text-dark">⭐ Destaque</span>
                                            <?php endif; ?>
                                        </div>

                                    </div>

                                    <?php if (!empty($parceiro['cidade'])): ?>
                                        <p class="small text-muted mb-2">
                                            <i class="bi bi-geo-alt"></i>
                                            <?= htmlspecialchars($parceiro['cidade']) ?><?= !empty($parceiro['estado']) ? '/' . htmlspecialchars($parceiro['estado']) : '' ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($parceiro['como_ajuda'])): ?>
                                        <p class="small mb-2">
                                            <i class="bi bi-hand-thumbs-up"></i>
                                            <?= htmlspecialchars($parceiro['como_ajuda']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($parceiro['descricao'])): ?>
                                        <p class="card-text text-muted small">
                                            <?= nl2br(htmlspecialchars(mb_strimwidth($parceiro['descricao'], 0, 140, '...'))) ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="mt-auto pt-2">

                                        <span class="badge bg-light text-dark border mb-2">
                                            <?= (int) $parceiro['total_campanhas'] ?> ação<?= (int) $parceiro['total_campanhas'] === 1 ? '' : 'ões' ?> em andamento
                                        </span>

                                        <a href="<?= Url::pagina('parceiro.php') ?>?id=<?= (int) $parceiro['id'] ?>"
                                            class="btn btn-outline-primary w-100">
                                            Ver perfil e campanhas
                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <?php if ($totalParceiros > 0): ?>
                <div class="mt-4">
                    <?= Paginador::renderizar($paginaParceiros, $totalPaginasParceiros, 2, 'pagina_parceiros') ?>
                    <p class="text-center text-muted small mt-2 mb-0">
                        Página <?= $paginaParceiros ?> de <?= $totalPaginasParceiros ?>
                        · <?= $totalParceiros ?> parceiro<?= $totalParceiros === 1 ? '' : 's' ?> encontrado<?= $totalParceiros === 1 ? '' : 's' ?>
                    </p>
                </div>
            <?php endif; ?>

        </section>

        <!-- ========================================================= -->
        <!-- CHAMADA PARA NOVOS PARCEIROS -->
        <!-- ========================================================= -->

        <section class="mt-5 p-4 p-lg-5 rounded-4 text-white" style="background:#1B365D;">

            <div class="row align-items-center g-4">

                <div class="col-lg-8">
                    <h3 class="fw-bold">Sua ONG ou empresa também pode estar aqui</h3>
                    <p class="mb-0">
                        Parceiros ganham perfil público, espaço para divulgar campanhas, eventos e
                        pedidos de doação, e aparecem na home do PetFinder. Em troca, ajudam a levar
                        a plataforma para mais tutores — e mais pets encontram um lar.
                    </p>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <a href="<?= Url::pagina('cadastrar_parceiro.php') ?>" class="btn btn-warning btn-lg fw-bold">
                        Quero ser parceiro
                    </a>
                </div>

            </div>

        </section>

    </main>

    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            © <?= date('Y') ?> PetFinder Brasil ·
            <a href="<?= Url::raiz('index.html') ?>" class="text-light">Voltar para a home</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= Url::asset('js/compartilhamento.js') ?>"></script>

</body>

</html>
