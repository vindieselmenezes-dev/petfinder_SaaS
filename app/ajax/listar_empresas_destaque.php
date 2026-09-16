<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$empresas = (new EmpresaController())->listarDestaques(6);

echo json_encode($empresas, JSON_UNESCAPED_UNICODE);