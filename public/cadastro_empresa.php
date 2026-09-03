<?php

declare(strict_types=1);

session_start();

require_once "../app/Models/Usuario.php";
require_once "../app/Controllers/EmpresaController.php";
require_once "../app/Helpers/ValidacaoSenha.php";
require_once "../app/Helpers/Csrf.php";

$empresaController = new EmpresaController();
$categorias = $empresaController->listarCategorias();

$estados = [
    "AC",
    "AL",
    "AP",
    "AM",
    "BA",
    "CE",
    "DF",
    "ES",
    "GO",
    "MA",
    "MT",
    "MS",
    "MG",
    "PA",
    "PB",
    "PR",
    "PE",
    "PI",
    "RJ",
    "RN",
    "RS",
    "RO",
    "RR",
    "SC",
    "SP",
    "SE",
    "TO"
];

$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {

        $mensagem = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";

    } else {

        /*
        |--------------------------------------------------------------
        | Dados de acesso (conta do responsável)
        |--------------------------------------------------------------
        */

        $nome = trim($_POST["nome"] ?? "");
        $sobrenome = trim($_POST["sobrenome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $telefonePessoal = trim($_POST["telefone_pessoal"] ?? "");
        $senha = $_POST["senha"] ?? "";
        $confirmarSenha = $_POST["confirmar_senha"] ?? "";

        /*
        |--------------------------------------------------------------
        | Dados da empresa
        |--------------------------------------------------------------
        */

        $nomeFantasia = trim($_POST["nome_fantasia"] ?? "");
        $categoriaId = (int) ($_POST["categoria_id"] ?? 0);
        $cnpj = trim($_POST["cnpj"] ?? "");

        $erroSenha = ValidacaoSenha::validar($senha);
        $usuarioModel = new Usuario();

        if (empty($nome) || empty($sobrenome) || empty($email) || empty($senha)) {

            $mensagem = "Preencha todos os campos obrigatórios de acesso.";
            $tipoMensagem = "erro";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $mensagem = "Informe um e-mail válido.";
            $tipoMensagem = "erro";

        } elseif ($erroSenha !== null) {

            $mensagem = $erroSenha;
            $tipoMensagem = "erro";

        } elseif ($senha !== $confirmarSenha) {

            $mensagem = "As senhas não conferem.";
            $tipoMensagem = "erro";

        } elseif (empty($nomeFantasia) || empty($categoriaId)) {

            $mensagem = "Preencha o nome e a categoria da empresa.";
            $tipoMensagem = "erro";

        } elseif ($usuarioModel->emailExiste($email)) {

            $mensagem = "Este e-mail já está cadastrado.";
            $tipoMensagem = "erro";

        } elseif ($cnpj !== "" && $empresaController->cnpjExiste($cnpj)) {

            $mensagem = "Este CNPJ já está cadastrado.";
            $tipoMensagem = "erro";

        } else {

            /*
            |--------------------------------------------------------------
            | Cria a conta do responsável
            |--------------------------------------------------------------
            */

            $criouUsuario = $usuarioModel->cadastrar([
                "nome" => $nome,
                "sobrenome" => $sobrenome,
                "email" => $email,
                "telefone" => $telefonePessoal,
                "senha" => $senha
            ]);

            if (!$criouUsuario) {

                $mensagem = "Não foi possível criar a conta. Tente novamente.";
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
                    "usuario_id" => $novoUsuarioId,
                    "categoria_id" => $categoriaId,
                    "nome_fantasia" => $nomeFantasia,
                    "razao_social" => trim($_POST["razao_social"] ?? ""),
                    "cnpj" => $cnpj,
                    "descricao" => trim($_POST["descricao"] ?? ""),
                    "telefone" => trim($_POST["telefone_empresa"] ?? ""),
                    "whatsapp" => trim($_POST["whatsapp"] ?? ""),
                    "email" => trim($_POST["email_empresa"] ?? ""),
                    "site" => trim($_POST["site"] ?? ""),
                    "endereco" => trim($_POST["endereco"] ?? ""),
                    "numero" => trim($_POST["numero"] ?? ""),
                    "complemento" => trim($_POST["complemento"] ?? ""),
                    "bairro" => trim($_POST["bairro"] ?? ""),
                    "cidade" => trim($_POST["cidade"] ?? ""),
                    "estado" => trim($_POST["estado"] ?? ""),
                    "cep" => trim($_POST["cep"] ?? "")
                ];

                $novaEmpresaId = $empresaController->cadastrar($dadosEmpresa);

                if ($novaEmpresaId !== false) {

                    // Loga automaticamente
                    $_SESSION["usuario_id"] = $novoUsuarioId;
                    $_SESSION["usuario_nome"] = $nome;
                    $_SESSION["usuario_email"] = $email;
                    $_SESSION["perfil_tipo"] = "empresa";

                    $_SESSION["sucesso_empresa"] = "Conta e empresa criadas com sucesso! Complete seu perfil com fotos e horário de funcionamento.";

                    header("Location: minhas_empresas.php");
                    exit;

                }

                $mensagem = "A conta foi criada, mas houve um problema ao cadastrar a empresa. Fale com o suporte.";
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

</head>

<body>

    <header class="cabecalho">

        <div class="container">

            <h1><a href="../index.html">PetFinder Brasil</a></h1>

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
                            <input type="text" id="nome" name="nome" class="form-control" maxlength="150"
                                autocomplete="off" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="grupo-form">
                            <label for="sobrenome">Sobrenome *</label>
                            <input type="text" id="sobrenome" name="sobrenome" class="form-control" maxlength="150"
                                autocomplete="off" required value="<?= htmlspecialchars($_POST['sobrenome'] ?? '') ?>">
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
                            <input type="text" id="telefone_pessoal" name="telefone_pessoal" class="form-control"
                                placeholder="(31) 99999-9999"
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
                            <input type="text" id="nome_fantasia" name="nome_fantasia" class="form-control"
                                maxlength="180" autocomplete="off" required
                                value="<?= htmlspecialchars($_POST['nome_fantasia'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="grupo-form">
                            <label for="categoria_id">Categoria *</label>
                            <select id="categoria_id" name="categoria_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria["id"] ?>" <?= (isset($_POST['categoria_id']) && (int) $_POST['categoria_id'] === (int) $categoria['id']) ? 'selected' : '' ?>>
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
                            <input type="text" id="razao_social" name="razao_social" class="form-control"
                                maxlength="180" value="<?= htmlspecialchars($_POST['razao_social'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="grupo-form">
                            <label for="cnpj">CNPJ</label>
                            <input type="text" id="cnpj" name="cnpj" class="form-control"
                                placeholder="00.000.000/0000-00" maxlength="18"
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
                            <input type="text" id="telefone_empresa" name="telefone_empresa" class="form-control"
                                placeholder="(31) 3333-3333"
                                value="<?= htmlspecialchars($_POST['telefone_empresa'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="grupo-form">
                            <label for="whatsapp">WhatsApp</label>
                            <input type="text" id="whatsapp" name="whatsapp" class="form-control"
                                placeholder="(31) 99999-9999" value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>">
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
                            <input type="text" id="cep" name="cep" class="form-control" maxlength="9"
                                placeholder="00000-000" value="<?= htmlspecialchars($_POST['cep'] ?? '') ?>">
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
                                    <option value="<?= $uf ?>" <?= (($_POST['estado'] ?? '') === $uf) ? 'selected' : '' ?>>
                                        <?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>

                <hr>

                <div class="grupo-form">
                    <button type="submit" class="btn">🏢 Criar Conta e Cadastrar Empresa</button>
                </div>

            </form>

            <p style="margin-top:20px; text-align:center;">

                Já tem uma conta?

                <a href="login.php">Fazer Login</a>

                &nbsp;|&nbsp;

                É um tutor de pet?

                <a href="cadastro.php">Cadastro normal</a>

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

    <script src="../assets/js/click-sounds.js"></script>
</body>

</html>