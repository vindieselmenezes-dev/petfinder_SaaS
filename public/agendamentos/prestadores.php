<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



require_once "../../app/Controllers/PrestadorController.php";

$controller = new PrestadorController();

$tipo = $_GET["tipo"] ?? "";
if (!in_array($tipo, Prestador::TIPOS_VALIDOS, true)) {
    $tipo = "";
}

$cidade = trim($_GET["cidade"] ?? "");
$busca = trim($_GET["q"] ?? "");

$prestadores = $controller->listarAtivos($tipo, $cidade, $busca);

$tituloPagina = match ($tipo) {
    'pet_sitter' => "Pet Sitters",
    'taxista_pet' => "Táxi Pet",
    'passeador' => "Passeadores",
    'adestrador' => "Adestradores",
    default => "Passeadores, Pet Sitters, Táxi Pet e Adestradores",
};

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($tituloPagina) ?> - PetFinder Brasil</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">

</head>

<body style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="Voltar para a página anterior">← Voltar</button></div>

    <header class="border-bottom py-3 mb-4">

        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <a href="../../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../../assets/img/logo.png" alt="PetFinder Brasil" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <div class="btn-group flex-wrap">
                    <a href="cadastrar_prestador.php?tipo=passeador" class="btn btn-success">
                        <i class="bi bi-person-walking"></i> Seja Passeador
                    </a>
                    <a href="cadastrar_prestador.php?tipo=pet_sitter" class="btn btn-outline-success">
                        <i class="bi bi-house-heart"></i> Seja Pet Sitter
                    </a>
                    <a href="cadastrar_prestador.php?tipo=taxista_pet" class="btn btn-outline-success">
                        <i class="bi bi-car-front"></i> Seja Táxi Pet
                    </a>
                </div>
            <?php else: ?>
                <a href="<?= Url::pagina('login.php') ?>" class="btn btn-success">Entrar para se cadastrar como profissional</a>
            <?php endif; ?>

        </div>

    </header>

    <main class="container mb-5">

        <h1 class="fw-bold mb-1">🐕 Passeadores, Pet Sitters, Táxi Pet e Adestradores</h1>
        <p class="text-muted">Encontre profissionais de confiança pra passear, cuidar, adestrar ou transportar seu pet.</p>

        <form method="GET" class="row g-2 mb-4 align-items-end bg-light p-3 rounded-3">

            <div class="col-md-4">
                <label class="form-label small">Tipo de serviço</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos os tipos</option>
                    <option value="passeador" <?= $tipo === 'passeador' ? 'selected' : '' ?>>🐕 Passeador</option>
                    <option value="pet_sitter" <?= $tipo === 'pet_sitter' ? 'selected' : '' ?>>🏠 Pet Sitter</option>
                    <option value="taxista_pet" <?= $tipo === 'taxista_pet' ? 'selected' : '' ?>>🚕 Táxi Pet</option>
                    <option value="adestrador" <?= $tipo === 'adestrador' ? 'selected' : '' ?>>🎓 Adestrador</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small">Cidade</label>
                <input type="text" name="cidade" class="form-control" placeholder="Ex: Ouro Branco" value="<?= htmlspecialchars($cidade) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label small">Nome</label>
                <input type="text" name="q" class="form-control" placeholder="Buscar por nome" value="<?= htmlspecialchars($busca) ?>">
            </div>

            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>

        </form>

        <div class="row g-4">

            <?php if (count($prestadores) === 0): ?>

                <div class="col-12 text-center text-muted py-5">
                    Nenhum profissional encontrado com esses filtros. Que tal ser o primeiro?
                    <br>
                    <a href="cadastrar_prestador.php" class="btn btn-success mt-3">Cadastrar meu perfil</a>
                </div>

            <?php else: ?>

                <?php foreach ($prestadores as $prestador): ?>

                    <?php
                    $foto = Foto::url($prestador["foto"] ?? null, 'prestadores');
                    $badge = match ($prestador["tipo"]) {
                        'pet_sitter' => '🏠 Pet Sitter',
                        'taxista_pet' => '🚕 Táxi Pet',
                        'adestrador' => '🎓 Adestrador',
                        default => '🐕 Passeador',
                    };
                    ?>

                    <div class="col-lg-4 col-md-6">

                        <div class="card empresa-card h-100 shadow-sm">

                            <a href="prestador.php?id=<?= (int) $prestador['id'] ?>">
                                <img src="<?= htmlspecialchars($foto) ?>" class="card-img-top" style="height:220px; object-fit:cover;"
                                    alt="<?= htmlspecialchars($prestador['usuario_nome']) ?>">
                            </a>

                            <div class="card-body">

                                <span class="badge bg-primary mb-2"><?= $badge ?></span>

                                <h4>
                                    <a href="prestador.php?id=<?= (int) $prestador['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($prestador['usuario_nome'] . ' ' . $prestador['usuario_sobrenome']) ?>
                                    </a>
                                </h4>

                                <p class="text-muted small">
                                    <?= htmlspecialchars(mb_strimwidth($prestador['apresentacao'] ?? '', 0, 100, '...')) ?>
                                </p>

                                <div class="mb-2">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <?= htmlspecialchars(($prestador['cidade'] ?: 'Cidade não informada') . ($prestador['estado'] ? ' / ' . $prestador['estado'] : '')) ?>
                                </div>

                                <div>
                                    <?php if ((float) $prestador['avaliacao'] > 0): ?>
                                        ⭐ <?= number_format((float) $prestador['avaliacao'], 1) ?>
                                        <small class="text-muted">(<?= (int) $prestador['total_avaliacoes'] ?> avaliações)</small>
                                    <?php else: ?>
                                        <small class="text-muted">Sem avaliações ainda</small>
                                    <?php endif; ?>
                                </div>

                            </div>

                            <div class="card-footer bg-white">
                                <a href="prestador.php?id=<?= (int) $prestador['id'] ?>" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-eye"></i> Ver Perfil
                                </a>
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
