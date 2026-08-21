<?php declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['perfil_tipo'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

require_once '../app/Models/Usuario.php';
require_once '../app/Models/Empresa.php';
$pdo = Database::conectar();

$usuarioModel = new Usuario();
$empresaModel = new Empresa();

$usuarioId = (int) ($_GET['id'] ?? 0);
$usuario = $usuarioModel->buscarPorId($usuarioId);

if (!$usuario) {
    header('Location: admin_usuarios.php');
    exit;
}

$mensagem = '';
$tipoMensagem = '';

// Perfil atual (mesma regra de fallback do login: perfis.tipo OU usuarios.tipo_usuario)
$stmtPerfil = $pdo->prepare("SELECT tipo FROM perfis WHERE usuario_id = ?");
$stmtPerfil->execute([$usuarioId]);
$perfilAtual = $stmtPerfil->fetchColumn() ?: ($usuario['tipo_usuario'] ?? 'cliente');

// ==========================================================
// PROCESSAMENTO DOS FORMULÁRIOS (todos voltam pra essa mesma tela)
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar_dados') {
        $nome = trim($_POST['nome'] ?? '');
        $sobrenome = trim($_POST['sobrenome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '') ?: null;

        if ($nome !== '' && $email !== '') {
            $usuarioModel->atualizarDados($usuarioId, $nome, $sobrenome, $email, $telefone);
            $mensagem = 'Dados atualizados com sucesso!';
            $tipoMensagem = 'sucesso';
            $usuario = $usuarioModel->buscarPorId($usuarioId);
        } else {
            $mensagem = 'Nome e e-mail são obrigatórios.';
            $tipoMensagem = 'erro';
        }
    }

    if ($acao === 'atualizar_perfil') {
        $novoPerfil = $_POST['perfil'] ?? 'cliente';
        if (in_array($novoPerfil, ['cliente', 'empresa', 'veterinario', 'administrador'], true)) {
            $usuarioModel->definirPerfil($usuarioId, $novoPerfil);
            $perfilAtual = $novoPerfil;
            $mensagem = 'Perfil atualizado com sucesso!';
            $tipoMensagem = 'sucesso';
        }
    }

    if ($acao === 'redefinir_senha') {
        $novaSenha = $_POST['nova_senha'] ?? '';
        if (strlen($novaSenha) >= 6) {
            $usuarioModel->atualizarSenha($usuarioId, password_hash($novaSenha, PASSWORD_DEFAULT));
            $mensagem = 'Senha redefinida com sucesso!';
            $tipoMensagem = 'sucesso';
        } else {
            $mensagem = 'A nova senha precisa ter pelo menos 6 caracteres.';
            $tipoMensagem = 'erro';
        }
    }
}

// ==========================================================
// DADOS PRA EXIBIÇÃO
// ==========================================================

$stmtPets = $pdo->prepare("
    SELECT p.*, e.nome as especie_nome, r.nome as raca_nome
    FROM pets p
    LEFT JOIN especies e ON p.especie_id = e.id
    LEFT JOIN racas r ON p.raca_id = r.id
    WHERE p.usuario_id = ?
");
$stmtPets->execute([$usuarioId]);
$petsDoUsuario = $stmtPets->fetchAll(PDO::FETCH_ASSOC);

$empresasDoUsuario = $empresaModel->listarPorUsuario($usuarioId);

$tituloPagina = "Usuário: " . ($usuario['nome'] ?? '');

require_once '../app/Includes/header.php';
require_once '../app/Includes/menu.php';
?>

<main class="conteudo">
<div class="container">

    <p><a href="admin_usuarios.php" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar pra Gestão de Usuários</a></p>

    <?php if ($mensagem): ?>
        <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <h1>👤 <?= htmlspecialchars(($usuario['nome'] ?? '') . ' ' . ($usuario['sobrenome'] ?? '')); ?></h1>

    <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-start;">

        <!-- DADOS BÁSICOS -->
        <div class="formulario-cadastro" style="flex:1; min-width:320px;">
            <h3 style="margin-top:0;">Dados da conta</h3>
            <form method="POST">
                <input type="hidden" name="acao" value="atualizar_dados">

                <div class="grupo-form">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" class="form-control" autocomplete="off" value="<?= htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
                </div>

                <div class="grupo-form">
                    <label for="sobrenome">Sobrenome</label>
                    <input type="text" id="sobrenome" name="sobrenome" class="form-control" autocomplete="off" value="<?= htmlspecialchars($usuario['sobrenome'] ?? ''); ?>">
                </div>

                <div class="grupo-form">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" autocomplete="off" value="<?= htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                </div>

                <div class="grupo-form">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" class="form-control" autocomplete="off" value="<?= htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn-acao" style="background:#3498db; color:white; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Salvar Dados</button>
            </form>

            <hr style="margin:25px 0; border:0; border-top:1px solid #eee;">

            <h3>Perfil de acesso</h3>
            <form method="POST" style="display:flex; gap:8px; align-items:center;">
                <input type="hidden" name="acao" value="atualizar_perfil">
                <select name="perfil" class="form-select" style="width:auto;">
                    <?php foreach (['cliente', 'empresa', 'veterinario', 'administrador'] as $tipoOpcao): ?>
                        <option value="<?= $tipoOpcao; ?>" <?= $tipoOpcao === $perfilAtual ? 'selected' : ''; ?>><?= ucfirst($tipoOpcao); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-acao" style="background:#8e44ad; color:white; padding:8px 16px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Trocar Perfil</button>
            </form>

            <hr style="margin:25px 0; border:0; border-top:1px solid #eee;">

            <h3>Redefinir senha</h3>
            <form method="POST" onsubmit="return confirm('Definir uma nova senha pra este usuário?');">
                <input type="hidden" name="acao" value="redefinir_senha">
                <div class="grupo-form">
                    <label for="nova_senha">Nova senha (mín. 6 caracteres)</label>
                    <input type="password" id="nova_senha" name="nova_senha" class="form-control" autocomplete="new-password" minlength="6" required>
                </div>
                <button type="submit" class="btn-acao" style="background:#e67e22; color:white; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Redefinir Senha</button>
            </form>
        </div>

        <!-- PETS E EMPRESAS -->
        <div style="flex:1; min-width:320px;">

            <div class="org-item" style="margin-bottom:20px;">
                <h3 style="margin-top:0;">🐾 Pets (<?= count($petsDoUsuario); ?>)</h3>
                <?php if (count($petsDoUsuario) > 0): ?>
                    <?php foreach ($petsDoUsuario as $p): ?>
                        <div style="display:flex; align-items:center; gap:12px; padding:8px 0; border-bottom:1px solid #f1f1f1;">
                            <?php if (!empty($p['foto']) && file_exists(__DIR__ . '/../uploads/pets/' . $p['foto'])): ?>
                                <img src="../uploads/pets/<?= htmlspecialchars($p['foto']); ?>" width="40" height="40" style="border-radius:50%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:40px; height:40px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center;">🐶</div>
                            <?php endif; ?>
                            <div>
                                <strong><?= htmlspecialchars($p['nome']); ?></strong>
                                <span style="font-size:12px; color:#7f8c8d; display:block;"><?= htmlspecialchars($p['especie_nome'] ?? '—'); ?> · <?= htmlspecialchars($p['status']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#95a5a6; font-style:italic; margin:0;">Nenhum pet cadastrado.</p>
                <?php endif; ?>
            </div>

            <div class="org-item">
                <h3 style="margin-top:0;">🏢 Empresas (<?= count($empresasDoUsuario); ?>)</h3>
                <?php if (count($empresasDoUsuario) > 0): ?>
                    <?php foreach ($empresasDoUsuario as $emp): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f1f1f1;">
                            <div>
                                <strong><?= htmlspecialchars($emp['nome_fantasia']); ?></strong>
                                <span style="font-size:12px; color:#7f8c8d; display:block;"><?= htmlspecialchars($emp['categoria_nome'] ?? ''); ?> · papel: <?= htmlspecialchars($emp['meu_papel']); ?></span>
                            </div>
                            <a href="suporte_admin.php#empresa-<?= (int) $emp['id']; ?>" class="btn-acao" style="background:#e67e22; color:white;" title="Acessar via personificação (com justificativa, fica registrado na auditoria)">🛠️ Acessar via Suporte</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#95a5a6; font-style:italic; margin:0;">Não faz parte de nenhuma empresa.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>
