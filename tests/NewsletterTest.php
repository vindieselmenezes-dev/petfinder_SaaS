<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/Newsletter.php
 * ==========================================================
 */

echo "\n--- Testando Newsletter.php ---\n";

TestKit::run('gerarTokenDescadastro() é determinístico e diferente por e-mail', function () {

    $newsletter = new Newsletter();
    $emailA = TESTE_MARCADOR . '_news_a_' . uniqid() . '@petfinder.test';
    $emailB = TESTE_MARCADOR . '_news_b_' . uniqid() . '@petfinder.test';

    $tokenA1 = $newsletter->gerarTokenDescadastro($emailA);
    $tokenA2 = $newsletter->gerarTokenDescadastro($emailA);
    $tokenB = $newsletter->gerarTokenDescadastro($emailB);

    TestKit::assertEquals($tokenA1, $tokenA2, 'o mesmo e-mail deveria sempre gerar o mesmo token');
    TestKit::assertTrue($tokenA1 !== $tokenB, 'e-mails diferentes deveriam gerar tokens diferentes');

    // maiúsculas/espaços não deveriam mudar o token (o model normaliza)
    $tokenNormalizado = $newsletter->gerarTokenDescadastro('  ' . strtoupper($emailA) . '  ');
    TestKit::assertEquals($tokenA1, $tokenNormalizado, 'normalização de e-mail deveria gerar o mesmo token');
});

TestKit::run('tokenDescadastroValido() aceita o token certo e recusa qualquer outro', function () {

    $newsletter = new Newsletter();
    $email = TESTE_MARCADOR . '_news_valid_' . uniqid() . '@petfinder.test';

    $token = $newsletter->gerarTokenDescadastro($email);

    TestKit::assertTrue($newsletter->tokenDescadastroValido($email, $token), 'token correto deveria ser válido');
    TestKit::assertFalse($newsletter->tokenDescadastroValido($email, 'token-forjado'), 'token forjado não deveria ser válido');
    TestKit::assertFalse($newsletter->tokenDescadastroValido($email, ''), 'token vazio não deveria ser válido');

    $outroEmail = TESTE_MARCADOR . '_news_outro_' . uniqid() . '@petfinder.test';
    TestKit::assertFalse(
        $newsletter->tokenDescadastroValido($outroEmail, $token),
        'token de um e-mail não deveria validar para outro e-mail'
    );
});

TestKit::run('inscrever() → cancelar() → inscrever() (ciclo completo de descadastro)', function () {

    $newsletter = new Newsletter();
    $email = TESTE_MARCADOR . '_news_ciclo_' . uniqid() . '@petfinder.test';

    $primeira = $newsletter->inscrever($email);
    TestKit::assertTrue($primeira['sucesso'], 'primeira inscrição deveria funcionar');
    TestKit::assertFalse($primeira['jaInscrito'], 'não deveria estar inscrito ainda na primeira vez');

    $segunda = $newsletter->inscrever($email);
    TestKit::assertTrue($segunda['sucesso']);
    TestKit::assertTrue($segunda['jaInscrito'], 'inscrever de novo com o mesmo e-mail deveria detectar duplicidade');

    TestKit::assertTrue($newsletter->cancelar($email), 'cancelar() deveria funcionar para um e-mail inscrito');

    // depois de cancelado, inscrever de novo reativa (não é mais
    // "já inscrito", porque tinha sido desativado)
    $terceira = $newsletter->inscrever($email);
    TestKit::assertTrue($terceira['sucesso'], 'reinscrição após cancelamento deveria funcionar');
    TestKit::assertFalse($terceira['jaInscrito'], 'depois de cancelado, não deveria contar como "já inscrito"');
});

TestKit::run('inscrever() recusa e-mail inválido', function () {

    $newsletter = new Newsletter();
    $resultado = $newsletter->inscrever('isso-nao-e-um-email');

    TestKit::assertFalse($resultado['sucesso'], 'e-mail inválido não deveria ser aceito');
});

// --- limpeza: remove tudo que os testes acima criaram -----------------------
$pdoLimpezaNewsletter = Database::conectar();
$pdoLimpezaNewsletter->prepare("DELETE FROM newsletter WHERE email LIKE :marcador")
    ->execute([':marcador' => '%' . strtolower(TESTE_MARCADOR) . '%']);
