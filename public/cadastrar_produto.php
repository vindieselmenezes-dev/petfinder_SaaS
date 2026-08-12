<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/EmpresaController.php";
require_once "../app/Controllers/ProdutoController.php";
require_once "../app/Helpers/Csrf.php";

$empresaController = new EmpresaController();
$produtoController  = new ProdutoController();

$usuarioId = (int) $_SESSION["usuario_id"];
$empresaId = (int) ($_GET["empresa_id"] ?? 0);

$empresa = $empresaController->buscarPorId($empresaId);

/*
|--------------------------------------------------------------------------
| Segurança: empresa precisa existir e pertencer ao usuário logado
|--------------------------------------------------------------------------
*/

if ($empresa === null || (int) $empresa["usuario_id"] !== $usuarioId) {
    $_SESSION["erro_empresa"] = "Empresa não encontrada.";
    header("Location: minhas_empresas.php");
    exit;
}

$subcategorias = $produtoController->listarSubcategorias();
$marcas        = $produtoController->listarMarcas();

$mensagem     = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {

        $mensagem     = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";

    } else {

        $dados = [
            "empresa_id"         => $empresaId,
            "subcategoria_id"    => $_POST["subcategoria_id"] ?? "",
            "marca_id"           => $_POST["marca_id"] ?? "",
            "nome"               => trim($_POST["nome"] ?? ""),
            "descricao"          => trim($_POST["descricao"] ?? ""),
            "sku"                => trim($_POST["sku"] ?? ""),
            "codigo_barras"      => trim($_POST["codigo_barras"] ?? ""),
            "peso"               => $_POST["peso"] ?? "",
            "altura"             => $_POST["altura"] ?? "",
            "largura"            => $_POST["largura"] ?? "",
            "comprimento"        => $_POST["comprimento"] ?? "",
            "preco_custo"        => $_POST["preco_custo"] ?? "",
            "preco_venda"        => $_POST["preco_venda"] ?? "",
            "preco_promocional"  => $_POST["preco_promocional"] ?? "",
            "destaque"           => isset($_POST["destaque"]) ? 1 : 0
        ];

        $novoId = $produtoController->cadastrar($dados);

        if ($novoId !== false) {

            /*
            |--------------------------------------------------------------
            | Estoque inicial
            |--------------------------------------------------------------
            */

            $quantidade = (int) ($_POST["estoque_quantidade"] ?? 0);
            $minimo     = (int) ($_POST["estoque_minimo"] ?? 0);
            $maximo     = (int) ($_POST["estoque_maximo"] ?? 0);

            $produtoController->atualizarEstoque($novoId, $quantidade, $minimo, $maximo);

            /*
            |--------------------------------------------------------------
            | Imagens
            |--------------------------------------------------------------
            */

            if (!empty($_FILES["imagens"]["name"][0])) {
                $imagensSalvas = $produtoController->processarImagens($_FILES["imagens"]);
                $produtoController->salvarImagens($novoId, $imagensSalvas);
            }

            $_SESSION["sucesso_produto"] = "Produto cadastrado com sucesso!";
            header("Location: meus_produtos.php?empresa_id=" . $empresaId);
            exit;

        }

        $mensagem     = "Não foi possível cadastrar o produto. Verifique o nome e o preço de venda.";
        $tipoMensagem = "erro";

    }

}

require_once "../app/Includes/header.php";
require_once "../app/Includes/menu.php";
?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Cadastrar Produto</title>

<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">

</head>

<body>

<main class="conteudo">

<div class="container">

<h1>📦 Cadastrar Produto</h1>

<p>Empresa: <strong><?= htmlspecialchars($empresa["nome_fantasia"]) ?></strong></p>

<?php if (!empty($mensagem)): ?>

<div class="mensagem <?= $tipoMensagem ?>">
    <?= htmlspecialchars($mensagem) ?>
</div>

<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<?= Csrf::campoHtml() ?>

<h3>Dados do Produto</h3>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="nome">Nome do Produto *</label>
            <input type="text" id="nome" name="nome" class="form-control" maxlength="200" required>
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="subcategoria_id">Subcategoria</label>
            <select id="subcategoria_id" name="subcategoria_id" class="form-select">
                <option value="">Selecione</option>
                <?php foreach ($subcategorias as $sub): ?>
                    <option value="<?= $sub["id"] ?>"><?= htmlspecialchars($sub["nome"]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="marca_id">Marca</label>
            <select id="marca_id" name="marca_id" class="form-select">
                <option value="">Sem marca</option>
                <?php foreach ($marcas as $marca): ?>
                    <option value="<?= $marca["id"] ?>"><?= htmlspecialchars($marca["nome"]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>

<div class="grupo-form">
    <label for="descricao">Descrição</label>
    <textarea id="descricao" name="descricao" rows="4" class="form-control"></textarea>
</div>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="sku">SKU (código interno)</label>
            <input type="text" id="sku" name="sku" class="form-control" maxlength="80">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="codigo_barras">Código de Barras</label>
            <input type="text" id="codigo_barras" name="codigo_barras" class="form-control" maxlength="50">
        </div>
    </div>

</div>

<hr>

<h3>Preços</h3>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="preco_custo">Preço de Custo (R$)</label>
            <input type="number" step="0.01" id="preco_custo" name="preco_custo" class="form-control">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="preco_venda">Preço de Venda (R$) *</label>
            <input type="number" step="0.01" id="preco_venda" name="preco_venda" class="form-control" required>
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="preco_promocional">Preço Promocional (R$)</label>
            <input type="number" step="0.01" id="preco_promocional" name="preco_promocional" class="form-control">
        </div>
    </div>

</div>

<div class="grupo-form">
    <label>
        <input type="checkbox" name="destaque" value="1">
        Destacar este produto (aparece em primeiro na busca)
    </label>
</div>

<hr>

<h3>Dimensões e Peso (opcional, útil pra frete)</h3>

<div class="row">

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="peso">Peso (kg)</label>
            <input type="number" step="0.01" id="peso" name="peso" class="form-control">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="altura">Altura (cm)</label>
            <input type="number" step="0.01" id="altura" name="altura" class="form-control">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="largura">Largura (cm)</label>
            <input type="number" step="0.01" id="largura" name="largura" class="form-control">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="comprimento">Comprimento (cm)</label>
            <input type="number" step="0.01" id="comprimento" name="comprimento" class="form-control">
        </div>
    </div>

</div>

<hr>

<h3>Estoque</h3>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="estoque_quantidade">Quantidade disponível</label>
            <input type="number" id="estoque_quantidade" name="estoque_quantidade" class="form-control" value="0" min="0">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="estoque_minimo">Estoque mínimo (alerta)</label>
            <input type="number" id="estoque_minimo" name="estoque_minimo" class="form-control" value="0" min="0">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="estoque_maximo">Estoque máximo</label>
            <input type="number" id="estoque_maximo" name="estoque_maximo" class="form-control" value="0" min="0">
        </div>
    </div>

</div>

<hr>

<h3>Fotos</h3>

<div class="grupo-form">
    <label for="imagens">Fotos do produto (pode escolher várias, a primeira vira a foto principal)</label>
    <input type="file" id="imagens" name="imagens[]" accept=".jpg,.jpeg,.png,.webp" multiple class="form-control">
</div>

<hr>

<div class="row mt-4">

    <div class="col-md-6">
        <a href="meus_produtos.php?empresa_id=<?= $empresaId ?>" class="btn btn-secondary w-100">← Cancelar</a>
    </div>

    <div class="col-md-6">
        <button type="submit" class="btn btn-success w-100">📦 Cadastrar Produto</button>
    </div>

</div>

</form>

</div>

</main>

<?php require_once "../app/Includes/footer.php"; ?>

</body>

</html>
