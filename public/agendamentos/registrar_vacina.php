<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Helpers/EmpresaAcesso.php';

Middleware::exigirLogin();

$usuarioId = (int) Auth::id();
$empresaId = (int) ($_GET['empresa_id'] ?? $_POST['empresa_id'] ?? 0);
$petId = (int) ($_GET['pet_id'] ?? $_POST['pet_id'] ?? 0);

$pdo = Database::conectar();

// Quem vacina é a equipe de saúde da clínica; atendente só agenda.
if (!EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId, ['proprietario', 'administrador', 'veterinario'])) {
    Flash::erro('Você não tem permissão para registrar vacinas nesta empresa.');
    Url::redirecionar(Url::pagina('dashboard.php'));
    exit;
}

$empresa = (new EmpresaController())->buscarPorId($empresaId);
$pet = (new PetController())->buscarPorId($petId);
$vacinaController = new PetVacinaController();
$voltar = Url::pagina('consultas_empresa.php') . '?empresa_id=' . $empresaId;

if (!$pet || !$empresa) {
    Flash::erro('Pet não encontrado.');
    Url::redirecionar($voltar);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Middleware::exigirCsrfValido();

    $erro = $vacinaController->registrarPelaClinica($pet, $empresaId, $_POST, $usuarioId, (string) $empresa['nome_fantasia']);

    if ($erro === null) {
        Flash::sucesso('Vacina registrada na carteira de ' . $pet['nome'] . '. O tutor foi avisado.');
        Url::redirecionar($voltar);
        exit;
    }
    Flash::erro($erro);
}

$quadro = $vacinaController->montarQuadro($petId);
$catalogo = $vacinaController->listarCatalogo();
$tituloPagina = 'Registrar vacina';

require_once '../../app/Includes/header.php';
require_once '../../app/Includes/menu.php';
?>

<main class="conteudo">
    <div class="container" style="max-width:720px; margin:0 auto;">

        <p><a href="<?= htmlspecialchars($voltar); ?>" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar para as consultas</a></p>

        <h1>💉 Registrar vacina</h1>
        <p style="color:#7f8c8d;">
            <strong><?= htmlspecialchars($pet['nome']); ?></strong>
            · <?= htmlspecialchars($empresa['nome_fantasia']); ?>
        </p>

        <?php Flash::render(); ?>

        <div class="record-card" style="margin-bottom:25px;">
            <strong>Situação atual</strong>
            <ul style="margin:8px 0 0; padding-left:18px; font-size:14px;">
                <?php foreach ($quadro as $l): ?>
                    <li>
                        <?= htmlspecialchars($l['vacina']); ?> —
                        <?= htmlspecialchars($l['situacao']); ?>
                        <?php if ($l['dose']): ?>(última: <?= date('d/m/Y', strtotime($l['dose']['data_aplicacao'])); ?>)<?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <form method="POST">
            <?= Csrf::campoHtml() ?>
            <input type="hidden" name="empresa_id" value="<?= $empresaId; ?>">
            <input type="hidden" name="pet_id" value="<?= $petId; ?>">

            <label for="vacina_id">Vacina</label>
            <select id="vacina_id" name="vacina_id" class="form-control" required>
                <option value="">Selecione…</option>
                <?php foreach ($catalogo as $v): ?>
                    <option value="<?= (int) $v['id']; ?>" data-dias="<?= (int) ($v['periodicidade_dias'] ?? 0); ?>">
                        <?= htmlspecialchars($v['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:10px;">
                <div style="flex:1; min-width:160px;">
                    <label for="data_aplicacao">Data da aplicação</label>
                    <input type="date" id="data_aplicacao" name="data_aplicacao" class="form-control"
                        value="<?= date('Y-m-d'); ?>" max="<?= date('Y-m-d'); ?>" required>
                </div>
                <div style="flex:1; min-width:160px;">
                    <label for="proxima_dose">Próxima dose</label>
                    <input type="date" id="proxima_dose" name="proxima_dose" class="form-control">
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:10px;">
                <div style="flex:1; min-width:160px;">
                    <label for="veterinario">Veterinário responsável</label>
                    <input type="text" id="veterinario" name="veterinario" class="form-control" maxlength="150"
                        value="<?= htmlspecialchars((string) Auth::nome()); ?>">
                </div>
                <div style="flex:1; min-width:160px;">
                    <label for="lote">Lote</label>
                    <input type="text" id="lote" name="lote" class="form-control" maxlength="100">
                </div>
            </div>

            <label for="observacoes" style="margin-top:10px; display:block;">Observações</label>
            <textarea id="observacoes" name="observacoes" rows="2" class="form-control"></textarea>

            <button type="submit" class="btn btn-success" style="margin-top:14px;">Registrar vacina</button>
        </form>
    </div>
</main>

<script>
    (function () {
        var vacina = document.getElementById('vacina_id');
        var aplicacao = document.getElementById('data_aplicacao');
        var proxima = document.getElementById('proxima_dose');
        var editouManual = false;
        proxima.addEventListener('input', function () { editouManual = true; });
        function sugerir() {
            var opt = vacina.selectedOptions[0];
            var dias = parseInt(opt ? opt.dataset.dias : '0', 10);
            if (editouManual || !dias || !aplicacao.value) { return; }
            var d = new Date(aplicacao.value + 'T12:00:00');
            d.setDate(d.getDate() + dias);
            proxima.value = d.toISOString().slice(0, 10);
        }
        vacina.addEventListener('change', sugerir);
        aplicacao.addEventListener('change', sugerir);
    })();
</script>

<?php require_once '../../app/Includes/footer.php'; ?>
