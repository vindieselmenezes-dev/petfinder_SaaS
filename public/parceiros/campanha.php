<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Campanha / evento / doação
 * ==========================================================
 * Página de detalhe da ação de um parceiro, com o formulário
 * "quero ajudar" (doar, ser voluntário ou divulgar).
 *
 * O registro de apoio NÃO movimenta dinheiro: ele só avisa o
 * parceiro que alguém quer ajudar. Quem confirma o recebimento é
 * o próprio parceiro, pelo painel.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

$campanhaModel = new Campanha();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Administrador pode pré-visualizar uma publicação ainda pendente de
// moderação (link "Ver publicação" em admin_campanhas.php); qualquer
// outra pessoa só vê o que já está público (ativa + aprovada).
$apenasPublicas = !Auth::ehAdministrador();
$campanha = $id > 0 ? $campanhaModel->buscarPorId($id, $apenasPublicas) : null;

// --- Registro de apoio ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $campanha !== null) {

    Middleware::exigirCsrfValido();

    $contato = trim($_POST['contato'] ?? '');

    // Visitante deslogado precisa deixar um contato, senão o parceiro
    // não tem como responder.
    if (!Auth::check() && $contato === '') {
        Flash::erro('Informe um e-mail ou telefone para o parceiro entrar em contato.');
    } else {
        $registrado = $campanhaModel->registrarApoio($id, Auth::id(), [
            'tipo'     => $_POST['tipo_apoio'] ?? 'doacao',
            'valor'    => str_replace(',', '.', trim($_POST['valor'] ?? '')),
            'mensagem' => trim($_POST['mensagem'] ?? ''),
            'contato'  => $contato !== '' ? $contato : Auth::email(),
        ]);

        if ($registrado) {

            // Avisa o responsável pelo parceiro que chegou gente querendo ajudar.
            $rotulos = [
                'doacao'       => 'quer doar',
                'voluntariado' => 'quer ser voluntário',
                'divulgacao'   => 'quer divulgar',
                'duvida'       => 'tem uma dúvida',
            ];
            $acao = $rotulos[$_POST['tipo_apoio'] ?? ''] ?? 'quer ajudar';

            (new Notificacao())->criar(
                (int) $campanha['parceiro_usuario_id'],
                'Novo apoio em "' . $campanha['titulo'] . '"',
                (Auth::check() ? Auth::nome() : 'Um visitante') . ' ' . $acao . '. Veja o contato no painel.',
                'Sistema',
                Url::pagina('apoios_campanha.php') . '?id=' . $id
            );

            Flash::sucesso('Obrigado! O parceiro foi avisado e vai entrar em contato com você.');
        } else {
            Flash::erro('Não foi possível registrar seu apoio agora. Tente novamente.');
        }
    }

    header('Location: ' . Url::pagina('campanha.php') . '?id=' . $id);
    exit;
}

if ($campanha !== null) {
    $campanhaModel->registrarVisualizacao($id);
}

$imagem = $campanha
    ? Foto::url($campanha['imagem'] ?? null, 'campanhas', 'img/parceiros/parceiro03.jpg')
    : '';

$percentual = $campanha
    ? Campanha::percentualMeta(
        $campanha['meta_valor'] !== null ? (float) $campanha['meta_valor'] : null,
        (float) $campanha['valor_arrecadado']
    )
    : null;

// Chave PIX da campanha, com a do parceiro como reserva.
$pix = $campanha ? ($campanha['chave_pix'] ?: $campanha['parceiro_pix']) : null;

// URL completa (com domínio) para compartilhar nas redes — precisa ser
// absoluta, senão o link não abre certo fora do próprio site.
$urlCampanha = $campanha
    ? Url::absoluta(Url::pagina('campanha.php') . '?id=' . (int) $campanha['id'])
    : '';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($campanha['titulo'] ?? 'Campanha não encontrada') ?> - PetFinder Brasil</title>

    <?php if ($campanha !== null && !empty($campanha['resumo'])): ?>
        <meta name="description" content="<?= htmlspecialchars($campanha['resumo']) ?>">
    <?php endif; ?>

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

        <?php Flash::render(); ?>

        <?php if ($campanha === null): ?>

            <div class="text-center py-5">
                <div class="fs-1">🐾</div>
                <h1 class="fw-bold">Ação não encontrada</h1>
                <p class="text-muted">Ela pode ter sido encerrada ou removida pelo parceiro.</p>
                <a href="<?= Url::pagina('parceiros.php') ?>" class="btn btn-primary">Ver campanhas ativas</a>
            </div>

        <?php else: ?>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= Url::pagina('parceiros.php') ?>">Parceiros</a></li>
                    <li class="breadcrumb-item">
                        <a href="<?= Url::pagina('parceiro.php') ?>?id=<?= (int) $campanha['parceiro_id'] ?>">
                            <?= htmlspecialchars($campanha['parceiro_nome']) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= htmlspecialchars($campanha['titulo']) ?>
                    </li>
                </ol>
            </nav>

            <div class="row g-4">

                <div class="col-lg-8">

                    <img src="<?= htmlspecialchars($imagem) ?>" class="img-fluid rounded-4 mb-4 w-100"
                        style="max-height:380px; object-fit:cover;"
                        alt="<?= htmlspecialchars($campanha['titulo']) ?>">

                    <span class="badge bg-dark mb-2">
                        <?= Campanha::iconeTipo($campanha['tipo']) ?>
                        <?= htmlspecialchars(Campanha::rotuloTipo($campanha['tipo'])) ?>
                    </span>

                    <h1 class="fw-bold"><?= htmlspecialchars($campanha['titulo']) ?></h1>

                    <p class="text-muted">
                        por
                        <a href="<?= Url::pagina('parceiro.php') ?>?id=<?= (int) $campanha['parceiro_id'] ?>">
                            <?= htmlspecialchars($campanha['parceiro_nome']) ?>
                        </a>
                        <?php if (!empty($campanha['parceiro_cidade'])): ?>
                            · <?= htmlspecialchars($campanha['parceiro_cidade']) ?><?= !empty($campanha['parceiro_estado']) ? '/' . htmlspecialchars($campanha['parceiro_estado']) : '' ?>
                        <?php endif; ?>
                    </p>

                    <?php if (!empty($campanha['resumo'])): ?>
                        <p class="lead"><?= htmlspecialchars($campanha['resumo']) ?></p>
                    <?php endif; ?>

                    <div class="border rounded-3 p-3 mb-4 bg-light">
                        <p class="small fw-bold mb-2">
                            <i class="bi bi-megaphone-fill"></i> Compartilhe essa ação
                        </p>
                        <?= Compartilhamento::renderizarBloco($campanha['titulo'], $urlCampanha) ?>
                    </div>

                    <ul class="list-group list-group-flush mb-4">

                        <?php if (!empty($campanha['data_inicio'])): ?>
                            <li class="list-group-item">
                                <i class="bi bi-calendar-event"></i>
                                <strong>Início:</strong>
                                <?= date('d/m/Y H:i', strtotime($campanha['data_inicio'])) ?>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($campanha['data_fim'])): ?>
                            <li class="list-group-item">
                                <i class="bi bi-hourglass-split"></i>
                                <strong>Encerra em:</strong>
                                <?= date('d/m/Y', strtotime($campanha['data_fim'])) ?>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($campanha['local_evento'])): ?>
                            <li class="list-group-item">
                                <i class="bi bi-geo-alt"></i>
                                <strong>Local:</strong>
                                <?= htmlspecialchars($campanha['local_evento']) ?>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($campanha['itens_desejados'])): ?>
                            <li class="list-group-item">
                                <i class="bi bi-box-seam"></i>
                                <strong>Itens necessários:</strong>
                                <?= htmlspecialchars($campanha['itens_desejados']) ?>
                            </li>
                        <?php endif; ?>

                    </ul>

                    <?php if (!empty($campanha['descricao'])): ?>
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h2 class="h5 fw-bold">Detalhes</h2>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($campanha['descricao'])) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- ============ AJUDAR ============ -->

                <div class="col-lg-4">

                    <div class="card shadow-sm border-success mb-4">

                        <div class="card-body">

                            <?php if ($percentual !== null): ?>

                                <h2 class="h5 fw-bold mb-3">
                                    R$ <?= number_format((float) $campanha['valor_arrecadado'], 2, ',', '.') ?>
                                    <small class="text-muted fw-normal">
                                        de R$ <?= number_format((float) $campanha['meta_valor'], 2, ',', '.') ?>
                                    </small>
                                </h2>

                                <div class="progress mb-3" style="height:12px;">
                                    <div class="progress-bar bg-success" style="width: <?= $percentual ?>%;"
                                        role="progressbar" aria-valuenow="<?= $percentual ?>" aria-valuemin="0"
                                        aria-valuemax="100"><?= $percentual ?>%</div>
                                </div>

                            <?php endif; ?>

                            <?php if (!empty($pix)): ?>

                                <p class="small text-muted mb-1">Chave PIX do parceiro</p>

                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="chavePix" readonly
                                        value="<?= htmlspecialchars($pix) ?>">
                                    <button class="btn btn-outline-secondary" type="button" id="copiarPix">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>

                            <?php endif; ?>

                            <?php
                            $linkExterno = $campanha['link_externo'] ?: $campanha['parceiro_link_doacao'];
                            ?>

                            <?php if (!empty($linkExterno)): ?>
                                <a href="<?= htmlspecialchars($linkExterno) ?>" target="_blank" rel="noopener"
                                    class="btn btn-success w-100 mb-3">
                                    <i class="bi bi-box-arrow-up-right"></i> Doar / participar
                                </a>
                            <?php endif; ?>

                            <p class="small text-muted mb-0">
                                O valor vai direto para o parceiro — o PetFinder não recebe nem
                                intermedia doações.
                            </p>

                        </div>

                    </div>

                    <!-- Formulário de apoio -->

                    <div class="card shadow-sm">

                        <div class="card-header fw-bold">💚 Quero ajudar</div>

                        <div class="card-body">

                            <form method="POST">

                                <?= Csrf::campoHtml() ?>

                                <div class="mb-3">
                                    <label class="form-label small" for="tipo_apoio">Como você quer ajudar?</label>
                                    <select name="tipo_apoio" id="tipo_apoio" class="form-select" required>
                                        <option value="doacao">Vou doar (dinheiro ou itens)</option>
                                        <option value="voluntariado">Quero ser voluntário</option>
                                        <option value="divulgacao">Posso divulgar</option>
                                        <option value="duvida">Tenho uma dúvida</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small" for="valor">Valor pretendido (opcional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="text" inputmode="decimal" name="valor" id="valor"
                                            class="form-control" placeholder="0,00">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small" for="contato">
                                        Contato <?= Auth::check() ? '(opcional)' : '(obrigatório)' ?>
                                    </label>
                                    <input type="text" name="contato" id="contato" class="form-control"
                                        placeholder="E-mail ou WhatsApp"
                                        value="<?= htmlspecialchars(Auth::check() ? Auth::email() : '') ?>"
                                        <?= Auth::check() ? '' : 'required' ?>>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small" for="mensagem">Mensagem (opcional)</label>
                                    <textarea name="mensagem" id="mensagem" class="form-control" rows="3"
                                        placeholder="Ex: posso levar 10kg de ração no sábado"></textarea>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    Enviar meu apoio
                                </button>

                            </form>

                        </div>

                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="bi bi-eye"></i>
                            <?= (int) $campanha['visualizacoes'] ?> visualizações
                        </small>
                    </div>

                </div>

            </div>

        <?php endif; ?>

    </main>

    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            © <?= date('Y') ?> PetFinder Brasil ·
            <a href="<?= Url::pagina('parceiros.php') ?>" class="text-light">Ver todas as campanhas</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= Url::asset('js/compartilhamento.js') ?>"></script>

    <script>
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
