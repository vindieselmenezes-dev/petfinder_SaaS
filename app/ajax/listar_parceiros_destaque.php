<?php

declare(strict_types=1);

/**
 * Alimenta a seção "Parceiros, campanhas e doações" da home
 * (index.html é estático, então os dados chegam por aqui).
 *
 * Devolve os parceiros em destaque e as ações em andamento —
 * campanhas, eventos e pedidos de doação.
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$parceiroModel = new Parceiro();
$campanhaModel = new Campanha();

echo json_encode([
    'parceiros' => $parceiroModel->listarDestaques(8),
    'campanhas' => $campanhaModel->listarAtivas('', 3),
    'resumo'    => $parceiroModel->resumo(),
], JSON_UNESCAPED_UNICODE);
