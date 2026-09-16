<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


/**
 * Página temática "Clínica Veterinária" -- conteúdo inspirado nas páginas
 * clinica.html e consultaveterinaria.html do projetointegrador, listando
 * de verdade as empresas das categorias "Clínica Veterinária" e
 * "Hospital Veterinário" já cadastradas no banco do SaaS.
 */

require_once "../../app/Controllers/EmpresaController.php";

$controller = new EmpresaController();

// categoria 2 = "Clínica Veterinária", categoria 3 = "Hospital Veterinário"
$clinicas = $controller->listarAtivas(2);
$hospitais = $controller->listarAtivas(3);

function renderizarCardEmpresaClinica(array $empresa): string
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
                    <a href="agendar_consulta.php?empresa_id={$id}" class="btn btn-success w-50 btn-sm">🩺 Agendar</a>
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
    <title>Clínica Veterinária - PetFinder Brasil</title>
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
                <i class="bi bi-megaphone"></i> Anunciar minha clínica
            </a>
        </div>
    </header>

    <main>

        <section class="position-relative">
            <img src="../../assets/img/servicos/clinica-consulta.jpg" class="w-100" style="max-height:340px; object-fit:cover;" alt="Clínica Veterinária">
            <div class="position-absolute top-50 start-0 translate-middle-y bg-white bg-opacity-75 p-4 rounded-end" style="max-width:520px;">
                <h1 class="fw-bold">🩺 Clínica Veterinária</h1>
                <p class="mb-0">Consultas, especialidades e urgência 24h pra cuidar da saúde do seu pet.</p>
            </div>
        </section>

        <div class="container my-5">

            <h2 class="fw-bold mb-3 text-center">Especialidades</h2>

            <div class="row g-4 mb-5 text-center">
                <div class="col-md-3 col-6">
                    <img src="../../assets/img/servicos/clinica-cardiologia.jpg" class="rounded-3 mb-2" style="height:120px;width:100%;object-fit:cover;">
                    <h6>Cardiologia</h6>
                </div>
                <div class="col-md-3 col-6">
                    <img src="../../assets/img/servicos/clinica-ortopedia.jpg" class="rounded-3 mb-2" style="height:120px;width:100%;object-fit:cover;">
                    <h6>Ortopedia</h6>
                </div>
                <div class="col-md-3 col-6">
                    <img src="../../assets/img/servicos/clinica-odontologia.jpg" class="rounded-3 mb-2" style="height:120px;width:100%;object-fit:cover;">
                    <h6>Odontologia</h6>
                </div>
                <div class="col-md-3 col-6">
                    <img src="../../assets/img/servicos/clinica-fisioterapia.jpg" class="rounded-3 mb-2" style="height:120px;width:100%;object-fit:cover;">
                    <h6>Fisioterapia</h6>
                </div>
                <div class="col-md-3 col-6">
                    <div class="rounded-3 mb-2 d-flex align-items-center justify-content-center bg-light" style="height:120px;">
                        <span class="fs-1">👁️</span>
                    </div>
                    <h6>Oftalmologia</h6>
                </div>
                <div class="col-md-3 col-6">
                    <div class="rounded-3 mb-2 d-flex align-items-center justify-content-center bg-light" style="height:120px;">
                        <span class="fs-1">🧴</span>
                    </div>
                    <h6>Dermatologia</h6>
                </div>
                <div class="col-md-3 col-6">
                    <div class="rounded-3 mb-2 d-flex align-items-center justify-content-center bg-light" style="height:120px;">
                        <span class="fs-1">🥗</span>
                    </div>
                    <h6>Nutrição</h6>
                </div>
            </div>

            <div class="row g-4 mb-5 text-center">
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 h-100">
                        <div class="fs-2 mb-1">🔬</div>
                        <h6 class="mb-1">Exames Laboratoriais e de Imagem</h6>
                        <p class="text-muted small mb-0">Sangue, raio-x, ultrassom e mais.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 h-100">
                        <div class="fs-2 mb-1">💉</div>
                        <h6 class="mb-1">Vacinação</h6>
                        <p class="text-muted small mb-0">Calendário completo de vacinas pro seu pet.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 h-100">
                        <div class="fs-2 mb-1">📋</div>
                        <h6 class="mb-1">Acompanhamento Clínico</h6>
                        <p class="text-muted small mb-0">Retorno e evolução do tratamento registrados no histórico do pet.</p>
                    </div>
                </div>
            </div>

            <div class="alert alert-danger d-flex align-items-center gap-3 mb-5">
                <img src="../../assets/img/servicos/clinica-urgencia24h.jpg" style="width:70px;height:70px;object-fit:cover;border-radius:8px;" alt="Urgência 24h">
                <div>
                    <strong>Urgência 24h</strong><br>
                    <span class="small">Em caso de emergência, procure a clínica ou hospital mais próximo cadastrado abaixo.</span>
                </div>
            </div>

            <h2 class="fw-bold mb-3">🐾 Clínicas cadastradas</h2>

            <div class="row g-4 mb-5">
                <?php if (empty($clinicas)): ?>
                    <div class="col-12 text-muted">Ainda não há clínicas cadastradas na sua região. <a href="<?= Url::pagina('cadastrar_empresa.php') ?>">Seja a primeira a anunciar!</a></div>
                <?php else: ?>
                    <?php foreach ($clinicas as $empresa): echo renderizarCardEmpresaClinica($empresa); endforeach; ?>
                <?php endif; ?>
            </div>

            <h2 class="fw-bold mb-3">🏥 Hospitais Veterinários</h2>

            <div class="row g-4">
                <?php if (empty($hospitais)): ?>
                    <div class="col-12 text-muted">Ainda não há hospitais cadastrados. <a href="<?= Url::pagina('cadastrar_empresa.php') ?>">Seja o primeiro a anunciar!</a></div>
                <?php else: ?>
                    <?php foreach ($hospitais as $empresa): echo renderizarCardEmpresaClinica($empresa); endforeach; ?>
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
