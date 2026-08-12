<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/EmpresaController.php";
require_once "../app/Helpers/Csrf.php";

$controller = new EmpresaController();
$categorias = $controller->listarCategorias();

$mensagem     = "";
$tipoMensagem = "";

$diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];

$estados = [
    "AC","AL","AP","AM","BA","CE","DF","ES","GO",
    "MA","MT","MS","MG","PA","PB","PR","PE","PI",
    "RJ","RN","RS","RO","RR","SC","SP","SE","TO"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {

        $mensagem     = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";

    } else {

        $dados = [
            "usuario_id"    => $_SESSION["usuario_id"],
            "categoria_id"  => (int) ($_POST["categoria_id"] ?? 0),
            "nome_fantasia" => trim($_POST["nome_fantasia"] ?? ""),
            "razao_social"  => trim($_POST["razao_social"] ?? ""),
            "cnpj"          => trim($_POST["cnpj"] ?? ""),
            "descricao"     => trim($_POST["descricao"] ?? ""),
            "telefone"      => trim($_POST["telefone"] ?? ""),
            "whatsapp"      => trim($_POST["whatsapp"] ?? ""),
            "email"         => trim($_POST["email"] ?? ""),
            "site"          => trim($_POST["site"] ?? ""),
            "endereco"      => trim($_POST["endereco"] ?? ""),
            "numero"        => trim($_POST["numero"] ?? ""),
            "complemento"   => trim($_POST["complemento"] ?? ""),
            "bairro"        => trim($_POST["bairro"] ?? ""),
            "cidade"        => trim($_POST["cidade"] ?? ""),
            "estado"        => trim($_POST["estado"] ?? ""),
            "cep"           => trim($_POST["cep"] ?? "")
        ];

        /*
        |--------------------------------------------------------------
        | Upload de logo e capa (opcionais)
        |--------------------------------------------------------------
        */

        $dados["logo"] = null;
        $dados["capa"] = null;

        if (!empty($_FILES["logo"]["name"])) {
            $dados["logo"] = $controller->processarImagemUnica($_FILES["logo"]);
        }

        if (!empty($_FILES["capa"]["name"])) {
            $dados["capa"] = $controller->processarImagemUnica($_FILES["capa"]);
        }

        $novoId = $controller->cadastrar($dados);

        if ($novoId !== false) {

            /*
            |--------------------------------------------------------------
            | Salva horários de funcionamento
            |--------------------------------------------------------------
            */

            $controller->salvarHorarios($novoId, $_POST["horario"] ?? []);

            /*
            |--------------------------------------------------------------
            | Salva galeria de fotos (opcional, múltiplas)
            |--------------------------------------------------------------
            */

            if (!empty($_FILES["galeria"]["name"][0])) {
                $imagensGaleria = $controller->processarGaleria($_FILES["galeria"]);
                $controller->salvarGaleria($novoId, $imagensGaleria);
            }

            $_SESSION["sucesso_empresa"] = "Empresa cadastrada com sucesso!";
            header("Location: minhas_empresas.php");
            exit;

        }

        $mensagem     = "Não foi possível cadastrar a empresa. Verifique o nome, a categoria, e se o CNPJ já não está em uso.";
        $tipoMensagem = "erro";

    }

}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Cadastrar Empresa</title>

<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">

</head>

<body>

<?php require_once "../app/Includes/header.php"; ?>

<?php require_once "../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>🏢 Cadastrar Empresa</h1>

<p>Anuncie seu pet shop, clínica, hotel ou qualquer serviço para pets na plataforma.</p>

<?php if (!empty($mensagem)): ?>

<div class="mensagem <?= $tipoMensagem ?>">
    <?= htmlspecialchars($mensagem) ?>
</div>

<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<?= Csrf::campoHtml() ?>

<h3>Dados da Empresa</h3>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="nome_fantasia">Nome Fantasia *</label>
            <input type="text" id="nome_fantasia" name="nome_fantasia" class="form-control" maxlength="180" required>
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="categoria_id">Categoria *</label>
            <select id="categoria_id" name="categoria_id" class="form-select" required>
                <option value="">Selecione</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= $categoria["id"] ?>"><?= htmlspecialchars($categoria["nome"]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="razao_social">Razão Social</label>
            <input type="text" id="razao_social" name="razao_social" class="form-control" maxlength="180">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" class="form-control" placeholder="00.000.000/0000-00" maxlength="18">
        </div>
    </div>

</div>

<div class="grupo-form">
    <label for="descricao">Descrição</label>
    <textarea id="descricao" name="descricao" rows="4" class="form-control" placeholder="Conte um pouco sobre a empresa, os serviços oferecidos, diferenciais..."></textarea>
</div>

<hr>

<h3>Contato</h3>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" class="form-control" placeholder="(31) 3333-3333">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="whatsapp">WhatsApp</label>
            <input type="text" id="whatsapp" name="whatsapp" class="form-control" placeholder="(31) 99999-9999">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" class="form-control">
        </div>
    </div>

</div>

<div class="grupo-form">
    <label for="site">Site</label>
    <input type="text" id="site" name="site" class="form-control" placeholder="https://...">
</div>

<hr>

<h3>Endereço</h3>

<div class="row">

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="cep">CEP</label>
            <input type="text" id="cep" name="cep" class="form-control" maxlength="9" placeholder="00000-000">
        </div>
    </div>

    <div class="col-md-7">
        <div class="grupo-form">
            <label for="endereco">Rua / Avenida</label>
            <input type="text" id="endereco" name="endereco" class="form-control">
        </div>
    </div>

    <div class="col-md-2">
        <div class="grupo-form">
            <label for="numero">Número</label>
            <input type="text" id="numero" name="numero" class="form-control">
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="complemento">Complemento</label>
            <input type="text" id="complemento" name="complemento" class="form-control">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="bairro">Bairro</label>
            <input type="text" id="bairro" name="bairro" class="form-control">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="cidade">Cidade</label>
            <input type="text" id="cidade" name="cidade" class="form-control">
        </div>
    </div>

    <div class="col-md-1">
        <div class="grupo-form">
            <label for="estado">UF</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">-</option>
                <?php foreach ($estados as $uf): ?>
                    <option value="<?= $uf ?>"><?= $uf ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>

<hr>

<h3>Fotos</h3>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="logo">Logo</label>
            <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp" class="form-control">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="capa">Imagem de Capa</label>
            <input type="file" id="capa" name="capa" accept=".jpg,.jpeg,.png,.webp" class="form-control">
        </div>
    </div>

</div>

<div class="grupo-form">
    <label for="galeria">Galeria de Fotos (pode escolher várias)</label>
    <input type="file" id="galeria" name="galeria[]" accept=".jpg,.jpeg,.png,.webp" multiple class="form-control">
</div>

<hr>

<h3>Horário de Funcionamento</h3>

<div class="table-responsive">

    <table class="table align-middle">

        <thead>
            <tr>
                <th>Dia</th>
                <th>Abertura</th>
                <th>Fechamento</th>
                <th>Fechado</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($diasSemana as $dia): ?>

                <tr>
                    <td><?= $dia ?></td>
                    <td>
                        <input type="time" name="horario[<?= $dia ?>][abertura]" class="form-control">
                    </td>
                    <td>
                        <input type="time" name="horario[<?= $dia ?>][fechamento]" class="form-control">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" name="horario[<?= $dia ?>][fechado]" value="1" class="form-check-input">
                    </td>
                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>

<hr>

<div class="row mt-4">

    <div class="col-md-6">
        <a href="dashboard.php" class="btn btn-secondary w-100">← Cancelar</a>
    </div>

    <div class="col-md-6">
        <button type="submit" class="btn btn-success w-100">🏢 Cadastrar Empresa</button>
    </div>

</div>

</form>

</div>

</main>

<?php require_once "../app/Includes/footer.php"; ?>

<script>

/*
|--------------------------------------------------------------------------
| Máscara simples de CNPJ (só formata visualmente, servidor limpa de novo)
|--------------------------------------------------------------------------
*/

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

</body>

</html>
