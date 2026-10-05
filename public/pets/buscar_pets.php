<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



require_once '../../app/Controllers/PetController.php';
require_once '../../app/Models/Favorito.php';

$controller = new PetController();
$favoritoModel = new Favorito();

// ==========================================================
// FILTROS VINDOS DA URL (busca por GET pra poder compartilhar o link)
// ==========================================================

$cidade = trim($_GET['cidade'] ?? '');
$especieId = (int) ($_GET['especie_id'] ?? 0);
$racaId = (int) ($_GET['raca_id'] ?? 0);
$status = 'Para Adoção';

$porPagina = 24;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$pets = $controller->buscarAdocaoPublico(
    cidade: $cidade,
    especieId: $especieId,
    racaId: $racaId,
    status: $status,
    pagina: $pagina,
    porPagina: $porPagina
);

$totalPets = $controller->contarAdocaoPublico(
    cidade: $cidade,
    especieId: $especieId,
    racaId: $racaId,
    status: $status
);
$totalPaginas = (int) ceil($totalPets / $porPagina);

$especies = $controller->listarEspecies();
$racas = [];
if ($especieId > 0) {
    $racas = $controller->listarRacas($especieId);
} else {
    foreach ($especies as $especie) {
        $racas = array_merge($racas, $controller->listarRacas((int) $especie['id']));
    }
}

$tituloPagina = "Buscar Pets";

require_once '../../app/Includes/header.php';
if (isset($_SESSION['usuario_id'])) {
    require_once '../../app/Includes/menu.php';
}
?>

<main class="conteudo" <?= isset($_SESSION['usuario_id']) ? '' : 'style="margin-left:0 !important;"'; ?>>
    <div class="container">

        <h1>🏠 Pets para Adoção</h1>
        <p class="text-muted mb-4">Encontre um pet para adotar filtrando por espécie, raça e cidade.</p>

        <!-- FILTROS -->
        <form method="GET" class="bg-white border rounded-3 shadow-sm p-3 p-md-4 mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Filtros de adoção</h2>
                    <p class="text-muted small mb-0">Refine por espécie, raça e cidade.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search" aria-hidden="true"></i> Buscar
                    </button>
                    <a href="buscar_pets.php" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="especie_id" class="form-label">Espécie</label>
                    <select id="especie_id" name="especie_id" class="form-select">
                        <option value="0">Todas as espécies</option>
                        <?php foreach ($especies as $especie): ?>
                            <option value="<?= (int) $especie['id']; ?>" <?= $especieId === (int) $especie['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($especie['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="raca_id" class="form-label">Raça</label>
                    <select id="raca_id" name="raca_id" class="form-select">
                        <option value="0">Todas as raças</option>
                        <?php foreach ($racas as $raca): ?>
                            <option value="<?= (int) $raca['id']; ?>" <?= $racaId === (int) $raca['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($raca['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" id="cidade" name="cidade" class="form-control" list="listaCidadesBusca"
                        autocomplete="off" placeholder="Digite sua cidade" value="<?= htmlspecialchars($cidade); ?>">
                    <datalist id="listaCidadesBusca">
                        <?php foreach ($controller->listarCidadesComPets() as $cidadeSugerida): ?>
                            <option value="<?= htmlspecialchars($cidadeSugerida); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>
        </form>

        <p style="color:#7f8c8d;"><?= $totalPets; ?> pet(s) encontrado(s)</p>

        <!-- RESULTADOS -->
        <?php if (count($pets) > 0): ?>
            <table class="tabela-pets">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>Espécie/Raça</th>
                        <th>Sexo</th>
                        <th>Cidade</th>
                        <th style="text-align:center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pets as $pet): ?>
                        <tr>
                            <td>
                                <?php if (Foto::existe($pet['foto'], 'pets')): ?>
                                    <img src="<?= htmlspecialchars(Foto::url($pet['foto'], 'pets')) ?>" width="45" height="45"
                                        style="border-radius:50%; object-fit:cover;">
                                <?php else: ?>
                                    <div
                                        style="width:45px; height:45px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center;">
                                        🐶</div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:bold;"><?= htmlspecialchars($pet['nome']); ?></td>
                            <td><?= htmlspecialchars($pet['especie'] ?? ''); ?> · <?= htmlspecialchars($pet['raca'] ?? ''); ?>
                            </td>
                            <td><?= htmlspecialchars($pet['sexo'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($pet['cidade'] ?? 'Não informado'); ?></td>
                            <td style="text-align:center; white-space:nowrap;">
                                <a href="pet.php?id=<?= (int) $pet['id']; ?>" class="btn-acao"
                                    style="background:#3498db; color:white;">👁️ Ver Perfil</a>
                                <?php if (isset($_SESSION['usuario_id'])): ?>
                                    <?php if ($favoritoModel->existe((int) $_SESSION['usuario_id'], (int) $pet['id'])): ?>
                                        <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=remover" class="btn-acao"
                                            style="background:#e74c3c; color:white;">⭐</a>
                                    <?php else: ?>
                                        <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=adicionar" class="btn-acao"
                                            style="background:#f39c12; color:white;">☆</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?= Paginador::renderizar($pagina, $totalPaginas) ?>
        <?php else: ?>
            <p style="color:#95a5a6; font-style:italic;">Nenhum pet encontrado com esses filtros. Tente ajustar a busca.</p>
        <?php endif; ?>

    </div>
</main>

<script>
    // Recarrega as raças correspondentes sempre que a espécie mudar.
    document.getElementById('especie_id').addEventListener('change', function () {
        const especieId = this.value;
        const selectRaca = document.getElementById('raca_id');

        fetch('../../app/ajax/listar_filtros.php')
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                const racas = especieId === '0'
                    ? Object.values(dados.racas).flat()
                    : dados.racas[especieId] || [];
                selectRaca.replaceChildren(new Option('Todas as raças', '0'));
                racas.forEach(function (raca) {
                    selectRaca.add(new Option(raca.nome, raca.id));
                });
            });
    });
</script>

<?php require_once '../../app/Includes/footer.php'; ?>