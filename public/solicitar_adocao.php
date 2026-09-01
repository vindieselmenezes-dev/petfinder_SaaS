<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/Controllers/PetController.php';
require_once '../app/Controllers/SolicitacaoAdocaoController.php';
require_once '../app/Helpers/Csrf.php';

$petController = new PetController();
$solicitacaoController = new SolicitacaoAdocaoController();
$usuarioId = (int) $_SESSION['usuario_id'];

$petId = (int) ($_GET['pet_id'] ?? 0);
$pet = $petController->buscarPorId($petId);

if (!$pet) {
    header('Location: pets_adocao.php');
    exit;
}

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {

        $mensagem = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';

    } else {

        $resultado = $solicitacaoController->solicitar($petId, $usuarioId, $_POST['mensagem'] ?? '');

        if ($resultado['sucesso']) {
            header('Location: minhas_solicitacoes.php?enviado=1');
            exit;
        }

        $mensagem = $resultado['erro'];
        $tipoMensagem = 'erro';
    }
}

$tituloPagina = "Solicitar Adoção";

require_once '../app/Includes/header.php';
require_once '../app/Includes/menu.php';
?>

<main class="conteudo">
<div class="container" style="max-width:500px; margin:0 auto;">

    <?php if ($mensagem): ?>
        <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <div class="formulario-cadastro">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:15px;">
            <?php if (!empty($pet['foto']) && file_exists(__DIR__ . '/../uploads/pets/' . $pet['foto'])): ?>
                <img src="../uploads/pets/<?= htmlspecialchars($pet['foto']); ?>" width="60" height="60" style="border-radius:50%; object-fit:cover;">
            <?php else: ?>
                <div style="width:60px; height:60px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:26px;">🐶</div>
            <?php endif; ?>
            <div>
                <h1 style="margin:0; font-size:22px;">🏠 Adotar <?= htmlspecialchars($pet['nome']); ?></h1>
                <span style="font-size:13px; color:#7f8c8d;">Sua solicitação vai direto pro dono responder.</span>
            </div>
        </div>

        <form method="POST">
            <?= Csrf::campoHtml(); ?>
            <div class="grupo-form">
                <label for="mensagem">Conte um pouco sobre você e por que quer adotar</label>
                <textarea id="mensagem" name="mensagem" class="form-control" rows="5" required placeholder="Ex: Moro em casa com quintal, já tive outros pets, posso levar pra conhecer..."></textarea>
            </div>
            <button type="submit" class="btn-acao" style="background:#2ecc71; color:white; width:100%; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Enviar Solicitação</button>
        </form>

        <p style="text-align:center; margin-top:15px;">
            <a href="pet.php?id=<?= $petId; ?>" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar pro perfil do pet</a>
        </p>
    </div>

</div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>
