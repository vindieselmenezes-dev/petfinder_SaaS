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
                    <a href="solicitar_servico_empresa.php?empresa_id={$id}" class="btn btn-success w-50 btn-sm">✂️ Solicitar</a>
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
    <title>Banho e Tosa - PetFinder Brasil</title>
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
                <i class="bi bi-megaphone"></i> Anunciar meu petshop de banho e tosa
            </a>
        </div>
    </header>

    <main>

        <section class="position-relative">
            <img src="../../assets/img/servicos/banho-tosa.jpg" class="w-100" style="max-height:320px; object-fit:cover;" alt="Banho e Tosa">
            <div class="position-absolute top-50 start-0 translate-middle-y bg-white bg-opacity-75 p-4 rounded-end" style="max-width:520px;">
                <h1 class="fw-bold">✂️ Banho e Tosa</h1>
                <p class="mb-0">Higiene e estética pro seu pet, com profissionais que cuidam de cada detalhe.</p>
            </div>
        </section>

        <div class="container my-5">

            <div class="row g-4 align-items-center mb-5">

                <div class="col-lg-6">
                    <h2 class="fw-bold mb-3">💰 Simule o valor do serviço</h2>
                    <p class="text-muted">Estimativa rápida — o valor final pode variar de acordo com o petshop escolhido.</p>

                    <div class="grupo-form">
                        <label class="form-label">Porte do animal</label>
                        <select id="porteAnimal" class="form-select">
                            <option value="18">Pequeno (até 10kg)</option>
                            <option value="28">Médio (10 a 25kg)</option>
                            <option value="40">Grande (acima de 25kg)</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="servicoBanho" checked>
                            <label class="form-check-label" for="servicoBanho">Banho</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="servicoTosa">
                            <label class="form-check-label" for="servicoTosa">Tosa (+ R$ 20,00)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="servicoHigiene">
                            <label class="form-check-label" for="servicoHigiene">Higiene completa: unhas, ouvido e glândula (+ R$ 15,00)</label>
                        </div>
                    </div>

                    <div class="alert alert-success mt-3">
                        <strong>Valor estimado: R$ <span id="valorTotal">18,00</span></strong>
                    </div>
                </div>

                <div class="col-lg-6">
                    <img src="../../assets/img/servicos/higiene-cuidados.jpg" class="rounded-3 w-100" style="max-height:320px;object-fit:cover;" alt="Cuidados de higiene">
                </div>

            </div>

            <h2 class="fw-bold mb-3">🐾 Petshops de banho e tosa cadastrados</h2>

            <div class="row g-4">
                <?php if (empty($empresasBanhoTosa)): ?>
                    <div class="col-12 text-muted">Ainda não há petshops de banho e tosa cadastrados na sua região. <a href="<?= Url::pagina('cadastrar_empresa.php') ?>">Seja o primeiro a anunciar!</a></div>
                <?php else: ?>
                    <?php foreach ($empresasBanhoTosa as $empresa): echo renderizarCardEmpresaBanhoTosa($empresa); endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    (function () {
        const porte = document.getElementById('porteAnimal');
        const banho = document.getElementById('servicoBanho');
        const tosa = document.getElementById('servicoTosa');
        const higiene = document.getElementById('servicoHigiene');
        const total = document.getElementById('valorTotal');

        function calcular() {
            let valor = 0;
            if (banho.checked) {
                valor += parseFloat(porte.value);
            }
            if (tosa.checked) {
                valor += 20;
            }
            if (higiene.checked) {
                valor += 15;
            }
            total.textContent = valor.toFixed(2).replace('.', ',');
        }

        [porte, banho, tosa, higiene].forEach(function (el) {
            el.addEventListener('change', calcular);
        });

        calcular();
    })();
    </script>

</body>

</html>
