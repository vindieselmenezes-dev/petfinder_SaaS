<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



require_once "../../app/Controllers/PlanoController.php";

$planos = (new PlanoController())->listarAtivos();

$empresaId   = (int) ($_GET['empresa_id'] ?? 0);
$prestadorId = (int) ($_GET['prestador_id'] ?? 0);

// O plano Grátis só vale no cadastro -- quem já tem empresa/perfil não
// pode "renovar" o período de teste de graça escolhendo Grátis de novo.
$exibindoParaEntidadeExistente = $empresaId > 0 || $prestadorId > 0;

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $prestadorId > 0 ? 'Planos para Prestadores' : 'Planos para Empresas' ?> - PetFinder Brasil</title>
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
                <div class="fw-bold text-dark">PetFinder Brasil</div>
            </a>
            <?php if (!isset($_SESSION['usuario_id'])): ?>
                <a href="<?= Url::pagina('login.php') ?>" class="btn btn-outline-success">Já tenho conta</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="container mb-5">

        <div class="text-center mb-5">
            <?php if ($prestadorId > 0): ?>
                <h1 class="fw-bold">📈 Planos para prestadores autônomos</h1>
                <p class="text-muted">Passeador, pet sitter, táxi pet ou adestrador — apareça pra milhares de tutores, sem precisar de CNPJ ou empresa registrada.</p>
            <?php else: ?>
                <h1 class="fw-bold">📈 Planos para sua empresa</h1>
                <p class="text-muted">Anuncie seu petshop, clínica, hotel ou serviço pra milhares de tutores.</p>
            <?php endif; ?>
        </div>

        <div class="row g-4 justify-content-center">

            <?php foreach ($planos as $plano): ?>

                <?php
                $destaque = (bool) $plano['destaque'];
                if ($prestadorId > 0) {
                    // Prestador autônomo não tem catálogo de produtos — a
                    // mesma coluna do plano (limite_produtos) aqui só serve
                    // como um jeito de diferenciar "básico" de "completo".
                    $limite = $plano['limite_produtos'] !== null
                        ? 'Perfil com foto, descrição e serviços cadastrados'
                        : 'Perfil completo com galeria de fotos ilimitada';
                } else {
                    $limite = $plano['limite_produtos'] !== null
                        ? (int) $plano['limite_produtos'] . ' produtos/serviços'
                        : 'Produtos/serviços ilimitados';
                }
                ?>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm <?= $destaque ? 'border-warning border-2' : '' ?>">

                        <?php if ($destaque): ?>
                            <div class="card-header bg-warning text-dark text-center fw-bold">⭐ MAIS POPULAR</div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">

                            <h3 class="fw-bold"><?= htmlspecialchars($plano['nome']) ?></h3>
                            <p class="text-muted"><?= htmlspecialchars($plano['descricao']) ?></p>

                            <div class="mb-3">
                                <?php if ((float) $plano['preco_mensal'] > 0): ?>
                                    <span class="display-6 fw-bold">R$ <?= number_format((float) $plano['preco_mensal'], 2, ',', '.') ?></span>
                                    <span class="text-muted">/mês</span>
                                <?php else: ?>
                                    <span class="display-6 fw-bold">Grátis</span>
                                <?php endif; ?>
                            </div>

                            <ul class="list-unstyled mb-4">
                                <li class="mb-2">✅ Perfil na plataforma</li>
                                <li class="mb-2">✅ <?= htmlspecialchars($limite) ?></li>
                                <?php if ((int) $plano['prioridade'] > 0): ?>
                                    <li class="mb-2">✅ Prioridade nas buscas</li>
                                <?php endif; ?>
                                <?php if ($destaque): ?>
                                    <li class="mb-2">✅ Selo de destaque</li>
                                    <li class="mb-2">✅ Topo da categoria</li>
                                <?php endif; ?>
                                <?php if ((int) $plano['dias_trial'] > 0): ?>
                                    <li class="mb-2">
                                        ✅ <?= (int) $plano['dias_trial'] ?> dias
                                        <?= $plano['slug'] === 'gratis' ? 'de acesso antes de escolher um plano pago' : 'grátis pra testar' ?>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <div class="mt-auto">
                                <?php if ($plano['slug'] === 'gratis' && $exibindoParaEntidadeExistente): ?>
                                    <button class="btn btn-outline-secondary w-100" disabled>
                                        Já utilizado no cadastro
                                    </button>
                                <?php elseif ($empresaId > 0): ?>
                                    <a href="simular_assinatura.php?empresa_id=<?= $empresaId ?>&plano_id=<?= (int) $plano['id'] ?>"
                                        class="btn <?= $destaque ? 'btn-warning' : 'btn-success' ?> w-100">
                                        Escolher este plano
                                    </a>
                                <?php elseif ($prestadorId > 0): ?>
                                    <a href="simular_assinatura.php?prestador_id=<?= $prestadorId ?>&plano_id=<?= (int) $plano['id'] ?>"
                                        class="btn <?= $destaque ? 'btn-warning' : 'btn-success' ?> w-100">
                                        Escolher este plano
                                    </a>
                                <?php else: ?>
                                    <a href="cadastrar_empresa.php" class="btn <?= $destaque ? 'btn-warning' : 'btn-success' ?> w-100 mb-2">
                                        Cadastrar minha empresa
                                    </a>
                                    <a href="../agendamentos/cadastrar_prestador.php" class="btn btn-outline-secondary w-100">
                                        Sou prestador autônomo
                                    </a>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>

        <div class="alert alert-light border mt-5 text-center small">
            💳 Pagamento automatizado em breve (Pix, cartão e boleto). Por enquanto, a troca de plano é simulada
            pra você já testar como fica o painel da sua empresa.
        </div>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
