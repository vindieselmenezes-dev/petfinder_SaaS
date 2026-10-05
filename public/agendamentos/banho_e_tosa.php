<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


/**
 * Página temática "Banho e Tosa" -- conteúdo inspirado na página
 * servicos.html do projetointegrador (que tinha uma calculadora de preço
 * por porte do animal), listando de verdade as empresas da categoria
 * "Banho e Tosa" já cadastradas no banco do SaaS.
 */

require_once "../../app/Controllers/EmpresaController.php";

$controller = new EmpresaController();

// categoria 4 = "Banho e Tosa"
$empresasBanhoTosa = $controller->listarAtivas(4);

function renderizarCardEmpresaBanhoTosa(array $empresa): string
{
    $capa = Foto::url($empresa["capa"] ?? null, 'empresas');

    $descricao = htmlspecialchars(mb_strimwidth($empresa['descricao'] ?? '', 0, 90, '...'));
    $nome = htmlspecialchars($empresa['nome_fantasia']);
    $cidade = htmlspecialchars(($empresa['cidade'] ?: 'Cidade não informada') . ($empresa['estado'] ? ' / ' . $empresa['estado'] : ''));
    $id = (int) $empresa['id'];
    $perfilUrl = htmlspecialchars(Url::pagina('empresa.php') . '?id=' . $id, ENT_QUOTES, 'UTF-8');
    $solicitarUrl = htmlspecialchars(Url::pagina('pedir_servico.php') . '?categoria_id=4', ENT_QUOTES, 'UTF-8');
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
                    <a href="{$solicitarUrl}" class="btn btn-success w-50 btn-sm">✂️ Pedir orçamento</a>
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
    <title>Banho e Tosa - EcoSistemPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .banner-banho-tosa {
            display: block;
            width: 100%;
            max-width: 900px;
            max-height: min(65vh, 500px);
            height: auto;
            object-fit: contain;
            margin: 0 auto;
        }
    </style>
</head>

<body style="padding-top:38px;">
    <div
        style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;">
        <button type="button"
            onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}"
            style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;"
            aria-label="Voltar para a página anterior">← Voltar</button>
    </div>

    <header class="border-bottom py-3 mb-4">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
            <a href="../../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../../assets/img/logo.png" alt="EcoSistemPet" height="40" class="me-2">
                <div class="fw-bold text-dark">EcoSistemPet</div>
            </a>
            <a href="<?= Url::pagina('cadastrar_empresa.php') ?>" class="btn btn-success">
                <i class="bi bi-megaphone"></i> Anunciar meu petshop de banho e tosa
            </a>
        </div>
    </header>

    <main>

        <section>
            <img src="../../assets/img/servicos/banho-tosa.jpg" class="banner-banho-tosa" alt="Banho e tosa de cães">
        </section>

        <div class="container my-5">
            <h1 class="fw-bold mb-2">✂️ Banho e Tosa</h1>
            <p class="text-muted mb-4">Higiene e estética pro seu pet, com profissionais que cuidam de cada detalhe.</p>

            <div class="row g-4 align-items-center mb-5">

                <div class="col-lg-6">
                    <h2 class="fw-bold mb-3">💬 Orçamento confirmado pelo petshop</h2>
                    <p class="text-muted">Informe o serviço e a data desejada. Os petshops recebem seu pedido em ordem
                        de prioridade e o valor só aparece depois que uma empresa enviar uma proposta.</p>
                    <?php if (!empty($empresasBanhoTosa)): ?>
                        <a href="<?= Url::pagina('pedir_servico.php') ?>?categoria_id=4" class="btn btn-success">Pedir
                            orçamento</a>
                    <?php endif; ?>
                </div>

                <div class="col-lg-6">
                    <img src="../../assets/img/servicos/higiene-cuidados.jpg" class="rounded-3 w-100"
                        style="max-height:320px;object-fit:cover;" alt="Cuidados de higiene">
                </div>

            </div>

            <h2 class="fw-bold mb-3">🐾 Petshops de banho e tosa cadastrados</h2>

            <div class="row g-4">
                <?php if (empty($empresasBanhoTosa)): ?>
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            <p class="mb-3">Não há petshops de banho e tosa disponíveis no momento. Você pode enviar um
                                pedido geral para nossa equipe procurar um prestador; o envio não confirma um horário.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= Url::pagina('contato.php') ?>?pedido=banho_e_tosa"
                                    class="btn btn-success">Pedir ajuda para encontrar atendimento</a>
                                <a href="<?= Url::pagina('cadastrar_empresa.php') ?>"
                                    class="btn btn-outline-success">Anunciar meu petshop</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($empresasBanhoTosa as $empresa):
                        echo renderizarCardEmpresaBanhoTosa($empresa);
                    endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> EcoSistemPet
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>