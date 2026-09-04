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
$etapaAtual = (int) ($_GET['etapa'] ?? 0);

$empresa = null;
if ($empresaId > 0) {
    $stmt = $pdo->prepare('SELECT id, nome_fantasia, onboarding_concluido, onboarding_etapa FROM empresas WHERE id = :id');
    $stmt->execute([':id' => $empresaId]);
    $empresa = $stmt->fetch();
    $etapaAtual = (int) ($empresa['onboarding_etapa'] ?? $etapaAtual);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($empresaId > 0 && in_array($perfil, ['empresa', 'administrador'], true)) {
            $proximaEtapa = min(3, $etapaAtual + 1);
            $stmt = $pdo->prepare('UPDATE empresas e JOIN empresa_equipe ee ON ee.empresa_id = e.id SET e.onboarding_etapa = :etapa, e.onboarding_concluido = :concluido WHERE e.id = :empresa_id AND ee.usuario_id = :usuario_id AND ee.status = "ativo"');
            $stmt->execute([':etapa' => $proximaEtapa, ':concluido' => $proximaEtapa >= 3 ? 1 : 0, ':empresa_id' => $empresaId, ':usuario_id' => $usuarioId]);
        } else {
            $proximaEtapa = min(3, $etapaAtual + 1);
            $stmt = $pdo->prepare('UPDATE usuarios SET onboarding_concluido = :concluido WHERE id = :id');
            $stmt->execute([':concluido' => $proximaEtapa >= 3 ? 1 : 0, ':id' => $usuarioId]);
        }
        if ($proximaEtapa >= 3) {
            header('Location: dashboard.php');
            exit;
        }
        $parametroEmpresa = $empresaId > 0 ? '&empresa_id=' . $empresaId : '';
        header('Location: onboarding.php?etapa=' . $proximaEtapa . $parametroEmpresa);
        exit;
    } catch (Throwable $exception) {
        $erro = 'Não foi possível salvar seu progresso. A migration 013 precisa estar aplicada.';
    }
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
        <div class="progress mb-4" role="progressbar" aria-label="Progresso do onboarding"
            aria-valuenow="<?= $etapaAtual ?>" aria-valuemin="0" aria-valuemax="3">
            <div class="progress-bar" style="width: <?= (int) (($etapaAtual / 3) * 100) ?>%"></div>
        </div>
        <ol class="list-group list-group-numbered mb-4">
            <?php foreach ($etapas as $indice => $etapa): ?>
                <li
                    class="list-group-item py-3 <?= $indice < $etapaAtual ? 'list-group-item-success' : ($indice === $etapaAtual ? 'list-group-item-primary' : '') ?>">
                    <?= htmlspecialchars($etapa) ?>
                    <span
                        class="float-end"><?= $indice < $etapaAtual ? 'Concluído' : ($indice === $etapaAtual ? 'Em andamento' : '') ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <form method="post">
            <?php if ($empresaId > 0): ?><input type="hidden" name="empresa_id"
                    value="<?= $empresaId ?>"><?php endif; ?>
            <input type="hidden" name="etapa" value="<?= $etapaAtual ?>">
            <?php if ($etapaAtual === 0): ?><a class="btn btn-outline-primary"
                    href="<?= $isEmpresa ? 'editar_empresa.php?id=' . $empresaId : 'meu_perfil.php' ?>">Completar
                    etapa</a><?php elseif ($etapaAtual === 1): ?><a class="btn btn-outline-primary"
                    href="<?= $isEmpresa ? 'cadastrar_produto.php?empresa_id=' . $empresaId : 'cadastrar_pet.php' ?>">Completar
                    etapa</a><?php else: ?><a class="btn btn-outline-primary"
                    href="<?= $isEmpresa ? 'painel_b2b.php?empresa_id=' . $empresaId : 'pets_adocao.php' ?>">Explorar</a><?php endif; ?>
            <button class="btn btn-primary"
                type="submit"><?= $etapaAtual >= 2 ? 'Concluir onboarding' : 'Avançar etapa' ?></button>
            <a class="btn btn-link" href="dashboard.php">Pular por enquanto</a>
        </form>
    </main>
</body>

</html>