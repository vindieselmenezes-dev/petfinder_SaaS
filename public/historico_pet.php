<?php

declare(strict_types=1);

session_start();

require_once '../app/Controllers/PetController.php';

$controller = new PetController();

$petId = (int) ($_GET['id'] ?? 0);
$pet = $controller->buscarPorId($petId);

if (!$pet) {
    die("<h1 style='text-align:center; margin-top:50px; color:red;'>Pet não encontrado.</h1>");
}

$historico = $controller->buscarHistoricoCompleto($petId);

$tituloPagina = "Histórico de " . $pet['nome'];

require_once '../app/Includes/header.php';
if (isset($_SESSION['usuario_id'])) {
    require_once '../app/Includes/menu.php';
}

$corStatus = [
    'Com Tutor' => '#2ecc71',
    'Perdido' => '#e74c3c',
    'Encontrado' => '#3498db',
    'Para Adoção' => '#f39c12',
    'Adotado' => '#8e44ad',
];

$corTipo = [
    'Banho' => '#3498db',
    'Tosa' => '#9b59b6',
    'Consulta' => '#e74c3c',
    'Cuidados especiais' => '#f39c12',
    'Adestramento' => '#2ecc71',
    'Agenda de passeios' => '#16a085',
];
?>

<main class="conteudo" <?= isset($_SESSION['usuario_id']) ? '' : 'style="margin-left:0 !important;"'; ?>>
    <div class="container" style="max-width:650px; margin:0 auto;">

        <p><a href="pet.php?id=<?= $petId; ?>" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar pro perfil de
                <?= htmlspecialchars($pet['nome']); ?></a></p>

        <h1>🕒 Histórico de <?= htmlspecialchars($pet['nome']); ?></h1>
        <p style="color:#7f8c8d; margin-bottom:25px;">Linha do tempo completa de tudo que já aconteceu com esse pet no
            sistema.</p>

        <!-- Status atual, sempre no topo -->
        <div class="record-card" style="border-left:4px solid <?= $corStatus[$pet['status']] ?? '#95a5a6'; ?>;">
            <strong>Status atual:</strong>
            <span class="badge-status"
                style="background:<?= $corStatus[$pet['status']] ?? '#95a5a6'; ?>; margin-left:6px;"><?= htmlspecialchars($pet['status']); ?></span>
        </div>

        <?php if (count($historico) > 0): ?>
            <div style="margin-top:20px; position:relative; padding-left:20px; border-left:2px solid #eee;">
                <?php foreach ($historico as $evento): ?>
                    <div style="position:relative; margin-bottom:20px;">
                        <?php $corEvento = $corTipo[$evento['tipo']] ?? ($corStatus[$evento['status_novo'] ?? ''] ?? '#95a5a6'); ?>
                        <div
                            style="position:absolute; left:-26px; top:4px; width:12px; height:12px; border-radius:50%; background:<?= $corEvento ?>; border:2px solid white; box-shadow:0 0 0 2px <?= $corEvento ?>;">
                        </div>
                        <div class="record-card" style="margin:0;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <span class="badge-status"
                                        style="background:<?= $corEvento ?>;"><?= htmlspecialchars($evento['tipo']); ?></span>
                                    <strong><?= htmlspecialchars($evento['descricao']); ?></strong>
                                </div>
                                <span
                                    style="font-size:12px; color:#aaa;"><?= date('d/m/Y H:i', strtotime($evento['data_evento'])); ?></span>
                            </div>
                            <?php if (!empty($evento['detalhes'])): ?>
                                <p style="margin:8px 0 0 0; font-size:14px; color:#555;">
                                    <?= htmlspecialchars($evento['detalhes']); ?></p>
                            <?php endif; ?>
                            <p style="margin:6px 0 0 0; font-size:12px; color:#999;">
                                Por: <?= htmlspecialchars($evento['alterado_por_nome'] ?? 'Sistema'); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#95a5a6; font-style:italic; margin-top:20px;">Nenhuma mudança de status registrada ainda — esse
                pet está com o status original desde o cadastro.</p>
        <?php endif; ?>

    </div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>