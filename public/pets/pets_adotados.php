<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


require_once "../../app/Controllers/PetController.php";
require_once "../../app/Models/Favorito.php";

$controller = new PetController();
$favoritoModel = new Favorito();

$porPagina = 24;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$pets = $controller->listarPorStatus("Adotado", $pagina, $porPagina);
$totalPets = $controller->contarPorStatus("Adotado");
$totalPaginas = (int) ceil($totalPets / $porPagina);

require_once "../../app/Includes/header.php";
if (isset($_SESSION['usuario_id'])) {
    require_once "../../app/Includes/menu.php";
}
?>

<main class="container<?= isset($_SESSION['usuario_id']) ? '' : ' sem-sidebar' ?>" style="margin-top: 100px !important; padding: 20px !important; display: block !important;">

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 40px auto 0 auto !important; position: relative !important; display: block !important;">

        <h1 style="color: #2c3e50; margin-bottom: 5px; font-family: sans-serif; font-weight: bold;">🎉 Pets Adotados</h1>
        <p style="color: #7f8c8d; margin-bottom: 30px; font-family: sans-serif;">Pets que já encontraram um novo lar através da plataforma.</p>

        <table class="tabela-pets tabela-cartao-mobile" style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); font-family: sans-serif;">
            <thead>
                <tr style="background: #34495e; color: white; text-align: left;">
                    <th style="padding: 15px; text-align: center; width: 100px;">Foto</th>
                    <th style="padding: 15px;">Nome</th>
                    <th style="padding: 15px;">Espécie / Raça</th>
                    <th style="padding: 15px;">Cidade</th>
                    <th style="padding: 15px;">Data</th>
                    <th style="padding: 15px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pets) > 0): ?>
                    <?php foreach ($pets as $pet): ?>
                        <tr style="border-bottom: 1px solid #eaeaea;">
                            <td data-col="foto" style="padding: 15px; text-align: center;">
                                <?php
                                if (Foto::existe($pet['foto'], 'pets')) {
                                    $caminhoFoto = Foto::url($pet['foto'], 'pets');
                                    echo '<img src="'.htmlspecialchars($caminhoFoto).'" width="55" height="55" style="object-fit: cover; border-radius: 50%; border: 2px solid #ddd;" alt="Pet">';
                                } else {
                                    echo '<div style="width: 55px; height: 55px; background: #e2e8f0; border-radius: 50%; border: 2px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 20px; font-weight: bold;">🐾</div>';
                                }
                                ?>
                            </td>
                            <td data-col="nome" style="padding: 15px; font-weight: bold; color: #333;">
                                <?= htmlspecialchars($pet["nome"] ?? 'Sem Nome'); ?>
                            </td>
                            <td data-col="especie" data-th="Espécie / Raça" style="padding: 15px; color: #555;">
                                <?= htmlspecialchars($pet["especie"] ?? 'Não informada'); ?>
                                <br><small style="color: #999;"><?= htmlspecialchars($pet["raca"] ?? 'Mestiço / Vira-lata'); ?></small>
                            </td>
                            <td data-col="cidade" data-th="Cidade" style="padding: 15px; color: #777;">
                                📍 <?= htmlspecialchars($pet["cidade"] ?? 'Não informada'); ?>
                            </td>
                            <td data-col="data" data-th="Data" style="padding: 15px; color: #777;">
                                <?= !empty($pet["criado_em"]) ? date("d/m/Y", strtotime($pet["criado_em"])) : 'Não informado'; ?>
                            </td>
                            <td data-col="acoes" style="padding: 15px; text-align: center; white-space: nowrap;">
                                <a href="pet.php?id=<?= (int) $pet['id']; ?>" style="display: inline-block; background: #3498db; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif; margin-bottom: 4px;">👁️ Ver Perfil</a>
                                <?php if (isset($_SESSION['usuario_id'])): ?>
                                    <?php if ($favoritoModel->existe((int) $_SESSION['usuario_id'], (int) $pet['id'])): ?>
                                        <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=remover" style="display: inline-block; background: #e74c3c; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif;">⭐ Remover</a>
                                    <?php else: ?>
                                        <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=adicionar" style="display: inline-block; background: #f39c12; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif;">☆ Favoritar</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?= Url::pagina('login.php') ?>" style="display: inline-block; background: #7f8c8d; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif;">Entrar pra favoritar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: #95a5a6; font-style: italic;">Nenhum pet adotado registrado ainda. 🎉</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= Paginador::renderizar($pagina, $totalPaginas) ?>
    </div>
</main>

<?php require_once "../../app/Includes/footer.php"; ?>
