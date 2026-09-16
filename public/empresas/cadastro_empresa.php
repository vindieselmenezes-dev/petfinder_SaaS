<?php

declare(strict_types=1);

/**
 * ==========================================================
 * CADASTRO DE EMPRESA (fluxo público, sem login)
 * ==========================================================
 * Página de cadastro completa para quem ainda NÃO tem conta: cria o
 * usuário e a empresa juntos, e mostra planos disponíveis. Não exige
 * estar logado.
 *
 * Não confundir com cadastrar_empresa.php, que serve para um usuário
 * JÁ logado adicionar mais uma empresa à própria conta.
 * ==========================================================
 */

require_once __DIR__ . '/../../app/bootstrap.php';



require_once "../../app/Models/Usuario.php";
require_once "../../app/Controllers/EmpresaController.php";
require_once "../../app/Controllers/PlanoController.php";
require_once "../../app/Helpers/ValidacaoSenha.php";
require_once "../../app/Helpers/Csrf.php";

$empresaController = new EmpresaController();
$categorias = $empresaController->listarCategorias();
$planos     = (new PlanoController())->listarAtivos();

$estados = [
    "AC","AL","AP","AM","BA","CE","DF","ES","GO",
    "MA","MT","MS","MG","PA","PB","PR","PE","PI",
    "RJ","RN","RS","RO","RR","SC","SP","SE","TO"
];

$mensagem     = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {

        $mensagem     = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";

    } else {

        /*
        |--------------------------------------------------------------
        | Dados de acesso (conta do responsável)
        |--------------------------------------------------------------
        */

        $nome           = trim($_POST["nome"] ?? "");
        $sobrenome      = trim($_POST["sobrenome"] ?? "");
        $email          = trim($_POST["email"] ?? "");
        $telefonePessoal = trim($_POST["telefone_pessoal"] ?? "");
        $senha          = $_POST["senha"] ?? "";
        $confirmarSenha = $_POST["confirmar_senha"] ?? "";

        /*
        |--------------------------------------------------------------
        | Dados da empresa
        |--------------------------------------------------------------
        */

        $nomeFantasia = trim($_POST["nome_fantasia"] ?? "");
        $categoriaId  = (int) ($_POST["categoria_id"] ?? 0);
        $cnpj         = trim($_POST["cnpj"] ?? "");

        $erroSenha = ValidacaoSenha::validar($senha);
        $usuarioModel = new Usuario();

        if (empty($nome) || empty($sobrenome) || empty($email) || empty($senha)) {

            $mensagem     = "Preencha todos os campos obrigatórios de acesso.";
            $tipoMensagem = "erro";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $mensagem     = "Informe um e-mail válido.";
            $tipoMensagem = "erro";

        } elseif ($erroSenha !== null) {

            $mensagem     = $erroSenha;
            $tipoMensagem = "erro";

        } elseif ($senha !== $confirmarSenha) {

            $mensagem     = "As senhas não conferem.";
            $tipoMensagem = "erro";

        } elseif (empty($nomeFantasia) || empty($categoriaId)) {

            $mensagem     = "Preencha o nome e a categoria da empresa.";
            $tipoMensagem = "erro";

        } elseif ($usuarioModel->emailExiste($email)) {

            $mensagem     = "Este e-mail já está cadastrado.";
            $tipoMensagem = "erro";

        } elseif ($cnpj !== "" && $empresaController->cnpjExiste($cnpj)) {

            $mensagem     = "Este CNPJ já está cadastrado.";
            $tipoMensagem = "erro";

        } else {

            /*
            |--------------------------------------------------------------
            | Cria a conta do responsável
            |--------------------------------------------------------------
            */

            $criouUsuario = $usuarioModel->cadastrar([
                "nome"      => $nome,
                "sobrenome" => $sobrenome,
                "email"     => $email,
                "telefone"  => $telefonePessoal,
                "senha"     => $senha
            ]);

            if (!$criouUsuario) {

                $mensagem     = "Não foi possível criar a conta. Tente novamente.";
                $tipoMensagem = "erro";

            } else {

                $dadosUsuario = $usuarioModel->buscarPorEmail($email);
                $novoUsuarioId = (int) $dadosUsuario["id"];

                $usuarioModel->definirPerfil($novoUsuarioId, "empresa");

                /*
                |--------------------------------------------------------------
                | Cria a empresa vinculada
                |--------------------------------------------------------------
                */

                $dadosEmpresa = [
                    "usuario_id"    => $novoUsuarioId,
                    "categoria_id"  => $categoriaId,
                    "nome_fantasia" => $nomeFantasia,
                    "razao_social"  => trim($_POST["razao_social"] ?? ""),
                    "cnpj"          => $cnpj,
                    "descricao"     => trim($_POST["descricao"] ?? ""),
                    "telefone"      => trim($_POST["telefone_empresa"] ?? ""),
                    "whatsapp"      => trim($_POST["whatsapp"] ?? ""),
                    "email"         => trim($_POST["email_empresa"] ?? ""),
                    "site"          => trim($_POST["site"] ?? ""),
                    "endereco"      => trim($_POST["endereco"] ?? ""),
                    "numero"        => trim($_POST["numero"] ?? ""),
                    "complemento"   => trim($_POST["complemento"] ?? ""),
                    "bairro"        => trim($_POST["bairro"] ?? ""),
                    "cidade"        => trim($_POST["cidade"] ?? ""),
                    "estado"        => trim($_POST["estado"] ?? ""),
                    "cep"           => trim($_POST["cep"] ?? "")
                ];

                // Plano escolhido no cadastro (valida contra os planos ativos; cai no Grátis se inválido/ausente)
                $planoEscolhidoId = (int) ($_POST["plano_id"] ?? 0);
                $planoValido = null;

                foreach ($planos as $p) {
                    if ((int) $p["id"] === $planoEscolhidoId) {
                        $planoValido = $p;
                        break;
                    }
                }

                $dadosEmpresa["plano_id"] = $planoValido ? (int) $planoValido["id"] : null;

                $novaEmpresaId = $empresaController->cadastrar($dadosEmpresa);

                if ($novaEmpresaId !== false) {

                    // Loga automaticamente
                    $_SESSION["usuario_id"]    = $novoUsuarioId;
                    $_SESSION["usuario_nome"]  = $nome;
                    $_SESSION["usuario_email"] = $email;
                    $_SESSION["perfil_tipo"]   = "empresa";

                    Flash::sucesso("Conta e empresa criadas com sucesso! Complete seu perfil com fotos e horário de funcionamento.");

                    if ($planoValido && (float) $planoValido["preco_mensal"] > 0) {
                        header("Location: simular_assinatura.php?empresa_id=" . $novaEmpresaId . "&plano_id=" . (int) $planoValido["id"]);
                        exit;
                    }

                    header('Location: ' . Url::pagina('minhas_empresas.php'));
                    exit;

                }

                $mensagem     = "A conta foi criada, mas houve um problema ao cadastrar a empresa. Fale com o suporte.";
                $tipoMensagem = "erro";

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

<title>Cadastre sua Empresa - PetFinder Brasil</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/dashboard.css">

</head>

<body style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="Voltar para a página anterior">← Voltar</button></div>

<header class="cabecalho">

    <div class="container">

        <h1><a href="../../index.html">PetFinder Brasil</a></h1>

        <p>Anuncie seu negócio para milhares de tutores de pets.</p>

    </div>

</header>

<main class="container">

<section class="formulario-cadastro">

<h2>🏢 Cadastre sua Empresa</h2>

<p>Crie sua conta e anuncie seu pet shop, clínica, hotel ou serviço em um só passo.</p>

<?php if (!empty($mensagem)): ?>

<div class="mensagem <?= $tipoMensagem ?>">
    <?= htmlspecialchars($mensagem) ?>
</div>

<?php endif; ?>

<form method="POST" action="">

<?= Csrf::campoHtml() ?>

<h3>Seus Dados de Acesso</h3>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="nome">Seu nome *</label>
            <input type="text" id="nome" name="nome" class="form-control" maxlength="150" autocomplete="off" required
                value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="sobrenome">Sobrenome *</label>
            <input type="text" id="sobrenome" name="sobrenome" class="form-control" maxlength="150" autocomplete="off" required
                value="<?= htmlspecialchars($_POST['sobrenome'] ?? '') ?>">
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="email">Seu e-mail (login) *</label>
            <input type="email" id="email" name="email" class="form-control" maxlength="180" required
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="telefone_pessoal">Seu telefone</label>
            <input type="text" id="telefone_pessoal" name="telefone_pessoal" class="form-control" placeholder="(31) 99999-9999"
                value="<?= htmlspecialchars($_POST['telefone_pessoal'] ?? '') ?>">
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="senha">Senha *</label>
            <input type="password" id="senha" name="senha" minlength="8" required>
            <small style="display:block; color:#6c757d; margin-top:4px;">
                Mínimo 8 caracteres, com 1 letra maiúscula e 1 número.
            </small>
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="confirmar_senha">Confirmar senha *</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" required>
        </div>
    </div>

</div>

<hr>

<h3>Dados da Empresa</h3>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="nome_fantasia">Nome Fantasia *</label>
            <input type="text" id="nome_fantasia" name="nome_fantasia" class="form-control" maxlength="180" autocomplete="off" required
                value="<?= htmlspecialchars($_POST['nome_fantasia'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="categoria_id">Categoria *</label>
            <select id="categoria_id" name="categoria_id" class="form-select" required>
                <option value="">Selecione</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= $categoria["id"] ?>"
                        <?= (isset($_POST['categoria_id']) && (int) $_POST['categoria_id'] === (int) $categoria['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($categoria["nome"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="razao_social">Razão Social</label>
            <input type="text" id="razao_social" name="razao_social" class="form-control" maxlength="180"
                value="<?= htmlspecialchars($_POST['razao_social'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" class="form-control" placeholder="00.000.000/0000-00" maxlength="18"
                value="<?= htmlspecialchars($_POST['cnpj'] ?? '') ?>">
        </div>
    </div>

</div>

<div class="grupo-form">
    <label for="descricao">Descrição</label>
    <textarea id="descricao" name="descricao" rows="3" class="form-control" autocomplete="off"
        placeholder="Conte um pouco sobre a empresa e os serviços oferecidos"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
</div>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="telefone_empresa">Telefone da empresa</label>
            <input type="text" id="telefone_empresa" name="telefone_empresa" class="form-control" placeholder="(31) 3333-3333"
                value="<?= htmlspecialchars($_POST['telefone_empresa'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="whatsapp">WhatsApp</label>
            <input type="text" id="whatsapp" name="whatsapp" class="form-control" placeholder="(31) 99999-9999"
                value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="email_empresa">E-mail da empresa</label>
            <input type="email" id="email_empresa" name="email_empresa" class="form-control"
                value="<?= htmlspecialchars($_POST['email_empresa'] ?? '') ?>">
        </div>
    </div>

</div>

<div class="grupo-form">
    <label for="site">Site</label>
    <input type="text" id="site" name="site" class="form-control" placeholder="https://..."
        value="<?= htmlspecialchars($_POST['site'] ?? '') ?>">
</div>

<hr>

<h3>Endereço</h3>

<div class="row">

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="cep">CEP</label>
            <input type="text" id="cep" name="cep" class="form-control" maxlength="9" placeholder="00000-000"
                value="<?= htmlspecialchars($_POST['cep'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-7">
        <div class="grupo-form">
            <label for="endereco">Rua / Avenida</label>
            <input type="text" id="endereco" name="endereco" class="form-control"
                value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-2">
        <div class="grupo-form">
            <label for="numero">Número</label>
            <input type="text" id="numero" name="numero" class="form-control"
                value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>">
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="complemento">Complemento</label>
            <input type="text" id="complemento" name="complemento" class="form-control"
                value="<?= htmlspecialchars($_POST['complemento'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="bairro">Bairro</label>
            <input type="text" id="bairro" name="bairro" class="form-control"
                value="<?= htmlspecialchars($_POST['bairro'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="cidade">Cidade</label>
            <input type="text" id="cidade" name="cidade" class="form-control"
                value="<?= htmlspecialchars($_POST['cidade'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-1">
        <div class="grupo-form">
            <label for="estado">UF</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">-</option>
                <?php foreach ($estados as $uf): ?>
                    <option value="<?= $uf ?>" <?= (($_POST['estado'] ?? '') === $uf) ? 'selected' : '' ?>><?= $uf ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>

<hr>

<h3>📈 Escolha seu Plano</h3>

<p class="text-muted small">Você pode trocar de plano depois, a qualquer momento, pelo painel da empresa.</p>

<div class="row g-3 mb-3">

    <?php foreach ($planos as $plano): ?>

        <?php
        $destaque = (bool) $plano['destaque'];
        $selecionadoPadrao = $plano['slug'] === 'gratis';
        $limite = $plano['limite_produtos'] !== null ? (int) $plano['limite_produtos'] . ' produtos/serviços' : 'Produtos/serviços ilimitados';
        ?>

        <div class="col-md-4">
            <label class="card h-100 p-3 <?= $destaque ? 'border-warning border-2' : '' ?>" style="cursor:pointer;">

                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="plano_id" value="<?= (int) $plano['id'] ?>"
                        id="plano_<?= (int) $plano['id'] ?>" <?= $selecionadoPadrao ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="plano_<?= (int) $plano['id'] ?>">
                        <?= htmlspecialchars($plano['nome']) ?>
                        <?php if ($destaque): ?><span class="badge bg-warning text-dark">⭐ Popular</span><?php endif; ?>
                    </label>
                </div>

                <div class="mb-2">
                    <?php if ((float) $plano['preco_mensal'] > 0): ?>
                        <span class="fs-5 fw-bold">R$ <?= number_format((float) $plano['preco_mensal'], 2, ',', '.') ?></span>
                        <span class="text-muted small">/mês</span>
                    <?php else: ?>
                        <span class="fs-5 fw-bold">Grátis</span>
                    <?php endif; ?>
                </div>

                <p class="small text-muted mb-1"><?= htmlspecialchars($limite) ?></p>

                <?php if ((int) $plano['dias_trial'] > 0): ?>
                    <p class="small text-success mb-0"><?= (int) $plano['dias_trial'] ?> dias grátis pra testar</p>
                <?php endif; ?>

            </label>
        </div>

    <?php endforeach; ?>

</div>

<hr>

<div class="grupo-form">
    <button type="submit" class="btn">🏢 Criar Conta e Cadastrar Empresa</button>
</div>

</form>

<p style="margin-top:20px; text-align:center;">

    Já tem uma conta?

    <a href="<?= Url::pagina('login.php') ?>">Fazer Login</a>

    &nbsp;|&nbsp;

    É um tutor de pet?

    <a href="<?= Url::pagina('cadastro.php') ?>">Cadastro normal</a>

</p>

</section>

</main>

<footer class="rodape">

    <div class="container">
        <p>
            © <?= date("Y") ?> PetFinder Brasil
            <br>
            Informação, cuidado e carinho para seu pet.
        </p>
    </div>

</footer>

<script>

const cnpjInput = document.getElementById("cnpj");

if (cnpjInput) {

    cnpjInput.addEventListener("input", function () {

        let valor = this.value.replace(/\D/g, "").slice(0, 14);

        valor = valor.replace(/^(\d{2})(\d)/, "$1.$2");
        valor = valor.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
        valor = valor.replace(/\.(\d{3})(\d)/, ".$1/$2");
        valor = valor.replace(/(\d{4})(\d)/, "$1-$2");

        this.value = valor;

    });

}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
