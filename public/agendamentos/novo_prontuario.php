<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
 
require_once __DIR__ . '/../../app/Models/Usuario.php'; 
require_once __DIR__ . '/../../app/Models/Veterinario.php';
require_once __DIR__ . '/../../app/Controllers/ConsultaController.php';
require_once __DIR__ . '/../../app/Helpers/EmpresaAcesso.php';
require_once __DIR__ . '/../../app/Helpers/Csrf.php';
$pdo = Database::conectar(); 


if (!isset($_SESSION['usuario_id']) || !isset($_GET['empresa_id'])) { 
    header('Location: ' . Url::pagina('login.php')); 
    exit(); 
} 

$empresaId = (int)$_GET['empresa_id'];
$usuarioId = (int)$_SESSION['usuario_id'];

// Só quem é da equipe da empresa (dono, admin ou veterinário) pode registrar prontuário
if (!EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId, ['proprietario', 'administrador', 'veterinario'])) {
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>🚨 Erro de Segurança: Você não tem permissão pra registrar prontuários nesta empresa.</h1>");
}

$veterinarioModel = new Veterinario();
$meuRegistroVet = $veterinarioModel->buscarPorUsuario($usuarioId);

// Cadastro rápido de CRMV, se a pessoa ainda não tiver um registro de veterinário
if (!$meuRegistroVet && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crmv']) && Csrf::validar($_POST['csrf_token'] ?? null)) {
    $crmv = trim($_POST['crmv']);
    if ($crmv !== '') {
        $veterinarioModel->cadastrar($usuarioId, $crmv);
        $meuRegistroVet = $veterinarioModel->buscarPorUsuario($usuarioId);
    }
}

/*
|--------------------------------------------------------------------------
| Se veio de uma consulta agendada pelo tutor (consultas_empresa.php), já
| pré-preenche pet e motivo, e trava o prontuário nessa mesma consulta em
| vez de criar uma nova do zero.
|--------------------------------------------------------------------------
*/

$consultaId = isset($_GET['consulta_id']) ? (int) $_GET['consulta_id'] : 0;
$consultaVinculada = null;
$prontuarioJaExiste = false;

if ($consultaId > 0) {
    $consultaVinculada = (new ConsultaController())->buscarPorId($consultaId);

    if (!$consultaVinculada || (int) $consultaVinculada['empresa_id'] !== $empresaId) {
        $consultaVinculada = null;
        $consultaId = 0;
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM prontuarios WHERE consulta_id = :consulta_id LIMIT 1");
        $stmtCheck->execute([':consulta_id' => $consultaId]);
        $prontuarioJaExiste = (bool) $stmtCheck->fetchColumn();
    }
}

if ($consultaVinculada && !empty($consultaVinculada['pet_id'])) {
    $stmtPets = $pdo->prepare("SELECT id, nome FROM pets WHERE id = :id");
    $stmtPets->execute([':id' => $consultaVinculada['pet_id']]);
} else {
    $stmtPets = $pdo->query("SELECT id, nome FROM pets ORDER BY nome ASC");
}
$pets = $stmtPets->fetchAll();

include __DIR__ . '/../../app/Includes/header.php'; 
include __DIR__ . '/../../app/Includes/menu.php';
?>

<main class="conteudo">
<div class="container" style="max-width:500px; margin:0 auto;">

    <?php if ($prontuarioJaExiste): ?>
        <div class="mensagem">
            Essa consulta já tem um prontuário registrado.
            <a href="historico_prontuario.php?pet_id=<?= (int) $consultaVinculada['pet_id'] ?>">Ver histórico do pet</a>
            ou <a href="consultas_empresa.php?empresa_id=<?= $empresaId ?>">voltar pras consultas</a>.
        </div>
    <?php elseif (!$meuRegistroVet): ?>
        <!-- ETAPA 1: quem ainda não tem registro de veterinário precisa informar o CRMV primeiro -->
        <div class="formulario-cadastro">
            <h2 style="text-align:center; color:#2c3e50;">🩺 Complete seu cadastro de veterinário</h2>
            <p style="color:#7f8c8d; text-align:center; font-size:14px;">Pra registrar prontuários, precisamos do seu CRMV (registro profissional). Isso só é pedido uma vez.</p>
            <form method="POST">
                <?= Csrf::campoHtml() ?>
                <div class="grupo-form">
                    <label for="crmv">Número do CRMV</label>
                    <input type="text" id="crmv" name="crmv" class="form-control" autocomplete="off" required placeholder="Ex: CRMV-SP 12345">
                </div>
                <button type="submit" class="btn-acao" style="background:#3498db; color:white; width:100%; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Confirmar e Continuar</button>
            </form>
            <div class="back-link" style="text-align:center; margin-top:15px;">
                <a href="<?= Url::pagina('painel_b2b.php') ?>?empresa_id=<?php echo $empresaId; ?>">⬅ Cancelar e Voltar</a>
            </div>
        </div>
    <?php else: ?>
        <!-- ETAPA 2: formulário de verdade do prontuário -->
        <div class="formulario-cadastro">
            <h2 style="text-align:center; color:#2c3e50;">📋 <?= $consultaVinculada ? 'Registrar Prontuário da Consulta' : 'Emitir Novo Prontuário Médico' ?></h2>

            <?php if ($consultaVinculada): ?>
                <p style="color:#7f8c8d; text-align:center; font-size:14px;">
                    Consulta de <?= htmlspecialchars($consultaVinculada['data_consulta']) ?> às <?= htmlspecialchars(substr($consultaVinculada['hora_consulta'], 0, 5)) ?>.
                    Isso vai completar o registro dessa consulta, sem criar uma duplicada.
                </p>
            <?php endif; ?>

            <form action="processa_prontuario.php" method="POST">
                <?= Csrf::campoHtml() ?>
                <input type="hidden" name="empresa_id" value="<?php echo $empresaId; ?>">
                <?php if ($consultaId > 0): ?>
                    <input type="hidden" name="consulta_id" value="<?php echo $consultaId; ?>">
                <?php endif; ?>

                <div class="grupo-form">
                    <label for="pet_id">Selecione o Paciente (Pet)</label>
                    <select id="pet_id" name="pet_id" class="form-select" required <?= ($consultaVinculada && !empty($consultaVinculada['pet_id'])) ? 'disabled' : '' ?>>
                        <option value="">-- Escolha o Animal --</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?php echo $pet['id']; ?>" <?= ($consultaVinculada && !empty($consultaVinculada['pet_id'])) ? 'selected' : '' ?>><?php echo htmlspecialchars($pet['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($consultaVinculada && !empty($consultaVinculada['pet_id'])): ?>
                        <input type="hidden" name="pet_id" value="<?= (int) $consultaVinculada['pet_id'] ?>">
                    <?php elseif ($consultaVinculada): ?>
                        <small class="text-muted">O tutor não informou o pet ao agendar — selecione manualmente.</small>
                    <?php endif; ?>
                </div>

                <div class="grupo-form">
                    <label for="motivo">Motivo da Consulta</label>
                    <input type="text" id="motivo" name="motivo" class="form-control" autocomplete="off"
                        value="<?= htmlspecialchars($consultaVinculada['motivo'] ?? '') ?>"
                        placeholder="Ex: Check-up de rotina, vômito, ferimento...">
                </div>

                <div class="grupo-form">
                    <label for="diagnostico">Diagnóstico Clínico</label>
                    <textarea id="diagnostico" name="diagnostico" class="form-control" rows="3" required placeholder="Descreva os sintomas e o diagnóstico..."></textarea>
                </div>

                <div class="grupo-form">
                    <label for="tratamento">Tratamento Prescrito</label>
                    <textarea id="tratamento" name="tratamento" class="form-control" rows="2" placeholder="Procedimentos realizados..."></textarea>
                </div>

                <div class="grupo-form">
                    <label for="medicamentos">Medicamentos</label>
                    <textarea id="medicamentos" name="medicamentos" class="form-control" rows="2" placeholder="Nome, dosagem e frequência..."></textarea>
                </div>

                <div class="grupo-form">
                    <label for="recomendacoes">Recomendações</label>
                    <textarea id="recomendacoes" name="recomendacoes" class="form-control" rows="2" placeholder="Cuidados em casa, dieta..."></textarea>
                </div>

                <div class="grupo-form">
                    <label for="retorno">Data de retorno (opcional)</label>
                    <input type="date" id="retorno" name="retorno" class="form-control">
                </div>

                <button type="submit" class="btn-acao" style="background:#3498db; color:white; width:100%; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Salvar Registro</button>
            </form>
            <div class="back-link" style="text-align:center; margin-top:15px;">
                <a href="<?= Url::pagina('painel_b2b.php') ?>?empresa_id=<?php echo $empresaId; ?>">⬅ Cancelar e Voltar</a>
            </div>
        </div>
    <?php endif; ?>

</div>
</main>

<?php 
include __DIR__ . '/../../app/Includes/footer.php'; 
?>
