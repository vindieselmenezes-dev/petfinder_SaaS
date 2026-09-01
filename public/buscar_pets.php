<?php

declare(strict_types=1);

session_start();

require_once '../app/Controllers/PetController.php';
require_once '../app/Models/Favorito.php';

$controller = new PetController();
$favoritoModel = new Favorito();

// ==========================================================
// FILTROS VINDOS DA URL (busca por GET pra poder compartilhar o link)
// ==========================================================

$busca     = trim($_GET['busca'] ?? '');
$cidade    = trim($_GET['cidade'] ?? '');
$especieId = (int) ($_GET['especie_id'] ?? 0);
$racaId    = (int) ($_GET['raca_id'] ?? 0);
$sexo      = trim($_GET['sexo'] ?? '');
$status    = trim($_GET['status'] ?? 'Para Adoção');
$idadeMin  = (int) ($_GET['idade_min'] ?? 0);
$idadeMax  = (int) ($_GET['idade_max'] ?? 0);
$ordem     = trim($_GET['ordem'] ?? 'recente');

$pets = $controller->buscarAdocaoPublico(
    busca: $busca,
    cidade: $cidade,
    especieId: $especieId,
    racaId: $racaId,
    sexo: $sexo,
    idadeMin: $idadeMin,
    idadeMax: $idadeMax,
    status: $status,
    ordem: $ordem
);

$especies = $controller->listarEspecies();
$racas    = $especieId > 0 ? $controller->listarRacas($especieId) : [];

$tituloPagina = "Buscar Pets";

require_once '../app/Includes/header.php';
if (isset($_SESSION['usuario_id'])) {
    require_once '../app/Includes/menu.php';
}
?>

<main class="conteudo" <?= isset($_SESSION['usuario_id']) ? '' : 'style="margin-left:0 !important;"'; ?>>
<div class="container">

    <h1>🔎 Buscar Pets</h1>

    <!-- FILTROS -->
    <form method="GET" class="formulario-cadastro" style="margin-bottom:25px;">
        <div class="row g-3">

            <div class="col-md-4">
                <div class="grupo-form">
                    <label for="busca">Buscar por nome, raça, cor...</label>
                    <input type="text" id="busca" name="busca" class="form-control" autocomplete="off" value="<?= htmlspecialchars($busca); ?>">
                </div>
            </div>

            <div class="col-md-4">
                <div class="grupo-form">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" class="form-control" list="listaCidadesBusca" autocomplete="off" value="<?= htmlspecialchars($cidade); ?>">
                    <datalist id="listaCidadesBusca">
                        <?php foreach ($controller->listarCidadesComPets() as $cidadeSugerida): ?>
                            <option value="<?= htmlspecialchars($cidadeSugerida); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <div class="col-md-4">
                <div class="grupo-form">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <?php foreach (['Para Adoção', 'Com Tutor', 'Perdido', 'Encontrado', 'Adotado', 'Todos'] as $opcao): ?>
                            <option value="<?= $opcao; ?>" <?= $status === $opcao ? 'selected' : ''; ?>><?= $opcao; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="grupo-form">
                    <label for="especie_id">Espécie</label>
                    <select id="especie_id" name="especie_id" class="form-select">
                        <option value="0">Todas</option>
                        <?php foreach ($especies as $especie): ?>
                            <option value="<?= $especie['id']; ?>" <?= $especieId === (int) $especie['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($especie['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="grupo-form">
                    <label for="raca_id">Raça</label>
                    <select id="raca_id" name="raca_id" class="form-select">
                        <option value="0">Todas</option>
                        <?php foreach ($racas as $raca): ?>
                            <option value="<?= $raca['id']; ?>" <?= $racaId === (int) $raca['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($raca['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="col-md-2">
                <div class="grupo-form">
                    <label for="sexo">Sexo</label>
                    <select id="sexo" name="sexo" class="form-select">
                        <option value="">Ambos</option>
                        <option value="Macho" <?= $sexo === 'Macho' ? 'selected' : ''; ?>>Macho</option>
                        <option value="Fêmea" <?= $sexo === 'Fêmea' ? 'selected' : ''; ?>>Fêmea</option>
                    </select>
                </div>
            </div>

            <div class="col-md-2">
                <div class="grupo-form">
                    <label for="idade_min">Idade mín.</label>
                    <input type="number" id="idade_min" name="idade_min" class="form-control" min="0" value="<?= $idadeMin ?: ''; ?>">
                </div>
            </div>

            <div class="col-md-2">
                <div class="grupo-form">
                    <label for="idade_max">Idade máx.</label>
                    <input type="number" id="idade_max" name="idade_max" class="form-control" min="0" value="<?= $idadeMax ?: ''; ?>">
                </div>
            </div>

            <div class="col-md-4">
                <div class="grupo-form">
                    <label for="ordem">Ordenar por</label>
                    <select id="ordem" name="ordem" class="form-select">
                        <option value="recente" <?= $ordem === 'recente' ? 'selected' : ''; ?>>Mais recentes</option>
                        <option value="antigo" <?= $ordem === 'antigo' ? 'selected' : ''; ?>>Mais antigos</option>
                        <option value="nome_asc" <?= $ordem === 'nome_asc' ? 'selected' : ''; ?>>Nome (A-Z)</option>
                        <option value="nome_desc" <?= $ordem === 'nome_desc' ? 'selected' : ''; ?>>Nome (Z-A)</option>
                        <option value="idade_asc" <?= $ordem === 'idade_asc' ? 'selected' : ''; ?>>Mais novos primeiro</option>
                        <option value="idade_desc" <?= $ordem === 'idade_desc' ? 'selected' : ''; ?>>Mais velhos primeiro</option>
                    </select>
                </div>
            </div>

        </div>

        <button type="submit" class="btn-acao" style="background:#3498db; color:white; padding:10px 24px; border:none; border-radius:6px; font-weight:bold; cursor:pointer; margin-top:10px;">🔎 Buscar</button>
        <a href="buscar_pets.php" class="btn-acao" style="background:#95a5a6; color:white; padding:10px 24px; border-radius:6px; margin-top:10px; text-decoration:none; display:inline-block;">Limpar filtros</a>
    </form>

    <p style="color:#7f8c8d;"><?= count($pets); ?> pet(s) encontrado(s)</p>

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
                            <?php if (!empty($pet['foto']) && file_exists(__DIR__ . '/../uploads/pets/' . $pet['foto'])): ?>
                                <img src="../uploads/pets/<?= htmlspecialchars($pet['foto']); ?>" width="45" height="45" style="border-radius:50%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:45px; height:45px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center;">🐶</div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:bold;"><?= htmlspecialchars($pet['nome']); ?></td>
                        <td><?= htmlspecialchars($pet['especie'] ?? ''); ?> · <?= htmlspecialchars($pet['raca'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($pet['sexo'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($pet['cidade'] ?? 'Não informado'); ?></td>
                        <td style="text-align:center; white-space:nowrap;">
                            <a href="pet.php?id=<?= (int) $pet['id']; ?>" class="btn-acao" style="background:#3498db; color:white;">👁️ Ver Perfil</a>
                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <?php if ($favoritoModel->existe((int) $_SESSION['usuario_id'], (int) $pet['id'])): ?>
                                    <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=remover" class="btn-acao" style="background:#e74c3c; color:white;">⭐</a>
                                <?php else: ?>
                                    <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=adicionar" class="btn-acao" style="background:#f39c12; color:white;">☆</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color:#95a5a6; font-style:italic;">Nenhum pet encontrado com esses filtros. Tente ajustar a busca.</p>
    <?php endif; ?>

</div>
</main>

<script>
    // Recarrega a lista de raças quando a espécie muda, buscando de listar_filtros.php
    document.getElementById('especie_id').addEventListener('change', function () {
        const especieId = this.value;
        const selectRaca = document.getElementById('raca_id');

        if (especieId === '0') {
            selectRaca.innerHTML = '<option value="0">Todas</option>';
            return;
        }

        fetch('../app/ajax/listar_filtros.php')
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                const racas = dados.racas[especieId] || [];
                let html = '<option value="0">Todas</option>';
                racas.forEach(function (raca) {
                    html += '<option value="' + raca.id + '">' + raca.nome + '</option>';
                });
                selectRaca.innerHTML = html;
            });
    });
</script>

<?php require_once '../app/Includes/footer.php'; ?>
