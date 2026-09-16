<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Perfil público de um parceiro
 * ==========================================================
 * Mostra quem é a ONG/empresa, como falar com ela, como doar e
 * tudo o que ela tem publicado (campanhas, eventos e doações).
 */

require_once __DIR__ . '/../../app/bootstrap.php';

$parceiroModel = new Parceiro();
$campanhaModel = new Campanha();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$parceiro = $id > 0 ? $parceiroModel->buscarPorId($id) : null;

if ($parceiro === null) {
    http_response_code(404);
    $tituloErro = 'Parceiro não encontrado';
}

$campanhas = $parceiro ? $campanhaModel->listarPorParceiro($id) : [];

$logo = $parceiro ? Foto::url($parceiro['logo'] ?? null, 'parceiros', 'img/logo.png') : '';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($parceiro['nome'] ?? 'Parceiro não encontrado') ?> - PetFinder Brasil</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= Url::asset('css/style.css') ?>">

</head>

<body style="padding-top:38px;">

    <div
        style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;">
        <button type="button"
            onclick="if(window.history.length>1){history.back();}else{window.location.href='<?= Url::pagina('parceiros.php') ?>';}"
            style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;"
            aria-label="Voltar para a página anterior">← Voltar</button>
    </div>

    <main class="container my-4">

        <?php if ($parceiro === null): ?>

            <div class="text-center py-5">
                <div class="fs-1">🐾</div>
                <h1 class="fw-bold">Parceiro não encontrado</h1>
                <p class="text-muted">
                    Esse perfil pode ter sido removido ou ainda estar aguardando aprovação.
                </p>
                <a href="<?= Url::pagina('parceiros.php') ?>" class="btn btn-primary">
                    Ver todos os parceiros
                </a>
            </div>

        <?php else: ?>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= Url::raiz('index.html') ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?= Url::pagina('parceiros.php') ?>">Parceiros</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= htmlspecialchars($parceiro['nome']) ?>
                    </li>
                </ol>
            </nav>

            <!-- ============ IDENTIFICAÇÃO ============ -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <div class="row g-4 align-items-center">

                        <div class="col-md-2 text-center">
                            <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($parceiro['nome']) ?>"
                                class="rounded-circle border" style="width:110px; height:110px; object-fit:cover;">
                        </div>

                        <div class="col-md-7">

                            <h1 class="fw-bold mb-2"><?= htmlspecialchars($parceiro['nome']) ?></h1>

                            <p class="mb-2">
                                <span class="badge bg-primary">
                                    <?= htmlspecialchars(Parceiro::rotuloTipo($parceiro['tipo'])) ?>
                                </span>
                                <?php if (!empty($parceiro['destaque'])): ?>
                                    <span class="badge bg-warning text-dark">⭐ Parceiro em destaque</span>
                                <?php endif; ?>
                                <?php if (!empty($parceiro['aceita_voluntarios'])): ?>
                                    <span class="badge bg-success">Aceita voluntários</span>
                                <?php endif; ?>
                            </p>

                            <?php if (!empty($parceiro['cidade'])): ?>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-geo-alt"></i>
                                    <?= htmlspecialchars($parceiro['cidade']) ?><?= !empty($parceiro['estado']) ? '/' . htmlspecialchars($parceiro['estado']) : '' ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($parceiro['como_ajuda'])): ?>
                                <p class="mb-0">
                                    <i class="bi bi-hand-thumbs-up text-success"></i>
                                    <strong>Como apoia o PetFinder:</strong>
                                    <?= htmlspecialchars($parceiro['como_ajuda']) ?>
                                </p>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-3 d-grid gap-2">

                            <?php if (!empty($parceiro['whatsapp'])): ?>
                                <a class="btn btn-success"
                                    href="https://wa.me/55<?= preg_replace('/\D/', '', $parceiro['whatsapp']) ?>"
                                    target="_blank" rel="noopener">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($parceiro['instagram'])): ?>
                                <a class="btn btn-outline-dark"
                                    href="https://instagram.com/<?= htmlspecialchars(ltrim($parceiro['instagram'], '@')) ?>"
                                    target="_blank" rel="noopener">
                                    <i class="bi bi-instagram"></i> Instagram
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($parceiro['site'])): ?>
                                <a class="btn btn-outline-primary" href="<?= htmlspecialchars($parceiro['site']) ?>"
                                    target="_blank" rel="noopener">
                                    <i class="bi bi-globe"></i> Site
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($parceiro['email_contato'])): ?>
                                <a class="btn btn-outline-secondary"
                                    href="mailto:<?= htmlspecialchars($parceiro['email_contato']) ?>">
                                    <i class="bi bi-envelope"></i> E-mail
                                </a>
                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

            <div class="row g-4">

                <!-- ============ SOBRE + CAMPANHAS ============ -->

                <div class="col-lg-8">

                    <?php if (!empty($parceiro['descricao'])): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h2 class="h5 fw-bold">Sobre</h2>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($parceiro['descricao'])) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h2 class="h4 fw-bold mb-3">📣 Campanhas, eventos e doações</h2>

                    <?php if (count($campanhas) === 0): ?>

                        <div class="border rounded-3 text-center text-muted py-5">
                            Este parceiro ainda não publicou nenhuma ação.
                        </div>

                    <?php else: ?>

                        <div class="row g-3">

                            <?php foreach ($campanhas as $campanha): ?>

                                <?php
                                $imagem = Foto::url($campanha['imagem'] ?? null, 'campanhas', 'img/parceiros/parceiro02.jpg');
                                $percentual = Campanha::percentualMeta(
                                    $campanha['meta_valor'] !== null ? (float) $campanha['meta_valor'] : null,
                                    (float) $campanha['valor_arrecadado']
                                );
                                ?>

                                <div class="col-md-6">

                                    <div class="card h-100 shadow-sm">

                                        <img src="<?= htmlspecialchars($imagem) ?>" class="card-img-top"
                                            style="height:160px; object-fit:cover;"
                                            alt="<?= htmlspecialchars($campanha['titulo']) ?>">

                                        <div class="card-body d-flex flex-column">

                                            <span class="badge bg-dark align-self-start mb-2">
                                                <?= Campanha::iconeTipo($campanha['tipo']) ?>
                                                <?= htmlspecialchars(Campanha::rotuloTipo($campanha['tipo'])) ?>
                                            </span>

                                            <h3 class="h6 fw-bold"><?= htmlspecialchars($campanha['titulo']) ?></h3>

                                            <?php if (!empty($campanha['resumo'])): ?>
                                                <p class="small text-muted"><?= htmlspecialchars($campanha['resumo']) ?></p>
                                            <?php endif; ?>

                                            <?php if ($percentual !== null): ?>
                                                <div class="progress mb-2" style="height:8px;">
                                                    <div class="progress-bar bg-success" style="width: <?= $percentual ?>%;"
                                                        role="progressbar" aria-valuenow="<?= $percentual ?>"
                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-muted mb-2">
                                                    <?= $percentual ?>% da meta de
                                                    R$ <?= number_format((float) $campanha['meta_valor'], 2, ',', '.') ?>
                                                </small>
                                            <?php endif; ?>

                                            <a href="<?= Url::pagina('campanha.php') ?>?id=<?= (int) $campanha['id'] ?>"
                                                class="btn btn-success btn-sm w-100 mt-auto">
                                                <i class="bi bi-heart-fill"></i> Quero ajudar
                                            </a>

                                        </div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

                <!-- ============ COMO DOAR ============ -->

                <div class="col-lg-4">

                    <div class="card shadow-sm border-success">

                        <div class="card-header bg-success text-white fw-bold">
                            💚 Como doar para este parceiro
                        </div>

                        <div class="card-body">

                            <?php if (!empty($parceiro['chave_pix'])): ?>

                                <p class="small text-muted mb-1">Chave PIX</p>

                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="chavePix" readonly
                                        value="<?= htmlspecialchars($parceiro['chave_pix']) ?>">
                                    <button class="btn btn-outline-secondary" type="button" id="copiarPix">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>

                            <?php endif; ?>

                            <?php if (!empty($parceiro['link_doacao'])): ?>
                                <a href="<?= htmlspecialchars($parceiro['link_doacao']) ?>" target="_blank" rel="noopener"
                                    class="btn btn-success w-100 mb-3">
                                    Doar pelo site do parceiro
                                </a>
                            <?php endif; ?>

                            <?php if (empty($parceiro['chave_pix']) && empty($parceiro['link_doacao'])): ?>
                                <p class="text-muted small mb-3">
                                    Este parceiro ainda não cadastrou uma forma de doação direta.
                                    Fale com ele pelos contatos acima ou apoie uma campanha específica.
                                </p>
                            <?php endif; ?>

                            <hr>

                            <p class="small mb-0">
                                O PetFinder <strong>não recebe nem intermedia</strong> esses valores:
                                a doação vai direto para o parceiro. Confira os dados antes de transferir.
                            </p>

                        </div>

                    </div>

                    <div class="card shadow-sm mt-4">
                        <div class="card-body text-center">
                            <p class="mb-2 small text-muted">Conhece outra ONG ou empresa que deveria estar aqui?</p>
                            <a href="<?= Url::pagina('cadastrar_parceiro.php') ?>" class="btn btn-outline-primary btn-sm">
                                Indicar / cadastrar parceiro
                            </a>
                        </div>
                    </div>

                </div>

            </div>

        <?php endif; ?>

    </main>

    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            © <?= date('Y') ?> PetFinder Brasil ·
            <a href="<?= Url::pagina('parceiros.php') ?>" class="text-light">Todos os parceiros</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Copiar a chave PIX sem sair da página (com aviso visual no botão).
        (function () {
            var botao = document.getElementById('copiarPix');
            var campo = document.getElementById('chavePix');

            if (!botao || !campo) {
                return;
            }

            botao.addEventListener('click', function () {
                campo.select();
                campo.setSelectionRange(0, 99999);

                var conclui = function () {
                    botao.innerHTML = '<i class="bi bi-check2"></i>';
                    setTimeout(function () {
                        botao.innerHTML = '<i class="bi bi-clipboard"></i>';
                    }, 2000);
                };

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(campo.value).then(conclui);
                } else {
                    document.execCommand('copy');
                    conclui();
                }
            });
        })();
    </script>

</body>

</html>
