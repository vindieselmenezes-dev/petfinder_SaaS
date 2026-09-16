<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

/**
 * "Adestramento" deixou de ser uma categoria de empresa (nenhuma empresa
 * real usava essa categoria) e passou a ser um tipo de prestador autônomo,
 * junto com Passeador/Pet Sitter/Táxi Pet — sem exigir CNPJ.
 *
 * Esta página só existe pra não quebrar o link que já estava no menu;
 * ela manda direto pra listagem de adestradores autônomos.
 */
header('Location: ' . Url::pagina('prestadores.php') . '?tipo=adestrador');
exit;
