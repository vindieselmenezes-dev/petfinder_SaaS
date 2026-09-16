<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Apoios recebidos por uma publicação
 * ==========================================================
 * Lista quem se ofereceu para doar, ser voluntário ou divulgar,
 * com o contato deixado por cada pessoa.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

$controller = new ParceiroController();
$parceiroModel = $controller->parceiros();
$campanhaModel = $controller->campanhas();

$campanhaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$campanha = $campanhaId > 0 ? $campanhaModel->buscarPorId($campanhaId, false) : null;

if ($campanha === null) {
    Flash::erro('Publicação não encontrada.');
    header('Location: ' . Url::pagina('painel_parceiro.php'));
    exit;
}

if (!$parceiroModel->podeAdministrar((int) $campanha['parceiro_id'], (int) Auth::id(), Auth::ehAdministrador())) {
    http_response_code(403);
    Flash::erro('Você não tem permissão para ver os apoios desta publicação.');
    header('Location: ' . Url::pagina('painel_parceiro.php'));
    exit;
}

$apoios = $campanhaModel->listarApoios($campanhaId);

$rotulosApoio = [
    'doacao'       => '💰 Doação',
    'voluntariado' => '🙋 Voluntariado',
    'divulgacao'   => '📢 Divulgação',
    'duvida'       => '❓ Dúvida',
];

$tituloPagina = 'Apoios recebidos';

require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';

?>

<main class="container" style="margin-top:100px; margin-left:240px; padding:20px; max-width:1000px;">

    <div style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <div>
                <h1 class="fw-bold" style="font-size:24px; margin-bottom:5px;">
                    💚 Apoios recebidos
                </h1>
                <p style="color:#7f8c8d; margin:0;">
                    <?= htmlspecialchars($campanha['titulo']) ?>
                </p>
            </div>

            <a href="<?= Url::pagina('painel_parceiro.php') ?>" class="btn btn-outline-secondary btn-sm">
                ← Voltar ao painel
            </a>

        </div>

        <?php if (count($apoios) === 0): ?>

            <div class="text-center border rounded-3 py-5 text-muted">
                Ninguém se ofereceu para ajudar ainda. Compartilhe o link da campanha nas suas redes!
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>Quando</th>
                            <th>Pessoa</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Contato</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($apoios as $apoio): ?>

                            <tr>

                                <td class="small text-muted">
                                    <?= date('d/m/Y H:i', strtotime($apoio['criado_em'])) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($apoio['usuario_nome'] ?? 'Visitante') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($rotulosApoio[$apoio['tipo']] ?? $apoio['tipo']) ?>
                                </td>

                                <td>
                                    <?= $apoio['valor'] !== null
                                        ? 'R$ ' . number_format((float) $apoio['valor'], 2, ',', '.')
                                        : '—' ?>
                                </td>

                                <td class="small">
                                    <?= htmlspecialchars($apoio['contato'] ?: ($apoio['usuario_email'] ?? '—')) ?>
                                </td>

                                <td class="small">
                                    <?= nl2br(htmlspecialchars($apoio['mensagem'] ?? '')) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>

</div>

</body>

</html>
