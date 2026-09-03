<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = Database::conectar();
$usuarioId = (int) $_SESSION['usuario_id'];
$mensagem = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $ativo = ($_POST['dois_fatores_ativo'] ?? '') === '1' ? 1 : 0;
        try {
            $stmt = $pdo->prepare('UPDATE usuarios SET dois_fatores_ativo = :ativo, dois_fatores_codigo = NULL, dois_fatores_codigo_expira = NULL WHERE id = :id');
            $stmt->execute([':ativo' => $ativo, ':id' => $usuarioId]);
            $mensagem = $ativo ? 'Verificação em duas etapas ativada.' : 'Verificação em duas etapas desativada.';
        } catch (Throwable $exception) {
            $erro = 'Aplique a migration 013 antes de alterar essa configuração.';
        }
    }
}

try {
    $stmt = $pdo->prepare('SELECT dois_fatores_ativo FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $usuarioId]);
    $doisFatoresAtivo = (bool) $stmt->fetchColumn();
} catch (Throwable $exception) {
    $doisFatoresAtivo = false;
    $erro = $erro ?: 'Aplique a migration 013 para habilitar a segurança da conta.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Segurança da conta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <main class="container py-5" style="max-width:640px">
        <h1>Segurança da conta</h1>
        <p>Proteja contas administrativas e empresariais com um código enviado por e-mail a cada login.</p>
        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?><?php if ($erro): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
        <form method="post"><?php echo Csrf::campoHtml(); ?><input type="hidden" name="dois_fatores_ativo"
                value="<?= $doisFatoresAtivo ? '0' : '1' ?>"><button
                class="btn btn-<?= $doisFatoresAtivo ? 'outline-danger' : 'primary' ?>"
                type="submit"><?= $doisFatoresAtivo ? 'Desativar 2FA' : 'Ativar 2FA' ?></button></form>
    </main>
</body>

</html>