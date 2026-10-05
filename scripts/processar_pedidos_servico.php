<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Controllers/PedidoServicoController.php';

$total = (new PedidoServicoController())->processarSegundaRodada();
fwrite(STDOUT, sprintf("Notificacoes da segunda rodada enviadas: %d\n", $total));
