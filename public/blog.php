<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../app/Models/BlogComment.php';
require_once __DIR__ . '/../app/Models/BlogShare.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';

$blogComment = new BlogComment();
$blogShare = new BlogShare();

$postId = isset($_GET['post']) ? (int) $_GET['post'] : 1;
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
        $mensagem = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';
    } elseif (!isset($_SESSION['usuario_id'])) {
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
            $blogShare->salvar($postId, $usuarioId, $redeSocial);
        }
    }
}

$comentariosPost1 = $blogComment->listarPorPostId(1);
$comentariosPost2 = $blogComment->listarPorPostId(2);
$comentariosPost3 = $blogComment->listarPorPostId(3);

function exibirComentarios(array $comentarios): string
{
    if (count($comentarios) === 0) {
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

    <title>Blog - PetFinder Brasil</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <header class="border-bottom py-3 mb-4">

        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <a href="../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../assets/img/logo.png" alt="PetFinder Brasil" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
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
                <h1 class="fw-bold">Blog PetFinder</h1>
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

            <div class="mt-4">
                <strong>Compartilhar:</strong>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <form method="POST" class="d-inline">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="1">
                        <input type="hidden" name="rede_social" value="facebook">
                        <button type="submit" class="btn btn-primary btn-sm me-2">Facebook</button>
                    </form>
                    <form method="POST" class="d-inline">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="1">
                        <input type="hidden" name="rede_social" value="whatsapp">
                        <button type="submit" class="btn btn-success btn-sm me-2">WhatsApp</button>
                    </form>
                    <form method="POST" class="d-inline">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="1">
                        <input type="hidden" name="rede_social" value="twitter">
                        <button type="submit" class="btn btn-info btn-sm text-white">Twitter</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Faça login para compartilhar este post.</p>
                <?php endif; ?>
            </div>

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

            <div class="mt-4">
                <strong>Compartilhar:</strong>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <form method="POST" class="d-inline">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="2">
                        <input type="hidden" name="rede_social" value="facebook">
                        <button type="submit" class="btn btn-primary btn-sm me-2">Facebook</button>
                    </form>
                    <form method="POST" class="d-inline">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="2">
                        <input type="hidden" name="rede_social" value="whatsapp">
                        <button type="submit" class="btn btn-success btn-sm me-2">WhatsApp</button>
                    </form>
                    <form method="POST" class="d-inline">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="2">
                        <input type="hidden" name="rede_social" value="twitter">
                        <button type="submit" class="btn btn-info btn-sm text-white">Twitter</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Faça login para compartilhar este post.</p>
                <?php endif; ?>
            </div>

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

            <div class="mt-4">
                <strong>Compartilhar:</strong>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <form method="POST" class="d-inline">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="3">
                        <input type="hidden" name="rede_social" value="facebook">
                        <button type="submit" class="btn btn-primary btn-sm me-2">Facebook</button>
                    </form>
                    <form method="POST" class="d-inline">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="3">
                        <input type="hidden" name="rede_social" value="whatsapp">
                        <button type="submit" class="btn btn-success btn-sm me-2">WhatsApp</button>
                    </form>
                    <form method="POST" class="d-inline">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="post" value="3">
                        <input type="hidden" name="rede_social" value="twitter">
                        <button type="submit" class="btn btn-info btn-sm text-white">Twitter</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Faça login para compartilhar este post.</p>
                <?php endif; ?>
            </div>

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

</body>

</html>