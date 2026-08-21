<?php
declare(strict_types=1);
session_start();

require_once "../app/Controllers/PetController.php";
require_once "../app/Models/Favorito.php";

$controller = new PetController();
$favoritoModel = new Favorito();

$pets = $controller->listarPorStatus("Para Adoção");

require_once "../app/Includes/header.php";
if (isset($_SESSION['usuario_id'])) {
    require_once "../app/Includes/menu.php";
}
?>

<main class="container" style="margin-top: 100px !important; margin-left: <?= isset($_SESSION['usuario_id']) ? '240px' : '0'; ?> !important; padding: 20px !important; display: block !important;">

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 40px auto 0 auto !important; position: relative !important; display: block !important;">

        <h1 style="color: #2c3e50; margin-bottom: 5px; font-family: sans-serif; font-weight: bold;">🏠 Pets Para Adoção</h1>
        <p style="color: #7f8c8d; margin-bottom: 30px; font-family: sans-serif;">Pets disponíveis para adoção. Veja o perfil completo e envie seu pedido de adoção direto pelo site.</p>

        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); font-family: sans-serif;">
            <thead>
                <tr style="background: #34495e; color: white; text-align: left;">
                    <th style="padding: 15px; text-align: center; width: 100px;">Foto</th>
                    <th style="padding: 15px;">Nome</th>
                    <th style="padding: 15px;">Espécie / Raça</th>
                    <th style="padding: 15px;">Cidade</th>
                    <th style="padding: 15px;">Tutor</th>
                    <th style="padding: 15px;">Contato</th>
                    <th style="padding: 15px;">Data</th>
                    <th style="padding: 15px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pets) > 0): ?>
                    <?php foreach ($pets as $pet): ?>
                        <tr style="border-bottom: 1px solid #eaeaea;">
                            <td style="padding: 15px; text-align: center;">
                                <?php
                                if (!empty($pet['foto']) && file_exists("../uploads/pets/" . $pet['foto'])) {
                                    $caminhoFoto = "../uploads/pets/" . $pet['foto'];
                                    echo '<img src="'.htmlspecialchars($caminhoFoto).'" width="55" height="55" style="object-fit: cover; border-radius: 50%; border: 2px solid #ddd;" alt="Pet">';
                                } else {
                                    echo '<div style="width: 55px; height: 55px; background: #e2e8f0; border-radius: 50%; border: 2px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 20px; font-weight: bold;">🐾</div>';
                                }
                                ?>
                            </td>
                            <td style="padding: 15px; font-weight: bold; color: #333;">
                                <?= htmlspecialchars($pet["nome"] ?? 'Sem Nome'); ?>
                            </td>
                            <td style="padding: 15px; color: #555;">
                                <?= htmlspecialchars($pet["especie"] ?? 'Não informada'); ?>
                                <br><small style="color: #999;"><?= htmlspecialchars($pet["raca"] ?? 'Mestiço / Vira-lata'); ?></small>
                            </td>
                            <td style="padding: 15px; color: #777;">
                                📍 <?= htmlspecialchars($pet["cidade"] ?? 'Não informada'); ?>
                            </td>
                            <td style="padding: 15px; color: #333;">
                                <?= htmlspecialchars($pet["tutor_nome"] ?? 'Não informado'); ?>
                            </td>
                            <td style="padding: 15px; color: #555;">
                                📞 <?= htmlspecialchars($pet["tutor_telefone"] ?? 'Não informado'); ?>
                            </td>
                            <td style="padding: 15px; color: #777;">
                                <?= !empty($pet["criado_em"]) ? date("d/m/Y", strtotime($pet["criado_em"])) : 'Não informado'; ?>
                            </td>
                            <td style="padding: 15px; text-align: center; white-space: nowrap;">
                                <a href="pet.php?id=<?= (int) $pet['id']; ?>" style="display: inline-block; background: #3498db; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif; margin-bottom: 4px;">👁️ Ver Perfil</a>
                                <?php if (isset($_SESSION['usuario_id'])): ?>
                                    <?php if ((int) $pet['usuario_id'] !== (int) $_SESSION['usuario_id']): ?>
                                        <a href="solicitar_adocao.php?pet_id=<?= (int) $pet['id']; ?>" style="display: inline-block; background: #2ecc71; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif; margin-bottom: 4px;">🏠 Adotar</a>
                                    <?php endif; ?>
                                    <?php if ($favoritoModel->existe((int) $_SESSION['usuario_id'], (int) $pet['id'])): ?>
                                        <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=remover" style="display: inline-block; background: #e74c3c; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif;">⭐ Remover</a>
                                    <?php else: ?>
                                        <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=adicionar" style="display: inline-block; background: #f39c12; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif;">☆ Favoritar</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" style="display: inline-block; background: #7f8c8d; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif;">Entrar pra adotar/favoritar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="padding: 30px; text-align: center; color: #95a5a6; font-style: italic;">Nenhum pet disponível para adoção no momento. 🎉</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once "../app/Includes/footer.php"; ?>
