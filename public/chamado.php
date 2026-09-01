<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/SuporteController.php";
require_once "../app/Controllers/NotificacaoController.php";
require_once "../app/Models/Usuario.php";
require_once "../app/Helpers/Csrf.php";

$controller = new SuporteController();
$usuarioId  = (int) $_SESSION["usuario_id"];
$ehAdmin    = ($_SESSION['perfil_tipo'] ?? '') === 'administrador';

$chamadoId = (int) ($_GET["id"] ?? 0);

// Administrador pode ver qualquer chamado; usuário comum só o próprio
$chamado = $ehAdmin
    ? $controller->buscarPorId($chamadoId)
    : $controller->buscarPorId($chamadoId, $usuarioId);

if (!$chamado) {
    die("<h1 style='text-align:center; margin-top:50px; color:red;'>Chamado não encontrado ou você não tem permissão pra ver ele.</h1>");
}

$respostas = $controller->listarRespostas($chamadoId);

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validar($_POST['csrf_token'] ?? null)) {
    die("Erro: token de segurança inválido ou expirado. Atualize a página e tente novamente.");
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resposta'])) {
    $controller->responder($chamadoId, $usuarioId, $_POST['resposta']);

    // Se o admin respondeu um chamado que ainda tava "Aberto", avança pra "Em Atendimento"
    if ($ehAdmin && $chamado['status'] === 'Aberto') {
        $controller->atualizarStatus($chamadoId, 'Em Atendimento');
    }

    // Notifica a outra ponta da conversa
    $notificacaoController = new NotificacaoController();
    $assuntoChamado = $chamado['assunto'];
    $linkChamado = 'chamado.php?id=' . $chamadoId;

    if ($ehAdmin) {
        // Admin respondeu -> avisa o dono do chamado (se não for ele mesmo)
        if ((int) $chamado['usuario_id'] !== $usuarioId) {
            $notificacaoController->criar(
                (int) $chamado['usuario_id'],
                "💬 Seu chamado foi respondido",
                "Suporte PetFinder respondeu ao chamado \"$assuntoChamado\".",
                'Sistema',
                $linkChamado
            );
        }
    } else {
        // Cliente/empresa respondeu -> avisa todos os administradores
        $usuarioModel = new Usuario();
        $remetenteNome = $_SESSION['usuario_nome'] ?? 'Um usuário';
        foreach ($usuarioModel->listarIdsAdministradores() as $adminId) {
            $notificacaoController->criar(
                (int) $adminId,
                "💬 Nova resposta em chamado",
                "$remetenteNome respondeu no chamado \"$assuntoChamado\".",
                'Sistema',
                $linkChamado
            );
        }
    }

    header("Location: chamado.php?id=" . $chamadoId);
    exit;
}

if (isset($_POST['novo_status']) && $ehAdmin) {
    $controller->atualizarStatus($chamadoId, $_POST['novo_status']);
    header("Location: chamado.php?id=" . $chamadoId);
    exit;
}

$tituloPagina = "Chamado #" . $chamadoId;

require_once "../app/Includes/header.php";
require_once "../app/Includes/menu.php";

$corStatus = [
    'Aberto' => '#3498db',
    'Em Atendimento' => '#f39c12',
    'Resolvido' => '#2ecc71',
    'Fechado' => '#95a5a6',
][$chamado['status']] ?? '#95a5a6';
?>

<main class="conteudo">
<div class="container">

    <p><a href="<?= $ehAdmin ? 'suporte_admin.php' : 'suporte.php'; ?>" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar</a></p>

    <div class="formulario-cadastro" style="max-width:700px; margin:0 auto;">

        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
            <div>
                <h1 style="margin-bottom:5px;"><?= htmlspecialchars($chamado['assunto']); ?></h1>
                <?php if ($ehAdmin): ?>
                    <p style="color:#7f8c8d; font-size:13px; margin:0;">Aberto por: <?= htmlspecialchars($chamado['usuario_nome']); ?> (<?= htmlspecialchars($chamado['usuario_email']); ?>)</p>
                <?php endif; ?>
            </div>
            <span class="badge-status" style="background:<?= $corStatus; ?>; white-space:nowrap;"><?= htmlspecialchars($chamado['status']); ?></span>
        </div>

        <p style="font-size:13px; color:#7f8c8d;">Prioridade: <strong><?= htmlspecialchars($chamado['prioridade']); ?></strong> • Aberto em <?= date('d/m/Y H:i', strtotime($chamado['criado_em'])); ?></p>

        <div class="record-card" style="margin-top:15px;">
            <p style="margin:0; white-space:pre-line;"><?= htmlspecialchars($chamado['descricao']); ?></p>
        </div>

        <?php if ($ehAdmin): ?>
            <form method="POST" style="margin:15px 0; display:flex; gap:8px; align-items:center;">
                <?= Csrf::campoHtml() ?>
                <label for="novo_status" style="margin:0; font-size:13px; color:#555;">Mudar status:</label>
                <select name="novo_status" id="novo_status" class="form-select" style="width:auto;" onchange="this.form.submit()">
                    <?php foreach ($controller->statusValidos() as $statusOpcao): ?>
                        <option value="<?= $statusOpcao; ?>" <?= $statusOpcao === $chamado['status'] ? 'selected' : ''; ?>><?= $statusOpcao; ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>

        <h3 style="margin-top:25px; margin-bottom:12px;">Histórico de respostas</h3>

        <?php if (count($respostas) > 0): ?>
            <?php foreach ($respostas as $resp):
                $ehRespostaAdmin = ($resp['tipo_usuario'] ?? '') === 'administrador';
            ?>
                <div class="record-card" style="<?= $ehRespostaAdmin ? 'background:#eaf4ff; border-color:#bcdcff;' : ''; ?>">
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                        <strong style="font-size:13px;"><?= $ehRespostaAdmin ? '🛠️ Suporte PetFinder' : htmlspecialchars($resp['usuario_nome']); ?></strong>
                        <span style="font-size:12px; color:#999;"><?= date('d/m/Y H:i', strtotime($resp['criado_em'])); ?></span>
                    </div>
                    <p style="margin:0; white-space:pre-line;"><?= htmlspecialchars($resp['resposta']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#95a5a6; font-style:italic;">Nenhuma resposta ainda.</p>
        <?php endif; ?>

        <?php if ($chamado['status'] !== 'Fechado'): ?>
            <form method="POST" style="margin-top:20px;">
                <?= Csrf::campoHtml() ?>
                <div class="grupo-form">
                    <label for="resposta">Sua resposta</label>
                    <textarea name="resposta" id="resposta" class="form-control" rows="3" required autocomplete="off"></textarea>
                </div>
                <button type="submit" class="btn-acao" style="background:#3498db; color:white; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Enviar Resposta</button>
            </form>
        <?php else: ?>
            <p style="color:#95a5a6; font-style:italic; margin-top:15px;">Este chamado está fechado.</p>
        <?php endif; ?>

    </div>
</div>
</main>

<?php require_once "../app/Includes/footer.php"; ?>
