<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Candidatura / edição de parceiro
 * ==========================================================
 * Mesma página serve para cadastrar uma nova parceria e para
 * editar uma já existente (?id=X), já que os campos são os mesmos.
 *
 * Cadastro novo entra como "pendente" e só aparece no site depois
 * que um administrador aprovar.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

$controller = new ParceiroController();
$parceiroModel = $controller->parceiros();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = $id > 0;

if ($editando && !$parceiroModel->podeAdministrar($id, (int) Auth::id(), Auth::ehAdministrador())) {
    http_response_code(403);
    Flash::erro('Você não tem permissão para editar este parceiro.');
    header('Location: ' . Url::pagina('painel_parceiro.php'));
    exit;
}

$parceiro = $editando ? $parceiroModel->buscarPorId($id, false) : null;

if ($editando && $parceiro === null) {
    Flash::erro('Parceiro não encontrado.');
    header('Location: ' . Url::pagina('painel_parceiro.php'));
    exit;
}

$erros = [];

// Valores exibidos no formulário: o que veio do banco (edição) ou vazio.
$form = [
    'tipo'               => $parceiro['tipo'] ?? 'ong',
    'nome'               => $parceiro['nome'] ?? '',
    'documento'          => $parceiro['documento'] ?? '',
    'descricao'          => $parceiro['descricao'] ?? '',
    'como_ajuda'         => $parceiro['como_ajuda'] ?? '',
    'cidade'             => $parceiro['cidade'] ?? '',
    'estado'             => $parceiro['estado'] ?? '',
    'site'               => $parceiro['site'] ?? '',
    'instagram'          => $parceiro['instagram'] ?? '',
    'whatsapp'           => $parceiro['whatsapp'] ?? '',
    'email_contato'      => $parceiro['email_contato'] ?? Auth::email(),
    'chave_pix'          => $parceiro['chave_pix'] ?? '',
    'link_doacao'        => $parceiro['link_doacao'] ?? '',
    'aceita_voluntarios' => $parceiro['aceita_voluntarios'] ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Middleware::exigirCsrfValido();

    $form = $controller->dadosParceiroDoPost($_POST);
    $erros = $controller->validarParceiro($form);

    if (count($erros) === 0) {

        $logo = $controller->processarImagem($_FILES['logo'] ?? [], 'parceiros', 'parceiro');

        if ($logo === null && !empty($_FILES['logo']['name'])) {
            $erros[] = 'Não foi possível salvar a logo. Use JPG, PNG ou WEBP de até 5 MB.';
        }
    }

    if (count($erros) === 0) {

        $form['logo'] = $logo ?? '';

        if ($editando) {

            if ($parceiroModel->atualizar($id, $form)) {
                Flash::sucesso('Dados do parceiro atualizados!');
                header('Location: ' . Url::pagina('painel_parceiro.php'));
                exit;
            }

            $erros[] = 'Não foi possível salvar as alterações. Tente novamente.';

        } else {

            $form['usuario_id'] = Auth::id();
            $form['empresa_id'] = isset($_POST['empresa_id']) ? (int) $_POST['empresa_id'] : 0;

            $novoId = $parceiroModel->cadastrar($form);

            if ($novoId !== false) {
                Flash::sucesso(
                    'Candidatura enviada! Nossa equipe vai analisar e você recebe um retorno em breve.'
                );
                header('Location: ' . Url::pagina('painel_parceiro.php'));
                exit;
            }

            $erros[] = 'Não foi possível enviar sua candidatura. Tente novamente.';
        }
    }
}

$tituloPagina = $editando ? 'Editar parceiro' : 'Seja um parceiro';

require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';

?>

<main class="container" style="margin-top:100px; margin-left:240px; padding:20px; max-width:900px;">

    <div style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">

        <h1 class="fw-bold" style="font-size:26px; margin-bottom:6px;">
            🤝 <?= $editando ? 'Editar parceiro' : 'Seja um parceiro do PetFinder' ?>
        </h1>

        <p style="color:#7f8c8d;">
            <?php if ($editando): ?>
                Atualize os dados que aparecem no perfil público do parceiro.
            <?php else: ?>
                ONGs, protetores e empresas que apoiam a causa ganham perfil público, espaço para
                divulgar campanhas, eventos e doações, e podem aparecer na home do site.
            <?php endif; ?>
        </p>

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

            <div class="col-md-6">
                <label class="form-label" for="tipo">Tipo de parceria *</label>
                <select name="tipo" id="tipo" class="form-select" required>
                    <?php foreach (Parceiro::TIPOS as $chave => $rotulo): ?>
                        <option value="<?= $chave ?>" <?= $form['tipo'] === $chave ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rotulo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="nome">Nome da ONG / empresa *</label>
                <input type="text" name="nome" id="nome" class="form-control" required maxlength="150"
                    value="<?= htmlspecialchars($form['nome']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="documento">CNPJ (opcional)</label>
                <input type="text" name="documento" id="documento" class="form-control" maxlength="20"
                    placeholder="Somente números" value="<?= htmlspecialchars($form['documento']) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label" for="cidade">Cidade</label>
                <input type="text" name="cidade" id="cidade" class="form-control"
                    value="<?= htmlspecialchars($form['cidade']) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label" for="estado">UF</label>
                <input type="text" name="estado" id="estado" class="form-control" maxlength="2"
                    value="<?= htmlspecialchars($form['estado']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label" for="descricao">Sobre o trabalho de vocês *</label>
                <textarea name="descricao" id="descricao" class="form-control" rows="4" required
                    placeholder="O que vocês fazem, há quanto tempo, quantos animais atendem..."><?= htmlspecialchars($form['descricao']) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label" for="como_ajuda">Como vocês ajudam a divulgar o PetFinder?</label>
                <input type="text" name="como_ajuda" id="como_ajuda" class="form-control" maxlength="255"
                    placeholder="Ex: divulgamos os pets perdidos nas nossas redes e em eventos"
                    value="<?= htmlspecialchars($form['como_ajuda']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="email_contato">E-mail de contato</label>
                <input type="email" name="email_contato" id="email_contato" class="form-control"
                    value="<?= htmlspecialchars($form['email_contato']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="whatsapp">WhatsApp</label>
                <input type="text" name="whatsapp" id="whatsapp" class="form-control" placeholder="(31) 99999-9999"
                    value="<?= htmlspecialchars($form['whatsapp']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="instagram">Instagram</label>
                <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="text" name="instagram" id="instagram" class="form-control"
                        value="<?= htmlspecialchars($form['instagram']) ?>">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="site">Site</label>
                <input type="url" name="site" id="site" class="form-control" placeholder="https://"
                    value="<?= htmlspecialchars($form['site']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="chave_pix">Chave PIX para doações</label>
                <input type="text" name="chave_pix" id="chave_pix" class="form-control"
                    value="<?= htmlspecialchars($form['chave_pix']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="link_doacao">Link de doação (vaquinha, site próprio)</label>
                <input type="url" name="link_doacao" id="link_doacao" class="form-control" placeholder="https://"
                    value="<?= htmlspecialchars($form['link_doacao']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="logo">
                    Logo <?= $editando ? '(envie só se quiser trocar)' : '' ?>
                </label>
                <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                <small class="text-muted">JPG, PNG ou WEBP, até 5 MB.</small>
            </div>

            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="aceita_voluntarios" id="aceita_voluntarios"
                        value="1" <?= !empty($form['aceita_voluntarios']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="aceita_voluntarios">
                        Aceitamos voluntários
                    </label>
                </div>
            </div>

            <div class="col-12 d-flex gap-2 pt-3">

                <button type="submit" class="btn btn-success">
                    <?= $editando ? '💾 Salvar alterações' : '🤝 Enviar candidatura' ?>
                </button>

                <a href="<?= Url::pagina('painel_parceiro.php') ?>" class="btn btn-outline-secondary">
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>

</div>

</body>

</html>
