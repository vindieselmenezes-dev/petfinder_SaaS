<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Mensagens de contato (admin)
 * ==========================================================
 * Lista as mensagens enviadas pelo formulário público de contato
 * (public/contato.php) e permite marcar o andamento de cada uma.
 */

require_once __DIR__ . '/../app/bootstrap.php';

Middleware::exigirTipo(Auth::TIPO_ADMINISTRADOR);

$mensagemModel = new MensagemContato();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Middleware::exigirCsrfValido();

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $status = $_POST['status'] ?? '';

    if ($id > 0 && $mensagemModel->alterarStatus($id, $status)) {
        Flash::sucesso('Status atualizado.');
    } else {
        Flash::erro('Não foi possível atualizar o status.');
    }

    header('Location: ' . Url::pagina('admin_contatos.php'));
    exit;
}

$filtroStatus = $_GET['status'] ?? '';
if (!in_array($filtroStatus, ['novo', 'em_andamento', 'respondido'], true)) {
    $filtroStatus = '';
}

$mensagens = $mensagemModel->listar($filtroStatus);

$rotulosStatus = [
    'novo'         => ['classe' => 'bg-warning text-dark', 'rotulo' => '🆕 Novo'],
    'em_andamento' => ['classe' => 'bg-info text-dark', 'rotulo' => '🔧 Em andamento'],
    'respondido'   => ['classe' => 'bg-success', 'rotulo' => '✅ Respondido'],
];

$tituloPagina = 'Mensagens de contato';

require_once __DIR__ . '/../app/Includes/header.php';
require_once __DIR__ . '/../app/Includes/menu.php';

?>

<main class="container" style="margin-top:100px; margin-left:240px; padding:20px; max-width:1100px;">

    <div style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">

        <h1 class="fw-bold" style="font-size:26px; margin-bottom:6px;">📬 Mensagens de contato</h1>
        <p style="color:#7f8c8d;">Enviadas pelo formulário público de contato do site.</p>

        <div class="btn-group mb-4">
            <a href="?status=" class="btn btn-<?= $filtroStatus === '' ? 'primary' : 'outline-primary' ?>">Todas</a>
            <a href="?status=novo"
                class="btn btn-<?= $filtroStatus === 'novo' ? 'primary' : 'outline-primary' ?>">Novas</a>
            <a href="?status=em_andamento"
                class="btn btn-<?= $filtroStatus === 'em_andamento' ? 'primary' : 'outline-primary' ?>">Em andamento</a>
            <a href="?status=respondido"
                class="btn btn-<?= $filtroStatus === 'respondido' ? 'primary' : 'outline-primary' ?>">Respondidas</a>
        </div>

        <?php if (count($mensagens) === 0): ?>

            <div class="text-center border rounded-3 py-5 text-muted">
                Nenhuma mensagem por aqui.
            </div>

        <?php else: ?>

            <?php foreach ($mensagens as $mensagem): ?>

                <?php $config = $rotulosStatus[$mensagem['status']] ?? $rotulosStatus['novo']; ?>

                <div class="card shadow-sm mb-3">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">

                            <div>
                                <h2 class="h6 fw-bold mb-1">
                                    <?= htmlspecialchars($mensagem['nome']) ?>
                                    <span class="badge <?= $config['classe'] ?>"><?= $config['rotulo'] ?></span>
                                </h2>
                                <p class="small text-muted mb-0">
                                    <a href="mailto:<?= htmlspecialchars($mensagem['email']) ?>">
                                        <?= htmlspecialchars($mensagem['email']) ?>
                                    </a>
                                    <?php if (!empty($mensagem['assunto'])): ?>
                                        · <?= htmlspecialchars($mensagem['assunto']) ?>
                                    <?php endif; ?>
                                    · <?= date('d/m/Y H:i', strtotime($mensagem['criado_em'])) ?>
                                </p>
                            </div>

                            <form method="POST" class="d-flex gap-1">
                                <?= Csrf::campoHtml() ?>
                                <input type="hidden" name="id" value="<?= (int) $mensagem['id'] ?>">
                                <select name="status" class="form-select form-select-sm" style="width:auto;"
                                    onchange="this.form.submit()">
                                    <option value="novo" <?= $mensagem['status'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                                    <option value="em_andamento" <?= $mensagem['status'] === 'em_andamento' ? 'selected' : '' ?>>
                                        Em andamento
                                    </option>
                                    <option value="respondido" <?= $mensagem['status'] === 'respondido' ? 'selected' : '' ?>>
                                        Respondido
                                    </option>
                                </select>
                            </form>

                        </div>

                        <p class="mb-0"><?= nl2br(htmlspecialchars($mensagem['mensagem'])) ?></p>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

<?php require_once __DIR__ . '/../app/Includes/footer.php'; ?>

</div>

</body>

</html>
