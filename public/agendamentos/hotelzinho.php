<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


/**
 * Página temática "Hotelzinho para Pets" -- conteúdo inspirado na página
 * hotelzinho.html do projetointegrador, mas listando de verdade as
 * empresas da categoria "Hotel para Pets" já cadastradas no banco do SaaS.
 */

require_once "../../app/Controllers/EmpresaController.php";

$controller = new EmpresaController();

// categoria 5 = "Hotel para Pets", categoria 6 = "Creche Pet" (day care)
$hoteis = $controller->listarAtivas(5);
$creches = $controller->listarAtivas(6);

function renderizarCardEmpresa(array $empresa): string
{
    $capa = Foto::url($empresa["capa"] ?? null, 'empresas');

    $descricao = htmlspecialchars(mb_strimwidth($empresa['descricao'] ?? '', 0, 90, '...'));
    $nome = htmlspecialchars($empresa['nome_fantasia']);
    $cidade = htmlspecialchars(($empresa['cidade'] ?: 'Cidade não informada') . ($empresa['estado'] ? ' / ' . $empresa['estado'] : ''));
    $id = (int) $empresa['id'];
    $destaque = !empty($empresa['plano_destaque']);
    $classeDestaque = $destaque ? 'border-warning border-2' : '';
    $seloDestaque = $destaque ? '<span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">⭐ Destaque</span>' : '';

    return <<<HTML
        <div class="col-lg-4 col-md-6">
            <div class="card empresa-card h-100 shadow-sm {$classeDestaque}">
                <a href="<?= Url::pagina('empresa.php') ?>?id={$id}" class="position-relative">
                    <img src="{$capa}" class="card-img-top" style="height:180px;object-fit:cover;" alt="{$nome}">
                    {$seloDestaque}
                </a>
                <div class="card-body">
                    <h5><a href="<?= Url::pagina('empresa.php') ?>?id={$id}" class="text-decoration-none text-dark">{$nome}</a></h5>
                    <p class="text-muted small">{$descricao}</p>
                    <div class="small"><i class="bi bi-geo-alt-fill"></i> {$cidade}</div>
                </div>
                <div class="card-footer bg-white d-flex gap-2">
                    <a href="<?= Url::pagina('empresa.php') ?>?id={$id}" class="btn btn-outline-primary w-50 btn-sm">Ver Perfil</a>
                    <a href="solicitar_servico_empresa.php?empresa_id={$id}" class="btn btn-success w-50 btn-sm">🏨 Solicitar</a>
                </div>
            </div>
        </div>
        HTML;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotelzinho para Cães e Pets - PetFinder Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="Voltar para a página anterior">← Voltar</button></div>

    <header class="border-bottom py-3 mb-4">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
            <a href="../../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../../assets/img/logo.png" alt="PetFinder Brasil" height="40" class="me-2">
                <div class="fw-bold text-dark">PetFinder Brasil</div>
            </a>
            <a href="<?= Url::pagina('cadastrar_empresa.php') ?>" class="btn btn-success">
                <i class="bi bi-megaphone"></i> Anunciar meu hotelzinho
            </a>
        </div>
    </header>

    <main>

        <section class="position-relative">
            <img src="../../assets/img/servicos/hotelzinho-suite.jpg" class="w-100" style="max-height:340px; object-fit:cover;" alt="Hotelzinho para Pets">
            <div class="position-absolute top-50 start-0 translate-middle-y bg-white bg-opacity-75 p-4 rounded-end" style="max-width:500px;">
                <h1 class="fw-bold">🏨 Hotelzinho para Cães e Pets</h1>
                <p class="mb-0">Hospedagem com carinho pra quando você viajar: suítes, área de recreação e cuidado 24h.</p>
            </div>
        </section>

        <div class="container my-5">

            <div class="row g-4 mb-5 text-center">
                <div class="col-md-4">
                    <img src="../../assets/img/servicos/hotelzinho-suite.jpg" class="rounded-3 mb-2" style="height:160px;width:100%;object-fit:cover;">
                    <h5>Suítes confortáveis</h5>
                    <p class="text-muted small">Espaços individuais e higienizados, pensados pro conforto do seu pet.</p>
                </div>
                <div class="col-md-4">
                    <img src="../../assets/img/servicos/hotelzinho-recreacao.jpg" class="rounded-3 mb-2" style="height:160px;width:100%;object-fit:cover;">
                    <h5>Área de recreação</h5>
                    <p class="text-muted small">Espaço livre pra brincar e gastar energia com outros pets.</p>
                </div>
                <div class="col-md-4">
                    <img src="../../assets/img/servicos/hotelzinho-suites.webp" class="rounded-3 mb-2" style="height:160px;width:100%;object-fit:cover;">
                    <h5>Acompanhamento 24h</h5>
                    <p class="text-muted small">Equipe disponível o dia inteiro pra cuidar de alimentação e bem-estar.</p>
                </div>
            </div>

            <h2 class="fw-bold mb-3">🐾 Hotéis para Pets cadastrados</h2>

            <div class="row g-4 mb-5">
                <?php if (empty($hoteis)): ?>
                    <div class="col-12 text-muted">Ainda não há hotéis cadastrados na sua região. <a href="<?= Url::pagina('cadastrar_empresa.php') ?>">Seja o primeiro a anunciar!</a></div>
                <?php else: ?>
                    <?php foreach ($hoteis as $empresa): echo renderizarCardEmpresa($empresa); endforeach; ?>
                <?php endif; ?>
            </div>

            <h2 class="fw-bold mb-3">🐕 Creches / Day Care</h2>

            <div class="row g-4">
                <?php if (empty($creches)): ?>
                    <div class="col-12 text-muted">Ainda não há creches cadastradas. <a href="<?= Url::pagina('cadastrar_empresa.php') ?>">Seja a primeira a anunciar!</a></div>
                <?php else: ?>
                    <?php foreach ($creches as $empresa): echo renderizarCardEmpresa($empresa); endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
