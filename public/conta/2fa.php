<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Mailer.php';
require_once __DIR__ . '/../../app/Models/RateLimiter.php';

/**
 * O código NÃO fica em texto puro no banco: guarda-se só um HMAC (10 primeiros
 * caracteres) feito com a APP_KEY. Quem lesse o banco não conseguiria nem ver
 * nem calcular os códigos ativos sem a chave do servidor.
 */
function codigo2faHash(string $codigo, int $usuarioId): string
{
    $chave = (string) (Env::get('APP_KEY') ?: 'ecosistempet-2fa');

    return substr(hash_hmac('sha256', $codigo . ':' . $usuarioId, $chave), 0, 10);
}

/**
 * Gera um código novo (o anterior deixa de valer), grava o hash e envia por
 * e-mail. Devolve false se o envio falhar, para a tela avisar a pessoa.
 *
 * @param array<string,mixed> $usuario Precisa de id e email.
 */
function gerarEEnviarCodigo2fa(PDO $pdo, array $usuario): bool
{
    $codigo = (string) random_int(100000, 999999);

    $pdo->prepare(
        'UPDATE usuarios SET dois_fatores_codigo = :codigo, dois_fatores_codigo_expira = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = :id'
    )->execute([':codigo' => codigo2faHash($codigo, (int) $usuario['id']), ':id' => (int) $usuario['id']]);

    return Mailer::enviar(
        (string) $usuario['email'],
        'Seu código de acesso EcoSistemPet',
        '<p>Seu código de verificação é <strong>' . $codigo . '</strong>. Ele expira em 10 minutos.</p>'
    );
}

$usuarioId = (int) ($_SESSION['2fa_pendente_usuario_id'] ?? 0);
if ($usuarioId <= 0) {
    Url::redirecionar(Url::pagina('login.php'));
    exit;
}

$pdo = Database::conectar();
// codigo_valido é calculado PELO BANCO (NOW() do mesmo relógio que gravou a validade).
// Comparar a data do banco com time() do PHP falha quando os dois estão em fusos
// diferentes (o código correto aparecia como "expirado").
$stmt = $pdo->prepare('SELECT id, nome, email, dois_fatores_codigo, dois_fatores_codigo_expira, dois_fatores_ativo, tipo_usuario, (dois_fatores_codigo_expira IS NOT NULL AND dois_fatores_codigo_expira > NOW()) AS codigo_valido FROM usuarios WHERE id = :id');
$stmt->execute([':id' => $usuarioId]);
$usuario = $stmt->fetch();
if (!$usuario || empty($usuario['dois_fatores_ativo'])) {
    unset($_SESSION['2fa_pendente_usuario_id']);
    Url::redirecionar(Url::pagina('login.php'));
    exit;
}

$mensagem = null;
$erro = null;

// O código tem só 6 dígitos (1 milhão de combinações), então sem limite
// daria para adivinhá-lo por tentativa e erro. Limite POR USUÁRIO (não por
// sessão, senão abrir uma sessão nova zeraria a contagem): 5 envios a cada
// 10 minutos; ao estourar, 15 minutos de bloqueio e o código atual é
// descartado. Tentativas com CSRF inválido não contam.
$limite2fa = new RateLimiter();
$idLimite2fa = 'u' . $usuarioId;
$textoBloqueio2fa = static fn(): string => 'Muitas tentativas. Tente novamente em '
    . max(1, $limite2fa->minutosRestantes('2fa', $idLimite2fa)) . ' minuto(s).';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif (isset($_POST['reenviar'])) {
        // Reenvio: não consome as tentativas de digitar o código; tem limite próprio
        // (3 novos códigos a cada 10 minutos) para não encher a caixa de entrada.
        if ($limite2fa->minutosRestantes('2fa', $idLimite2fa) > 0) {
            $erro = $textoBloqueio2fa();
        } elseif (!Mailer::disponivel()) {
            $erro = 'O envio de e-mail está indisponível no momento. Fale com o suporte.';
        } elseif (!$limite2fa->permitido('2fa_reenvio', 3, 10, 10, $idLimite2fa)) {
            $erro = 'Muitos pedidos de novo código. Tente novamente em '
                . max(1, $limite2fa->minutosRestantes('2fa_reenvio', $idLimite2fa)) . ' minuto(s).';
        } elseif (gerarEEnviarCodigo2fa($pdo, $usuario)) {
            $mensagem = 'Enviamos um novo código. O anterior deixou de valer.';
        } else {
            $erro = 'Não foi possível enviar o e-mail agora. Tente novamente em instantes.';
        }
    } elseif (!$limite2fa->permitido('2fa', 5, 10, 15, $idLimite2fa)) {
        $pdo->prepare('UPDATE usuarios SET dois_fatores_codigo = NULL, dois_fatores_codigo_expira = NULL WHERE id = :id')
            ->execute([':id' => $usuarioId]);
        $erro = $textoBloqueio2fa();
    } else {
        $codigo = preg_replace('/\D/', '', (string) ($_POST['codigo'] ?? ''));
        if (strlen($codigo) === 6 && hash_equals((string) $usuario['dois_fatores_codigo'], codigo2faHash($codigo, $usuarioId)) && !empty($usuario['codigo_valido'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['perfil_tipo'] = $_SESSION['2fa_pendente_perfil'] ?? $usuario['tipo_usuario'] ?? 'cliente';
            unset($_SESSION['2fa_pendente_usuario_id'], $_SESSION['2fa_pendente_perfil']);
            $limite2fa->zerar('2fa', $idLimite2fa);
            $pdo->prepare('UPDATE usuarios SET dois_fatores_codigo = NULL, dois_fatores_codigo_expira = NULL, ultimo_login = NOW() WHERE id = :id')->execute([':id' => $usuarioId]);
            $destino = $_SESSION['2fa_voltar'] ?? Url::pagina('onboarding.php');
            unset($_SESSION['2fa_voltar']);
            Url::redirecionar($destino);
            exit;
        }
        $erro = 'Código inválido ou expirado.';
    }
} elseif ($limite2fa->minutosRestantes('2fa', $idLimite2fa) > 0) {
    // Em bloqueio não gera nem envia código novo (evita também encher o e-mail do usuário).
    $erro = $textoBloqueio2fa();
} elseif (!Mailer::disponivel()) {
    // Conta com 2FA, mas o servidor não consegue enviar e-mail: avisa em vez de fingir que enviou.
    $erro = 'O envio de e-mail está indisponível no momento. Fale com o suporte.';
} elseif (empty($usuario['dois_fatores_codigo']) || empty($usuario['codigo_valido'])) {
    if (gerarEEnviarCodigo2fa($pdo, $usuario)) {
        $mensagem = 'Enviamos um código de verificação para seu e-mail.';
    } else {
        $erro = 'Não foi possível enviar o código agora. Use "Reenviar código" em instantes.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificação em duas etapas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
</head>

<body style="padding-top:38px;">
    <div
        style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;">
        <button type="button"
            onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}"
            style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;"
            aria-label="← Voltar para a página anterior">← Voltar</button></div>
    <main class="container py-5" style="max-width:480px">
        <h1>Verificação em duas etapas</h1>
        <p>Digite o código enviado ao seu e-mail.</p><?php if ($mensagem): ?>
            <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?><?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
        <form method="post">
            <?= Csrf::campoHtml() ?>
            <label class="form-label" for="codigo">Código de 6 dígitos</label><input class="form-control mb-3"
                id="codigo" name="codigo" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required
                autofocus><button class="btn btn-primary" type="submit">Verificar</button>
        </form>
        <form method="post" class="mt-3">
            <?= Csrf::campoHtml() ?>
            <input type="hidden" name="reenviar" value="1">
            <small class="text-muted d-block mb-1">Não chegou? Confira também a caixa de spam.</small>
            <button class="btn btn-link p-0" type="submit">Reenviar código</button>
        </form>
    </main>
</body>

</html>
