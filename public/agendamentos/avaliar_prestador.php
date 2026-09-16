<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



require_once __DIR__ . '/../../app/Controllers/PrestadorController.php';
require_once __DIR__ . '/../../app/Helpers/Csrf.php';

$prestadorId = (int) ($_POST['prestador_id'] ?? 0);
$nota = (int) ($_POST['nota'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || !Csrf::validar($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Requisição inválida.');
}

$sucesso = (new PrestadorController())->avaliar($prestadorId, (int) $_SESSION['usuario_id'], $nota, $comentario);

$_SESSION['avaliacao_mensagem_prestador'] = $sucesso
    ? 'Avaliação registrada com sucesso.'
    : 'Você já avaliou esse profissional ou a nota é inválida.';

header('Location: prestador.php?id=' . $prestadorId);
exit;
