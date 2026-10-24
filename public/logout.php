<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

// Evita "logout forçado": um link/imagem em OUTRO site não pode deslogar o
// usuário. Navegadores modernos informam a origem em Sec-Fetch-Site.
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') {
    http_response_code(403);
    exit('Para sair, use o botão "Sair" do próprio site.');
}

Auth::logout();

header('Location: login.php');
exit;
