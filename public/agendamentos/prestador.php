<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



require_once "../../app/Controllers/PrestadorController.php";
require_once "../../app/Controllers/PetController.php";
require_once "../../app/Helpers/Csrf.php";

$controller = new PrestadorController();

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$prestador = $controller->buscarPorId($id);

$avaliacaoMensagem = $_SESSION['avaliacao_mensagem_prestador'] ?? null;
unset($_SESSION['avaliacao_mensagem_prestador']);

$solicitacaoMensagem = $_SESSION['solicitacao_mensagem'] ?? null;
unset($_SESSION['solicitacao_mensagem']);

$servicos = $prestador ? $controller->buscarServicos($id) : [];
$animais = $prestador ? $controller->buscarAnimaisAtendidos($id) : [];
$disponibilidade = $prestador ? $controller->buscarDisponibilidade($id) : [];
$avaliacoes = $prestador ? $controller->listarAvaliacoes($id) : [];

$meusPets = [];
if ($prestador && isset($_SESSION['usuario_id'])) {
    $meusPets = (new PetController())->listarPorUsuario((int) $_SESSION['usuario_id']);
}

$badge = match ($prestador['tipo'] ?? '') {
    'pet_sitter' => '🏠 Pet Sitter',
    'taxista_pet' => '🚕 Táxi Pet',
    'adestrador' => '🎓 Adestrador',
    default => '🐕 Passeador',
};

$trialVencido = $prestador ? $controller->trialVencido($id) : false;

$veiculo = $prestador && $prestador['tipo'] === 'taxista_pet' ? $controller->buscarVeiculo($id) : null;

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $prestador ? htmlspecialchars($prestador["usuario_nome"]) . " - " : "" ?>PetFinder Brasil</title>

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
            <a href="prestadores.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Ver todos os profissionais
            </a>
        </div>
    </header>

    <main class="container mb-5">

        <?php if (!$prestador): ?>

            <div class="alert alert-warning">Profissional não encontrado.</div>

        <?php else: ?>

            <?php if (!empty($_GET['voce'])): ?>
                <div class="alert alert-success">Esse é o seu perfil de <?= $badge ?>! Ele já está visível no diretório.</div>
            <?php endif; ?>

            <?php if ($avaliacaoMensagem): ?>
                <div class="alert alert-info"><?= htmlspecialchars($avaliacaoMensagem) ?></div>
            <?php endif; ?>

            <?php if ($solicitacaoMensagem): ?>
                <div class="alert alert-success"><?= htmlspecialchars($solicitacaoMensagem) ?></div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-lg-8">

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <?php
                        $foto = Foto::url($prestador["foto"] ?? null, 'prestadores');
                        ?>
                        <img src="<?= htmlspecialchars($foto) ?>" width="90" height="90"
                            style="border-radius:50%; object-fit:cover;" alt="Foto do profissional">
                        <div>
                            <span class="badge bg-primary mb-1"><?= $badge ?></span>
                            <h1 class="h3 mb-0"><?= htmlspecialchars($prestador["usuario_nome"] . ' ' . $prestador["usuario_sobrenome"]) ?></h1>
                            <div>
                                <?php if ((float) $prestador['avaliacao'] > 0): ?>
                                    ⭐ <?= number_format((float) $prestador['avaliacao'], 1) ?>
                                    <small class="text-muted">(<?= (int) $prestador['total_avaliacoes'] ?> avaliações)</small>
                                <?php else: ?>
                                    <small class="text-muted">Sem avaliações ainda</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($prestador["apresentacao"])): ?>
                        <p><?= nl2br(htmlspecialchars($prestador["apresentacao"])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($prestador["diferencial"])): ?>
                        <p class="fst-italic text-muted">"<?= htmlspecialchars($prestador["diferencial"]) ?>"</p>
                    <?php endif; ?>

                    <?php if (!empty($servicos)): ?>
                        <?php if ($veiculo): ?>
                        <h5 class="mt-4">🚗 Veículo</h5>
                        <ul>
                            <li><?= htmlspecialchars($veiculo['tipo_veiculo']) ?><?= $veiculo['modelo'] ? ' — ' . htmlspecialchars($veiculo['modelo']) : '' ?><?= $veiculo['ano'] ? ' (' . htmlspecialchars($veiculo['ano']) . ')' : '' ?></li>
                            <li>Capacidade: <?= (int) $veiculo['capacidade_pets'] ?> pet(s) por corrida</li>
                            <?php if ($veiculo['ar_condicionado']): ?><li>Ar-condicionado</li><?php endif; ?>
                            <?php if ($veiculo['caixa_transporte']): ?><li>Possui caixa de transporte</li><?php endif; ?>
                            <?php if ($veiculo['aceita_animais_grandes']): ?><li>Aceita animais de grande porte</li><?php endif; ?>
                        </ul>
                        <?php if ($veiculo['valor_km'] || $veiculo['valor_corrida_minima']): ?>
                            <p>
                                <?php if ($veiculo['valor_km']): ?>Km rodado: <strong>R$ <?= number_format((float) $veiculo['valor_km'], 2, ',', '.') ?></strong><br><?php endif; ?>
                                <?php if ($veiculo['valor_corrida_minima']): ?>Corrida mínima: <strong>R$ <?= number_format((float) $veiculo['valor_corrida_minima'], 2, ',', '.') ?></strong><?php endif; ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <h5 class="mt-4">Serviços oferecidos</h5>
                        <ul>
                            <?php foreach ($servicos as $servico): ?>
                                <li><?= htmlspecialchars($servico) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($animais)): ?>
                        <h5 class="mt-4">Animais atendidos</h5>
                        <ul>
                            <?php foreach ($animais as $animal): ?>
                                <li><?= htmlspecialchars($animal) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($disponibilidade)): ?>
                        <h5 class="mt-4">Disponibilidade</h5>
                        <table class="table table-sm w-auto">
                            <?php
                            $porDia = [];
                            foreach ($disponibilidade as $d) {
                                $porDia[$d['dia_semana']][] = $d['periodo'];
                            }
                            ?>
                            <tbody>
                                <?php foreach ($porDia as $dia => $periodos): ?>
                                    <tr>
                                        <td class="fw-semibold pe-4"><?= $dia ?></td>
                                        <td><?= implode(', ', $periodos) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if ($prestador['valor_hora'] || $prestador['valor_diaria']): ?>
                        <h5 class="mt-4">Valores</h5>
                        <p>
                            <?php if ($prestador['valor_hora']): ?>
                                Passeio/hora: <strong>R$ <?= number_format((float) $prestador['valor_hora'], 2, ',', '.') ?></strong><br>
                            <?php endif; ?>
                            <?php if ($prestador['valor_diaria']): ?>
                                Diária: <strong>R$ <?= number_format((float) $prestador['valor_diaria'], 2, ',', '.') ?></strong><br>
                            <?php endif; ?>
                            <?php if ($prestador['forma_pagamento']): ?>
                                <small class="text-muted">Pagamento: <?= htmlspecialchars($prestador['forma_pagamento']) ?></small>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <!-- AVALIAÇÕES -->

                    <h5 class="mt-4">⭐ Avaliações dos clientes</h5>

                    <?php if (empty($avaliacoes)): ?>
                        <p class="text-muted">Ainda não há avaliações.</p>
                    <?php else: ?>
                        <?php foreach ($avaliacoes as $avaliacao): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <strong><?= htmlspecialchars($avaliacao['usuario_nome']) ?></strong>
                                — <?= str_repeat('⭐', (int) $avaliacao['nota']) ?>
                                <?php if (!empty($avaliacao['comentario'])): ?>
                                    <p class="mb-0 small"><?= htmlspecialchars($avaliacao['comentario']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] !== (int) $prestador['usuario_id']): ?>
                        <form method="POST" action="avaliar_prestador.php" class="mt-3">
                            <?= Csrf::campoHtml() ?>
                            <input type="hidden" name="prestador_id" value="<?= (int) $prestador['id'] ?>">
                            <div class="grupo-form">
                                <label class="form-label small">Deixe sua avaliação</label>
                                <select name="nota" class="form-select w-auto d-inline-block me-2" required>
                                    <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                                    <option value="4">⭐⭐⭐⭐ Muito bom</option>
                                    <option value="3">⭐⭐⭐ Bom</option>
                                    <option value="2">⭐⭐ Regular</option>
                                    <option value="1">⭐ Ruim</option>
                                </select>
                                <input type="text" name="comentario" class="form-control d-inline-block w-auto" placeholder="Comentário (opcional)" style="min-width:250px;">
                                <button type="submit" class="btn btn-outline-primary">Enviar avaliação</button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>

                <!-- COLUNA LATERAL: CONTATO + SOLICITAÇÃO -->

                <div class="col-lg-4">

                    <div class="border rounded-3 p-4 sticky-top mb-3" style="top: 20px;">

                        <h5>Contato</h5>

                        <?php if (!empty($prestador["cidade"])): ?>
                            <p class="mb-2">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?= htmlspecialchars($prestador["cidade"]) ?> / <?= htmlspecialchars($prestador["estado"] ?? '') ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($prestador["area_atendimento"])): ?>
                            <p class="mb-2 small text-muted">
                                <i class="bi bi-signpost-2"></i> Atende: <?= htmlspecialchars($prestador["area_atendimento"]) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($prestador["whatsapp"])): ?>
                            <a href="https://wa.me/55<?= preg_replace('/\D/', '', $prestador["whatsapp"]) ?>" target="_blank"
                                rel="noopener" class="btn btn-success w-100 mb-2">
                                <i class="bi bi-whatsapp"></i> Falar no WhatsApp
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($prestador["instagram"])): ?>
                            <p class="mb-1 small"><i class="bi bi-instagram"></i> <?= htmlspecialchars($prestador["instagram"]) ?></p>
                        <?php endif; ?>

                    </div>

                    <?php if (isset($_SESSION['usuario_id'])): ?>

                        <?php if ((int) $_SESSION['usuario_id'] === (int) $prestador['usuario_id']): ?>

                            <div class="alert alert-secondary">
                                Este é o seu perfil profissional.
                                <a href="solicitacoes_prestador.php?prestador_id=<?= (int) $prestador['id'] ?>">Ver solicitações recebidas</a>
                            </div>

                            <?php if ($trialVencido): ?>
                                <div class="alert alert-danger">
                                    ⏰ Seu período de teste venceu. Seu perfil parou de aparecer nas buscas e não recebe novas solicitações.
                                    <br>
                                    <a href="<?= Url::pagina('planos.php') ?>?prestador_id=<?= (int) $prestador['id'] ?>" class="btn btn-success btn-sm mt-2">📈 Ver planos</a>
                                </div>
                            <?php endif; ?>

                        <?php elseif ($trialVencido): ?>

                            <div class="alert alert-secondary">
                                Este profissional está temporariamente indisponível pra novas solicitações.
                            </div>

                        <?php else: ?>

                            <div class="border rounded-3 p-4">

                                <h5>📅 Solicitar serviço</h5>

                                <form method="POST" action="solicitar_servico_prestador.php">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="prestador_id" value="<?= (int) $prestador['id'] ?>">

                                    <?php if (!empty($meusPets)): ?>
                                        <div class="grupo-form">
                                            <label class="form-label small">Pet</label>
                                            <select name="pet_id" class="form-select">
                                                <option value="">Selecione (opcional)</option>
                                                <?php foreach ($meusPets as $pet): ?>
                                                    <option value="<?= (int) $pet['id'] ?>"><?= htmlspecialchars($pet['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <div class="grupo-form">
                                        <label class="form-label small">Data desejada</label>
                                        <input type="date" name="data_desejada" class="form-control">
                                    </div>

                                    <div class="grupo-form">
                                        <label class="form-label small">Período</label>
                                        <select name="periodo" class="form-select">
                                            <option value="">Sem preferência</option>
                                            <option value="Manhã">Manhã</option>
                                            <option value="Tarde">Tarde</option>
                                            <option value="Noite">Noite</option>
                                        </select>
                                    </div>

                                    <div class="grupo-form">
                                        <label class="form-label small">Mensagem</label>
                                        <textarea name="mensagem" rows="3" class="form-control" placeholder="Conte um pouco sobre o que você precisa..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100">Enviar solicitação</button>
                                </form>

                            </div>

                        <?php endif; ?>

                    <?php else: ?>

                        <div class="alert alert-secondary">
                            <a href="<?= Url::pagina('login.php') ?>">Entre na sua conta</a> para solicitar este serviço.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
