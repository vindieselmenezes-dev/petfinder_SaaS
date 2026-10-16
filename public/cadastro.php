<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Helpers/ValidacaoSenha.php';
require_once __DIR__ . '/../app/Models/RateLimiter.php';
require_once __DIR__ . '/../app/Models/ConfirmacaoEmail.php';

/**
 * Limite de cadastros por IP: 10 tentativas por hora (bloqueio de 1 hora).
 * Segura quem cria contas em massa (spam, testes de e-mail). Retorna os
 * minutos que faltam de bloqueio (0 = liberado).
 */
function minutosDeBloqueioDoCadastro(): int
{
    $limite = new RateLimiter();

    if ($limite->permitido('cadastro', 10, 60, 60)) {
        return 0;
    }

    return max(1, $limite->minutosRestantes('cadastro'));
}

$pdo = Database::conectar();

$mensagem = "";
$tipoMensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
        $mensagem = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";
    } elseif (($minutosBloqueio = minutosDeBloqueioDoCadastro()) > 0) {
        $mensagem = "Muitas tentativas de cadastro. Tente novamente em {$minutosBloqueio} minuto(s).";
        $tipoMensagem = "erro";
    } else {
    $nomeCompleto = trim($_POST['name'] ?? '');
    // A tela só pede "Nome Completo" num campo só, mas a coluna
    // usuarios.sobrenome é obrigatória (NOT NULL) — separamos aqui pra
    // não quebrar o INSERT abaixo.
    $partesNome = preg_split('/\s+/', $nomeCompleto, 2);
    $nome = $partesNome[0] ?? '';
    $sobrenome = $partesNome[1] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';
    // Só tutor ou empresa: administrador nunca é escolhido por quem se cadastra.
    $perfil = Auth::tipoDeCadastroPublico($_POST['perfil'] ?? null);

    if (empty($nomeCompleto) || empty($email) || empty($password)) {
        $mensagem = "Por favor, preencha todos os campos.";
        $tipoMensagem = "erro";
    } elseif (!hash_equals($password, $confirmarPassword)) {
        $mensagem = "As senhas não conferem. Digite a mesma senha nos dois campos.";
        $tipoMensagem = "erro";
    } elseif (($erroSenha = ValidacaoSenha::validar($password)) !== null) {
        $mensagem = $erroSenha;
        $tipoMensagem = "erro";
    } else {
        // Verifica duplicidade na tabela de destino padronizada
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $mensagem = "Este e-mail já está cadastrado no sistema.";
            $tipoMensagem = "erro";
        } else {
            try {
                $pdo->beginTransaction();

                // Mantém o padrão Bcrypt para os novos cadastros
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Grava diretamente na tabela 'usuarios' usando as colunas reais do banco
                $stmtUser = $pdo->prepare("INSERT INTO usuarios (nome, sobrenome, email, senha, tipo_usuario, status) VALUES (?, ?, ?, ?, ?, 'ativo')");
                $stmtUser->execute([$nome, $sobrenome, $email, $hashedPassword, $perfil]);
                $userId = $pdo->lastInsertId();

                // Cria o registro assessório na tabela intermediária do SaaS
                try {
                    $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
                    $stmtRole->execute([$perfil === 'empresa' ? 'Admin_Clinica' : 'Tutor']);
                    $roleId = $stmtRole->fetchColumn() ?: 1;

                    $stmtVinculo = $pdo->prepare("INSERT IGNORE INTO organization_user_role (organization_id, user_id, role_id) VALUES (NULL, ?, ?)");
                    $stmtVinculo->execute([$userId, (int)$roleId]);
                } catch (Exception $eVinculo) {
                    // O vínculo de perfil é complementar: não bloqueia o cadastro, mas fica registrado.
                    error_log('Falha ao criar vínculo de perfil: ' . $eVinculo->getMessage());
                }

                $pdo->commit();

                // E-mail de confirmação. A conta já existe: uma falha no envio não
                // pode desfazer o cadastro (a pessoa pede um novo link na tela
                // seguinte). Só diz "enviamos" quando a geração do link deu certo.
                $_SESSION['confirmacao_email_pendente'] = $email;
                $parametroCadastro = '';
                try {
                    $linkConfirmacao = (new ConfirmacaoEmail())->enviarLink([
                        'id'    => (int) $userId,
                        'nome'  => $nome,
                        'email' => $email,
                    ]);
                    if (SegurancaHttp::ehAmbienteLocal()) {
                        $_SESSION['confirmacao_link_local'] = $linkConfirmacao;
                    }
                    $parametroCadastro = '?cadastro=1';
                } catch (Throwable $eEnvio) {
                    error_log('Falha ao enviar confirmação de e-mail: ' . $eEnvio->getMessage());
                }

                header('Location: ' . Url::pagina('reenviar_confirmacao.php') . $parametroCadastro);
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                // O detalhe técnico (nome de tabela, coluna, SQL) vai só para o log do
                // servidor: mostrá-lo ao visitante ajudaria quem procura falhas.
                error_log('Falha ao cadastrar usuário: ' . $e->getMessage());
                $mensagem = "Não foi possível criar a conta agora. Tente novamente em instantes.";
                $tipoMensagem = "erro";
            }
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Ecossistema Pet</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #FFF8E1 !important; display: flex; justify-content: center; align-items: center; min-height: 100vh; box-sizing: border-box; margin: 0; position: relative; }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('<?= Url::asset('img/logo.png') ?>');
            background-repeat: no-repeat;
            background-position: center;
            background-size: min(90vw, 900px);
            z-index: 0;
            pointer-events: none;
        }
        .card { position: relative; z-index: 1; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #34495e; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; }
        .campo-senha { position: relative; }
        .form-group .campo-senha input { padding-right: 46px; }
        .campo-senha .btn-olho { position: absolute; top: 0; right: 0; height: 100%; width: 44px; display: flex; align-items: center; justify-content: center; background: none; border: none; color: #7f8c8d; cursor: pointer; border-radius: 0 6px 6px 0; }
        .campo-senha .btn-olho:hover, .campo-senha .btn-olho:focus-visible { color: #2c3e50; }
        .campo-senha .btn-olho svg { width: 22px; height: 22px; }
        .campo-senha .btn-olho .olho-fechado { display: none; }
        .campo-senha .btn-olho[aria-pressed="true"] .olho-aberto { display: none; }
        .campo-senha .btn-olho[aria-pressed="true"] .olho-fechado { display: block; }
        .aviso-senha { font-size: 13px; margin-top: 5px; min-height: 16px; }
        .aviso-senha.erro { color: #c0392b; }
        .aviso-senha.ok { color: #27ae60; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #2980b9; }
        .mensagem { padding: 10px; margin-bottom: 15px; border-radius: 6px; text-align: center; font-size: 14px; font-weight: bold; }
        .mensagem.erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .footer-link { text-align: center; margin-top: 15px; font-size: 14px; color: #7f8c8d; }
    </style>
</head>
<body style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="← Voltar para a página anterior">← Voltar</button></div>
    <div class="card">
        <h2>🐾 Criar Conta Única</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <?= Csrf::campoHtml() ?>
            <div class="form-group">
                <label for="name">Nome Completo</label>
                <input type="text" id="name" name="name" required placeholder="Digite seu nome">
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="Ex: meuadm@email.com">
            </div>
            <div class="form-group">
                <label for="password">Senha (<?= htmlspecialchars(ValidacaoSenha::regraTexto()) ?>)</label>
                <div class="campo-senha">
                    <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Crie uma senha segura">
                    <button type="button" class="btn-olho" aria-label="Mostrar senha" aria-pressed="false" data-alvo="password">
                        <svg class="olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="confirmar_password">Confirmar senha</label>
                <div class="campo-senha">
                    <input type="password" id="confirmar_password" name="confirmar_password" required autocomplete="new-password" placeholder="Digite a senha novamente">
                    <button type="button" class="btn-olho" aria-label="Mostrar senha" aria-pressed="false" data-alvo="confirmar_password">
                        <svg class="olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
                <div class="aviso-senha" id="avisoSenha" aria-live="polite"></div>
            </div>

            <div class="form-group">
                <label for="perfil">Tipo de Conta (Perfil)</label>
                <select id="perfil" name="perfil" required>
                    <option value="tutor">Tutor (Cliente Comum)</option>
                    <option value="empresa">Empresa (Clínica Veterinária)</option>
                </select>
            </div>

            <button type="submit" class="btn">Cadastrar no Ecossistema</button>
        </form>
        <div class="footer-link">
            Já tem uma conta? <a href="login.php" style="color: #3498db; text-decoration: none; font-weight: bold;">Faça login</a>
        </div>
    </div>
    <script>
        (function () {
            // Olho: mostra/esconde a senha do campo correspondente.
            document.querySelectorAll('.btn-olho').forEach(function (botao) {
                botao.addEventListener('click', function () {
                    var campo = document.getElementById(botao.getAttribute('data-alvo'));
                    if (!campo) return;
                    var mostrar = campo.type === 'password';
                    campo.type = mostrar ? 'text' : 'password';
                    botao.setAttribute('aria-pressed', String(mostrar));
                    botao.setAttribute('aria-label', mostrar ? 'Esconder senha' : 'Mostrar senha');
                });
            });

            // Confere a confirmação enquanto digita e antes de enviar
            // (o servidor confere de novo, isto é só pra ajudar quem digita).
            var senha = document.getElementById('password');
            var confirmar = document.getElementById('confirmar_password');
            var aviso = document.getElementById('avisoSenha');
            if (!senha || !confirmar || !aviso) return;

            function conferir() {
                if (confirmar.value === '') {
                    aviso.textContent = '';
                    aviso.className = 'aviso-senha';
                    confirmar.setCustomValidity('');
                    return true;
                }
                var igual = senha.value === confirmar.value;
                aviso.textContent = igual ? 'As senhas conferem.' : 'As senhas não conferem.';
                aviso.className = 'aviso-senha ' + (igual ? 'ok' : 'erro');
                confirmar.setCustomValidity(igual ? '' : 'As senhas não conferem.');
                return igual;
            }

            senha.addEventListener('input', conferir);
            confirmar.addEventListener('input', conferir);
        })();
    </script>
</body>
</html>
