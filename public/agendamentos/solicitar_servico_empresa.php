<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/EmpresaSolicitacaoController.php";
require_once "../../app/Controllers/EmpresaController.php";
require_once "../../app/Controllers/PetController.php";
require_once "../../app/Helpers/Csrf.php";

$solicitacaoController = new EmpresaSolicitacaoController();
$empresaController = new EmpresaController();
$petController = new PetController();

$usuarioId = (int) $_SESSION["usuario_id"];
$empresaId = (int) ($_GET["empresa_id"] ?? $_POST["empresa_id"] ?? 0);

$empresa = $empresaId > 0 ? $empresaController->buscarPorId($empresaId) : null;
$categoriaOk = $empresa && $solicitacaoController->categoriaSuportada((int) $empresa["categoria_id"]);
$meusPets = $petController->listarPorUsuario($usuarioId);

// Sugestões de serviço por categoria, só pra ajudar o tutor a preencher --
// o campo continua sendo texto livre.
$sugestoesServico = [
    4 => ['Banho', 'Tosa', 'Banho e Tosa completos', 'Hidratação'],
    5 => ['Diária de hospedagem', 'Pacote de fim de semana'],
    6 => ['Diária de creche (day care)'],
    7 => ['Adestramento básico', 'Adestramento comportamental', 'Avaliação inicial'],
];
$categoriaId = $empresa ? (int) $empresa["categoria_id"] : 0;
$sugestoes = $sugestoesServico[$categoriaId] ?? [];

$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {
        $mensagem = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";
    } elseif (!$categoriaOk) {
        $mensagem = "Esta empresa não está disponível para pedidos de serviço online no momento.";
        $tipoMensagem = "erro";
    } else {
        $novoId = $solicitacaoController->criar([
            "usuario_id" => $usuarioId,
            "empresa_id" => $empresaId,
            "pet_id" => (int) ($_POST["pet_id"] ?? 0) ?: null,
            "servico" => trim($_POST["servico"] ?? ""),
            "data_desejada" => trim($_POST["data_desejada"] ?? ""),
            "periodo" => trim($_POST["periodo"] ?? "") ?: null,
            "mensagem" => trim($_POST["mensagem"] ?? ""),
            "busca_em_casa" => isset($_POST["busca_em_casa"]) ? 1 : 0,
            "endereco_busca" => trim($_POST["endereco_busca"] ?? ""),
        ]);

        if ($novoId !== false) {
            Flash::sucesso("Pedido enviado! A empresa vai confirmar em breve.");
            header('Location: ' . Url::pagina('minhas_solicitacoes_empresa.php'));
            exit;
        }

        $mensagem = isset($_POST["busca_em_casa"]) && empty(trim($_POST["endereco_busca"] ?? ""))
            ? "Informe o endereço para a busca em casa."
            : "Não foi possível enviar o pedido. Confira a data e tente novamente.";
        $tipoMensagem = "erro";
    }
}

?>

<?php require_once "../../app/Includes/header.php"; ?>

<?php require_once "../../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>🐾 Solicitar Serviço</h1>

<?php if (!$empresa): ?>

    <div class="mensagem erro">
        Escolha uma empresa pra solicitar o serviço.
        <a href="<?= Url::pagina('empresas.php') ?>">Ver empresas cadastradas</a>
    </div>

<?php elseif (!$categoriaOk): ?>

    <div class="mensagem">
        <?php if (in_array((int) $empresa["categoria_id"], [2, 3], true)): ?>
            <?= htmlspecialchars($empresa["nome_fantasia"]) ?> é uma clínica/hospital veterinário.
            <a href="agendar_consulta.php?empresa_id=<?= $empresaId ?>">Agende uma consulta aqui</a>.
        <?php else: ?>
            Esta empresa ainda não está disponível para pedidos de serviço online.
            Entre em contato diretamente pelo <a href="<?= Url::pagina('empresa.php') ?>?id=<?= $empresaId ?>">perfil da empresa</a>.
        <?php endif; ?>
    </div>

<?php else: ?>

    <p>Solicitando serviço em <strong><?= htmlspecialchars($empresa["nome_fantasia"]) ?></strong>.</p>

    <?php if (!empty($mensagem)): ?>
        <div class="mensagem <?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
        <?= Csrf::campoHtml() ?>

        <div class="row">

            <div class="col-md-6">
                <div class="grupo-form">
                    <label for="servico">Serviço desejado *</label>
                    <input type="text" id="servico" name="servico" class="form-control"
                        list="sugestoesServico" placeholder="Ex: Banho e Tosa completos" required>
                    <datalist id="sugestoesServico">
                        <?php foreach ($sugestoes as $sugestao): ?>
                            <option value="<?= htmlspecialchars($sugestao) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <div class="col-md-6">
                <div class="grupo-form">
                    <label for="pet_id">Pet</label>
                    <select id="pet_id" name="pet_id" class="form-select">
                        <option value="">Selecione (opcional)</option>
                        <?php foreach ($meusPets as $pet): ?>
                            <option value="<?= (int) $pet['id'] ?>"><?= htmlspecialchars($pet['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

        </div>

        <div class="row">

            <div class="col-md-6">
                <div class="grupo-form">
                    <label for="data_desejada">Data desejada *</label>
                    <input type="date" id="data_desejada" name="data_desejada" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="grupo-form">
                    <label for="periodo">Período preferido</label>
                    <select id="periodo" name="periodo" class="form-select">
                        <option value="">Sem preferência</option>
                        <option value="Manhã">Manhã</option>
                        <option value="Tarde">Tarde</option>
                        <option value="Noite">Noite</option>
                    </select>
                </div>
            </div>

        </div>

        <div class="grupo-form">
            <label for="mensagem">Observações</label>
            <textarea id="mensagem" name="mensagem" rows="3" class="form-control"
                placeholder="Ex: porte do animal, alguma restrição, preferências de horário..."></textarea>
        </div>

        <?php $buscaEmCasaMarcada = !empty($_POST["busca_em_casa"]); ?>
        <div class="grupo-form form-check">
            <input type="checkbox" id="busca_em_casa" name="busca_em_casa" class="form-check-input"
                <?= $buscaEmCasaMarcada ? "checked" : "" ?>
                onchange="document.getElementById('bloco-endereco-busca').style.display = this.checked ? 'block' : 'none';">
            <label for="busca_em_casa" class="form-check-label">🚐 Preciso de busca e entrega em casa</label>
        </div>

        <div class="grupo-form" id="bloco-endereco-busca" style="display:<?= $buscaEmCasaMarcada ? 'block' : 'none' ?>;">
            <label for="endereco_busca">Endereço para busca *</label>
            <input type="text" id="endereco_busca" name="endereco_busca" class="form-control"
                value="<?= htmlspecialchars($_POST["endereco_busca"] ?? "") ?>"
                placeholder="Rua, número, bairro, cidade">
        </div>

        <div class="alert alert-light border small">
            💡 Este é um <strong>pedido de contratação</strong>. A empresa vai confirmar a data e o horário exatos,
            e vai te dizer quem vai atender seu pet. Se você pediu busca em casa, ela também confirma isso.
        </div>

        <button type="submit" class="btn btn-success w-100">📥 Enviar Pedido</button>

    </form>

<?php endif; ?>

</div>

</main>

<?php require_once "../../app/Includes/footer.php"; ?>

</body>

</html>
