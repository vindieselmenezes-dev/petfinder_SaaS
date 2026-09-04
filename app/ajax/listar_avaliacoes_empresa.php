<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Models/Empresa.php';

$empresaId = (int) ($_GET['empresa_id'] ?? 0);
$avaliacoes = (new Empresa())->listarAvaliacoes($empresaId);
echo json_encode($avaliacoes, JSON_UNESCAPED_UNICODE);
