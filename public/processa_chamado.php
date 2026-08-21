<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/SuporteController.php";
require_once "../app/Controllers/NotificacaoController.php";
require_once "../app/Models/Usuario.php";

$controller = new SuporteController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$assunto    = trim($_POST["assunto"] ?? "");
$prioridade = trim($_POST["prioridade"] ?? "Média");
$descricao  = trim($_POST["descricao"] ?? "");

$novoId = $controller->abrirChamado($usuarioId, $assunto, $descricao, $prioridade);

if ($novoId !== false) {
    // Avisa todos os administradores da plataforma que um novo chamado chegou
    $usuarioModel = new Usuario();
    $notificacaoController = new NotificacaoController();
    $solicitanteNome = $_SESSION['usuario_nome'] ?? 'Um usuário';
    foreach ($usuarioModel->listarIdsAdministradores() as $adminId) {
        $notificacaoController->criar(
            (int) $adminId,
            "💬 Novo chamado de suporte",
            "$solicitanteNome abriu o chamado \"$assunto\" (prioridade $prioridade).",
            'Sistema',
            'chamado.php?id=' . $novoId
        );
    }

    $_SESSION["sucesso_chamado"] = "Chamado aberto com sucesso! Nossa equipe vai te responder em breve.";
    header("Location: chamado.php?id=" . $novoId);
} else {
    $_SESSION["erro_chamado"] = "Preencha o assunto e a descrição pra abrir o chamado.";
    header("Location: novo_chamado.php");
}
exit;
