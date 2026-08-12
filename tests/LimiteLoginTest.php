<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/LimiteLogin.php
 * ==========================================================
 */

echo "\n--- Testando LimiteLogin.php ---\n";

TestKit::run('registrarFalha() bloqueia após atingir o limite de tentativas', function () {

    require_once __DIR__ . '/../app/Models/LimiteLogin.php';

    $limite = new LimiteLogin();
    $email = TESTE_MARCADOR . '_login_' . uniqid() . '@petfinder.test';

    TestKit::assertFalse($limite->estaBloqueado($email), 'não deveria estar bloqueado no início');

    // simula 5 tentativas erradas seguidas
    for ($i = 0; $i < 5; $i++) {
        $limite->registrarFalha($email);
    }

    TestKit::assertTrue($limite->estaBloqueado($email), 'deveria estar bloqueado após 5 tentativas');
    TestKit::assertTrue($limite->minutosRestantes($email) > 0, 'deveria ter minutos restantes de bloqueio');

    // limpeza
    $limite->registrarSucesso($email);
});

TestKit::run('registrarSucesso() limpa o histórico de tentativas', function () {

    require_once __DIR__ . '/../app/Models/LimiteLogin.php';

    $limite = new LimiteLogin();
    $email = TESTE_MARCADOR . '_login_' . uniqid() . '@petfinder.test';

    $limite->registrarFalha($email);
    $limite->registrarFalha($email);

    TestKit::assertEquals(3, $limite->tentativasRestantes($email), 'deveria restar 3 tentativas (5 - 2)');

    $limite->registrarSucesso($email);

    TestKit::assertEquals(5, $limite->tentativasRestantes($email), 'deveria voltar a ter 5 tentativas disponíveis após sucesso');
    TestKit::assertFalse($limite->estaBloqueado($email));
});
