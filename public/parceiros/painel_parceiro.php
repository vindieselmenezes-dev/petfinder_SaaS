<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Painel do parceiro
 * ==========================================================
 * Onde a ONG/empresa acompanha o status da parceria e administra
 * suas campanhas, eventos e pedidos de doação.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

$controller = new ParceiroController();
$parceiroModel = $controller->parceiros();
$campanhaModel = $controller->campanhas();

$parceiros = $parceiroModel->listarPorUsuario((int) Auth::id());

/**
 * Badge colorido do status da parceria.
 */
function badgeStatusParceria(string $status): string
{
    $mapa = [
        'pendente' => ['classe' => 'bg-warning text-dark', 'rotulo' => '⏳ Em análise'],
        'aprovado' => ['classe' => 'bg-success', 'rotulo' => '✅ Aprovado'],
        'recusado' => ['classe' => 'bg-danger', 'rotulo' => '❌ Recusado'],
        'inativo'  => ['classe' => 'bg-secondary', 'rotulo' => '⏸️ Inativo'],
    ];

    $config = $mapa[$status] ?? ['classe' => 'bg-secondary', 'rotulo' => $status];

    return '<span class="badge ' . $config['classe'] . '">' . $config['rotulo'] . '</span>';
}

$tituloPagina = 'Painel do parceiro';

require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';

?>

<main class="container" style="margin-top:100px; margin-left:240px; padding:20px; max-width:1100px;">

    <div style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">

            <div>
                <h1 class="fw-bold" style="font-size:26px; margin-bottom:5px;">🤝 Painel do parceiro</h1>
                <p style="color:#7f8c8d; margin:0;">
                    Acompanhe sua parceria e publique campanhas, eventos e pedidos de doação.
                </p>
            </div>

            <a href="<?= Url::pagina('cadastrar_parceiro.php') ?>" class="btn btn-success">
                ➕ Nova parceria
            </a>

        </div>

        <?php if (count($parceiros) === 0): ?>

            <div class="text-center border rounded-3 py-5">
                <div class="fs-1">🐾</div>
                <p class="text-muted mb-3">
                    Você ainda não tem nenhuma parceria cadastrada.
                </p>
                <a href="<?= Url::pagina('cadastrar_parceiro.php') ?>" class="btn btn-success">
                    Quero ser parceiro do PetFinder
                </a>
            </div>

        <?php else: ?>

            <?php foreach ($parceiros as $parceiro): ?>

                <?php
                $parceiroId = (int) $parceiro['id'];
                $campanhas = $campanhaModel->listarPorParceiro($parceiroId, false);
                $totaisApoios = $campanhaModel->contarApoiosPorParceiro($parceiroId);
                $logo = Foto::url($parceiro['logo'] ?? null, 'parceiros', 'img/logo.png');
                ?>

                <div class="card shadow-sm mb-4">

                    <div class="card-body">

                        <div class="d-flex align-items-center gap-3 flex-wrap mb-3">

                            <img src="<?= htmlspecialchars($logo) ?>" alt="" class="rounded-circle border"
                                style="width:56px; height:56px; object-fit:cover;">

                            <div class="flex-grow-1">
                                <h2 class="h5 fw-bold mb-1"><?= htmlspecialchars($parceiro['nome']) ?></h2>
                                <?= badgeStatusParceria($parceiro['status']) ?>
                                <span class="badge bg-primary">
                                    <?= htmlspecialchars(Parceiro::rotuloTipo($parceiro['tipo'])) ?>
                                </span>
                                <?php if (!empty($parceiro['destaque'])): ?>
                                    <span class="badge bg-warning text-dark">⭐ Destaque na home</span>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">

                                <a href="<?= Url::pagina('cadastrar_parceiro.php') ?>?id=<?= $parceiroId ?>"
                                    class="btn btn-outline-secondary btn-sm">
                                    ✏️ Editar dados
                                </a>

                                <?php if ($parceiro['status'] === 'aprovado'): ?>
                                    <a href="<?= Url::pagina('parceiro.php') ?>?id=<?= $parceiroId ?>"
                                        class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
                                        👁️ Ver perfil público
                                    </a>
                                <?php endif; ?>

                                <a href="<?= Url::pagina('campanha_form.php') ?>?parceiro_id=<?= $parceiroId ?>"
                                    class="btn btn-success btn-sm">
                                    ➕ Nova publicação
                                </a>

                            </div>

                        </div>

                        <?php if ($parceiro['status'] === 'pendente'): ?>
                            <div class="alert alert-warning py-2 small">
                                Sua parceria está em análise. Você já pode cadastrar campanhas — elas
                                ficam visíveis no site assim que a parceria for aprovada.
                            </div>
                        <?php elseif ($parceiro['status'] === 'recusado'): ?>
                            <div class="alert alert-danger py-2 small">
                                Parceria recusada.
                                <?php if (!empty($parceiro['observacao_admin'])): ?>
                                    Motivo: <?= htmlspecialchars($parceiro['observacao_admin']) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- ============ CAMPANHAS DO PARCEIRO ============ -->

                        <?php if (count($campanhas) === 0): ?>

                            <p class="text-muted small mb-0">
                                Nenhuma campanha, evento ou pedido de doação publicado ainda.
                            </p>

                        <?php else: ?>

                            <div class="table-responsive">

                                <table class="table table-sm align-middle">

                                    <thead class="table-light">
                                        <tr>
                                            <th>Publicação</th>
                                            <th>Tipo</th>
                                            <th>Prazo</th>
                                            <th>Arrecadado</th>
                                            <th>Apoios</th>
                                            <th>Status</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($campanhas as $campanha): ?>

                                            <?php
                                            $campanhaId = (int) $campanha['id'];
                                            $percentual = Campanha::percentualMeta(
                                                $campanha['meta_valor'] !== null ? (float) $campanha['meta_valor'] : null,
                                                (float) $campanha['valor_arrecadado']
                                            );
                                            ?>

                                            <tr>

                                                <td>
                                                    <strong><?= htmlspecialchars($campanha['titulo']) ?></strong>
                                                    <?php if (!empty($campanha['destaque'])): ?>
                                                        <span class="badge bg-warning text-dark">⭐</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= (int) $campanha['visualizacoes'] ?> visualizações
                                                    </small>
                                                </td>

                                                <td>
                                                    <?= Campanha::iconeTipo($campanha['tipo']) ?>
                                                    <?= htmlspecialchars(Campanha::rotuloTipo($campanha['tipo'])) ?>
                                                </td>

                                                <td class="small">
                                                    <?= !empty($campanha['data_fim'])
                                                        ? date('d/m/Y', strtotime($campanha['data_fim']))
                                                        : '—' ?>
                                                </td>

                                                <td class="small">
                                                    R$ <?= number_format((float) $campanha['valor_arrecadado'], 2, ',', '.') ?>
                                                    <?php if ($percentual !== null): ?>
                                                        <br><span class="text-muted"><?= $percentual ?>% da meta</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <a href="<?= Url::pagina('apoios_campanha.php') ?>?id=<?= $campanhaId ?>"
                                                        class="badge bg-info text-dark text-decoration-none">
                                                        <?= $totaisApoios[$campanhaId] ?? 0 ?> apoio(s)
                                                    </a>
                                                </td>

                                                <td>
                                                    <?php if ($campanha['status'] === 'ativa'): ?>
                                                        <span class="badge bg-success">Ativa</span>
                                                    <?php elseif ($campanha['status'] === 'rascunho'): ?>
                                                        <span class="badge bg-secondary">Rascunho</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-dark">Encerrada</span>
                                                    <?php endif; ?>

                                                    <?php
                                                    // status_moderacao só existe depois da migration_026; sem
                                                    // ela, todo mundo continua vendo só o badge de status acima.
                                                    $statusModeracao = $campanha['status_moderacao'] ?? 'aprovada';
                                                    ?>
                                                    <?php if ($statusModeracao === 'pendente'): ?>
                                                        <br><span class="badge bg-warning text-dark mt-1">⏳ Aguardando aprovação</span>
                                                    <?php elseif ($statusModeracao === 'recusada'): ?>
                                                        <br><span class="badge bg-danger mt-1" title="<?= htmlspecialchars($campanha['observacao_moderacao'] ?? '') ?>">
                                                            ❌ Não aprovada
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="text-end">

                                                    <div class="d-inline-flex gap-1">

                                                        <a href="<?= Url::pagina('campanha_form.php') ?>?id=<?= $campanhaId ?>"
                                                            class="btn btn-outline-secondary btn-sm">✏️</a>

                                                        <form method="POST"
                                                            action="<?= Url::pagina('campanha_acoes.php') ?>"
                                                            onsubmit="return confirm('Excluir esta publicação? Essa ação não pode ser desfeita.');">
                                                            <?= Csrf::campoHtml() ?>
                                                            <input type="hidden" name="acao" value="excluir">
                                                            <input type="hidden" name="campanha_id"
                                                                value="<?= $campanhaId ?>">
                                                            <button type="submit"
                                                                class="btn btn-outline-danger btn-sm">🗑️</button>
                                                        </form>

                                                    </div>

                                                    <!-- Atualização do total arrecadado, informado pelo parceiro -->
                                                    <form method="POST" action="<?= Url::pagina('campanha_acoes.php') ?>"
                                                        class="d-inline-flex gap-1 mt-1">
                                                        <?= Csrf::campoHtml() ?>
                                                        <input type="hidden" name="acao" value="arrecadado">
                                                        <input type="hidden" name="campanha_id" value="<?= $campanhaId ?>">
                                                        <input type="text" inputmode="decimal" name="valor"
                                                            class="form-control form-control-sm" style="width:100px;"
                                                            placeholder="R$ total"
                                                            value="<?= htmlspecialchars(number_format((float) $campanha['valor_arrecadado'], 2, ',', '')) ?>">
                                                        <button type="submit"
                                                            class="btn btn-outline-success btn-sm">💾</button>
                                                    </form>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>

</div>

</body>

</html>
