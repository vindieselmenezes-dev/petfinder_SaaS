<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/EmpresaController.php";
require_once "../app/Controllers/ProdutoController.php";
require_once "../app/Helpers/EmpresaAcesso.php";
require_once "../app/Helpers/Csrf.php";
require_once "../app/Models/Usuario.php";

$empresaController = new EmpresaController();
$produtoController = new ProdutoController();
$pdo = Database::conectar();

$usuarioId = (int) $_SESSION["usuario_id"];
$empresaId = (int) ($_GET["empresa_id"] ?? 0);

$empresa = $empresaController->buscarPorId($empresaId);

if ($empresa === null || !EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId)) {
    $_SESSION["erro_empresa"] = "Empresa não encontrada.";
    header("Location: minhas_empresas.php");
    exit;
}

$produtos = $produtoController->listarPorEmpresa($empresaId);

$mensagem = "";
$tipoMensagem = "";

if (!empty($_SESSION["sucesso_produto"])) {
    $mensagem = $_SESSION["sucesso_produto"];
    $tipoMensagem = "sucesso";
    unset($_SESSION["sucesso_produto"]);
} elseif (!empty($_SESSION["erro_produto"])) {
    $mensagem = $_SESSION["erro_produto"];
    $tipoMensagem = "erro";
    unset($_SESSION["erro_produto"]);
}

require_once "../app/Includes/header.php";
require_once "../app/Includes/menu.php";
?>

<main class="conteudo">

    <h1>📦 Produtos - <?= htmlspecialchars($empresa["nome_fantasia"]) ?></h1>

    <p><a href="minhas_empresas.php">← Voltar para Minhas Empresas</a></p>

    <?php if (!empty($mensagem)): ?>

        <div class="mensagem <?= $tipoMensagem; ?>">
            <?= htmlspecialchars($mensagem); ?>
        </div>

    <?php endif; ?>

    <div class="mb-3">
        <a href="cadastrar_produto.php?empresa_id=<?= $empresaId ?>" class="btn btn-success">➕ Cadastrar Novo
            Produto</a>
    </div>

    <table class="tabela-pets">

        <thead>
            <tr>
                <th>Foto</th>
                <th>Nome</th>
                <th>Subcategoria</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>

            <?php if (count($produtos) > 0): ?>

                <?php foreach ($produtos as $produto): ?>

                    <tr>

                        <td>
                            <img src="<?= !empty($produto['imagem_principal']) ? '../uploads/produtos/' . htmlspecialchars($produto['imagem_principal']) : '../assets/img/pets/sem-foto.png'; ?>"
                                width="60" height="60" style="object-fit:cover; border-radius:8px;" alt="Produto">
                        </td>

                        <td>
                            <?= htmlspecialchars($produto['nome'] ?? ''); ?>
                            <?php if (!empty($produto['destaque'])): ?>
                                <span class="badge-status badge-para-adocao">⭐ Destaque</span>
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($produto['subcategoria_nome'] ?? 'Não informada'); ?></td>

                        <td>
                            <?php if (!empty($produto['preco_promocional'])): ?>
                                <span style="text-decoration:line-through; color:#999;">R$
                                    <?= number_format((float) $produto['preco_venda'], 2, ',', '.'); ?></span><br>
                                <strong>R$ <?= number_format((float) $produto['preco_promocional'], 2, ',', '.'); ?></strong>
                            <?php else: ?>
                                R$ <?= number_format((float) $produto['preco_venda'], 2, ',', '.'); ?>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= (int) $produto['estoque_quantidade']; ?> un.
                        </td>

                        <td>
                            <?php if ((int) $produto['ativo'] === 1): ?>
                                <span class="badge-status badge-com-tutor">Ativo</span>
                            <?php else: ?>
                                <span class="badge-status badge-perdido">Inativo</span>
                            <?php endif; ?>
                        </td>

                        <td>

                            <a class="btn-editar" href="editar_produto.php?id=<?= (int) $produto['id']; ?>">✏️ Editar</a>

                            <?php if (!empty($produto['destaque'])): ?>
                                <form action="alterar_destaque.php" method="post" style="display:inline">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                                    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                                    <input type="hidden" name="destaque" value="0">
                                    <button class="btn-excluir" type="submit">Remover destaque</button>
                                </form>
                            <?php endif; ?>

                            <a class="btn-excluir"
                                href="excluir_produto.php?id=<?= (int) $produto['id']; ?>&empresa_id=<?= $empresaId; ?>"
                                onclick="return confirm('Deseja excluir este produto?');">
                                🗑 Excluir
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7">Nenhum produto cadastrado ainda.</td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</main>

<?php
require_once "../app/Includes/footer.php";
?>