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
require_once "../app/Helpers/EmpresaAcesso.php";
require_once "../app/Models/Usuario.php";

$empresaController = new EmpresaController();
$produtoController  = new ProdutoController();
$pdo = Database::conectar();

$usuarioId = (int) $_SESSION["usuario_id"];
$produtoId = (int) ($_GET["id"] ?? 0);

$produto = $produtoController->buscarPorId($produtoId);

/*
|--------------------------------------------------------------------------
| Segurança: produto precisa existir e o usuário precisa ter acesso à
| empresa dona dele (dono OU equipe, via empresa_equipe)
|--------------------------------------------------------------------------
*/

if ($produto === null || !EmpresaAcesso::temAcesso($pdo, (int) $produto["empresa_id"], $usuarioId)) {
    $_SESSION["erro_empresa"] = "Produto não encontrado.";
    header("Location: minhas_empresas.php");
    exit;
}

$empresaId = (int) $produto["empresa_id"];

$subcategorias = $produtoController->listarSubcategorias();
$marcas        = $produtoController->listarMarcas();
$estoqueAtual  = $produtoController->buscarEstoque($produtoId) ?? ['quantidade' => 0, 'estoque_minimo' => 0, 'estoque_maximo' => 0];
$imagensAtuais = $produtoController->buscarImagens($produtoId);

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
            "destaque"           => isset($_POST["destaque"]) ? 1 : 0,
            "ativo"              => isset($_POST["ativo"]) ? 1 : 0
        ];

        if ($produtoController->atualizar($produtoId, $dados)) {

            $quantidade = (int) ($_POST["estoque_quantidade"] ?? 0);
            $minimo     = (int) ($_POST["estoque_minimo"] ?? 0);
            $maximo     = (int) ($_POST["estoque_maximo"] ?? 0);

            $produtoController->atualizarEstoque($produtoId, $quantidade, $minimo, $maximo);

            if (!empty($_FILES["imagens"]["name"][0])) {
                $novasImagens = $produtoController->processarImagens($_FILES["imagens"]);
                $produtoController->salvarImagens($produtoId, $novasImagens);
            }

            $_SESSION["sucesso_produto"] = "Produto atualizado com sucesso!";
            header("Location: meus_produtos.php?empresa_id=" . $empresaId);
            exit;

        }

        $mensagem     = "Não foi possível atualizar o produto. Verifique o nome e o preço de venda.";
        $tipoMensagem = "erro";

        $produto = array_merge($produto, $dados);

    }

}

$tituloPagina = "Editar Produto";

require_once "../app/Includes/header.php";
require_once "../app/Includes/menu.php";
?>

<main class="conteudo">

<div class="container">

<h1>✏️ Editar Produto</h1>

<p>Empresa: <strong><?= htmlspecialchars($produto["empresa_nome"]) ?></strong></p>

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
            <input type="text" id="nome" name="nome" class="form-control" maxlength="200" required autocomplete="off"
                value="<?= htmlspecialchars($produto["nome"] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="subcategoria_id">Subcategoria</label>
            <select id="subcategoria_id" name="subcategoria_id" class="form-select">
                <option value="">Selecione</option>
                <?php foreach ($subcategorias as $sub): ?>
                    <option value="<?= $sub["id"] ?>" <?= (int) $sub["id"] === (int) ($produto['subcategoria_id'] ?? 0) ? "selected" : "" ?>>
                        <?= htmlspecialchars($sub["nome"]) ?>
                    </option>
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
                    <option value="<?= $marca["id"] ?>" <?= (int) $marca["id"] === (int) ($produto['marca_id'] ?? 0) ? "selected" : "" ?>>
                        <?= htmlspecialchars($marca["nome"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>

<div class="grupo-form">
    <label for="descricao">Descrição</label>
    <textarea id="descricao" name="descricao" rows="4" class="form-control" autocomplete="off"><?= htmlspecialchars($produto["descricao"] ?? '') ?></textarea>
</div>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="sku">SKU (código interno)</label>
            <input type="text" id="sku" name="sku" class="form-control" maxlength="80" autocomplete="off"
                value="<?= htmlspecialchars($produto["sku"] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="codigo_barras">Código de Barras</label>
            <input type="text" id="codigo_barras" name="codigo_barras" class="form-control" maxlength="50" autocomplete="off"
                value="<?= htmlspecialchars($produto["codigo_barras"] ?? '') ?>">
        </div>
    </div>

</div>

<hr>

<h3>Preços</h3>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="preco_custo">Preço de Custo (R$)</label>
            <input type="number" step="0.01" id="preco_custo" name="preco_custo" class="form-control"
                value="<?= htmlspecialchars((string) ($produto['preco_custo'] ?? '')) ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="preco_venda">Preço de Venda (R$) *</label>
            <input type="number" step="0.01" id="preco_venda" name="preco_venda" class="form-control" required
                value="<?= htmlspecialchars((string) ($produto['preco_venda'] ?? '')) ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="preco_promocional">Preço Promocional (R$)</label>
            <input type="number" step="0.01" id="preco_promocional" name="preco_promocional" class="form-control"
                value="<?= htmlspecialchars((string) ($produto['preco_promocional'] ?? '')) ?>">
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label>
                <input type="checkbox" name="destaque" value="1" <?= !empty($produto['destaque']) ? 'checked' : '' ?>>
                Destacar este produto
            </label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label>
                <input type="checkbox" name="ativo" value="1" <?= !empty($produto['ativo']) ? 'checked' : '' ?>>
                Produto ativo (visível na loja)
            </label>
        </div>
    </div>

</div>

<hr>

<h3>Dimensões e Peso</h3>

<div class="row">

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="peso">Peso (kg)</label>
            <input type="number" step="0.01" id="peso" name="peso" class="form-control"
                value="<?= htmlspecialchars((string) ($produto['peso'] ?? '')) ?>">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="altura">Altura (cm)</label>
            <input type="number" step="0.01" id="altura" name="altura" class="form-control"
                value="<?= htmlspecialchars((string) ($produto['altura'] ?? '')) ?>">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="largura">Largura (cm)</label>
            <input type="number" step="0.01" id="largura" name="largura" class="form-control"
                value="<?= htmlspecialchars((string) ($produto['largura'] ?? '')) ?>">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="comprimento">Comprimento (cm)</label>
            <input type="number" step="0.01" id="comprimento" name="comprimento" class="form-control"
                value="<?= htmlspecialchars((string) ($produto['comprimento'] ?? '')) ?>">
        </div>
    </div>

</div>

<hr>

<h3>Estoque</h3>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="estoque_quantidade">Quantidade disponível</label>
            <input type="number" id="estoque_quantidade" name="estoque_quantidade" class="form-control" min="0"
                value="<?= (int) $estoqueAtual['quantidade'] ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="estoque_minimo">Estoque mínimo (alerta)</label>
            <input type="number" id="estoque_minimo" name="estoque_minimo" class="form-control" min="0"
                value="<?= (int) $estoqueAtual['estoque_minimo'] ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="estoque_maximo">Estoque máximo</label>
            <input type="number" id="estoque_maximo" name="estoque_maximo" class="form-control" min="0"
                value="<?= (int) $estoqueAtual['estoque_maximo'] ?>">
        </div>
    </div>

</div>

<hr>

<h3>Fotos</h3>

<?php if (!empty($imagensAtuais)): ?>

    <div class="d-flex flex-wrap gap-2 mb-3">

        <?php foreach ($imagensAtuais as $imagem): ?>

            <div style="position:relative;">

                <img
                    src="../uploads/produtos/<?= htmlspecialchars($imagem['imagem']) ?>"
                    width="90"
                    height="90"
                    style="object-fit:cover; border-radius:8px; <?= !empty($imagem['principal']) ? 'border:3px solid #28a745;' : '' ?>"
                    alt="Foto do produto">

                <?php if (!empty($imagem['principal'])): ?>
                    <span style="position:absolute; bottom:2px; left:2px; background:#28a745; color:#fff; font-size:10px; padding:1px 4px; border-radius:4px;">Principal</span>
                <?php endif; ?>

                <a
                    href="excluir_imagem_produto.php?produto_id=<?= $produtoId ?>&imagem_id=<?= (int) $imagem['id'] ?>"
                    onclick="return confirm('Remover essa foto?');"
                    style="position:absolute; top:-6px; right:-6px; background:#dc3545; color:#fff; border-radius:50%; width:22px; height:22px; display:flex; align-items:center; justify-content:center; text-decoration:none; font-size:14px;">
                    ×
                </a>

            </div>

        <?php endforeach; ?>

    </div>

<?php else: ?>

    <p class="text-muted">Nenhuma foto cadastrada ainda.</p>

<?php endif; ?>

<div class="grupo-form">
    <label for="imagens">Adicionar novas fotos</label>
    <input type="file" id="imagens" name="imagens[]" accept=".jpg,.jpeg,.png,.webp" multiple class="form-control">
</div>

<hr>

<div class="row mt-4">

    <div class="col-md-6">
        <a href="meus_produtos.php?empresa_id=<?= $empresaId ?>" class="btn btn-secondary w-100">← Cancelar</a>
    </div>

    <div class="col-md-6">
        <button type="submit" class="btn btn-success w-100">💾 Salvar Alterações</button>
    </div>

</div>

</form>

</div>

</main>

<?php require_once "../app/Includes/footer.php"; ?>

</body>

</html>
