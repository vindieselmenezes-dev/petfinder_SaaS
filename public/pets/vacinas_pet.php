<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

$petController = new PetController();
$vacinaController = new PetVacinaController();

$petId = (int) ($_GET['id'] ?? 0);
$pet = $petController->buscarPorId($petId);

if (!$pet) {
    die("<h1 style='text-align:center; margin-top:50px; color:red;'>Pet não encontrado.</h1>");
}

// Carteira de vacinação é dado do tutor: só ele (e o administrador) enxerga.
$ehTutor = PetAcesso::ehDonoPara($pet, Auth::id());
if (!PetAcesso::podeVerPrivado($pet)) {
    Flash::erro('Você não tem acesso ao quadro de vacinas deste pet.');
    Url::redirecionar(Url::pagina('meus_pets.php'));
    exit;
}

$quadro = $vacinaController->montarQuadro($petId);
$doses = $vacinaController->listarPorPet($petId);
$catalogo = $vacinaController->listarCatalogo();

$tituloPagina = 'Vacinas de ' . $pet['nome'];

$corSituacao = [
    'Em dia' => '#2ecc71',
    'Vence em breve' => '#f39c12',
    'Atrasada' => '#e74c3c',
    'Pendente' => '#95a5a6',
];

$fmt = static fn (?string $d): string => $d ? date('d/m/Y', strtotime($d)) : '—';

require_once '../../app/Includes/header.php';
require_once '../../app/Includes/menu.php';
?>

<main class="conteudo">
    <div class="container" style="max-width:800px; margin:0 auto;">

        <p><a href="pet.php?id=<?= $petId; ?>" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar pro perfil de
                <?= htmlspecialchars($pet['nome']); ?></a></p>

        <h1>💉 Vacinas de <?= htmlspecialchars($pet['nome']); ?></h1>
        <p style="color:#7f8c8d; margin-bottom:25px;">Quadro de vacinação: situação de cada vacina e histórico das doses aplicadas.</p>

        <?php Flash::render(); ?>

        <!-- Quadro: situação atual de cada vacina -->
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:12px; margin-bottom:30px;">
            <?php foreach ($quadro as $linha): $cor = $corSituacao[$linha['situacao']]; ?>
                <div class="record-card" style="margin:0; border-left:4px solid <?= $cor; ?>;">
                    <strong><?= htmlspecialchars($linha['vacina']); ?></strong>
                    <?php if ($linha['obrigatoria']): ?><small style="color:#999;"> · obrigatória</small><?php endif; ?>
                    <div style="margin:6px 0;">
                        <span class="badge-status" style="background:<?= $cor; ?>;"><?= htmlspecialchars($linha['situacao']); ?></span>
                    </div>
                    <?php if ($linha['dose']): ?>
                        <div style="font-size:13px; color:#555;">Aplicada em <?= $fmt($linha['dose']['data_aplicacao']); ?></div>
                        <div style="font-size:13px; color:#555;">Próxima dose: <?= $fmt($linha['dose']['proxima_dose']); ?></div>
                    <?php else: ?>
                        <div style="font-size:13px; color:#999;">Nenhuma dose registrada.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($ehTutor): ?>
            <!-- Registrar nova dose -->
            <div class="record-card" style="margin-bottom:30px;">
                <h2 style="font-size:18px; margin-top:0;">Registrar vacina aplicada</h2>
                <form method="POST" action="processa_vacina.php">
                    <?= Csrf::campoHtml() ?>
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
                                max="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div style="flex:1; min-width:160px;">
                            <label for="proxima_dose">Próxima dose</label>
                            <input type="date" id="proxima_dose" name="proxima_dose" class="form-control">
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:10px;">
                        <div style="flex:1; min-width:160px;">
                            <label for="veterinario">Veterinário</label>
                            <input type="text" id="veterinario" name="veterinario" class="form-control" maxlength="150">
                        </div>
                        <div style="flex:1; min-width:160px;">
                            <label for="lote">Lote</label>
                            <input type="text" id="lote" name="lote" class="form-control" maxlength="100">
                        </div>
                    </div>

                    <label for="observacoes" style="margin-top:10px; display:block;">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="2" class="form-control"></textarea>

                    <button type="submit" class="btn btn-success" style="margin-top:14px;">Salvar vacina</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Histórico de doses -->
        <h2 style="font-size:18px;">Histórico de doses</h2>
        <?php if ($doses): ?>
            <?php foreach ($doses as $d): ?>
                <div class="record-card" style="margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                        <strong><?= htmlspecialchars($d['vacina']); ?></strong>
                        <span style="font-size:12px; color:#aaa;"><?= $fmt($d['data_aplicacao']); ?></span>
                    </div>
                    <div style="font-size:13px; color:#555; margin-top:4px;">
                        Próxima dose: <?= $fmt($d['proxima_dose']); ?>
                        <?php if (!empty($d['veterinario'])): ?> · Vet.: <?= htmlspecialchars($d['veterinario']); ?><?php endif; ?>
                        <?php if (!empty($d['lote'])): ?> · Lote: <?= htmlspecialchars($d['lote']); ?><?php endif; ?>
                        <?php if (!empty($d['clinica'])): ?> · 🏥 <?= htmlspecialchars($d['clinica']); ?><?php endif; ?>
                    </div>
                    <?php if (!empty($d['observacoes'])): ?>
                        <p style="margin:6px 0 0; font-size:13px; color:#555;"><?= nl2br(htmlspecialchars($d['observacoes'])); ?></p>
                    <?php endif; ?>
                    <?php if ($ehTutor): ?>
                        <p style="margin:8px 0 0;">
                            <a <?= AcaoPost::atributos(Url::pagina('excluir_vacina.php'), ['pet_id' => $petId, 'dose_id' => (int) $d['id']], 'Remover este registro de vacina?') ?>
                                style="font-size:12px; color:#e74c3c;">Remover registro</a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#95a5a6; font-style:italic;">Nenhuma vacina registrada ainda.</p>
        <?php endif; ?>

    </div>
</main>

<script>
    // Sugere a próxima dose a partir da periodicidade da vacina escolhida.
    (function () {
        var vacina = document.getElementById('vacina_id');
        var aplicacao = document.getElementById('data_aplicacao');
        var proxima = document.getElementById('proxima_dose');
        if (!vacina || !aplicacao || !proxima) { return; }
        var editouManual = false;
        proxima.addEventListener('input', function () { editouManual = true; });
        function sugerir() {
            var dias = parseInt(vacina.selectedOptions[0] ? vacina.selectedOptions[0].dataset.dias : '0', 10);
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
