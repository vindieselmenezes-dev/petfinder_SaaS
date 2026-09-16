<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Mailer.php';

$usuarioId = (int) ($_SESSION['2fa_pendente_usuario_id'] ?? 0);
if ($usuarioId <= 0) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

$pdo = Database::conectar();
$stmt = $pdo->prepare('SELECT id, nome, email, dois_fatores_codigo, dois_fatores_codigo_expira, dois_fatores_ativo, tipo_usuario FROM usuarios WHERE id = :id');
$stmt->execute([':id' => $usuarioId]);
$usuario = $stmt->fetch();
if (!$usuario || empty($usuario['dois_fatores_ativo'])) {
    unset($_SESSION['2fa_pendente_usuario_id']);
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

$mensagem = null;
$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } else {
    $codigo = preg_replace('/\D/', '', (string) ($_POST['codigo'] ?? ''));
    $expira = strtotime((string) ($usuario['dois_fatores_codigo_expira'] ?? ''));
    if (strlen($codigo) === 6 && hash_equals((string) $usuario['dois_fatores_codigo'], $codigo) && $expira > time()) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['perfil_tipo'] = $_SESSION['2fa_pendente_perfil'] ?? $usuario['tipo_usuario'] ?? 'cliente';
        unset($_SESSION['2fa_pendente_usuario_id'], $_SESSION['2fa_pendente_perfil']);
        $pdo->prepare('UPDATE usuarios SET dois_fatores_codigo = NULL, dois_fatores_codigo_expira = NULL, ultimo_login = NOW() WHERE id = :id')->execute([':id' => $usuarioId]);
        header('Location: ' . Url::pagina('onboarding.php'));
        exit;
    }
    $erro = 'Código inválido ou expirado.';
    }
} elseif (empty($usuario['dois_fatores_codigo']) || strtotime((string) $usuario['dois_fatores_codigo_expira']) <= time()) {
    $codigo = (string) random_int(100000, 999999);
    $pdo->prepare('UPDATE usuarios SET dois_fatores_codigo = :codigo, dois_fatores_codigo_expira = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = :id')->execute([':codigo' => $codigo, ':id' => $usuarioId]);
    Mailer::enviar((string) $usuario['email'], 'Seu código de acesso PetFinder', '<p>Seu código de verificação é <strong>' . $codigo . '</strong>. Ele expira em 10 minutos.</p>');
    $mensagem = 'Enviamos um código de verificação para seu e-mail.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificação em duas etapas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="Voltar para a página anterior">← Voltar</button></div>
    <main class="container py-5" style="max-width:480px">
        <h1>Verificação em duas etapas</h1>
        <p>Digite o código enviado ao seu e-mail.</p><?php if ($mensagem): ?>
            <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?><?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
        <form method="post">
            <?= Csrf::campoHtml() ?>
            <label class="form-label" for="codigo">Código de 6 dígitos</label><input
                class="form-control mb-3" id="codigo" name="codigo" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                required autofocus><button class="btn btn-primary" type="submit">Verificar</button></form>
    </main>
</body>

</html>