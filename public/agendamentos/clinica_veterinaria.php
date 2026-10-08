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
    $perfilUrl = htmlspecialchars(Url::pagina('empresa.php') . '?id=' . $id, ENT_QUOTES, 'UTF-8');
    $agendarUrl = htmlspecialchars(Url::pagina('agendar_consulta.php') . '?empresa_id=' . $id, ENT_QUOTES, 'UTF-8');
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
                    <a href="{$agendarUrl}" class="btn btn-success w-50 btn-sm">🩺 Agendar</a>
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
    <title>Clínica Veterinária - EcoSistemPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" integrity="sha384-CK2SzKma4jA5H/MXDUU7i1TqZlCFaD4T01vtyDFvPlD97JQyS+IsSh1nI2EFbpyk" crossorigin="anonymous">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .especialidade-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-height: 58px;
            padding: 0.65rem 0.8rem;
            border: 1px solid #e1e8e5;
            border-radius: 8px;
            background: #fff;
            color: #263b35;
            font-weight: 600;
        }

        .especialidade-item .bi {
            display: grid;
            place-items: center;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            border-radius: 7px;
            background: #edf5f1;
            color: #328064;
            font-size: 1.05rem;
        }

        .banner-clinica {
            display: block;
            width: 100%;
            height: auto;
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
                <i class="bi bi-megaphone"></i> Anunciar minha clínica
            </a>
        </div>
    </header>

    <main>

        <section>
            <h1 class="visually-hidden">Clínica Veterinária</h1>
            <img src="../../assets/img/servicos/banner-clinica-novo.png" class="banner-clinica"
                alt="Banner da clínica veterinária com serviços de consulta e atendimento especializado">
        </section>

        <div class="container my-5">

            <h2 class="fw-bold mb-3 text-center">Especialidades</h2>

            <ul class="list-unstyled row g-2 mb-5">
                <li class="col-md-6">
                    <div class="especialidade-item"><i class="bi bi-heart-pulse-fill"
                            aria-hidden="true"></i><span>Cardiologia</span></div>
                </li>
                <li class="col-md-6">
                    <div class="especialidade-item"><i class="bi bi-bone" aria-hidden="true"></i><span>Ortopedia</span>
                    </div>
                </li>
                <li class="col-md-6">
                    <div class="especialidade-item"><i class="bi bi-emoji-smile"
                            aria-hidden="true"></i><span>Odontologia</span></div>
                </li>
                <li class="col-md-6">
                    <div class="especialidade-item"><i class="bi bi-activity"
                            aria-hidden="true"></i><span>Fisioterapia</span></div>
                </li>
                <li class="col-md-6">
                    <div class="especialidade-item"><i class="bi bi-eye-fill"
                            aria-hidden="true"></i><span>Oftalmologia</span></div>
                </li>
                <li class="col-md-6">
                    <div class="especialidade-item"><i class="bi bi-droplet-half"
                            aria-hidden="true"></i><span>Dermatologia</span></div>
                </li>
                <li class="col-md-6">
                    <div class="especialidade-item"><i class="bi bi-egg-fried"
                            aria-hidden="true"></i><span>Nutrição</span></div>
                </li>
            </ul>

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
                        <p class="text-muted small mb-0">Retorno e evolução do tratamento registrados no histórico do
                            pet.
                        </p>
                    </div>
                </div>
            </div>

            <div class="alert alert-danger d-flex align-items-center gap-3 mb-5">
                <img src="../../assets/img/servicos/clinica-urgencia24h.jpg"
                    style="width:70px;height:70px;object-fit:cover;border-radius:8px;" alt="Urgência 24h">
                <div>
                    <strong>Urgência 24h</strong><br>
                    <span class="small">Em caso de emergência, procure a clínica ou hospital mais próximo cadastrado
                        abaixo.</span>
                </div>
            </div>

            <h2 class="fw-bold mb-3">🐾 Clínicas cadastradas</h2>

            <div class="row g-4 mb-5">
                <?php if (empty($clinicas)): ?>
                    <div class="col-12 text-muted">Ainda não há clínicas cadastradas na sua região. <a
                            href="<?= Url::pagina('cadastrar_empresa.php') ?>">Seja a primeira a anunciar!</a></div>
                <?php else: ?>
                    <?php foreach ($clinicas as $empresa):
                        echo renderizarCardEmpresaClinica($empresa);
                    endforeach; ?>
                <?php endif; ?>
            </div>

            <h2 class="fw-bold mb-3">🏥 Hospitais Veterinários</h2>

            <div class="row g-4">
                <?php if (empty($hospitais)): ?>
                    <div class="col-12 text-muted">Ainda não há hospitais cadastrados. <a
                            href="<?= Url::pagina('cadastrar_empresa.php') ?>">Seja o primeiro a anunciar!</a></div>
                <?php else: ?>
                    <?php foreach ($hospitais as $empresa):
                        echo renderizarCardEmpresaClinica($empresa);
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
