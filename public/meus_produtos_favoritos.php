<?php declare(strict_types=1);
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Models/FavoritoProduto.php";

$favoritoModel = new FavoritoProduto();

// Lista os produtos favoritados pelo usuário logado
$produtos = $favoritoModel->listarPorUsuario((int) $_SESSION["usuario_id"]);

require_once "../app/Includes/header.php";
require_once "../app/Includes/menu.php";
?>

<main class="container" style="margin-top: 100px !important; margin-left: 240px !important; padding: 20px !important; display: block !important;">
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 40px auto 0 auto !important; position: relative !important; display: block !important;">

        <h1 style="color: #2c3e50; margin-bottom: 5px; font-family: 'Poppins', sans-serif; font-weight: bold;">🛍️ Produtos Favoritos</h1>
        <p style="color: #7f8c8d; margin-bottom: 15px; font-family: 'Poppins', sans-serif;">Aqui estão os produtos e serviços que você marcou como favoritos.</p>

        <p style="margin-bottom: 25px;">
            <a href="meus_favoritos.php" style="color: #7f8c8d; text-decoration: none; font-size: 14px;">⭐ Ver pets favoritos</a>
        </p>

        <table class="tabela-pets">
            <thead>
                <tr>
                    <th style="text-align:center; width:100px;">Foto</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Status</th>
                    <th>Favoritado em</th>
                    <th style="text-align:center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($produtos) > 0): ?>
                    <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td style="text-align:center;">
                                <?php
                                if (!empty($produto['imagem_principal']) && file_exists("../uploads/produtos/" . $produto['imagem_principal'])) {
                                    $caminhoFoto = "../uploads/produtos/" . $produto['imagem_principal'];
                                    echo '<img src="' . htmlspecialchars($caminhoFoto) . '" width="55" height="55" style="object-fit: cover; border-radius: 8px;" alt="Produto">';
                                } else {
                                    echo '<div style="width: 55px; height: 55px; background: #e2e8f0; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 20px;">🛍️</div>';
                                }
                                ?>
                            </td>
                            <td style="font-weight: bold;">
                                <?= htmlspecialchars($produto["nome"] ?? 'Sem nome'); ?>
                            </td>
                            <td>
                                <?php if (!empty($produto['preco_promocional'])): ?>
                                    <span style="text-decoration:line-through; color:#999;">R$ <?= number_format((float) $produto['preco_venda'], 2, ',', '.'); ?></span><br>
                                    <strong>R$ <?= number_format((float) $produto['preco_promocional'], 2, ',', '.'); ?></strong>
                                <?php else: ?>
                                    R$ <?= number_format((float) ($produto['preco_venda'] ?? 0), 2, ',', '.'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) ($produto['ativo'] ?? 0) === 1): ?>
                                    <span class="badge-status badge-com-tutor">Disponível</span>
                                <?php else: ?>
                                    <span class="badge-status badge-perdido">Indisponível</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= !empty($produto["criado_em"]) ? date("d/m/Y", strtotime($produto["criado_em"])) : 'Não informado'; ?>
                            </td>
                            <td style="text-align:center; white-space:nowrap;">
                                <a class="btn-excluir" href="favoritar_produto.php?produto_id=<?= (int) $produto['produto_id']; ?>&acao=remover">🗑 Remover</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: #95a5a6; font-style: italic;">Você ainda não favoritou nenhum produto. 🛍️</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once "../app/Includes/footer.php"; ?>
