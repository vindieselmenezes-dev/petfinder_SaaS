<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Termos de Uso
 * ==========================================================
 * Conteúdo-modelo. Os trechos marcados com [ ] são dados jurídicos
 * reais (razão social, CNPJ, endereço, foro) que precisam ser
 * preenchidos pelo responsável pelo PetFinder Brasil antes da
 * publicação. O ideal é ter um advogado revisando antes de publicar
 * oficialmente.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$tituloPagina = 'Termos de Uso';
$dataAtualizacao = '15 de setembro de 2026';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Termos de Uso - PetFinder Brasil</title>

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

        </div>

    </header>

    <main class="container my-5">

        <div class="row justify-content-center">

            <div class="col-lg-8">

                <h1 class="fw-bold mb-1">Termos de Uso</h1>
                <p class="text-muted mb-5">Última atualização: <?= $dataAtualizacao ?></p>

                <p>
                    Estes Termos de Uso regulam o acesso e a utilização da plataforma
                    <strong>PetFinder Brasil</strong>, operada por
                    <strong>[razão social a definir]</strong>, inscrita no CNPJ sob o nº
                    <strong>[CNPJ a definir]</strong> ("PetFinder", "nós"). Ao criar uma conta ou
                    usar o site, você concorda com estes termos.
                </p>

                <h2 class="h5 fw-bold mt-4">1. O que é o PetFinder</h2>
                <p>
                    O PetFinder é uma plataforma que conecta tutores, ONGs, protetores
                    independentes, clínicas veterinárias, prestadores de serviço e empresas do
                    setor pet. Isso inclui: alertas de pets perdidos e encontrados, anúncios de
                    adoção, agendamento de serviços, uma loja de produtos e uma área de parceiros
                    (ONGs e empresas apoiadoras).
                </p>

                <h2 class="h5 fw-bold mt-4">2. Somos um intermediário</h2>
                <p>
                    O PetFinder <strong>não é dono</strong> dos pets anunciados, <strong>não
                    presta</strong> os serviços de veterinária, banho e tosa ou adestramento
                    listados na plataforma, e <strong>não é parte</strong> nas negociações de
                    adoção ou nas doações feitas a parceiros. Cada anúncio, agendamento ou
                    campanha é de responsabilidade de quem o publicou.
                </p>
                <p>
                    Fazemos o possível para moderar o conteúdo publicado (por exemplo, aprovando
                    parceiros antes de aparecerem no site), mas não garantimos a exatidão das
                    informações fornecidas por terceiros, nem o resultado de uma adoção,
                    atendimento ou doação.
                </p>

                <h2 class="h5 fw-bold mt-4">3. Sua conta</h2>
                <ul>
                    <li>Você é responsável por manter sua senha em sigilo e por tudo que acontecer usando sua conta;</li>
                    <li>As informações de cadastro devem ser verdadeiras e mantidas atualizadas;</li>
                    <li>Contas podem ser suspensas em caso de uso indevido, fraude ou violação destes termos;</li>
                    <li>Você pode encerrar sua conta a qualquer momento pela página de <a href="<?= Url::pagina('contato.php') ?>">contato</a>.</li>
                </ul>

                <h2 class="h5 fw-bold mt-4">4. Regras de uso</h2>
                <p>Ao usar o PetFinder, você concorda em não:</p>
                <ul>
                    <li>Publicar anúncios falsos de pets perdidos, encontrados ou para adoção;</li>
                    <li>Usar a área de parceiros para arrecadar doações sem a intenção real de usá-las para a causa animal;</li>
                    <li>Publicar conteúdo ofensivo, discriminatório ou que promova maus-tratos a animais;</li>
                    <li>Tentar acessar contas de outras pessoas ou dados que não sejam seus;</li>
                    <li>Usar a plataforma para qualquer finalidade ilegal.</li>
                </ul>

                <h2 class="h5 fw-bold mt-4">5. Compras na loja e agendamentos</h2>
                <p>
                    Produtos vendidos na loja e serviços agendados na plataforma são oferecidos
                    por empresas e prestadores cadastrados, cada um responsável pela qualidade,
                    entrega e cumprimento do que foi anunciado. O PetFinder pode intermediar
                    eventuais problemas através do suporte, mas a relação de consumo é entre você
                    e o fornecedor do produto ou serviço.
                </p>

                <h2 class="h5 fw-bold mt-4">6. Doações a parceiros</h2>
                <p>
                    O PetFinder <strong>não recebe nem intermedia</strong> valores doados a ONGs e
                    empresas parceiras: a doação é feita diretamente pela chave PIX ou link
                    informado pelo próprio parceiro em seu perfil. Confira sempre os dados antes
                    de transferir qualquer valor.
                </p>

                <h2 class="h5 fw-bold mt-4">7. Propriedade intelectual</h2>
                <p>
                    A marca, o layout e o código do PetFinder pertencem a
                    <strong>[razão social a definir]</strong>. O conteúdo que você publica (fotos
                    de pets, textos de campanhas, avaliações) continua sendo seu, mas você nos dá
                    permissão para exibi-lo na plataforma para cumprir a finalidade para a qual foi
                    enviado.
                </p>

                <h2 class="h5 fw-bold mt-4">8. Limitação de responsabilidade</h2>
                <p>
                    Na medida permitida pela lei, o PetFinder não se responsabiliza por danos
                    indiretos decorrentes do uso da plataforma, incluindo — mas não se limitando a
                    — resultados de adoções, qualidade de serviços de terceiros, ou o destino de
                    doações feitas diretamente a parceiros.
                </p>

                <h2 class="h5 fw-bold mt-4">9. Alterações destes termos</h2>
                <p>
                    Podemos atualizar estes Termos de Uso periodicamente. O uso contínuo da
                    plataforma após uma atualização significa que você concorda com os novos
                    termos. A data no topo desta página sempre indica a versão mais recente.
                </p>

                <h2 class="h5 fw-bold mt-4">10. Lei aplicável e foro</h2>
                <p>
                    Estes termos são regidos pelas leis da República Federativa do Brasil. Fica
                    eleito o foro da comarca de <strong>[cidade/UF a definir]</strong> para
                    dirimir eventuais controvérsias, com renúncia a qualquer outro, por mais
                    privilegiado que seja.
                </p>

                <hr class="my-4">

                <p class="text-muted small">
                    Veja também nossa <a href="<?= Url::pagina('privacidade.php') ?>">Política de
                    Privacidade</a>, ou fale com a gente pela
                    <a href="<?= Url::pagina('contato.php') ?>">página de contato</a>.
                </p>

            </div>

        </div>

    </main>

    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            © <?= date('Y') ?> PetFinder Brasil ·
            <a href="<?= Url::raiz('index.html') ?>" class="text-light">Voltar para a home</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
