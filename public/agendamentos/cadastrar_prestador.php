<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/PrestadorController.php";
require_once "../../app/Helpers/Csrf.php";

$controller = new PrestadorController();
$usuarioId = (int) $_SESSION["usuario_id"];

$tipo = $_GET["tipo"] ?? $_POST["tipo"] ?? "passeador";

if (!in_array($tipo, Prestador::TIPOS_VALIDOS, true)) {
    $tipo = "passeador";
}

// Se o usuário já tem um perfil desse tipo, manda pra edição/perfil em vez de duplicar
$perfilExistente = $controller->buscarPorUsuarioETipo($usuarioId, $tipo);

if ($perfilExistente && $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: prestador.php?id=" . (int) $perfilExistente["id"] . "&voce=1");
    exit;
}

$mensagem = "";
$tipoMensagem = "";

$diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
$periodosDia = ['Manhã', 'Tarde', 'Noite'];

$servicosPasseador = [
    'Passeio individual', 'Passeio em grupo', 'Passeio matinal',
    'Passeio vespertino', 'Passeio noturno', 'Corrida com o pet'
];

$servicosPetSitter = [
    'Visitas diárias', 'Hospedagem na casa do pet sitter', 'Pernoite na casa do tutor',
    'Alimentação e água', 'Administração de medicamentos', 'Brincadeiras e enriquecimento',
    'Higiene básica', 'Envio de fotos e atualizações'
];

$servicosAdestrador = [
    'Adestramento básico', 'Adestramento comportamental', 'Adestramento de filhotes',
    'Aulas em domicílio', 'Aulas em espaço próprio', 'Socialização e enriquecimento ambiental'
];

$servicosDisponiveis = match ($tipo) {
    'pet_sitter' => $servicosPetSitter,
    'adestrador' => $servicosAdestrador,
    default => $servicosPasseador,
};

$animaisDisponiveis = [
    'Cães de pequeno porte', 'Cães de médio porte', 'Cães de grande porte', 'Gatos', 'Outros animais'
];

$tiposVeiculo = ['Carro', 'Van', 'Moto com bag pet'];

$estados = [
    "AC","AL","AP","AM","BA","CE","DF","ES","GO",
    "MA","MT","MS","MG","PA","PB","PR","PE","PI",
    "RJ","RN","RS","RO","RR","SC","SP","SE","TO"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {

        $mensagem = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";

    } elseif ($perfilExistente) {

        $mensagem = "Você já tem um perfil cadastrado nesse tipo de serviço.";
        $tipoMensagem = "erro";

    } else {

        $dados = [
            "usuario_id" => $usuarioId,
            "tipo" => $tipo,
            "cpf" => $controller->limparCpf(trim($_POST["cpf"] ?? "")),
            "genero" => trim($_POST["genero"] ?? ""),
            "data_nascimento" => trim($_POST["dataNascimento"] ?? "") ?: null,
            "telefone" => trim($_POST["telefone"] ?? ""),
            "whatsapp" => trim($_POST["whatsapp"] ?? ""),
            "email" => trim($_POST["email"] ?? ""),
            "cep" => trim($_POST["cep"] ?? ""),
            "endereco" => trim($_POST["endereco"] ?? ""),
            "numero" => trim($_POST["numero"] ?? ""),
            "complemento" => trim($_POST["complemento"] ?? ""),
            "bairro" => trim($_POST["bairro"] ?? ""),
            "cidade" => trim($_POST["cidade"] ?? ""),
            "estado" => trim($_POST["estado"] ?? ""),
            "tempo_experiencia" => trim($_POST["tempoExperiencia"] ?? $_POST["experiencia"] ?? ""),
            "formacao" => trim($_POST["formacao"] ?? ""),
            "experiencia" => trim($_POST["experienciaTexto"] ?? ""),
            "apresentacao" => trim($_POST["apresentacao"] ?? $_POST["descricao"] ?? ""),
            "diferencial" => trim($_POST["diferencial"] ?? ""),
            "valor_hora" => trim($_POST["valorHora"] ?? $_POST["valor"] ?? ""),
            "valor_diaria" => trim($_POST["valorDiaria"] ?? ""),
            "forma_pagamento" => trim($_POST["formaPagamento"] ?? ""),
            "area_atendimento" => trim($_POST["areaAtendimento"] ?? ""),
            "instagram" => trim($_POST["instagram"] ?? ""),
            "facebook" => trim($_POST["facebook"] ?? ""),
        ];

        $dados["foto"] = null;

        if (!empty($_FILES["foto"]["name"])) {
            $dados["foto"] = $controller->processarFoto($_FILES["foto"]);
        }

        $servicos = $_POST["servicos"] ?? [];
        $animais = $_POST["animais"] ?? [];
        $dias = $_POST["dias"] ?? [];
        $periodos = $_POST["periodo"] ?? [];

        $veiculo = [];
        if ($tipo === 'taxista_pet') {
            $veiculo = [
                "tipo_veiculo" => trim($_POST["tipoVeiculo"] ?? ""),
                "modelo" => trim($_POST["modeloVeiculo"] ?? ""),
                "placa" => trim($_POST["placaVeiculo"] ?? ""),
                "ano" => trim($_POST["anoVeiculo"] ?? ""),
                "capacidade_pets" => trim($_POST["capacidadePets"] ?? "1"),
                "ar_condicionado" => !empty($_POST["arCondicionado"]),
                "caixa_transporte" => !empty($_POST["caixaTransporte"]),
                "aceita_animais_grandes" => !empty($_POST["aceitaAnimaisGrandes"]),
                "valor_km" => trim($_POST["valorKm"] ?? ""),
                "valor_corrida_minima" => trim($_POST["valorCorridaMinima"] ?? ""),
            ];
        }

        $novoId = $controller->cadastrarCompleto($dados, $servicos, $animais, $dias, $periodos, $veiculo);

        if ($novoId !== false) {
            Flash::sucesso("Cadastro enviado! Seu perfil já está visível no diretório de profissionais.");
            header("Location: prestador.php?id=" . $novoId . "&voce=1");
            exit;
        }

        $mensagem = "Não foi possível concluir o cadastro. Confira os campos obrigatórios.";
        $tipoMensagem = "erro";

    }

}

$tituloPagina = match ($tipo) {
    'pet_sitter' => "Cadastro de Pet Sitter",
    'taxista_pet' => "Cadastro de Táxi Pet",
    'adestrador' => "Cadastro de Adestrador",
    default => "Cadastro de Passeador",
};

?>

<?php require_once "../../app/Includes/header.php"; ?>

<?php require_once "../../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>
<?php if ($tipo === 'pet_sitter'): ?>
    🏠 Cadastro de Pet Sitter
<?php elseif ($tipo === 'taxista_pet'): ?>
    🚕 Cadastro de Táxi Pet
<?php elseif ($tipo === 'adestrador'): ?>
    🎓 Cadastro de Adestrador
<?php else: ?>
    🐕 Cadastro de Passeador
<?php endif; ?>
</h1>

<p>
<?php if ($tipo === 'pet_sitter'): ?>
    Ofereça hospedagem, visitas e cuidados para pets diretamente pela plataforma.
<?php elseif ($tipo === 'taxista_pet'): ?>
    Ofereça transporte seguro e confortável para pets: consultas, banho e tosa, viagens e mais.
<?php elseif ($tipo === 'adestrador'): ?>
    Ofereça aulas e serviços de adestramento como profissional autônomo, sem precisar de CNPJ ou empresa registrada.
<?php else: ?>
    Faça parte do PetFinder Brasil e ofereça seus serviços de passeio para tutores da sua cidade e região.
<?php endif; ?>
</p>

<div class="btn-group mb-3" role="group">
    <a href="cadastrar_prestador.php?tipo=passeador" class="btn <?= $tipo === 'passeador' ? 'btn-success' : 'btn-outline-success' ?>">🐕 Passeador</a>
    <a href="cadastrar_prestador.php?tipo=pet_sitter" class="btn <?= $tipo === 'pet_sitter' ? 'btn-success' : 'btn-outline-success' ?>">🏠 Pet Sitter</a>
    <a href="cadastrar_prestador.php?tipo=taxista_pet" class="btn <?= $tipo === 'taxista_pet' ? 'btn-success' : 'btn-outline-success' ?>">🚕 Táxi Pet</a>
    <a href="cadastrar_prestador.php?tipo=adestrador" class="btn <?= $tipo === 'adestrador' ? 'btn-success' : 'btn-outline-success' ?>">🎓 Adestrador</a>
</div>

<?php if (!empty($mensagem)): ?>
<div class="mensagem <?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
<?= Csrf::campoHtml() ?>

<h3>👤 Dados Pessoais</h3>

<div class="row">
    <div class="col-md-6">
        <div class="grupo-form">
            <label for="nome">Nome completo (usado do seu cadastro)</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars(($_SESSION['usuario_nome'] ?? '')) ?>" disabled>
        </div>
    </div>
    <div class="col-md-3">
        <div class="grupo-form">
            <label for="cpf">CPF *</label>
            <input type="text" id="cpf" name="cpf" class="form-control" placeholder="000.000.000-00" maxlength="14" required>
        </div>
    </div>
    <div class="col-md-3">
        <div class="grupo-form">
            <label for="dataNascimento">Data de nascimento</label>
            <input type="date" id="dataNascimento" name="dataNascimento" class="form-control">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="genero">Gênero</label>
            <select id="genero" name="genero" class="form-select">
                <option value="nao-informar">Prefiro não informar</option>
                <option value="feminino">Feminino</option>
                <option value="masculino">Masculino</option>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" class="form-control" placeholder="(31) 3333-3333">
        </div>
    </div>
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="whatsapp">WhatsApp</label>
            <input type="text" id="whatsapp" name="whatsapp" class="form-control" placeholder="(31) 99999-9999">
        </div>
    </div>
</div>

<div class="grupo-form">
    <label for="email">E-mail de contato</label>
    <input type="email" id="email" name="email" class="form-control">
</div>

<hr>

<h3>📍 Endereço / Área de Atendimento</h3>

<div class="row">
    <div class="col-md-3">
        <div class="grupo-form">
            <label for="cep">CEP</label>
            <input type="text" id="cep" name="cep" class="form-control" maxlength="9" placeholder="00000-000">
        </div>
    </div>
    <div class="col-md-6">
        <div class="grupo-form">
            <label for="endereco">Rua / Avenida</label>
            <input type="text" id="endereco" name="endereco" class="form-control">
        </div>
    </div>
    <div class="col-md-3">
        <div class="grupo-form">
            <label for="numero">Número</label>
            <input type="text" id="numero" name="numero" class="form-control">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="grupo-form">
            <label for="bairro">Bairro</label>
            <input type="text" id="bairro" name="bairro" class="form-control">
        </div>
    </div>
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="cidade">Cidade *</label>
            <input type="text" id="cidade" name="cidade" class="form-control" required>
        </div>
    </div>
    <div class="col-md-2">
        <div class="grupo-form">
            <label for="estado">UF</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">-</option>
                <?php foreach ($estados as $uf): ?>
                    <option value="<?= $uf ?>"><?= $uf ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="grupo-form">
    <label for="areaAtendimento">Bairros / regiões que você atende</label>
    <input type="text" id="areaAtendimento" name="areaAtendimento" class="form-control" placeholder="Ex: Centro, Bela Vista e região">
</div>

<hr>

<h3>🐾 Experiência</h3>

<div class="row">
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="tempoExperiencia">Tempo de experiência</label>
            <input type="text" id="tempoExperiencia" name="tempoExperiencia" class="form-control" placeholder="Ex: 3 anos">
        </div>
    </div>
    <div class="col-md-8">
        <div class="grupo-form">
            <label for="formacao">Formação / cursos (opcional)</label>
            <input type="text" id="formacao" name="formacao" class="form-control" placeholder="Ex: Curso de comportamento animal">
        </div>
    </div>
</div>

<div class="grupo-form">
    <label for="experienciaTexto">Conte sobre sua experiência com animais</label>
    <textarea id="experienciaTexto" name="experienciaTexto" rows="3" class="form-control"></textarea>
</div>

<hr>

<?php if ($tipo === 'taxista_pet'): ?>

<h3>🚗 Dados do Veículo</h3>

<div class="row">
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="tipoVeiculo">Tipo de veículo</label>
            <select id="tipoVeiculo" name="tipoVeiculo" class="form-select">
                <?php foreach ($tiposVeiculo as $tv): ?>
                    <option value="<?= htmlspecialchars($tv) ?>"><?= htmlspecialchars($tv) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="modeloVeiculo">Modelo</label>
            <input type="text" id="modeloVeiculo" name="modeloVeiculo" class="form-control" placeholder="Ex: Fiat Doblô">
        </div>
    </div>
    <div class="col-md-2">
        <div class="grupo-form">
            <label for="placaVeiculo">Placa</label>
            <input type="text" id="placaVeiculo" name="placaVeiculo" class="form-control" maxlength="8">
        </div>
    </div>
    <div class="col-md-2">
        <div class="grupo-form">
            <label for="anoVeiculo">Ano</label>
            <input type="text" id="anoVeiculo" name="anoVeiculo" class="form-control" maxlength="4">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="grupo-form">
            <label for="capacidadePets">Capacidade (nº de pets)</label>
            <input type="number" id="capacidadePets" name="capacidadePets" class="form-control" min="1" value="1">
        </div>
    </div>
    <div class="col-md-9 d-flex align-items-end gap-4 pb-2">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="arCondicionado" id="arCondicionado" value="1">
            <label class="form-check-label" for="arCondicionado">Ar-condicionado</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="caixaTransporte" id="caixaTransporte" value="1">
            <label class="form-check-label" for="caixaTransporte">Tenho caixa de transporte</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="aceitaAnimaisGrandes" id="aceitaAnimaisGrandes" value="1">
            <label class="form-check-label" for="aceitaAnimaisGrandes">Aceito animais de grande porte</label>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="grupo-form">
            <label for="valorKm">Valor por km (R$)</label>
            <input type="number" step="0.01" min="0" id="valorKm" name="valorKm" class="form-control">
        </div>
    </div>
    <div class="col-md-6">
        <div class="grupo-form">
            <label for="valorCorridaMinima">Valor mínimo da corrida (R$)</label>
            <input type="number" step="0.01" min="0" id="valorCorridaMinima" name="valorCorridaMinima" class="form-control">
        </div>
    </div>
</div>

<hr>

<h3>🐶 Animais que você transporta</h3>

<div class="row">
    <?php foreach ($animaisDisponiveis as $animal): ?>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="animais[]" value="<?= htmlspecialchars($animal) ?>" id="animal_<?= md5($animal) ?>">
                <label class="form-check-label" for="animal_<?= md5($animal) ?>"><?= htmlspecialchars($animal) ?></label>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>

<h3><?= match ($tipo) { 'pet_sitter' => '🏠 Serviços Oferecidos', 'adestrador' => '🎓 Serviços de Adestramento', default => '🚶 Serviços de Passeio' } ?></h3>

<div class="row">
    <?php foreach ($servicosDisponiveis as $servico): ?>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="servicos[]" value="<?= htmlspecialchars($servico) ?>" id="serv_<?= md5($servico) ?>">
                <label class="form-check-label" for="serv_<?= md5($servico) ?>"><?= htmlspecialchars($servico) ?></label>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<hr>

<h3>🐶 Animais que você atende</h3>

<div class="row">
    <?php foreach ($animaisDisponiveis as $animal): ?>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="animais[]" value="<?= htmlspecialchars($animal) ?>" id="animal_<?= md5($animal) ?>">
                <label class="form-check-label" for="animal_<?= md5($animal) ?>"><?= htmlspecialchars($animal) ?></label>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<hr>

<h3>📅 Disponibilidade</h3>

<div class="row">
    <div class="col-md-6">
        <label class="form-label small">Dias da semana</label>
        <?php foreach ($diasSemana as $dia): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="dias[]" value="<?= $dia ?>" id="dia_<?= $dia ?>">
                <label class="form-check-label" for="dia_<?= $dia ?>"><?= $dia ?></label>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label small">Períodos</label>
        <?php foreach ($periodosDia as $periodo): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="periodo[]" value="<?= $periodo ?>" id="per_<?= $periodo ?>">
                <label class="form-check-label" for="per_<?= $periodo ?>"><?= $periodo ?></label>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>

<hr>

<h3>💰 Valores e Pagamento</h3>

<div class="row">
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="valorHora">Valor por hora / passeio (R$)</label>
            <input type="number" step="0.01" min="0" id="valorHora" name="valorHora" class="form-control">
        </div>
    </div>
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="valorDiaria">Valor da diária (R$)</label>
            <input type="number" step="0.01" min="0" id="valorDiaria" name="valorDiaria" class="form-control">
        </div>
    </div>
    <div class="col-md-4">
        <div class="grupo-form">
            <label for="formaPagamento">Formas de pagamento aceitas</label>
            <input type="text" id="formaPagamento" name="formaPagamento" class="form-control" placeholder="Pix, cartão, dinheiro...">
        </div>
    </div>
</div>

<hr>

<h3>⭐ Apresentação Profissional</h3>

<div class="grupo-form">
    <label for="apresentacao">Fale sobre você para os tutores</label>
    <textarea id="apresentacao" name="apresentacao" rows="4" class="form-control" placeholder="Conte um pouco sobre seu trabalho, sua rotina de cuidados..."></textarea>
</div>

<div class="grupo-form">
    <label for="diferencial">Seu diferencial</label>
    <textarea id="diferencial" name="diferencial" rows="2" class="form-control"></textarea>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="grupo-form">
            <label for="instagram">Instagram (opcional)</label>
            <input type="text" id="instagram" name="instagram" class="form-control" placeholder="@seuinstagram">
        </div>
    </div>
    <div class="col-md-6">
        <div class="grupo-form">
            <label for="facebook">Facebook (opcional)</label>
            <input type="text" id="facebook" name="facebook" class="form-control">
        </div>
    </div>
</div>

<div class="grupo-form">
    <label for="foto">📷 Foto do profissional</label>
    <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp" class="form-control">
</div>

<hr>

<div class="row mt-4">
    <div class="col-md-6">
        <a href="<?= Url::pagina('dashboard.php') ?>" class="btn btn-secondary w-100">← Cancelar</a>
    </div>
    <div class="col-md-6">
        <button type="submit" class="btn btn-success w-100">
            <?php if ($tipo === 'pet_sitter'): ?>
                🏠 Concluir Cadastro de Pet Sitter
            <?php elseif ($tipo === 'taxista_pet'): ?>
                🚕 Concluir Cadastro de Táxi Pet
            <?php elseif ($tipo === 'adestrador'): ?>
                🎓 Concluir Cadastro de Adestrador
            <?php else: ?>
                🐕 Concluir Cadastro de Passeador
            <?php endif; ?>
        </button>
    </div>
</div>

</form>

</div>

</main>

<?php require_once "../../app/Includes/footer.php"; ?>

<script>
const cpfInput = document.getElementById("cpf");
if (cpfInput) {
    cpfInput.addEventListener("input", function () {
        let valor = this.value.replace(/\D/g, "").slice(0, 11);
        valor = valor.replace(/^(\d{3})(\d)/, "$1.$2");
        valor = valor.replace(/^(\d{3})\.(\d{3})(\d)/, "$1.$2.$3");
        valor = valor.replace(/\.(\d{3})(\d{1,2})$/, ".$1-$2");
        this.value = valor;
    });
}
</script>

</body>

</html>
