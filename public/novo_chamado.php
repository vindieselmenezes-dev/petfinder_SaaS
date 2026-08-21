<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$tituloPagina = "Novo Chamado";

require_once "../app/Includes/header.php";
require_once "../app/Includes/menu.php";
?>

<main class="conteudo">
<div class="container">

    <div class="formulario-cadastro" style="max-width:600px; margin:0 auto;">
        <h1>➕ Abrir Novo Chamado</h1>

        <form action="processa_chamado.php" method="POST">

            <div class="grupo-form">
                <label for="assunto">Assunto</label>
                <input type="text" id="assunto" name="assunto" class="form-control" maxlength="200" required autocomplete="off" placeholder="Ex: Não consigo cadastrar meu pet">
            </div>

            <div class="grupo-form">
                <label for="prioridade">Prioridade</label>
                <select id="prioridade" name="prioridade" class="form-select">
                    <option value="Baixa">Baixa</option>
                    <option value="Média" selected>Média</option>
                    <option value="Alta">Alta</option>
                    <option value="Urgente">Urgente</option>
                </select>
            </div>

            <div class="grupo-form">
                <label for="descricao">Descreva o problema ou dúvida</label>
                <textarea id="descricao" name="descricao" class="form-control" rows="5" autocomplete="off" required placeholder="Quanto mais detalhes, mais rápido conseguimos ajudar."></textarea>
            </div>

            <button type="submit" class="btn-acao" style="background:#3498db; color:white; width:100%; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Enviar Chamado</button>
        </form>

        <p style="text-align:center; margin-top:15px;">
            <a href="suporte.php" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar pros meus chamados</a>
        </p>
    </div>

</div>
</main>

<?php require_once "../app/Includes/footer.php"; ?>
