<?php

declare(strict_types=1);

/**
 * Compatibilidade: esta página foi movida para o módulo "empresas"
 * como parte da reorganização de public/ por funcionalidade.
 * Este arquivo existe só para não quebrar links/favoritos antigos.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$destino = Url::pagina('cadastrar_empresa.php');
if (!empty($_SERVER['QUERY_STRING'])) {
    $destino .= '?' . $_SERVER['QUERY_STRING'];
}

header('Location: ' . $destino, true, 301);
exit;
