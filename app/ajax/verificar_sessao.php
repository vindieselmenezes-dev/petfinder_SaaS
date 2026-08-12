<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

echo json_encode([
    "logado" => isset($_SESSION["usuario_id"]),
    "nome"   => $_SESSION["usuario_nome"] ?? null
]);
