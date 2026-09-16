<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Nova / editar publicação do parceiro
 * ==========================================================
 * Campanha de arrecadação, evento ou pedido de doação.
 *
 * ?parceiro_id=X -> criando uma publicação nova
 * ?id=Y          -> editando uma publicação existente
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

$controller = new ParceiroController();
$parceiroModel = $controller->parceiros();
$campanhaModel = $controller->campanhas();

$campanhaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = $campanhaId > 0;

$campanha = $editando ? $campanhaModel->buscarPorId($campanhaId, false) : null;

if ($editando && $campanha === null) {
    Flash::erro('Publicação não encontrada.');
    header('Location: ' . Url::pagina('painel_parceiro.php'));
    exit;
}

$parceiroId = $editando
    ? (int) $campanha['parceiro_id']
    : (isset($_GET['parceiro_id']) ? (int) $_GET['parceiro_id'] : 0);

if (!$parceiroModel->podeAdministrar($parceiroId, (int) Auth::id(), Auth::ehAdministrador())) {
    http_response_code(403);
    Flash::erro('Você não tem permissão para publicar em nome deste parceiro.');
    header('Location: ' . Url::pagina('painel_parceiro.php'));
    exit;
}

$parceiro = $parceiroModel->buscarPorId($parceiroId, false);
$erros = [];

$form = [
    'tipo'            => $campanha['tipo'] ?? 'campanha',
    'titulo'          => $campanha['titulo'] ?? '',
    'resumo'          => $campanha['resumo'] ?? '',
    'descricao'       => $campanha['descricao'] ?? '',
    'local_evento'    => $campanha['local_evento'] ?? '',
    'data_inicio'     => $campanha['data_inicio'] ?? '',
    'data_fim'        => $campanha['data_fim'] ?? '',
    'meta_valor'      => $campanha['meta_valor'] ?? '',
    'itens_desejados' => $campanha['itens_desejados'] ?? '',
    'chave_pix'       => $campanha['chave_pix'] ?? ($parceiro['chave_pix'] ?? ''),
    'link_externo'    => $campanha['link_externo'] ?? '',
    'status'          => $campanha['status'] ?? 'ativa',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Middleware::exigirCsrfValido();

    $form = $controller->dadosCampanhaDoPost($_POST);
    $erros = $controller->validarCampanha($form);

    $imagem = null;

    if (count($erros) === 0) {

        $imagem = $controller->processarImagem($_FILES['imagem'] ?? [], 'campanhas', 'campanha');

        if ($imagem === null && !empty($_FILES['imagem']['name'])) {
            $erros[] = 'Não foi possível salvar a imagem. Use JPG, PNG ou WEBP de até 5 MB.';
        }
    }

    if (count($erros) === 0) {

        $form['imagem'] = $imagem ?? '';

        // Só entra na fila de revisão quando quem edita é o próprio
        // parceiro — admin editando pelo painel não precisa reaprovar
        // a própria mudança (ver atualizar() no model).
        $reenviarModeracao = !Auth::ehAdministrador();

        if ($editando) {

            if ($campanhaModel->atualizar($campanhaId, $form, $reenviarModeracao)) {
                Flash::sucesso(
                    $reenviarModeracao && $campanhaModel->moderacaoAtiva()
                        ? 'Alterações salvas! Como você mudou o conteúdo, a publicação volta para análise antes de aparecer no site de novo.'
                        : 'Publicação atualizada!'
                );
                header('Location: ' . Url::pagina('painel_parceiro.php'));
                exit;
            }

            $erros[] = 'Não foi possível salvar as alterações.';

        } else {

            $form['parceiro_id'] = $parceiroId;

            if ($campanhaModel->criar($form) !== false) {
                Flash::sucesso(
                    $campanhaModel->moderacaoAtiva()
                        ? 'Publicação enviada! Ela fica visível no site assim que um administrador aprovar.'
                        : 'Publicação criada com sucesso!'
                );
                header('Location: ' . Url::pagina('painel_parceiro.php'));
                exit;
            }

            $erros[] = 'Não foi possível criar a publicação.';
        }
    }
}

/**
 * Formata a data do banco para o <input type="datetime-local">.
 */
function valorDataHora(?string $valor): string
{
    if (empty($valor)) {
        return '';
    }

    return date('Y-m-d\TH:i', strtotime($valor));
}

$tituloPagina = $editando ? 'Editar publicação' : 'Nova publicação';

require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';

?>

<main class="container" style="margin-top:100px; margin-left:240px; padding:20px; max-width:900px;">

    <div style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">

        <h1 class="fw-bold" style="font-size:26px; margin-bottom:6px;">
            📣 <?= $editando ? 'Editar publicação' : 'Nova campanha, evento ou doação' ?>
        </h1>

        <p style="color:#7f8c8d;">
            Publicando como <strong><?= htmlspecialchars($parceiro['nome'] ?? 'parceiro') ?></strong>.
        </p>

        <?php if ($campanhaModel->moderacaoAtiva()): ?>
            <div class="alert alert-info small">
                ℹ️ Publicações novas (e edições em publicações já aprovadas) passam por revisão de um
                administrador antes de aparecer no site.
            </div>
        <?php endif; ?>

        <?php if (count($erros) > 0): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($erros as $erro): ?>
                        <li><?= htmlspecialchars($erro) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="row g-3">

            <?= Csrf::campoHtml() ?>

            <div class="col-md-4">
                <label class="form-label" for="tipo">Tipo *</label>
                <select name="tipo" id="tipo" class="form-select" required>
                    <?php foreach (Campanha::TIPOS as $chave => $config): ?>
                        <option value="<?= $chave ?>" <?= $form['tipo'] === $chave ? 'selected' : '' ?>>
                            <?= $config['icone'] ?> <?= htmlspecialchars($config['rotulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">
                    Campanha = meta em dinheiro · Evento = data e local · Doação = itens.
                </small>
            </div>

            <div class="col-md-8">
                <label class="form-label" for="titulo">Título *</label>
                <input type="text" name="titulo" id="titulo" class="form-control" required maxlength="160"
                    placeholder="Ex: Mutirão de castração em outubro"
                    value="<?= htmlspecialchars((string) $form['titulo']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label" for="resumo">Resumo (aparece no card)</label>
                <input type="text" name="resumo" id="resumo" class="form-control" maxlength="255"
                    value="<?= htmlspecialchars((string) $form['resumo']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label" for="descricao">Descrição completa</label>
                <textarea name="descricao" id="descricao" class="form-control" rows="5"
                    placeholder="Explique para que serve, como a pessoa participa, o que já foi feito..."><?= htmlspecialchars((string) $form['descricao']) ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="data_inicio">Início</label>
                <input type="datetime-local" name="data_inicio" id="data_inicio" class="form-control"
                    value="<?= valorDataHora($form['data_inicio']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="data_fim">Encerramento</label>
                <input type="datetime-local" name="data_fim" id="data_fim" class="form-control"
                    value="<?= valorDataHora($form['data_fim']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label" for="local_evento">Local (para eventos)</label>
                <input type="text" name="local_evento" id="local_evento" class="form-control" maxlength="220"
                    placeholder="Ex: Praça da Liberdade, Belo Horizonte/MG"
                    value="<?= htmlspecialchars((string) $form['local_evento']) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label" for="meta_valor">Meta de arrecadação (R$)</label>
                <input type="text" inputmode="decimal" name="meta_valor" id="meta_valor" class="form-control"
                    placeholder="2500.00" value="<?= htmlspecialchars((string) $form['meta_valor']) ?>">
            </div>

            <div class="col-md-8">
                <label class="form-label" for="itens_desejados">Itens necessários (para doações)</label>
                <input type="text" name="itens_desejados" id="itens_desejados" class="form-control" maxlength="255"
                    placeholder="Ex: ração sênior, cobertores, vermífugo"
                    value="<?= htmlspecialchars((string) $form['itens_desejados']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="chave_pix">Chave PIX desta ação</label>
                <input type="text" name="chave_pix" id="chave_pix" class="form-control"
                    value="<?= htmlspecialchars((string) $form['chave_pix']) ?>">
                <small class="text-muted">Se deixar em branco, usamos a chave cadastrada no perfil.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="link_externo">Link externo (vaquinha, inscrição)</label>
                <input type="url" name="link_externo" id="link_externo" class="form-control" placeholder="https://"
                    value="<?= htmlspecialchars((string) $form['link_externo']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="imagem">
                    Imagem <?= $editando ? '(envie só se quiser trocar)' : '' ?>
                </label>
                <input type="file" name="imagem" id="imagem" class="form-control" accept="image/*">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="status">Situação</label>
                <select name="status" id="status" class="form-select">
                    <option value="ativa" <?= $form['status'] === 'ativa' ? 'selected' : '' ?>>
                        Ativa (visível no site)
                    </option>
                    <option value="rascunho" <?= $form['status'] === 'rascunho' ? 'selected' : '' ?>>
                        Rascunho (só você vê)
                    </option>
                    <option value="encerrada" <?= $form['status'] === 'encerrada' ? 'selected' : '' ?>>
                        Encerrada
                    </option>
                </select>
            </div>

            <div class="col-12 d-flex gap-2 pt-3">

                <button type="submit" class="btn btn-success">
                    <?= $editando ? '💾 Salvar' : '📣 Publicar' ?>
                </button>

                <a href="<?= Url::pagina('painel_parceiro.php') ?>" class="btn btn-outline-secondary">Cancelar</a>

            </div>

        </form>

    </div>

</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>

</div>

</body>

</html>
