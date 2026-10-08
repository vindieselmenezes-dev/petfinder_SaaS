<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

$categoriaId = (int) ($_GET['categoria_id'] ?? $_POST['categoria_id'] ?? 0);
$categorias = [4 => 'Banho e Tosa', 5 => 'Hotel para Pets', 6 => 'Creche Pet', 7 => 'Adestramento'];
$sugestoes = [
    4 => ['Banho', 'Tosa', 'Banho e tosa completos', 'Hidratação'],
    5 => ['Diária de hospedagem', 'Pacote de fim de semana'],
    6 => ['Diária de creche (day care)'],
    7 => ['Adestramento básico', 'Adestramento comportamental', 'Avaliação inicial'],
];

if (!isset($categorias[$categoriaId])) {
    Url::redirecionar(Url::pagina('banho_e_tosa.php'));
    exit;
}

if (!Auth::check()) {
    $voltar = Url::pagina('pedir_servico.php') . '?' . http_build_query(['categoria_id' => $categoriaId]);
    Url::redirecionar(Url::pagina('login.php') . '?' . http_build_query(['voltar' => $voltar]));
    exit;
}

require_once __DIR__ . '/../../app/Controllers/EmpresaController.php';
require_once __DIR__ . '/../../app/Controllers/PedidoServicoController.php';
require_once __DIR__ . '/../../app/Controllers/PetController.php';

$empresas = (new EmpresaController())->listarAtivas($categoriaId);
$pets = (new PetController())->listarPorUsuario(Auth::id());
$mensagem = '';
$tipoMensagem = 'erro';
$dados = [
    'servico' => trim($_POST['servico'] ?? ''),
    'pet_id' => (int) ($_POST['pet_id'] ?? 0),
    'data_desejada' => trim($_POST['data_desejada'] ?? ''),
    'periodo' => trim($_POST['periodo'] ?? ''),
    'mensagem' => trim($_POST['mensagem'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
        $mensagem = 'Sessão expirada. Atualize a página e tente novamente.';
    } else {
        $pedidoId = (new PedidoServicoController())->criar([
            'usuario_id' => Auth::id(),
            'categoria_id' => $categoriaId,
            'pet_id' => $dados['pet_id'],
            'servico' => $dados['servico'],
            'data_desejada' => $dados['data_desejada'],
            'periodo' => $dados['periodo'],
            'mensagem' => $dados['mensagem'],
        ]);

        if ($pedidoId !== false) {
            Flash::sucesso('Pedido enviado. As empresas em destaque receberam o aviso primeiro. Você verá os orçamentos quando as empresas responderem.');
            Url::redirecionar(Url::pagina('minhas_solicitacoes_empresa.php'));
            exit;
        }

        $mensagem = $empresas === []
            ? 'Não há empresas ativas e disponíveis para esse serviço agora.'
            : 'Não foi possível enviar o pedido. Confira os dados e tente novamente.';
    }
}

$tituloPagina = 'Pedir ' . $categorias[$categoriaId];
require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';

?>

<main class="conteudo">
    <div class="container">
        <h1>Solicitar <?= htmlspecialchars(mb_strtolower($categorias[$categoriaId])) ?></h1>
        <p class="text-muted">Primeiro avisaremos as empresas em destaque. Se nenhuma enviar um orçamento em 30 minutos, o pedido será enviado às demais empresas ativas. O valor só aparecerá quando uma empresa responder.</p>

        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?>"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($empresas === []): ?>
            <div class="alert alert-info">Não há empresas ativas e disponíveis para <?= htmlspecialchars(mb_strtolower($categorias[$categoriaId])) ?> no momento.</div>
            <a class="btn btn-outline-secondary" href="<?= Url::pagina('banho_e_tosa.php') ?>">Voltar aos serviços</a>
        <?php else: ?>
            <form method="POST" class="row g-3" style="max-width:760px">
                <?= Csrf::campoHtml() ?>
                <input type="hidden" name="categoria_id" value="<?= $categoriaId ?>">

                <div class="col-md-6">
                    <label class="form-label" for="servico">Serviço desejado *</label>
                    <input type="text" id="servico" name="servico" class="form-control" list="sugestoesServico"
                        value="<?= htmlspecialchars($dados['servico']) ?>" required maxlength="150">
                    <datalist id="sugestoesServico">
                        <?php foreach ($sugestoes[$categoriaId] as $sugestao): ?>
                            <option value="<?= htmlspecialchars($sugestao) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="pet_id">Pet</label>
                    <select id="pet_id" name="pet_id" class="form-select">
                        <option value="">Selecione (opcional)</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?= (int) $pet['id'] ?>" <?= $dados['pet_id'] === (int) $pet['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pet['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="data_desejada">Data desejada *</label>
                    <input type="date" id="data_desejada" name="data_desejada" class="form-control"
                        min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($dados['data_desejada']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="periodo">Período preferido</label>
                    <select id="periodo" name="periodo" class="form-select">
                        <option value="">Sem preferência</option>
                        <?php foreach (['Manhã', 'Tarde', 'Noite'] as $periodo): ?>
                            <option value="<?= htmlspecialchars($periodo) ?>" <?= $dados['periodo'] === $periodo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($periodo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label" for="mensagem">Observações</label>
                    <textarea id="mensagem" name="mensagem" class="form-control" rows="4" maxlength="2000"><?= htmlspecialchars($dados['mensagem']) ?></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-success">Enviar pedido de orçamento</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>

</body>

</html>
