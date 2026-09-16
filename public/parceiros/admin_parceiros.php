<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Moderação de parceiros (admin)
 * ==========================================================
 * Aprova, recusa, inativa e destaca as ONGs/empresas que pediram
 * parceria. Só parceiros aprovados aparecem no site.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirTipo(Auth::TIPO_ADMINISTRADOR);

$parceiroModel = new Parceiro();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Middleware::exigirCsrfValido();

    $parceiroId = isset($_POST['parceiro_id']) ? (int) $_POST['parceiro_id'] : 0;

    if ($parceiroId > 0) {

        if (($_POST['acao'] ?? '') === 'destaque') {

            $ligar = !empty($_POST['destaque']);
            $parceiroModel->alternarDestaque($parceiroId, $ligar);
            Flash::sucesso($ligar ? 'Parceiro colocado em destaque.' : 'Destaque removido.');

        } else {

            $status = $_POST['status'] ?? '';
            $observacao = trim($_POST['observacao'] ?? '');

            if ($parceiroModel->alterarStatus($parceiroId, $status, $observacao)) {

                // Avisa o responsável — principalmente quando a parceria é
                // aprovada, pra ele já começar a publicar campanhas.
                $parceiro = $parceiroModel->buscarPorId($parceiroId, false);

                if ($parceiro !== null) {

                    $mensagens = [
                        'aprovado' => 'Sua parceria foi aprovada! Já pode publicar campanhas, eventos e doações.',
                        'recusado' => 'Sua solicitação de parceria não foi aprovada.',
                        'inativo'  => 'Sua parceria foi inativada e não aparece mais no site.',
                        'pendente' => 'Sua parceria voltou para análise.',
                    ];

                    $texto = $mensagens[$status] ?? 'O status da sua parceria mudou.';

                    if ($observacao !== '') {
                        $texto .= ' Observação: ' . $observacao;
                    }

                    (new Notificacao())->criar(
                        (int) $parceiro['usuario_id'],
                        'Parceria: ' . $parceiro['nome'],
                        $texto,
                        'Sistema',
                        Url::pagina('painel_parceiro.php')
                    );
                }

                Flash::sucesso('Status do parceiro atualizado.');
            } else {
                Flash::erro('Status inválido.');
            }
        }
    }

    header('Location: ' . Url::pagina('admin_parceiros.php'));
    exit;
}

$filtroStatus = $_GET['status'] ?? '';
if (!in_array($filtroStatus, ['pendente', 'aprovado', 'recusado', 'inativo'], true)) {
    $filtroStatus = '';
}

$parceiros = $parceiroModel->listarParaModeracao($filtroStatus);

$tituloPagina = 'Moderação de parceiros';

require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';

?>

<main class="container" style="margin-top:100px; margin-left:240px; padding:20px; max-width:1100px;">

    <div style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">

        <h1 class="fw-bold" style="font-size:26px; margin-bottom:6px;">🛡️ Moderação de parceiros</h1>
        <p style="color:#7f8c8d;">
            ONGs e empresas que pediram para fazer parte da área de parceiros.
        </p>

        <div class="btn-group mb-4">
            <a href="?status=" class="btn btn-<?= $filtroStatus === '' ? 'primary' : 'outline-primary' ?>">Todos</a>
            <a href="?status=pendente"
                class="btn btn-<?= $filtroStatus === 'pendente' ? 'primary' : 'outline-primary' ?>">Pendentes</a>
            <a href="?status=aprovado"
                class="btn btn-<?= $filtroStatus === 'aprovado' ? 'primary' : 'outline-primary' ?>">Aprovados</a>
            <a href="?status=recusado"
                class="btn btn-<?= $filtroStatus === 'recusado' ? 'primary' : 'outline-primary' ?>">Recusados</a>
            <a href="?status=inativo"
                class="btn btn-<?= $filtroStatus === 'inativo' ? 'primary' : 'outline-primary' ?>">Inativos</a>
        </div>

        <?php if (count($parceiros) === 0): ?>

            <div class="text-center border rounded-3 py-5 text-muted">
                Nenhum parceiro nesta lista.
            </div>

        <?php else: ?>

            <?php foreach ($parceiros as $parceiro): ?>

                <?php
                $parceiroId = (int) $parceiro['id'];
                $logo = Foto::url($parceiro['logo'] ?? null, 'parceiros', 'img/logo.png');
                ?>

                <div class="card shadow-sm mb-3">

                    <div class="card-body">

                        <div class="d-flex gap-3 flex-wrap align-items-start">

                            <img src="<?= htmlspecialchars($logo) ?>" alt="" class="rounded border"
                                style="width:64px; height:64px; object-fit:cover;">

                            <div class="flex-grow-1" style="min-width:260px;">

                                <h2 class="h5 fw-bold mb-1">
                                    <?= htmlspecialchars($parceiro['nome']) ?>
                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars(Parceiro::rotuloTipo($parceiro['tipo'])) ?>
                                    </span>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($parceiro['status']) ?></span>
                                </h2>

                                <p class="small text-muted mb-1">
                                    Responsável: <?= htmlspecialchars($parceiro['responsavel_nome']) ?>
                                    (<?= htmlspecialchars($parceiro['responsavel_email']) ?>)
                                    · Cadastro: <?= date('d/m/Y', strtotime($parceiro['criado_em'])) ?>
                                </p>

                                <?php if (!empty($parceiro['cidade'])): ?>
                                    <p class="small text-muted mb-1">
                                        📍 <?= htmlspecialchars($parceiro['cidade']) ?><?= !empty($parceiro['estado']) ? '/' . htmlspecialchars($parceiro['estado']) : '' ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($parceiro['descricao'])): ?>
                                    <p class="small mb-2"><?= nl2br(htmlspecialchars($parceiro['descricao'])) ?></p>
                                <?php endif; ?>

                                <?php if (!empty($parceiro['como_ajuda'])): ?>
                                    <p class="small mb-0">
                                        <strong>Como ajuda:</strong> <?= htmlspecialchars($parceiro['como_ajuda']) ?>
                                    </p>
                                <?php endif; ?>

                            </div>

                            <div style="min-width:280px;">

                                <form method="POST" class="mb-2">

                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="parceiro_id" value="<?= $parceiroId ?>">

                                    <input type="text" name="observacao" class="form-control form-control-sm mb-2"
                                        placeholder="Observação (aparece para o parceiro)"
                                        value="<?= htmlspecialchars($parceiro['observacao_admin'] ?? '') ?>">

                                    <div class="d-flex gap-1 flex-wrap">
                                        <button type="submit" name="status" value="aprovado"
                                            class="btn btn-success btn-sm">✅ Aprovar</button>
                                        <button type="submit" name="status" value="recusado"
                                            class="btn btn-danger btn-sm">❌ Recusar</button>
                                        <button type="submit" name="status" value="inativo"
                                            class="btn btn-secondary btn-sm">⏸️ Inativar</button>
                                    </div>

                                </form>

                                <form method="POST">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="acao" value="destaque">
                                    <input type="hidden" name="parceiro_id" value="<?= $parceiroId ?>">
                                    <input type="hidden" name="destaque" value="<?= empty($parceiro['destaque']) ? '1' : '0' ?>">
                                    <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                        <?= empty($parceiro['destaque']) ? '⭐ Colocar em destaque' : '☆ Remover destaque' ?>
                                    </button>
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
