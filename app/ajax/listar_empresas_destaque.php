<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Controllers/EmpresaController.php';

$empresas = (new EmpresaController())->listarDestaques(6);

echo json_encode($empresas, JSON_UNESCAPED_UNICODE);