<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/EmpresaController.php";
require_once "../app/Helpers/Csrf.php";

$controller = new EmpresaController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$empresaId = (int) ($_GET["id"] ?? 0);
$empresa   = $controller->buscarPorId($empresaId);

/*
|--------------------------------------------------------------------------
| Segurança: empresa não existe ou não pertence a este usuário
|--------------------------------------------------------------------------
*/

if ($empresa === null || (int) $empresa["usuario_id"] !== $usuarioId) {
    $_SESSION["erro_empresa"] = "Empresa não encontrada.";
    header("Location: minhas_empresas.php");
    exit;
}

$categorias = $controller->listarCategorias();

$diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];

$estados = [
    "AC","AL","AP","AM","BA","CE","DF","ES","GO",
    "MA","MT","MS","MG","PA","PB","PR","PE","PI",
    "RJ","RN","RS","RO","RR","SC","SP","SE","TO"
];

$mensagem     = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {

        $mensagem     = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";

    } else {

        $dados = [
            "usuario_id"    => $usuarioId,
            "categoria_id"  => (int) ($_POST["categoria_id"] ?? 0),
            "nome_fantasia" => trim($_POST["nome_fantasia"] ?? ""),
            "razao_social"  => trim($_POST["razao_social"] ?? ""),
            "cnpj"          => trim($_POST["cnpj"] ?? ""),
            "descricao"     => trim($_POST["descricao"] ?? ""),
            "telefone"      => trim($_POST["telefone"] ?? ""),
            "whatsapp"      => trim($_POST["whatsapp"] ?? ""),
            "email"         => trim($_POST["email"] ?? ""),
            "site"          => trim($_POST["site"] ?? ""),
            "endereco"      => trim($_POST["endereco"] ?? ""),
            "numero"        => trim($_POST["numero"] ?? ""),
            "complemento"   => trim($_POST["complemento"] ?? ""),
            "bairro"        => trim($_POST["bairro"] ?? ""),
            "cidade"        => trim($_POST["cidade"] ?? ""),
            "estado"        => trim($_POST["estado"] ?? ""),
            "cep"           => trim($_POST["cep"] ?? ""),
            "logo"          => $empresa["logo"],
            "capa"          => $empresa["capa"]
        ];

        /*
        |--------------------------------------------------------------
        | Substitui logo/capa apenas se um novo arquivo foi enviado
        |--------------------------------------------------------------
        */

        if (!empty($_FILES["logo"]["name"])) {
            $novoLogo = $controller->processarImagemUnica($_FILES["logo"]);
            if ($novoLogo !== null) {
                $dados["logo"] = $novoLogo;
            }
        }

        if (!empty($_FILES["capa"]["name"])) {
            $novaCapa = $controller->processarImagemUnica($_FILES["capa"]);
            if ($novaCapa !== null) {
                $dados["capa"] = $novaCapa;
            }
        }

        if ($controller->atualizar($empresaId, $dados)) {

            $controller->salvarHorarios($empresaId, $_POST["horario"] ?? []);

            if (!empty($_FILES["galeria"]["name"][0])) {
                $novasImagens = $controller->processarGaleria($_FILES["galeria"]);
                $controller->salvarGaleria($empresaId, $novasImagens);
            }

            $_SESSION["sucesso_empresa"] = "Empresa atualizada com sucesso!";
            header("Location: minhas_empresas.php");
            exit;

        }

        $mensagem     = "Não foi possível atualizar a empresa. Verifique os campos obrigatórios.";
        $tipoMensagem = "erro";

        // Mantém os dados digitados na tela em caso de erro
        $empresa = array_merge($empresa, $dados);

    }

}

$horariosAtuais = $controller->buscarHorarios($empresaId);
$horariosPorDia = [];
foreach ($horariosAtuais as $horario) {
    $horariosPorDia[$horario['dia_semana']] = $horario;
}

$galeriaAtual = $controller->buscarGaleria($empresaId);

$tituloPagina = "Editar Empresa";

?>

<?php require_once "../app/Includes/header.php"; ?>

<?php require_once "../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>✏️ Editar Empresa</h1>

<?php if (!empty($mensagem)): ?>

<div class="mensagem <?= $tipoMensagem ?>">
    <?= htmlspecialchars($mensagem) ?>
</div>

<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<?= Csrf::campoHtml() ?>

<h3>Dados da Empresa</h3>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="nome_fantasia">Nome Fantasia *</label>
            <input type="text" id="nome_fantasia" name="nome_fantasia" class="form-control" maxlength="180" autocomplete="off" required
                value="<?= htmlspecialchars($empresa['nome_fantasia'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="categoria_id">Categoria *</label>
            <select id="categoria_id" name="categoria_id" class="form-select" required>
                <option value="">Selecione</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= $categoria["id"] ?>"
                        <?= (int) $categoria["id"] === (int) ($empresa['categoria_id'] ?? 0) ? "selected" : "" ?>>
                        <?= htmlspecialchars($categoria["nome"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="razao_social">Razão Social</label>
            <input type="text" id="razao_social" name="razao_social" class="form-control" maxlength="180"
                value="<?= htmlspecialchars($empresa['razao_social'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" class="form-control" placeholder="00.000.000/0000-00" maxlength="18"
                value="<?= htmlspecialchars($empresa['cnpj'] ?? '') ?>">
        </div>
    </div>

</div>

<div class="grupo-form">
    <label for="descricao">Descrição</label>
    <textarea id="descricao" name="descricao" rows="4" class="form-control" autocomplete="off"><?= htmlspecialchars($empresa['descricao'] ?? '') ?></textarea>
</div>

<hr>

<h3>Contato</h3>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" class="form-control"
                value="<?= htmlspecialchars($empresa['telefone'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="whatsapp">WhatsApp</label>
            <input type="text" id="whatsapp" name="whatsapp" class="form-control"
                value="<?= htmlspecialchars($empresa['whatsapp'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" class="form-control"
                value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
        </div>
    </div>

</div>

<div class="grupo-form">
    <label for="site">Site</label>
    <input type="text" id="site" name="site" class="form-control"
        value="<?= htmlspecialchars($empresa['site'] ?? '') ?>">
</div>

<hr>

<h3>Endereço</h3>

<div class="row">

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="cep">CEP</label>
            <input type="text" id="cep" name="cep" class="form-control" maxlength="9"
                value="<?= htmlspecialchars($empresa['cep'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-7">
        <div class="grupo-form">
            <label for="endereco">Rua / Avenida</label>
            <input type="text" id="endereco" name="endereco" class="form-control"
                value="<?= htmlspecialchars($empresa['endereco'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-2">
        <div class="grupo-form">
            <label for="numero">Número</label>
            <input type="text" id="numero" name="numero" class="form-control"
                value="<?= htmlspecialchars($empresa['numero'] ?? '') ?>">
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="complemento">Complemento</label>
            <input type="text" id="complemento" name="complemento" class="form-control"
                value="<?= htmlspecialchars($empresa['complemento'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-4">
        <div class="grupo-form">
            <label for="bairro">Bairro</label>
            <input type="text" id="bairro" name="bairro" class="form-control"
                value="<?= htmlspecialchars($empresa['bairro'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-3">
        <div class="grupo-form">
            <label for="cidade">Cidade</label>
            <input type="text" id="cidade" name="cidade" class="form-control"
                value="<?= htmlspecialchars($empresa['cidade'] ?? '') ?>">
        </div>
    </div>

    <div class="col-md-1">
        <div class="grupo-form">
            <label for="estado">UF</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">-</option>
                <?php foreach ($estados as $uf): ?>
                    <option value="<?= $uf ?>" <?= ($empresa['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>

<hr>

<h3>Fotos</h3>

<div class="row">

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="logo">Logo</label>
            <?php if (!empty($empresa['logo'])): ?>
                <img src="../uploads/empresas/<?= htmlspecialchars($empresa['logo']) ?>" width="100" style="border-radius:8px; margin-bottom:8px; display:block;" alt="Logo atual">
            <?php endif; ?>
            <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp" class="form-control">
            <small class="text-muted">Deixe em branco pra manter a imagem atual.</small>
        </div>
    </div>

    <div class="col-md-6">
        <div class="grupo-form">
            <label for="capa">Imagem de Capa</label>
            <?php if (!empty($empresa['capa'])): ?>
                <img src="../uploads/empresas/<?= htmlspecialchars($empresa['capa']) ?>" width="150" style="border-radius:8px; margin-bottom:8px; display:block;" alt="Capa atual">
            <?php endif; ?>
            <input type="file" id="capa" name="capa" accept=".jpg,.jpeg,.png,.webp" class="form-control">
            <small class="text-muted">Deixe em branco pra manter a imagem atual.</small>
        </div>
    </div>

</div>

<div class="grupo-form">

    <label>Galeria Atual</label>

    <?php if (!empty($galeriaAtual)): ?>

        <div class="d-flex flex-wrap gap-2 mb-2">

            <?php foreach ($galeriaAtual as $imagem): ?>

                <div style="position:relative;">

                    <img
                        src="../uploads/empresas/<?= htmlspecialchars($imagem['imagem']) ?>"
                        width="90"
                        height="90"
                        style="object-fit:cover; border-radius:8px;"
                        alt="Foto da galeria">

                    <a
                        href="excluir_imagem_empresa.php?empresa_id=<?= $empresaId ?>&imagem_id=<?= (int) $imagem['id'] ?>"
                        onclick="return confirm('Remover essa foto da galeria?');"
                        style="position:absolute; top:-6px; right:-6px; background:#dc3545; color:#fff; border-radius:50%; width:22px; height:22px; display:flex; align-items:center; justify-content:center; text-decoration:none; font-size:14px;">
                        ×
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <p class="text-muted">Nenhuma foto na galeria ainda.</p>

    <?php endif; ?>

    <label for="galeria">Adicionar novas fotos à galeria</label>
    <input type="file" id="galeria" name="galeria[]" accept=".jpg,.jpeg,.png,.webp" multiple class="form-control">

</div>

<hr>

<h3>Horário de Funcionamento</h3>

<div class="table-responsive">

    <table class="table align-middle">

        <thead>
            <tr>
                <th>Dia</th>
                <th>Abertura</th>
                <th>Fechamento</th>
                <th>Fechado</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($diasSemana as $dia): ?>

                <?php $horarioDia = $horariosPorDia[$dia] ?? null; ?>

                <tr>
                    <td><?= $dia ?></td>
                    <td>
                        <input type="time" name="horario[<?= $dia ?>][abertura]" class="form-control"
                            value="<?= htmlspecialchars(substr($horarioDia['abertura'] ?? '', 0, 5)) ?>">
                    </td>
                    <td>
                        <input type="time" name="horario[<?= $dia ?>][fechamento]" class="form-control"
                            value="<?= htmlspecialchars(substr($horarioDia['fechamento'] ?? '', 0, 5)) ?>">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" name="horario[<?= $dia ?>][fechado]" value="1" class="form-check-input"
                            <?= !empty($horarioDia['fechado']) ? 'checked' : '' ?>>
                    </td>
                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>

<hr>

<div class="row mt-4">

    <div class="col-md-6">
        <a href="minhas_empresas.php" class="btn btn-secondary w-100">← Cancelar</a>
    </div>

    <div class="col-md-6">
        <button type="submit" class="btn btn-success w-100">💾 Salvar Alterações</button>
    </div>

</div>

</form>

</div>

</main>

<?php require_once "../app/Includes/footer.php"; ?>

<script>

const cnpjInput = document.getElementById("cnpj");

if (cnpjInput) {

    cnpjInput.addEventListener("input", function () {

        let valor = this.value.replace(/\D/g, "").slice(0, 14);

        valor = valor.replace(/^(\d{2})(\d)/, "$1.$2");
        valor = valor.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
        valor = valor.replace(/\.(\d{3})(\d)/, ".$1/$2");
        valor = valor.replace(/(\d{4})(\d)/, "$1-$2");

        this.value = valor;

    });

}

</script>

</body>

</html>
