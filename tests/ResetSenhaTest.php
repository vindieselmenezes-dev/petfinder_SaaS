<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/ResetSenha.php
 * ==========================================================
 */

echo "\n--- Testando Reset de Senha ---\n";

TestKit::run('gerarToken() cria um token único e válido', function () {
    $reset = new ResetSenha();

    $token = $reset->gerarToken($GLOBALS['TESTE_USUARIO_ID']);

    TestKit::assertNotNull($token);
    TestKit::assertTrue(strlen($token) >= 32, 'o token deveria ser suficientemente longo pra ser seguro');

    $usuarioId = $reset->validarToken($token);
    TestKit::assertEquals($GLOBALS['TESTE_USUARIO_ID'], $usuarioId);
});

TestKit::run('validarToken() rejeita um token que não existe', function () {
    $reset = new ResetSenha();

    $resultado = $reset->validarToken('token_completamente_inventado_' . uniqid());

    TestKit::assertNull($resultado, 'token inexistente não deveria validar');
});

TestKit::run('marcarComoUsado() impede reaproveitar o mesmo token', function () {
    $reset = new ResetSenha();

    $token = $reset->gerarToken($GLOBALS['TESTE_USUARIO_ID']);

    // Confirma que valida antes de usar
    TestKit::assertNotNull($reset->validarToken($token));

    $reset->marcarComoUsado($token);

    // Depois de usado, não deveria validar mais
    $resultado = $reset->validarToken($token);
    TestKit::assertNull($resultado, 'um token já usado não deveria validar de novo');
});

TestKit::run('token expirado não passa mais na validação', function () {
    $reset = new ResetSenha();
    $pdo = Database::conectar();

    $token = $reset->gerarToken($GLOBALS['TESTE_USUARIO_ID']);

    // Simula o relógio andando: força a expiração pra 1 hora atrás
    $stmt = $pdo->prepare("UPDATE reset_senha_tokens SET expira_em = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE token = :token");
    $stmt->execute([':token' => $token]);

    $resultado = $reset->validarToken($token);
    TestKit::assertNull($resultado, 'um token expirado não deveria validar');
});

TestKit::run('cada solicitação gera um token diferente do anterior', function () {
    $reset = new ResetSenha();

    $token1 = $reset->gerarToken($GLOBALS['TESTE_USUARIO_ID']);
    $token2 = $reset->gerarToken($GLOBALS['TESTE_USUARIO_ID']);

    TestKit::assertFalse($token1 === $token2, 'dois tokens gerados em sequência não deveriam ser iguais');

    // Os dois continuam válidos (não existe limite de "1 token por vez")
    TestKit::assertNotNull($reset->validarToken($token1));
    TestKit::assertNotNull($reset->validarToken($token2));
});
