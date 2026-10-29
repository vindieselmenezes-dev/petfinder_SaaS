<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';



require_once __DIR__ . '/../app/Models/BlogComment.php';
require_once __DIR__ . '/../app/Models/BlogShare.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';
require_once __DIR__ . '/../app/Helpers/Compartilhamento.php';

$blogComment = new BlogComment();
$blogShare = new BlogShare();

$postId = isset($_GET['post']) ? (int) $_GET['post'] : 1;
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // O registro de "compartilhou" é enviado em segundo plano pelo JavaScript
    // (ajax=1) e não precisa de página de volta: só um código de status.
    $ehAjax = (($_POST['ajax'] ?? '') === '1');

    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
        if ($ehAjax) {
            http_response_code(403);
            exit;
        }
        $mensagem = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';
    } elseif (!isset($_SESSION['usuario_id'])) {
        if ($ehAjax) {
            http_response_code(401);
            exit;
        }
        $mensagem = 'Você precisa estar logado para comentar ou compartilhar.';
        $tipoMensagem = 'erro';
    } else {
        $usuarioId = (int) $_SESSION['usuario_id'];
        $postId = isset($_POST['post']) ? (int) $_POST['post'] : $postId;

        if (!empty($_POST['comentario'])) {
            $comentario = trim($_POST['comentario']);
            if ($blogComment->salvar($postId, $usuarioId, $comentario)) {
                $mensagem = 'Comentário enviado com sucesso.';
                $tipoMensagem = 'sucesso';
            } else {
                $mensagem = 'Erro ao enviar comentário. Tente novamente.';
                $tipoMensagem = 'erro';
            }
        }

        if (!empty($_POST['rede_social'])) {
            $redeSocial = trim($_POST['rede_social']);
            $redesPermitidas = ['whatsapp', 'facebook', 'twitter', 'telegram', 'link', 'nativo'];

            // Só grava redes conhecidas e posts que existem (1 a 3).
            if (in_array($redeSocial, $redesPermitidas, true) && in_array($postId, [1, 2, 3], true)) {
                $blogShare->salvar($postId, $usuarioId, $redeSocial);
            }

            if ($ehAjax) {
                http_response_code(204);
                exit;
            }
        }
    }
}

$comentariosPost1 = $blogComment->listarPorPostId(1);
$comentariosPost2 = $blogComment->listarPorPostId(2);
$comentariosPost3 = $blogComment->listarPorPostId(3);

// Dados de cada post: usados nos botões de compartilhar e na prévia (Open Graph)
// que WhatsApp, Facebook e X mostram quando o link é colado.
$posts = [
    1 => [
        'titulo' => 'Como escolher a melhor ração?',
        'resumo' => 'Escolher a ração certa é o primeiro passo para garantir saúde e longevidade ao seu pet.',
        'imagem' => 'img/blog/blog01.jpg',
    ],
    2 => [
        'titulo' => 'Vacinas obrigatórias',
        'resumo' => 'A proteção contra doenças começa com o calendário de vacinação correto.',
        'imagem' => 'img/blog/blog02.jpg',
    ],
    3 => [
        'titulo' => 'Como viajar com seu pet',
        'resumo' => 'Planejar a viagem faz toda a diferença para o conforto do seu animal de estimação.',
        'imagem' => 'img/blog/blog03.jpg',
    ],
];

function esc(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Link completo (com domínio) de um post, com a âncora para abrir já nele.
 */
function urlDoPost(int $id): string
{
    return Url::absoluta(Url::pagina('blog.php') . '?post=' . $id) . '#post' . $id;
}

/**
 * Botões "Compartilhar" de um post: WhatsApp, Facebook, X, Telegram e copiar
 * link, abertos para qualquer visitante. Quem está logado também tem o
 * compartilhamento registrado (tabela blog_shares) em segundo plano.
 */
function botoesCompartilhar(int $id, array $posts): string
{
    $post = $posts[$id];
    $url = urlDoPost($id);
    $texto = 'Confira: ' . $post['titulo'];

    $whatsapp = esc(Compartilhamento::linkWhatsapp($texto, $url));
    $facebook = esc(Compartilhamento::linkFacebook($url));
    $x = esc(Compartilhamento::linkTwitter($texto, $url));
    $telegram = esc(Compartilhamento::linkTelegram($texto, $url));

    $csrf = isset($_SESSION['usuario_id'])
        ? ' data-csrf="' . esc(Csrf::gerarToken()) . '"'
        : '';

    return '
            <div class="mt-4" data-compartilhar-post data-post="' . $id . '"
                data-titulo="' . esc($post['titulo']) . '" data-url="' . esc($url) . '"' . $csrf . '>
                <strong>Compartilhar:</strong>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <a href="' . $whatsapp . '" target="_blank" rel="noopener" data-rede="whatsapp"
                        class="btn btn-outline-success btn-sm" title="Compartilhar no WhatsApp">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                    <a href="' . $facebook . '" target="_blank" rel="noopener" data-rede="facebook"
                        class="btn btn-outline-primary btn-sm" title="Compartilhar no Facebook">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                    <a href="' . $x . '" target="_blank" rel="noopener" data-rede="twitter"
                        class="btn btn-outline-dark btn-sm" title="Compartilhar no X">
                        <i class="bi bi-twitter-x"></i> X
                    </a>
                    <a href="' . $telegram . '" target="_blank" rel="noopener" data-rede="telegram"
                        class="btn btn-outline-info btn-sm" title="Compartilhar no Telegram">
                        <i class="bi bi-telegram"></i> Telegram
                    </a>
                    <button type="button" data-rede="link" data-copiar-texto="' . esc($url) . '"
                        data-copiar-com-texto class="btn btn-outline-secondary btn-sm" title="Copiar link">
                        <i class="bi bi-link-45deg"></i> Copiar link
                    </button>
                    <button type="button" data-compartilhar-nativo
                        class="btn btn-outline-secondary btn-sm d-none" title="Mais opções">
                        <i class="bi bi-share"></i> Mais
                    </button>
                </div>
            </div>';
}

// Post usado na prévia quando o link é colado numa rede social.
$postPrevia = isset($posts[$postId]) ? $postId : 1;
$previaTitulo = isset($_GET['post']) ? $posts[$postPrevia]['titulo'] . ' - Blog EcoSistemPet' : 'Blog - EcoSistemPet';
$previaUrl = Url::absoluta(Url::pagina('blog.php') . '?post=' . $postPrevia);
$previaImagem = Url::absoluta(Url::asset($posts[$postPrevia]['imagem']));

function exibirComentarios(array $comentarios): string
{
    if (empty($comentarios)) {
        return '<p class="text-muted">Nenhum comentário ainda. Seja o primeiro a comentar!</p>';
    }

    $html = '';
    foreach ($comentarios as $comentario) {
        $nomeCompleto = htmlspecialchars($comentario['nome'] . ' ' . $comentario['sobrenome']);
        $conteudo = nl2br(htmlspecialchars($comentario['comentario']));
        $data = date('d/m/Y H:i', strtotime($comentario['criado_em']));

        $html .= "<div class='mb-4 p-3 border rounded'>"
            . "<strong>{$nomeCompleto}</strong> <small class='text-muted'>em {$data}</small>"
            . "<p class='mb-0 mt-2'>{$conteudo}</p>"
            . "</div>";
    }

    return $html;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= esc($previaTitulo) ?></title>
    <meta name="description" content="<?= esc($posts[$postPrevia]['resumo']) ?>">

    <!-- PRÉVIA AO COMPARTILHAR (WhatsApp, Facebook, X, Telegram) -->
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="EcoSistemPet">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:title" content="<?= esc($posts[$postPrevia]['titulo']) ?>">
    <meta property="og:description" content="<?= esc($posts[$postPrevia]['resumo']) ?>">
    <meta property="og:image" content="<?= esc($previaImagem) ?>">
    <meta property="og:url" content="<?= esc($previaUrl) ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= esc($posts[$postPrevia]['titulo']) ?>">
    <meta name="twitter:description" content="<?= esc($posts[$postPrevia]['resumo']) ?>">
    <meta name="twitter:image" content="<?= esc($previaImagem) ?>">

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" integrity="sha384-CK2SzKma4jA5H/MXDUU7i1TqZlCFaD4T01vtyDFvPlD97JQyS+IsSh1nI2EFbpyk" crossorigin="anonymous">

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="← Voltar para a página anterior">← Voltar</button></div>

    <header class="border-bottom py-3 mb-4">

        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <a href="../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../assets/img/logo.png" alt="EcoSistemPet" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">EcoSistemPet</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>

            <div class="d-flex gap-2 align-items-center">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <span class="badge bg-success text-white">Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
                    <a href="logout.php" class="btn btn-outline-secondary">Sair</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Entrar</a>
                    <a href="cadastro.php" class="btn btn-outline-primary">Cadastrar</a>
                <?php endif; ?>
            </div>

        </div>

    </header>

    <main class="container mb-5">

        <div class="row mb-4">
            <div class="col-lg-8">
                <h1 class="fw-bold">Blog EcoSistemPet</h1>
                <p class="text-muted">Dicas, cuidados e informações práticas para cuidar melhor do seu pet.</p>
            </div>
        </div>

        <?php if ($mensagem !== ''): ?>
            <div class="alert <?= $tipoMensagem === 'sucesso' ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">

            <div class="col-lg-4">
                <div class="card h-100 shadow-sm">
                    <img src="../assets/img/blog/blog01.jpg" class="card-img-top" alt="Como escolher a melhor ração">
                    <div class="card-body">
                        <h5>Como escolher a melhor ração?</h5>
                        <p>Descubra como alimentar corretamente seu melhor amigo com escolhas saudáveis e seguras.</p>
                        <a href="blog.php?post=1#post1" class="btn btn-outline-primary">Ler Artigo</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 shadow-sm">
                    <img src="../assets/img/blog/blog02.jpg" class="card-img-top" alt="Vacinas obrigatórias">
                    <div class="card-body">
                        <h5>Vacinas obrigatórias</h5>
                        <p>Veja quais vacinas são indispensáveis e como manter as proteções do seu pet sempre em dia.
                        </p>
                        <a href="blog.php?post=2#post2" class="btn btn-outline-primary">Ler Artigo</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 shadow-sm">
                    <img src="../assets/img/blog/blog03.jpg" class="card-img-top" alt="Como viajar com seu pet">
                    <div class="card-body">
                        <h5>Como viajar com seu pet</h5>
                        <p>Dicas para uma viagem segura e tranquila, desde a preparação até a chegada ao destino.</p>
                        <a href="blog.php?post=3#post3" class="btn btn-outline-primary">Ler Artigo</a>
                    </div>
                </div>
            </div>

        </div>

        <section id="post1" class="mb-5">
            <h2 class="fw-bold">Como escolher a melhor ração?</h2>
            <p class="text-muted">Escolher a ração certa é o primeiro passo para garantir saúde e longevidade ao seu
                pet.</p>
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <img src="../assets/img/blog/blog01.jpg" class="img-fluid rounded shadow-sm"
                        alt="Como escolher a melhor ração">
                </div>
                <div class="col-lg-6">
                    <p>Uma boa ração deve alinhar o porte, a idade, a espécie e as necessidades específicas do animal.
                        Leia os rótulos, observe os ingredientes e prefira produtos de qualidade. Alguns pontos
                        importantes:</p>
                    <ul>
                        <li>Proteínas de origem animal são essenciais para a nutrição.</li>
                        <li>Carboidratos complexos e fibras ajudam na digestão.</li>
                        <li>Vitaminas e minerais mantêm a imunidade e vitalidade.</li>
                    </ul>
                    <p>Converse com o veterinário para adaptar a alimentação à saúde e ao estilo de vida do seu pet.</p>
                </div>
            </div>

            <?= botoesCompartilhar(1, $posts) ?>

            <div class="mt-4">
                <h5>Comentários</h5>
                <?= exibirComentarios($comentariosPost1) ?>

                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <form method="POST" class="mt-4">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="1">
                        <div class="mb-3">
                            <label for="comentario_post1" class="form-label">Deixe seu comentário</label>
                            <textarea id="comentario_post1" name="comentario" class="form-control" rows="4"
                                required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar comentário</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Faça login para comentar.</p>
                <?php endif; ?>
            </div>

            <a href="#" class="btn btn-secondary mt-3">Voltar ao topo</a>
        </section>

        <section id="post2" class="mb-5">
            <h2 class="fw-bold">Vacinas obrigatórias</h2>
            <p class="text-muted">A proteção contra doenças começa com o calendário de vacinação correto.</p>
            <div class="row g-4 align-items-center flex-lg-row-reverse">
                <div class="col-lg-6">
                    <img src="../assets/img/blog/blog02.jpg" class="img-fluid rounded shadow-sm"
                        alt="Vacinas obrigatórias">
                </div>
                <div class="col-lg-6">
                    <p>Vacinar seu pet é fundamental para prevenir doenças graves. As principais vacinas para cães e
                        gatos incluem:</p>
                    <ul>
                        <li>V8/V10 para cães: proteção contra parvovirose, cinomose e outras doenças.</li>
                        <li>V4/V5 para gatos: proteção contra panleucopenia e calicivirose.</li>
                        <li>Antirrábica: obrigatória por lei, protege contra a raiva.</li>
                    </ul>
                    <p>Mantenha o histórico de vacinação sempre atualizado e consulte um médico veterinário para
                        reforços e imunizações adicionais.</p>
                </div>
            </div>

            <?= botoesCompartilhar(2, $posts) ?>

            <div class="mt-4">
                <h5>Comentários</h5>
                <?= exibirComentarios($comentariosPost2) ?>

                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <form method="POST" class="mt-4">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="2">
                        <div class="mb-3">
                            <label for="comentario_post2" class="form-label">Deixe seu comentário</label>
                            <textarea id="comentario_post2" name="comentario" class="form-control" rows="4"
                                required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar comentário</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Faça login para comentar.</p>
                <?php endif; ?>
            </div>

            <a href="#" class="btn btn-secondary mt-3">Voltar ao topo</a>
        </section>

        <section id="post3" class="mb-5">
            <h2 class="fw-bold">Como viajar com seu pet</h2>
            <p class="text-muted">Planejar a viagem faz toda a diferença para o conforto do seu animal de estimação.</p>
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <img src="../assets/img/blog/blog03.jpg" class="img-fluid rounded shadow-sm"
                        alt="Como viajar com seu pet">
                </div>
                <div class="col-lg-6">
                    <p>Viajar com pets exige preparação e atenção aos detalhes. Algumas dicas valiosas:</p>
                    <ul>
                        <li>Planeje paradas para hidratação e descanso.</li>
                        <li>Use transportadores confortáveis e seguros.</li>
                        <li>Verifique a necessidade de documentação e vacinas atualizadas.</li>
                    </ul>
                    <p>Com o mínimo de organização, a viagem será mais tranquila para você e para o seu pet.</p>
                </div>
            </div>

            <?= botoesCompartilhar(3, $posts) ?>

            <div class="mt-4">
                <h5>Comentários</h5>
                <?= exibirComentarios($comentariosPost3) ?>

                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <form method="POST" class="mt-4">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="3">
                        <div class="mb-3">
                            <label for="comentario_post3" class="form-label">Deixe seu comentário</label>
                            <textarea id="comentario_post3" name="comentario" class="form-control" rows="4"
                                required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar comentário</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Faça login para comentar.</p>
                <?php endif; ?>
            </div>

            <a href="#" class="btn btn-secondary mt-3">Voltar ao topo</a>
        </section>

    </main>

    <script src="<?= Url::asset('js/compartilhamento.js') ?>"></script>
    <script>
        // Compartilhar: registra o clique (só de quem está logado) e mostra o
        // botão "Mais" quando o aparelho tem o menu de compartilhar nativo.
        (function () {
            function registrar(bloco, rede) {
                var token = bloco.getAttribute('data-csrf');
                if (!token) { return; }

                var dados = new FormData();
                dados.append('csrf_token', token);
                dados.append('post', bloco.getAttribute('data-post'));
                dados.append('rede_social', rede);
                dados.append('ajax', '1');

                if (navigator.sendBeacon) {
                    navigator.sendBeacon(window.location.pathname, dados);
                } else {
                    fetch(window.location.pathname, { method: 'POST', body: dados, keepalive: true, credentials: 'same-origin' });
                }
            }

            document.addEventListener('click', function (evento) {
                var botao = evento.target.closest('[data-rede]');
                var bloco = botao && botao.closest('[data-compartilhar-post]');
                if (bloco) { registrar(bloco, botao.getAttribute('data-rede')); }
            });

            if (navigator.share) {
                document.querySelectorAll('[data-compartilhar-nativo]').forEach(function (botao) {
                    botao.classList.remove('d-none');
                    botao.addEventListener('click', function () {
                        var bloco = botao.closest('[data-compartilhar-post]');
                        navigator.share({
                            title: bloco.getAttribute('data-titulo'),
                            text: 'Confira: ' + bloco.getAttribute('data-titulo'),
                            url: bloco.getAttribute('data-url')
                        }).then(function () {
                            registrar(bloco, 'nativo');
                        }).catch(function () { /* pessoa cancelou */ });
                    });
                });
            }
        })();
    </script>

</body>

</html>
