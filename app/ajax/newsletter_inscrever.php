<?php

declare(strict_types=1);

/**
 * Recebe a inscrição do formulário "Receba nossas novidades" (rodapé
 * da home, index.html — página estática, por isso o POST é feito via
 * fetch/JSON em vez de um <form action="..."> tradicional).
 *
 * Sem CSRF de propósito: é um formulário público, sem sessão, que só
 * grava um e-mail — o pior cenário de um CSRF aqui é inscrever a
 * própria vítima na newsletter, o que não justifica a fricção extra.
 * Em compensação, tem honeypot contra bots simples.
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

// No máximo 8 inscrições por IP a cada 10 minutos; estourando isso,
// bloqueia o IP por 30 minutos. Roda antes do honeypot/validação, pra
// não gastar consulta com quem está floodando.
$rateLimiter = new RateLimiter();
if (!$rateLimiter->permitido('newsletter_inscrever', 8, 10, 30)) {
    http_response_code(429);
    echo json_encode([
        'sucesso'  => false,
        'mensagem' => 'Muitas tentativas em pouco tempo. Tente novamente em '
            . $rateLimiter->minutosRestantes('newsletter_inscrever') . ' minuto(s).',
    ]);
    exit;
}

// Honeypot: campo escondido no formulário que só um robô preencheria.
// Se vier preenchido, finge sucesso (sem gravar nada) pra não dar pista
// de que existe uma checagem antibot.
if (!empty($dados['site'])) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Inscrição confirmada!']);
    exit;
}

$email = trim((string) ($dados['email'] ?? ''));

if ($email === '') {
    http_response_code(422);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Informe um e-mail.']);
    exit;
}

$resultado = (new Newsletter())->inscrever($email);

if (!$resultado['sucesso']) {
    http_response_code(422);
}

echo json_encode([
    'sucesso'    => $resultado['sucesso'],
    'jaInscrito' => $resultado['jaInscrito'],
    'mensagem'   => $resultado['mensagem'],
]);
