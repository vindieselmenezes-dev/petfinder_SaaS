<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Sobre
 * ==========================================================
 * Página institucional pública (link "Sobre" no rodapé). Mostra a
 * missão do projeto e alguns números reais da plataforma.
 */

require_once __DIR__ . '/../app/bootstrap.php';

// Números reais da plataforma para a seção de estatísticas. Cada
// contagem é isolada num try/catch: se uma tabela ainda não existir
// num ambiente mais antigo, a página continua funcionando com 0 nesse
// número específico, em vez de quebrar a página inteira.
function contarComSeguranca(callable $consulta): int
{
    try {
        return $consulta();
    } catch (Throwable $e) {
        error_log('Sobre: falha ao calcular estatística - ' . $e->getMessage());
        return 0;
    }
}

$totalPets = contarComSeguranca(fn () => (new PetController())->contarPets());
$totalAdotados = contarComSeguranca(fn () => (new PetController())->contarPorStatus('Adotado'));
$totalEmpresas = contarComSeguranca(fn () => (new Empresa())->contarEmpresas());
$totalOngs = contarComSeguranca(fn () => (new Parceiro())->resumo()['ongs']);

$tituloPagina = 'Sobre';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sobre o PetFinder Brasil</title>

    <meta name="description"
        content="Conheça a missão do PetFinder Brasil: conectar tutores, pets, ONGs e empresas do setor pet em um só lugar.">

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

            <div class="d-flex gap-2 align-items-center">
                <?php if (Auth::check()): ?>
                    <a href="<?= Url::pagina('dashboard.php') ?>" class="btn btn-primary">Meu painel</a>
                <?php else: ?>
                    <a href="<?= Url::pagina('login.php') ?>" class="btn btn-primary">Entrar</a>
                    <a href="<?= Url::pagina('cadastro.php') ?>" class="btn btn-outline-primary">Cadastrar</a>
                <?php endif; ?>
            </div>

        </div>

    </header>

    <!-- ========================================================= -->
    <!-- CAPA -->
    <!-- ========================================================= -->

    <section class="py-5 text-white" style="background:linear-gradient(135deg,#015C1E,#1B365D);">

        <div class="container text-center">

            <h1 class="fw-bold mb-3">🐾 Sobre o PetFinder Brasil</h1>

            <p class="lead mb-0" style="max-width:720px; margin-inline:auto;">
                Uma plataforma pensada para conectar quem cuida — tutores, ONGs, protetores
                independentes, clínicas e empresas do universo pet — em um só lugar.
            </p>

        </div>

    </section>

    <main class="container my-5">

        <!-- ========================================================= -->
        <!-- MISSÃO -->
        <!-- ========================================================= -->

        <div class="row g-4 align-items-center mb-5">

            <div class="col-lg-6">

                <h2 class="fw-bold">Nossa missão</h2>

                <p>
                    O PetFinder Brasil nasceu de um problema simples e muito comum: encontrar um
                    pet perdido, achar um novo lar para um animal resgatado, ou até localizar um
                    veterinário de confiança perto de casa costuma exigir procurar em vários
                    lugares diferentes ao mesmo tempo.
                </p>

                <p>
                    Reunimos tudo isso numa única plataforma: alertas de pets perdidos e
                    encontrados, adoção responsável, agendamento de serviços (veterinários,
                    banho e tosa, adestramento), uma loja com produtos para pets e uma área
                    dedicada a ONGs e empresas que ajudam a sustentar essa rede de cuidado.
                </p>

                <p class="mb-0">
                    No fim das contas, o objetivo é sempre o mesmo: mais pets encontrando seu
                    caminho de volta para casa, ou encontrando um lar pela primeira vez.
                </p>

            </div>

            <div class="col-lg-6">

                <div class="row g-3 text-center">

                    <div class="col-6">
                        <div class="border rounded-4 p-4 h-100">
                            <div class="fs-2 fw-bold text-primary"><?= number_format($totalPets, 0, ',', '.') ?></div>
                            <small class="text-muted">pets cadastrados</small>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="border rounded-4 p-4 h-100">
                            <div class="fs-2 fw-bold text-success"><?= number_format($totalAdotados, 0, ',', '.') ?></div>
                            <small class="text-muted">adoções realizadas</small>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="border rounded-4 p-4 h-100">
                            <div class="fs-2 fw-bold text-primary"><?= number_format($totalEmpresas, 0, ',', '.') ?></div>
                            <small class="text-muted">empresas cadastradas</small>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="border rounded-4 p-4 h-100">
                            <div class="fs-2 fw-bold text-success"><?= number_format($totalOngs, 0, ',', '.') ?></div>
                            <small class="text-muted">ONGs parceiras</small>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- O QUE OFERECEMOS -->
        <!-- ========================================================= -->

        <h2 class="fw-bold text-center mb-4">O que você encontra aqui</h2>

        <div class="row g-4 mb-5">

            <div class="col-md-4">
                <div class="text-center p-4 h-100 border rounded-4">
                    <div class="fs-1 mb-2">🔎</div>
                    <h3 class="h5 fw-bold">Pets perdidos e adoção</h3>
                    <p class="text-muted small mb-0">
                        Alertas de pets perdidos e encontrados, e um mural de adoção responsável.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-center p-4 h-100 border rounded-4">
                    <div class="fs-1 mb-2">🩺</div>
                    <h3 class="h5 fw-bold">Serviços agendados</h3>
                    <p class="text-muted small mb-0">
                        Consultas veterinárias, banho e tosa e adestramento, direto pelo site.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-center p-4 h-100 border rounded-4">
                    <div class="fs-1 mb-2">🛍️</div>
                    <h3 class="h5 fw-bold">Loja para pets</h3>
                    <p class="text-muted small mb-0">
                        Ração, acessórios e produtos vendidos por empresas parceiras.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-center p-4 h-100 border rounded-4">
                    <div class="fs-1 mb-2">🤝</div>
                    <h3 class="h5 fw-bold">ONGs e empresas parceiras</h3>
                    <p class="text-muted small mb-0">
                        Campanhas, eventos e pedidos de doação de quem ajuda a causa animal.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-center p-4 h-100 border rounded-4">
                    <div class="fs-1 mb-2">💬</div>
                    <h3 class="h5 fw-bold">Suporte de verdade</h3>
                    <p class="text-muted small mb-0">
                        Chamados e conversas diretas para resolver qualquer problema.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-center p-4 h-100 border rounded-4">
                    <div class="fs-1 mb-2">📍</div>
                    <h3 class="h5 fw-bold">Tudo perto de você</h3>
                    <p class="text-muted small mb-0">
                        Busca por localização para achar quem está perto — pets, clínicas e lojas.
                    </p>
                </div>
            </div>

        </div>

        <!-- ========================================================= -->
        <!-- CHAMADA -->
        <!-- ========================================================= -->

        <div class="p-4 p-lg-5 rounded-4 text-white text-center" style="background:#1B365D;">

            <h3 class="fw-bold">Faça parte dessa rede</h3>

            <p class="mb-4">
                Seja cadastrando um pet, apoiando uma campanha ou se tornando um parceiro —
                toda ajuda conta.
            </p>

            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="<?= Url::pagina('parceiros.php') ?>" class="btn btn-warning fw-bold">
                    Conhecer os parceiros
                </a>
                <a href="<?= Url::pagina('contato.php') ?>" class="btn btn-outline-light">
                    Fale conosco
                </a>
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
