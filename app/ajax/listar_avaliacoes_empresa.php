<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$empresaId = (int) ($_GET['empresa_id'] ?? 0);
$avaliacoes = (new Empresa())->listarAvaliacoes($empresaId);
echo json_encode($avaliacoes, JSON_UNESCAPED_UNICODE);
