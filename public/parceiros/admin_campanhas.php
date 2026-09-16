<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Moderação de campanhas (admin)
 * ==========================================================
 * Fila de aprovação das publicações dos parceiros (campanha, evento
 * ou doação), quando a chave `moderacao_campanhas_ativa` está ligada
 * em `configuracoes`. Com ela desligada, esta tela existe mas fica
 * sempre vazia — toda publicação nova já nasce aprovada.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirTipo(Auth::TIPO_ADMINISTRADOR);

$campanhaModel = new Campanha();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Middleware::exigirCsrfValido();

    $campanhaId = isset($_POST['campanha_id']) ? (int) $_POST['campanha_id'] : 0;
    $status = $_POST['status'] ?? '';
    $observacao = trim($_POST['observacao'] ?? '');

    if ($campanhaId > 0 && $campanhaModel->alterarStatusModeracao($campanhaId, $status, $observacao)) {

        $campanha = $campanhaModel->buscarPorId($campanhaId, false);

        if ($campanha !== null) {

            $mensagens = [
                'aprovada' => 'Sua publicação "' . $campanha['titulo'] . '" foi aprovada e já está visível no site.',
                'recusada' => 'Sua publicação "' . $campanha['titulo'] . '" não foi aprovada.',
                'pendente' => 'Sua publicação "' . $campanha['titulo'] . '" voltou para análise.',
            ];

            $texto = $mensagens[$status] ?? 'O status de moderação da sua publicação mudou.';

            if ($observacao !== '') {
                $texto .= ' Observação: ' . $observacao;
            }

            (new Notificacao())->criar(
                (int) $campanha['parceiro_usuario_id'],
                'Moderação de publicação',
                $texto,
                'Sistema',
                Url::pagina('painel_parceiro.php')
            );
        }

        Flash::sucesso('Status de moderação atualizado.');
    } else {
        Flash::erro('Não foi possível atualizar. Confira o status informado.');
    }

    header('Location: ' . Url::pagina('admin_campanhas.php') . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

$filtroStatus = $_GET['status'] ?? 'pendente';
if (!in_array($filtroStatus, ['pendente', 'aprovada', 'recusada'], true)) {
    $filtroStatus = '';
}

$campanhas = $campanhaModel->listarParaModeracao($filtroStatus);
$moderacaoAtiva = $campanhaModel->moderacaoAtiva();

$tituloPagina = 'Moderação de campanhas';

require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';

?>

<main class="container" style="margin-top:100px; margin-left:240px; padding:20px; max-width:1100px;">

    <div style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">

        <h1 class="fw-bold" style="font-size:26px; margin-bottom:6px;">🛡️ Moderação de campanhas</h1>
        <p style="color:#7f8c8d;">
            Campanhas, eventos e doações publicados pelos parceiros.
        </p>

        <?php if (!$moderacaoAtiva): ?>
            <div class="alert alert-secondary">
                A moderação por campanha está <strong>desligada</strong>: toda publicação nova já entra aprovada
                automaticamente, e esta fila deve ficar sempre vazia. Para exigir aprovação, ligue em
                <a href="<?= Url::pagina('admin_configuracoes.php') ?>">Configurações Gerais</a>.
            </div>
        <?php endif; ?>

        <div class="btn-group mb-4">
            <a href="?status=pendente"
                class="btn btn-<?= $filtroStatus === 'pendente' ? 'primary' : 'outline-primary' ?>">Pendentes</a>
            <a href="?status=aprovada"
                class="btn btn-<?= $filtroStatus === 'aprovada' ? 'primary' : 'outline-primary' ?>">Aprovadas</a>
            <a href="?status=recusada"
                class="btn btn-<?= $filtroStatus === 'recusada' ? 'primary' : 'outline-primary' ?>">Recusadas</a>
            <a href="?status=" class="btn btn-<?= $filtroStatus === '' ? 'primary' : 'outline-primary' ?>">Todas</a>
        </div>

        <?php if (count($campanhas) === 0): ?>

            <div class="text-center border rounded-3 py-5 text-muted">
                Nenhuma publicação nesta lista.
            </div>

        <?php else: ?>

            <?php foreach ($campanhas as $campanha): ?>

                <?php $campanhaId = (int) $campanha['id']; ?>

                <div class="card shadow-sm mb-3">

                    <div class="card-body">

                        <div class="d-flex gap-3 flex-wrap align-items-start">

                            <div class="flex-grow-1" style="min-width:260px;">

                                <h2 class="h5 fw-bold mb-1">
                                    <?= Campanha::iconeTipo($campanha['tipo']) ?>
                                    <?= htmlspecialchars($campanha['titulo']) ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($campanha['status_moderacao']) ?></span>
                                </h2>

                                <p class="small text-muted mb-1">
                                    Parceiro: <?= htmlspecialchars($campanha['parceiro_nome']) ?>
                                    · Publicada em <?= date('d/m/Y', strtotime($campanha['criado_em'])) ?>
                                </p>

                                <?php if (!empty($campanha['resumo'])): ?>
                                    <p class="small mb-2"><?= htmlspecialchars($campanha['resumo']) ?></p>
                                <?php endif; ?>

                                <?php if (!empty($campanha['descricao'])): ?>
                                    <p class="small mb-0"><?= nl2br(htmlspecialchars($campanha['descricao'])) ?></p>
                                <?php endif; ?>

                                <p class="small mt-2">
                                    <a href="<?= Url::pagina('campanha.php') ?>?id=<?= $campanhaId ?>" target="_blank">
                                        Ver publicação →
                                    </a>
                                </p>

                            </div>

                            <div style="min-width:280px;">

                                <form method="POST">

                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="campanha_id" value="<?= $campanhaId ?>">

                                    <input type="text" name="observacao" class="form-control form-control-sm mb-2"
                                        placeholder="Observação (aparece para o parceiro)"
                                        value="<?= htmlspecialchars($campanha['observacao_moderacao'] ?? '') ?>">

                                    <div class="d-flex gap-1 flex-wrap">
                                        <button type="submit" name="status" value="aprovada"
                                            class="btn btn-success btn-sm">✅ Aprovar</button>
                                        <button type="submit" name="status" value="recusada"
                                            class="btn btn-danger btn-sm">❌ Recusar</button>
                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>

</div>

</body>

</html>
