<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$dados = Videoteca::carregar();
$categorias = $dados['categorias'];
$videos = $dados['videos'];
$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

// Cada categoria só aparece como aba se tiver vídeo.
$contagem = array_count_values(array_column($videos, 'categoria'));
$total = count($videos);
$videoAberto = preg_match('/^[a-f0-9]{8}$/', (string) ($_GET['v'] ?? '')) ? (string) $_GET['v'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videoteca Educativa | EcoSistemPet</title>
    <meta name="description" content="Vídeos educativos sobre saúde, alimentação, comportamento e adoção de pets. Assista, compartilhe e faça a diferença.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="EcoSistemPet">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:title" content="Videoteca Educativa | EcoSistemPet">
    <meta property="og:description" content="Assista, compartilhe e faça a diferença! Vídeos educativos para cuidar melhor do seu pet.">

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" integrity="sha384-CK2SzKma4jA5H/MXDUU7i1TqZlCFaD4T01vtyDFvPlD97JQyS+IsSh1nI2EFbpyk" crossorigin="anonymous">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .vt-hero {
            background: linear-gradient(135deg, #015C1E 0%, #003411 100%);
            color: #fff;
            border-radius: 0 0 28px 28px;
            padding: 48px 0 40px;
        }
        .vt-hero .selo { background: rgba(255, 193, 7, .18); color: #ffc107; font-weight: 700; }
        .vt-abas { position: sticky; top: 38px; z-index: 10; background: #fff; border-bottom: 1px solid #e5e5e5; }
        .vt-abas .nav-link { color: #015C1E; font-weight: 600; border-radius: 50rem; white-space: nowrap; }
        .vt-abas .nav-link.active { background: #015C1E; color: #fff; }
        .vt-abas .badge { background: #e9f5ec; color: #015C1E; }
        .vt-abas .nav-link.active .badge { background: #ffc107; color: #003411; }
        .vt-scroll { overflow-x: auto; flex-wrap: nowrap; scrollbar-width: none; }
        .vt-scroll::-webkit-scrollbar { display: none; }
        .vt-card { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 18px rgba(0, 0, 0, .08); transition: transform .15s; height: 100%; }
        .vt-card:hover { transform: translateY(-3px); }
        .vt-capa {
            position: relative; width: 100%; aspect-ratio: 16 / 9; border: 0; padding: 0; cursor: pointer;
            background: linear-gradient(135deg, #015C1E, #0a8a35); display: flex; align-items: center; justify-content: center; color: #fff;
        }
        .vt-capa .play {
            width: 64px; height: 64px; border-radius: 50%; background: rgba(255, 255, 255, .95); color: #015C1E;
            display: flex; align-items: center; justify-content: center; font-size: 30px; transition: transform .15s;
        }
        .vt-capa:hover .play { transform: scale(1.1); }
        .vt-dur { position: absolute; right: 10px; bottom: 10px; background: rgba(0, 0, 0, .7); color: #fff; font-size: 12px; padding: 2px 8px; border-radius: 6px; }
        .vt-breve .vt-capa { background: #e9ecef; cursor: default; color: #6c757d; }
        .vt-breve .play { background: #adb5bd; color: #fff; }
        .vt-player { aspect-ratio: 16 / 9; background: #000; }
        .vt-player iframe, .vt-player video { width: 100%; height: 100%; border: 0; display: block; }
    </style>
</head>

<body style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="← Voltar para a página anterior">← Voltar</button></div>

    <!-- HERO -->
    <header class="vt-hero mb-0">
        <div class="container">
            <a href="../index.html" class="d-inline-flex align-items-center text-decoration-none text-white mb-4">
                <img src="../assets/img/logo.png" alt="EcoSistemPet" height="44" class="me-2">
                <span class="fw-bold">EcoSistemPet</span>
            </a>
            <div><span class="badge selo rounded-pill px-3 py-2 mb-3"><i class="bi bi-play-circle-fill"></i> Videoteca Educativa</span></div>
            <h1 class="display-5 fw-bold mb-2">Aprenda a cuidar melhor do seu pet</h1>
            <p class="lead mb-4" style="max-width:640px;">Assista, compartilhe e faça a diferença! <?= $total > 0 ? $h((string) $total) . ' vídeo' . ($total > 1 ? 's' : '') . ' para você.' : 'Novos vídeos em breve.' ?></p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="blog.php" class="btn btn-outline-light"><i class="bi bi-journal-text"></i> Ver o Blog</a>
                <a href="parceiros/parceiros.php" class="btn btn-warning fw-semibold"><i class="bi bi-heart-fill"></i> ONGs parceiras</a>
            </div>
        </div>
    </header>

    <!-- ABAS POR CATEGORIA -->
    <nav class="vt-abas" aria-label="Categorias da videoteca">
        <div class="container">
            <ul class="nav nav-pills vt-scroll py-2 gap-1" id="abasVideoteca">
                <li class="nav-item"><button type="button" class="nav-link active" data-cat="todos">Todos <span class="badge rounded-pill ms-1"><?= $total ?></span></button></li>
                <?php foreach ($categorias as $slug => $rotulo): if (empty($contagem[$slug])) { continue; } ?>
                    <li class="nav-item"><button type="button" class="nav-link" data-cat="<?= $h($slug) ?>"><?= $h($rotulo) ?> <span class="badge rounded-pill ms-1"><?= (int) $contagem[$slug] ?></span></button></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <main class="container my-5" id="videoteca">
        <?php if ($videos): ?>
            <div class="row g-4" id="gradeVideos">
                <?php foreach ($videos as $v): ?>
                    <div class="col-12 col-sm-6 col-lg-4 vt-item" data-cat="<?= $h($v['categoria']) ?>">
                        <article class="card vt-card">
                            <button type="button" class="vt-capa" <?php if ($v['capa'] !== ''): ?>style="background:url('../<?= $h($v['capa']) ?>') center/cover"<?php endif; ?> data-abrir="<?= $h($v['chave']) ?>" data-tipo="<?= $h($v['tipo']) ?>"
                                data-ref="<?= $h($v['ref']) ?>" data-titulo="<?= $h($v['titulo']) ?>" aria-label="Assistir: <?= $h($v['titulo']) ?>">
                                <span class="play"><i class="bi bi-play-fill"></i></span>
                                <?php if ($v['duracao'] !== ''): ?><span class="vt-dur"><?= $h($v['duracao']) ?></span><?php endif; ?>
                            </button>
                            <div class="card-body">
                                <span class="badge text-bg-success mb-2"><?= $h($categorias[$v['categoria']]) ?></span>
                                <h2 class="h6 fw-bold mb-1"><?= $h($v['titulo']) ?></h2>
                                <?php if ($v['descricao'] !== ''): ?><p class="small text-muted mb-2"><?= $h($v['descricao']) ?></p><?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-success" data-compartilhar="<?= $h($v['chave']) ?>" data-titulo="<?= $h($v['titulo']) ?>">
                                    <i class="bi bi-share"></i> Compartilhar
                                </button>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <article class="card vt-card vt-breve">
                            <div class="vt-capa"><span class="play"><i class="bi bi-play-fill"></i></span></div>
                            <div class="card-body text-center">
                                <h2 class="h6 fw-bold mb-1">Novos vídeos em breve</h2>
                                <p class="small text-muted mb-0">Estamos preparando conteúdo para você.</p>
                            </div>
                        </article>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- PLAYER -->
    <div class="modal fade" id="modalPlayer" tabindex="-1" aria-labelledby="tituloPlayer" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-6" id="tituloPlayer">Vídeo</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="vt-player" id="areaPlayer"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            // Filtro por categoria (abas)
            var abas = document.querySelectorAll('#abasVideoteca [data-cat]');
            var itens = document.querySelectorAll('.vt-item');
            function filtrar(cat) {
                abas.forEach(function (a) { a.classList.toggle('active', a.dataset.cat === cat); });
                itens.forEach(function (i) { i.hidden = !(cat === 'todos' || i.dataset.cat === cat); });
            }
            abas.forEach(function (a) { a.addEventListener('click', function () { filtrar(a.dataset.cat); }); });

            // Player: o <iframe>/<video> só é criado ao clicar e removido ao fechar (para parar o som)
            var modalEl = document.getElementById('modalPlayer');
            var area = document.getElementById('areaPlayer');
            var modal = new bootstrap.Modal(modalEl);
            function abrir(btn) {
                var tipo = btn.dataset.tipo, ref = btn.dataset.ref;
                document.getElementById('tituloPlayer').textContent = btn.dataset.titulo;
                area.textContent = '';
                var el;
                if (tipo === 'youtube') {
                    el = document.createElement('iframe');
                    el.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(ref) + '?autoplay=1&rel=0';
                    el.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
                    el.allowFullscreen = true;
                    el.title = btn.dataset.titulo;
                } else {
                    el = document.createElement('video');
                    el.src = '../' + ref;
                    el.controls = true; el.autoplay = true; el.playsInline = true;
                }
                area.appendChild(el);
                modal.show();
            }
            modalEl.addEventListener('hidden.bs.modal', function () { area.textContent = ''; });
            document.querySelectorAll('[data-abrir]').forEach(function (b) { b.addEventListener('click', function () { abrir(b); }); });

            // Compartilhar: link direto que já abre o vídeo
            document.querySelectorAll('[data-compartilhar]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var url = location.origin + location.pathname + '?v=' + b.dataset.compartilhar;
                    if (navigator.share) {
                        navigator.share({ title: b.dataset.titulo, url: url }).catch(function () {});
                    } else if (navigator.clipboard) {
                        navigator.clipboard.writeText(url).then(function () {
                            var t = b.innerHTML; b.textContent = 'Link copiado!';
                            setTimeout(function () { b.innerHTML = t; }, 1800);
                        });
                    }
                });
            });

            // Link compartilhado (?v=chave) abre o vídeo direto
            var aberto = <?= json_encode($videoAberto) ?>;
            if (aberto) {
                var alvo = document.querySelector('[data-abrir="' + aberto + '"]');
                if (alvo) { abrir(alvo); }
            }
        })();
    </script>

    <?php require_once __DIR__ . '/../app/Includes/latido.php'; ?>
</body>

</html>
