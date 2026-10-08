<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

$controller = new PetController();
$pdo        = Database::conectar();
$usuarioId  = Auth::id();

$petId = (int) ($_GET["id"] ?? 0);

if ($petId <= 0) {
    Url::redirecionar(Url::pagina('meus_pets.php'));
    exit;
}

$pet = $controller->buscarPorId($petId);

if (!$pet || (int) $pet['usuario_id'] !== $usuarioId) {
    Flash::erro("Pet não encontrado ou você não tem permissão.");
    Url::redirecionar(Url::pagina('meus_pets.php'));
    exit;
}

// Volta o status do pet pro normal e encerra qualquer alerta ativo dele
$controller->atualizarStatus($petId, 'Com Tutor', $usuarioId, 'Marcado como recuperado pelo tutor');

$stmt = $pdo->prepare("UPDATE pet_alertas_perdidos SET status = 'Encontrado' WHERE pet_id = ? AND status = 'Ativo'");
$stmt->execute([$petId]);

Flash::sucesso("Que ótima notícia! " . htmlspecialchars($pet['nome']) . " foi marcado como recuperado.");

Url::redirecionar(Url::pagina('meus_pets.php'));
exit;
