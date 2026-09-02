<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/EnderecoController.php";
require_once "../app/Helpers/Csrf.php";

$controller = new EnderecoController();
$usuarioId  = (int) $_SESSION["usuario_id"];

function voltarSeguro(?string $url): ?string {
    // só aceita caminho relativo dentro do próprio site — nunca uma URL
    // completa, pra não virar um open-redirect.
    if (!$url || preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, '\\')) {
        return null;
    }
    return $url;
}

$voltar = voltarSeguro($_GET['voltar'] ?? $_POST['voltar'] ?? null);

$mensagem     = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {

        $mensagem     = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";

    } else {

        $dados = [
            "usuario_id"  => $usuarioId,
            "cep"         => trim($_POST["cep"] ?? ""),
            "logradouro"  => trim($_POST["logradouro"] ?? ""),
            "numero"      => trim($_POST["numero"] ?? ""),
            "complemento" => trim($_POST["complemento"] ?? ""),
            "referencia"  => trim($_POST["referencia"] ?? ""),
            "bairro"      => trim($_POST["bairro"] ?? ""),
            "cidade"      => trim($_POST["cidade"] ?? ""),
            "estado"      => trim($_POST["estado"] ?? "")
        ];

        if ($controller->salvar($dados)) {
            $mensagem     = "Endereço salvo com sucesso!";
            $tipoMensagem = "sucesso";
        } else {
            $mensagem     = "Verifique os campos obrigatórios (Cidade e Estado).";
            $tipoMensagem = "erro";
        }

    }
}

// Recarrega os dados atuais (após salvar, ou na primeira visita)
$endereco = $controller->buscarPorUsuario($usuarioId) ?? [];

require_once "../app/Includes/header.php";
require_once "../app/Includes/menu.php";

$estados = [
    "AC","AL","AP","AM","BA","CE","DF","ES","GO",
    "MA","MT","MS","MG","PA","PB","PR","PE","PI",
    "RJ","RN","RS","RO","RR","SC","SP","SE","TO"
];

?>

<main class="conteudo">

    <h1>📍 Meu Endereço</h1>

    <?php 
// Pega o tipo de usuário logado (assume tutor se não encontrar)
$tipoUsuarioPerfil = $_SESSION['perfil_tipo'] ?? 'tutor'; 

// SÓ EXIBE A FRASE SE O USUÁRIO NÃO FOR UMA EMPRESA
if ($tipoUsuarioPerfil !== 'empresa'): 
?>
    <p>Esse endereço aparece nos seus pets cadastrados</p>
<?php endif; ?>


    <?php if (!empty($mensagem)): ?>

        <div class="mensagem <?= $tipoMensagem; ?>">
            <?= htmlspecialchars($mensagem); ?>
        </div>

        <?php if ($tipoMensagem === 'sucesso' && $voltar): ?>
            <p><a href="<?= htmlspecialchars($voltar) ?>">Continuar para a finalização da compra &rarr;</a></p>
        <?php endif; ?>

    <?php endif; ?>

    <form method="POST" action="" class="formulario-endereco">

        <?= Csrf::campoHtml() ?>

        <?php if ($voltar): ?>
            <input type="hidden" name="voltar" value="<?= htmlspecialchars($voltar) ?>">
        <?php endif; ?>

        <div class="grupo-form">
            <label for="cep">CEP</label>
            <input
                type="text"
                id="cep"
                name="cep"
                maxlength="9"
                placeholder="00000-000"
                value="<?= htmlspecialchars($endereco["cep"] ?? ''); ?>">
        </div>

        <div class="grupo-form">
            <label for="logradouro">Rua / Avenida</label>
            <input
                type="text"
                id="logradouro"
                name="logradouro"
                maxlength="255"
                value="<?= htmlspecialchars($endereco["logradouro"] ?? ''); ?>">
        </div>

        <div class="grupo-form">
            <label for="numero">Número</label>
            <input
                type="text"
                id="numero"
                name="numero"
                maxlength="20"
                value="<?= htmlspecialchars($endereco["numero"] ?? ''); ?>">
        </div>

        <div class="grupo-form">
            <label for="complemento">Complemento</label>
            <input
                type="text"
                id="complemento"
                name="complemento"
                maxlength="255"
                value="<?= htmlspecialchars($endereco["complemento"] ?? ''); ?>">
        </div>

        <div class="grupo-form">
            <label for="referencia">Ponto de referência</label>
            <input
                type="text"
                id="referencia"
                name="referencia"
                maxlength="255"
                placeholder="Ex: perto da padaria, portão azul, próximo à praça..."
                value="<?= htmlspecialchars($endereco["referencia"] ?? ''); ?>">
        </div>

        <div class="grupo-form">
            <label for="bairro">Bairro</label>
            <input
                type="text"
                id="bairro"
                name="bairro"
                maxlength="150"
                value="<?= htmlspecialchars($endereco["bairro"] ?? ''); ?>">
        </div>

        <div class="grupo-form">
            <label for="cidade">Cidade *</label>
            <input
                type="text"
                id="cidade"
                name="cidade"
                maxlength="150"
                required
                value="<?= htmlspecialchars($endereco["cidade"] ?? ''); ?>">
        </div>

        <div class="grupo-form">
            <label for="estado">Estado *</label>
            <select id="estado" name="estado" required>
                <option value="">Selecione</option>
                <?php foreach ($estados as $uf): ?>
                    <option
                        value="<?= $uf; ?>"
                        <?= (($endereco["estado"] ?? '') === $uf) ? 'selected' : ''; ?>>
                        <?= $uf; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grupo-form">
            <button type="submit" class="btn">
                Salvar Endereço
            </button>
        </div>

    </form>

</main>

<?php
require_once "../app/Includes/footer.php";
?>
