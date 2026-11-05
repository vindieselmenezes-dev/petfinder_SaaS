<?php

declare(strict_types=1);

/**
 * ==========================================================
 * EcoSistemPet — Moderação dos Alertas Pet (admin)
 * ==========================================================
 * Duas listas:
 *  - Pets ENCONTRADOS (inclui os enviados pelo "Achei um pet" da home):
 *    o administrador pode remover um alerta falso ou abusivo. A remoção apaga
 *    o registro e as fotos e avisa quem o publicou.
 *  - Alertas de pets PERDIDOS ainda ativos: o administrador pode encerrar o
 *    alerta (o pet volta para "Com Tutor").
 * Também mostra quantos alertas a mesma pessoa publicou nas últimas 24 h,
 * o que ajuda a perceber abuso.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirTipo(Auth::TIPO_ADMINISTRADOR);

$pdo = Database::conectar();
$tipo = ($_GET['tipo'] ?? 'encontrados') === 'perdidos' ? 'perdidos' : 'encontrados';

/** Apaga o arquivo de uma foto de pet, sem nunca sair da pasta uploads/pets. */
function apagarArquivoPet(?string $arquivo): void
{
    $arquivo = basename((string) $arquivo);
    if ($arquivo === '' || $arquivo === 'sem-foto.png') {
        return;
    }

    $caminho = dirname(__DIR__, 2) . '/uploads/pets/' . $arquivo;
    if (is_file($caminho)) {
        @unlink($caminho);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Middleware::exigirCsrfValido();

    $acao = $_POST['acao'] ?? '';
    $petId = (int) ($_POST['pet_id'] ?? 0);
    $alertaId = (int) ($_POST['alerta_id'] ?? 0);
    $observacao = mb_substr(trim((string) ($_POST['observacao'] ?? '')), 0, 300);
    $notificacoes = new Notificacao();

    try {
        if ($acao === 'remover_encontrado' && $petId > 0) {
            $stmt = $pdo->prepare("SELECT usuario_id, foto FROM pets WHERE id = ? AND status = 'Encontrado'");
            $stmt->execute([$petId]);
            $pet = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pet) {
                $stmt = $pdo->prepare('SELECT arquivo FROM pet_imagens WHERE pet_id = ?');
                $stmt->execute([$petId]);
                $arquivos = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'arquivo');
                $arquivos[] = $pet['foto'];

                if ((new Pet())->excluirPorId($petId)) {
                    foreach (array_unique($arquivos) as $arquivo) {
                        apagarArquivoPet($arquivo);
                    }

                    $texto = 'Um alerta de pet encontrado que você publicou foi removido pela moderação.';
                    if ($observacao !== '') {
                        $texto .= ' Motivo: ' . $observacao;
                    }
                    $notificacoes->criar((int) $pet['usuario_id'], 'Alerta removido pela moderação', $texto, 'Sistema', Url::pagina('meus_pets.php'));

                    Flash::sucesso('Alerta removido e a pessoa foi avisada.');
                } else {
                    Flash::erro('Não foi possível remover o alerta.');
                }
            } else {
                Flash::erro('Esse alerta não existe mais.');
            }
        } elseif ($acao === 'encerrar_perdido' && $alertaId > 0) {
            $stmt = $pdo->prepare("SELECT pet_id, user_id FROM pet_alertas_perdidos WHERE id = ? AND status = 'Ativo'");
            $stmt->execute([$alertaId]);
            $alerta = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($alerta) {
                $pdo->prepare("UPDATE pet_alertas_perdidos SET status = 'Encontrado' WHERE id = ?")->execute([$alertaId]);
                $pdo->prepare("UPDATE pets SET status = 'Com Tutor' WHERE id = ? AND status = 'Perdido'")->execute([(int) $alerta['pet_id']]);

                $texto = 'Seu alerta de pet perdido foi encerrado pela moderação.';
                if ($observacao !== '') {
                    $texto .= ' Motivo: ' . $observacao;
                }
                $notificacoes->criar((int) $alerta['user_id'], 'Alerta encerrado pela moderação', $texto, 'Sistema', Url::pagina('meus_pets.php'));

                Flash::sucesso('Alerta encerrado e a pessoa foi avisada.');
            } else {
                Flash::erro('Esse alerta já não está ativo.');
            }
        } else {
            Flash::erro('Pedido inválido.');
        }
    } catch (Throwable $erro) {
        error_log('[admin_alertas_pet] ' . $erro->getMessage());
        Flash::erro('Não foi possível concluir agora. Tente novamente.');
    }

    header('Location: ' . Url::pagina('admin_alertas_pet.php') . '?tipo=' . $tipo);
    exit;
}

$itens = [];

if ($tipo === 'encontrados') {
    $itens = $pdo->query(
        "SELECT p.id, p.nome, p.foto, p.observacoes, p.criado_em, p.usuario_id,
                u.nome AS usuario_nome, u.sobrenome AS usuario_sobrenome, u.email AS usuario_email,
                e.nome AS especie,
                (SELECT COUNT(*) FROM pets p2
                  WHERE p2.usuario_id = p.usuario_id AND p2.status = 'Encontrado'
                    AND p2.criado_em >= NOW() - INTERVAL 1 DAY) AS alertas_24h
         FROM pets p
         JOIN usuarios u ON u.id = p.usuario_id
         LEFT JOIN especies e ON e.id = p.especie_id
         WHERE p.status = 'Encontrado'
         ORDER BY p.id DESC
         LIMIT 100"
    )->fetchAll(PDO::FETCH_ASSOC);
} else {
    $itens = $pdo->query(
        "SELECT a.id AS alerta_id, a.last_seen_location, a.description, a.lost_latitude, a.lost_longitude, a.created_at,
                p.id, p.nome, p.foto, e.nome AS especie,
                u.id AS usuario_id, u.nome AS usuario_nome, u.sobrenome AS usuario_sobrenome, u.email AS usuario_email
         FROM pet_alertas_perdidos a
         JOIN pets p ON p.id = a.pet_id
         JOIN usuarios u ON u.id = a.user_id
         LEFT JOIN especies e ON e.id = p.especie_id
         WHERE a.status = 'Ativo'
         ORDER BY a.id DESC
         LIMIT 100"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$tituloPagina = 'Moderação de Alertas Pet';
require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';
?>
<main class="container" style="margin-top:100px; margin-left:240px; padding:20px; max-width:1100px;">
    <div class="painel-caixa">
        <h1 class="fw-bold" style="font-size:26px; margin-bottom:6px;">🚨 Moderação de Alertas Pet</h1>
        <p style="color:#7f8c8d;">
            Remova alertas falsos ou abusivos. Quem publicou é avisado, com o motivo que você escrever.
        </p>

        <div class="btn-group mb-4">
            <a href="?tipo=encontrados" class="btn btn-<?= $tipo === 'encontrados' ? 'primary' : 'outline-primary' ?>">Pets encontrados</a>
            <a href="?tipo=perdidos" class="btn btn-<?= $tipo === 'perdidos' ? 'primary' : 'outline-primary' ?>">Pets perdidos (ativos)</a>
        </div>

        <?php if (count($itens) === 0): ?>
            <div class="text-center border rounded-3 py-5 text-muted">Nenhum alerta nesta lista.</div>
        <?php else: ?>
            <?php foreach ($itens as $item): ?>
                <?php
                $petId = (int) $item['id'];
                $quem = trim($item['usuario_nome'] . ' ' . ($item['usuario_sobrenome'] ?? ''));
                $quando = date('d/m/Y H:i', strtotime((string) ($tipo === 'encontrados' ? $item['criado_em'] : $item['created_at'])));
                ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex gap-3 flex-wrap align-items-start">
                            <img src="<?= htmlspecialchars(Foto::url($item['foto'], 'pets')) ?>" alt="Foto do pet"
                                style="width:110px; height:110px; object-fit:cover; border-radius:10px;">
                            <div class="flex-grow-1">
                                <h2 class="h5 fw-bold mb-1">
                                    <?= htmlspecialchars($item['nome']) ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars((string) ($item['especie'] ?? 'Pet')) ?></span>
                                    <?php if ($tipo === 'encontrados' && (int) $item['alertas_24h'] >= 3): ?>
                                        <span class="badge bg-danger">
                                            <?= (int) $item['alertas_24h'] ?> alertas dessa pessoa em 24 h
                                        </span>
                                    <?php endif; ?>
                                </h2>
                                <p class="small text-muted mb-1">
                                    Publicado por <?= htmlspecialchars($quem) ?> (<?= htmlspecialchars($item['usuario_email']) ?>)
                                    · <?= $quando ?>
                                </p>
                                <?php if ($tipo === 'perdidos'): ?>
                                    <p class="small mb-1">Visto por último: <?= htmlspecialchars($item['last_seen_location']) ?></p>
                                <?php endif; ?>
                                <?php $descricao = $tipo === 'encontrados' ? ($item['observacoes'] ?? '') : ($item['description'] ?? ''); ?>
                                <?php if ($descricao !== ''): ?>
                                    <p class="small mb-1"><?= nl2br(htmlspecialchars($descricao)) ?></p>
                                <?php endif; ?>
                                <?php if ($tipo === 'perdidos' && $item['lost_latitude'] !== null): ?>
                                    <p class="small mb-0">
                                        <a href="https://www.google.com/maps?q=<?= urlencode($item['lost_latitude'] . ',' . $item['lost_longitude']) ?>"
                                            target="_blank" rel="noopener">Ver no mapa →</a>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="acoes-moderacao">
                                <form method="POST">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="pet_id" value="<?= $petId ?>">
                                    <input type="hidden" name="alerta_id" value="<?= (int) ($item['alerta_id'] ?? 0) ?>">
                                    <label for="obs-<?= $petId ?>" class="visually-hidden">Motivo (aparece para a pessoa)</label>
                                    <input id="obs-<?= $petId ?>" type="text" name="observacao" maxlength="300"
                                        class="form-control form-control-sm mb-2" placeholder="Motivo (aparece para a pessoa)">
                                    <?php if ($tipo === 'encontrados'): ?>
                                        <button type="submit" name="acao" value="remover_encontrado" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Remover este alerta? As fotos também serão apagadas.');">
                                            🗑️ Remover alerta
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="acao" value="encerrar_perdido" class="btn btn-warning btn-sm"
                                            onclick="return confirm('Encerrar este alerta de pet perdido?');">
                                            ✔️ Encerrar alerta
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
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
