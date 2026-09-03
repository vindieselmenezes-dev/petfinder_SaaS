<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = Database::conectar();
$usuarioId = (int) $_SESSION['usuario_id'];
$perfil = (string) ($_SESSION['perfil_tipo'] ?? 'cliente');
$empresaId = (int) ($_GET['empresa_id'] ?? $_POST['empresa_id'] ?? 0);
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($empresaId > 0 && in_array($perfil, ['empresa', 'administrador'], true)) {
            $stmt = $pdo->prepare(
                'UPDATE empresas e JOIN empresa_equipe ee ON ee.empresa_id = e.id
                 SET e.onboarding_etapa = 3, e.onboarding_concluido = 1
                 WHERE e.id = :empresa_id AND ee.usuario_id = :usuario_id AND ee.status = "ativo"'
            );
            $stmt->execute([':empresa_id' => $empresaId, ':usuario_id' => $usuarioId]);
        } else {
            $stmt = $pdo->prepare('UPDATE usuarios SET onboarding_concluido = 1 WHERE id = :id');
            $stmt->execute([':id' => $usuarioId]);
        }
        header('Location: dashboard.php');
        exit;
    } catch (Throwable $exception) {
        $erro = 'Não foi possível salvar seu progresso. A migration 013 precisa estar aplicada.';
    }
}

$empresa = null;
if ($empresaId > 0) {
    $stmt = $pdo->prepare('SELECT id, nome_fantasia, onboarding_concluido FROM empresas WHERE id = :id');
    $stmt->execute([':id' => $empresaId]);
    $empresa = $stmt->fetch();
}

$isEmpresa = $empresa !== null;
$etapas = $isEmpresa
    ? ['Complete o perfil da empresa', 'Publique seu primeiro produto ou serviço', 'Convide sua equipe e revise o painel']
    : ['Complete seu perfil', 'Cadastre seu primeiro pet', 'Explore adoção, alertas e favoritos'];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Primeiros passos - PetFinder Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <main class="container py-5" style="max-width:760px">
        <h1>Primeiros passos</h1>
        <p class="lead">Vamos deixar sua experiência pronta para começar.</p>
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
        <ol class="list-group list-group-numbered mb-4">
            <?php foreach ($etapas as $etapa): ?>
                <li class="list-group-item py-3"><?= htmlspecialchars($etapa) ?></li><?php endforeach; ?>
        </ol>
        <form method="post">
            <?php if ($empresaId > 0): ?><input type="hidden" name="empresa_id"
                    value="<?= $empresaId ?>"><?php endif; ?>
            <button class="btn btn-primary" type="submit">Concluir onboarding</button>
            <a class="btn btn-link" href="dashboard.php">Pular por enquanto</a>
        </form>
    </main>
</body>

</html>