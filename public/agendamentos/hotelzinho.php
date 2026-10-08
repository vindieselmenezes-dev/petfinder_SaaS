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

function renderizarCardEmpresa(array $empresa, int $categoriaId): string
{
    $capa = Foto::url($empresa["capa"] ?? null, 'empresas');

    $descricao = htmlspecialchars(mb_strimwidth($empresa['descricao'] ?? '', 0, 90, '...'));
    $nome = htmlspecialchars($empresa['nome_fantasia']);
    $cidade = htmlspecialchars(($empresa['cidade'] ?: 'Cidade não informada') . ($empresa['estado'] ? ' / ' . $empresa['estado'] : ''));
    $id = (int) $empresa['id'];
    $perfilUrl = htmlspecialchars(Url::pagina('empresa.php') . '?id=' . $id, ENT_QUOTES, 'UTF-8');
    $solicitarUrl = htmlspecialchars(Url::pagina('pedir_servico.php') . '?categoria_id=' . $categoriaId, ENT_QUOTES, 'UTF-8');
    $destaque = !empty($empresa['plano_destaque']);
    $classeDestaque = $destaque ? 'border-warning border-2' : '';
    $seloDestaque = $destaque ? '<span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">⭐ Destaque</span>' : '';

    return <<<HTML
        <div class="col-lg-4 col-md-6">
            <div class="card empresa-card h-100 shadow-sm {$classeDestaque}">
                <a href="{$perfilUrl}" class="position-relative">
                    <img src="{$capa}" class="card-img-top" style="height:180px;object-fit:cover;" alt="{$nome}">
                    {$seloDestaque}
                </a>
                <div class="card-body">
                    <h5><a href="{$perfilUrl}" class="text-decoration-none text-dark">{$nome}</a></h5>
                    <p class="text-muted small">{$descricao}</p>
                    <div class="small"><i class="bi bi-geo-alt-fill"></i> {$cidade}</div>
                </div>
                <div class="card-footer bg-white d-flex gap-2">
                    <a href="{$perfilUrl}" class="btn btn-outline-primary w-50 btn-sm">Ver Perfil</a>
                    <a href="{$solicitarUrl}" class="btn btn-success w-50 btn-sm">🏨 Pedir orçamento</a>
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
    <title>Hotelzinho para Cães e Pets - EcoSistemPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" integrity="sha384-CK2SzKma4jA5H/MXDUU7i1TqZlCFaD4T01vtyDFvPlD97JQyS+IsSh1nI2EFbpyk" crossorigin="anonymous">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .banner-hotel-frame {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f1f5f6;
        }

        .banner-hotel {
            display: block;
            width: auto;
            max-width: 100%;
            max-height: min(65vh, 620px);
            object-fit: contain;
        }
    </style>
</head>

<body style="padding-top:38px;">
    <div
        style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;">
        <button type="button"
            onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}"
            style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;"
            aria-label="← Voltar para a página anterior">← Voltar</button>
    </div>

    <header class="border-bottom py-3 mb-4">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
            <a href="../../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../../assets/img/logo.png" alt="EcoSistemPet" height="40" class="me-2">
                <div class="fw-bold text-dark">EcoSistemPet</div>
            </a>
            <a href="<?= Url::pagina('cadastrar_empresa.php') ?>" class="btn btn-success">
                <i class="bi bi-megaphone"></i> Anunciar meu hotelzinho
            </a>
        </div>
    </header>

    <main>

        <section class="banner-hotel-frame">
            <img src="../../assets/img/servicos/hotelzinho-suite.jpg" class="banner-hotel"
                alt="Suíte confortável para hospedagem de pets">
        </section>

        <div class="container my-5">
            <h1 class="fw-bold mb-2">🏨 Hotelzinho para Cães e Pets</h1>
            <p class="text-muted mb-4">Hospedagem com carinho pra quando você viajar: suítes, área de recreação e
                cuidado 24h.</p>

            <div class="row g-4 mb-5 text-center">
                <div class="col-md-4">
                    <img src="../../assets/img/servicos/hotelzinho-suite.jpg" alt="Suíte do hotelzinho" class="rounded-3 mb-2"
                        style="height:160px;width:100%;object-fit:cover;">
                    <h5>Suítes confortáveis</h5>
                    <p class="text-muted small">Espaços individuais e higienizados, pensados pro conforto do seu pet.
                    </p>
                </div>
                <div class="col-md-4">
                    <img src="../../assets/img/servicos/hotelzinho-recreacao.jpg" alt="Área de recreação do hotelzinho" class="rounded-3 mb-2"
                        style="height:160px;width:100%;object-fit:cover;">
                    <h5>Área de recreação</h5>
                    <p class="text-muted small">Espaço livre pra brincar e gastar energia com outros pets.</p>
                </div>
                <div class="col-md-4">
                    <img src="../../assets/img/servicos/hotelzinho-suites.webp" alt="Hotelzinho com acompanhamento 24 horas" class="rounded-3 mb-2"
                        style="height:160px;width:100%;object-fit:cover;">
                    <h5>Acompanhamento 24h</h5>
                    <p class="text-muted small">Equipe disponível o dia inteiro pra cuidar de alimentação e bem-estar.
                    </p>
                </div>
            </div>

            <h2 class="fw-bold mb-3">🐾 Hotéis para Pets cadastrados</h2>

            <div class="row g-4 mb-5">
                <?php if (empty($hoteis)): ?>
                    <div class="col-12 text-muted">Ainda não há hotéis cadastrados na sua região. <a
                            href="<?= Url::pagina('cadastrar_empresa.php') ?>">Seja o primeiro a anunciar!</a></div>
                <?php else: ?>
                    <?php foreach ($hoteis as $empresa):
                        echo renderizarCardEmpresa($empresa, 5);
                    endforeach; ?>
                <?php endif; ?>
            </div>

            <h2 class="fw-bold mb-3">🐕 Creches / Day Care</h2>

            <div class="row g-4">
                <?php if (empty($creches)): ?>
                    <div class="col-12 text-muted">Ainda não há creches cadastradas. <a
                            href="<?= Url::pagina('cadastrar_empresa.php') ?>">Seja a primeira a anunciar!</a></div>
                <?php else: ?>
                    <?php foreach ($creches as $empresa):
                        echo renderizarCardEmpresa($empresa, 6);
                    endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> EcoSistemPet
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>

</body>

</html>
