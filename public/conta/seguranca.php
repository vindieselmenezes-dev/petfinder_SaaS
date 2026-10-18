<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Csrf.php';
require_once __DIR__ . '/../../app/Helpers/Mailer.php';
require_once __DIR__ . '/../../app/Models/RateLimiter.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: ' . Url::pagina('login.php'));
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
        if ($ativo === 1 && !Mailer::disponivel()) {
            // O código chega por e-mail: ativar agora trancaria a conta.
            $erro = 'A verificação em duas etapas está temporariamente indisponível.';
        } else {
            try {
                // Ativar ou desativar exige a senha atual: uma sessão esquecida aberta
                // não basta para desligar a proteção (nem para ligá-la e trancar o dono).
                $limiteSenha = new RateLimiter();
                $stmtSenha = $pdo->prepare('SELECT senha FROM usuarios WHERE id = :id');
                $stmtSenha->execute([':id' => $usuarioId]);
                $hashSenha = (string) $stmtSenha->fetchColumn();

                if (!$limiteSenha->permitido('seguranca_senha', 5, 15, 15, 'u' . $usuarioId)) {
                    $erro = 'Muitas tentativas. Tente novamente em '
                        . max(1, $limiteSenha->minutosRestantes('seguranca_senha', 'u' . $usuarioId)) . ' minuto(s).';
                } elseif ($hashSenha === '' || !password_verify((string) ($_POST['senha_atual'] ?? ''), $hashSenha)) {
                    $erro = 'Senha atual incorreta.';
                } else {
                    $limiteSenha->zerar('seguranca_senha', 'u' . $usuarioId);
                    $stmt = $pdo->prepare('UPDATE usuarios SET dois_fatores_ativo = :ativo, dois_fatores_codigo = NULL, dois_fatores_codigo_expira = NULL WHERE id = :id');
                    $stmt->execute([':ativo' => $ativo, ':id' => $usuarioId]);
                    $mensagem = $ativo ? 'Verificação em duas etapas ativada.' : 'Verificação em duas etapas desativada.';
                }
            } catch (Throwable $exception) {
                $erro = 'Aplique a migration 013 antes de alterar essa configuração.';
            }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
</head>

<body style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="← Voltar para a página anterior">← Voltar</button></div>
    <main class="container py-5" style="max-width:640px">
        <h1>Segurança da conta</h1>
        <p>Proteja contas administrativas e empresariais com um código enviado por e-mail a cada login.</p>
        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?><?php if ($erro): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
        <?php if (!$doisFatoresAtivo && !Mailer::disponivel()): ?>
            <div class="alert alert-secondary">A verificação em duas etapas por e-mail está temporariamente indisponível.</div>
        <?php else: ?>
        <form method="post"><?php echo Csrf::campoHtml(); ?><input type="hidden" name="dois_fatores_ativo"
                value="<?= $doisFatoresAtivo ? '0' : '1' ?>">
            <label class="form-label" for="senha_atual">Confirme sua senha para <?= $doisFatoresAtivo ? 'desativar' : 'ativar' ?> o 2FA</label>
            <input class="form-control mb-3" type="password" id="senha_atual" name="senha_atual" required
                autocomplete="current-password"><button
                class="btn btn-<?= $doisFatoresAtivo ? 'outline-danger' : 'primary' ?>"
                type="submit"><?= $doisFatoresAtivo ? 'Desativar 2FA' : 'Ativar 2FA' ?></button></form>
        <?php endif; ?>
    </main>
</body>

</html>
