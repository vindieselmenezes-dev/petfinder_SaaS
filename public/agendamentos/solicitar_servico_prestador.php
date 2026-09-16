<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



require_once __DIR__ . '/../../app/Controllers/PrestadorController.php';
require_once __DIR__ . '/../../app/Helpers/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id'])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

$prestadorId = (int) ($_POST['prestador_id'] ?? 0);

if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
    $_SESSION['solicitacao_mensagem'] = 'Sessão expirada. Tente novamente.';
    header('Location: prestador.php?id=' . $prestadorId);
    exit;
}

$controller = new PrestadorController();

$dados = [
    'prestador_id' => $prestadorId,
    'usuario_id' => (int) $_SESSION['usuario_id'],
    'pet_id' => (int) ($_POST['pet_id'] ?? 0) ?: null,
    'data_desejada' => trim($_POST['data_desejada'] ?? ''),
    'periodo' => trim($_POST['periodo'] ?? ''),
    'mensagem' => trim($_POST['mensagem'] ?? ''),
];

$resultado = $controller->criarSolicitacao($dados);

$_SESSION['solicitacao_mensagem'] = $resultado !== false
    ? 'Solicitação enviada! O profissional vai entrar em contato.'
    : 'Não foi possível enviar sua solicitação. Tente novamente.';

header('Location: prestador.php?id=' . $prestadorId);
exit;
