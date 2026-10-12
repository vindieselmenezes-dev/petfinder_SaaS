<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Regra única de senha e limite de tentativas
 * ==========================================================
 * Cobre as fraquezas que sobraram da auditoria:
 *   1. a regra de senha era diferente em cada tela (6 x 8 caracteres);
 *   2. o login, o pedido de redefinição e o código 2FA aceitavam
 *      tentativas ilimitadas;
 *   3. o RateLimiter confiava num X-Forwarded-For que qualquer pessoa
 *      pode forjar.
 */

require_once __DIR__ . '/../app/Helpers/ValidacaoSenha.php';
require_once __DIR__ . '/../app/Models/RateLimiter.php';
require_once __DIR__ . '/../app/Models/LimiteLogin.php';

/** Lê um arquivo do projeto (para conferir que as telas usam as proteções). */
function sslCodigoFonte(string $caminhoRelativo): string
{
    $conteudo = file_get_contents(__DIR__ . '/../' . $caminhoRelativo);

    return $conteudo === false ? '' : $conteudo;
}

/**
 * Roda $funcao com REMOTE_ADDR, X-Forwarded-For e TRUSTED_PROXIES
 * definidos, e devolve o que RateLimiter::ip() respondeu. Restaura tudo.
 */
function sslIpCom(string $remoto, ?string $encaminhado, ?string $proxies): string
{
    $antesRemoto = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $antesXff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
    $antesProxies = getenv('TRUSTED_PROXIES');

    try {
        $_SERVER['REMOTE_ADDR'] = $remoto;
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($encaminhado !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $encaminhado;
        }
        putenv($proxies === null ? 'TRUSTED_PROXIES' : 'TRUSTED_PROXIES=' . $proxies);

        return RateLimiter::ip();
    } finally {
        $_SERVER['REMOTE_ADDR'] = $antesRemoto;
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($antesXff !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $antesXff;
        }
        putenv($antesProxies === false ? 'TRUSTED_PROXIES' : 'TRUSTED_PROXIES=' . $antesProxies);
    }
}

echo "\n--- Testando a regra única de senha ---\n";

TestKit::run('ValidacaoSenha aceita senha com 8+ caracteres, maiúscula e número', function () {
    TestKit::assertNull(ValidacaoSenha::validar('Abcdefg1'), 'exatamente 8 caracteres deveria passar');
    TestKit::assertNull(ValidacaoSenha::validar('Demo@1234'));
    TestKit::assertNull(ValidacaoSenha::validar('Senha1Forte!'));
});

TestKit::run('ValidacaoSenha recusa senha curta (inclusive as de 6 e 7 caracteres)', function () {
    foreach (['', 'Ab1', 'Abcde1', 'Abcdef1'] as $curta) {
        TestKit::assertNotNull(ValidacaoSenha::validar($curta), "'{$curta}' deveria ser recusada");
    }
});

TestKit::run('ValidacaoSenha exige maiúscula e número', function () {
    TestKit::assertNotNull(ValidacaoSenha::validar('abcdefg1'), 'sem maiúscula');
    TestKit::assertNotNull(ValidacaoSenha::validar('Abcdefgh'), 'sem número');
});

TestKit::run('ValidacaoSenha limita o tamanho a 72 bytes (limite do bcrypt)', function () {
    $noLimite = 'A1' . str_repeat('x', ValidacaoSenha::MAXIMO - 2);
    TestKit::assertEquals(ValidacaoSenha::MAXIMO, strlen($noLimite));
    TestKit::assertNull(ValidacaoSenha::validar($noLimite), '72 bytes deveria passar');
    TestKit::assertNotNull(ValidacaoSenha::validar($noLimite . 'x'), '73 bytes deveria ser recusada');
});

TestKit::run('regraTexto() descreve o mesmo mínimo que validar() cobra', function () {
    TestKit::assertTrue(str_contains(ValidacaoSenha::regraTexto(), (string) ValidacaoSenha::MINIMO));
});

TestKit::run('toda tela que grava senha usa ValidacaoSenha e nenhuma aceita "6 caracteres"', function () {
    $telas = [
        'public/cadastro.php',
        'public/empresas/cadastro_empresa.php',
        'public/conta/alterar_senha.php',
        'public/conta/redefinir_senha.php',
        'public/admin/admin_usuario_detalhe.php',
    ];

    foreach ($telas as $tela) {
        $codigo = sslCodigoFonte($tela);
        TestKit::assertTrue($codigo !== '', "{$tela} não foi encontrado");
        TestKit::assertTrue(str_contains($codigo, 'ValidacaoSenha::validar('), "{$tela} deveria usar ValidacaoSenha::validar()");
        TestKit::assertFalse((bool) preg_match('/strlen\(\s*\$\w+\s*\)\s*(<|>=)\s*6\b/', $codigo), "{$tela} ainda tem uma regra própria de 6 caracteres");
        TestKit::assertFalse(str_contains($codigo, '6 caracteres'), "{$tela} ainda diz '6 caracteres' na tela");
        TestKit::assertFalse(str_contains($codigo, 'minlength="6"'), "{$tela} ainda tem minlength=\"6\"");
    }
});

echo "\n--- Testando o IP usado pelo limite (X-Forwarded-For não pode ser forjado) ---\n";

TestKit::run('sem TRUSTED_PROXIES o X-Forwarded-For é ignorado', function () {
    TestKit::assertEquals('203.0.113.9', sslIpCom('203.0.113.9', '1.2.3.4', null), 'cliente forjando o cabeçalho não pode mudar o IP');
    TestKit::assertEquals('203.0.113.9', sslIpCom('203.0.113.9', '1.2.3.4', ''), 'variável vazia = nenhuma confiança');
});

TestKit::run('com TRUSTED_PROXIES, o cabeçalho só vale se a conexão vier do proxy', function () {
    TestKit::assertEquals('198.51.100.7', sslIpCom('10.0.0.5', '198.51.100.7', '10.0.0.5'), 'conexão do proxy: usa o IP do visitante');
    TestKit::assertEquals('203.0.113.9', sslIpCom('203.0.113.9', '198.51.100.7', '10.0.0.5'), 'conexão de fora: ignora o cabeçalho');
});

TestKit::run('valores forjados à esquerda do X-Forwarded-For são ignorados', function () {
    // O cliente mandou "6.6.6.6"; o proxy anotou o IP real (198.51.100.7) à direita.
    TestKit::assertEquals('198.51.100.7', sslIpCom('10.0.0.5', '6.6.6.6, 198.51.100.7', '10.0.0.5'));
});

TestKit::run('cadeia com mais de um proxy confiável devolve o primeiro IP que não é proxy', function () {
    TestKit::assertEquals('198.51.100.7', sslIpCom('10.0.0.5', '198.51.100.7, 10.0.0.9', '10.0.0.0/8'));
});

TestKit::run('TRUSTED_PROXIES aceita faixas CIDR IPv4 e IPv6', function () {
    TestKit::assertEquals('198.51.100.7', sslIpCom('172.20.4.4', '198.51.100.7', '172.16.0.0/12'), 'IPv4 dentro da faixa');
    TestKit::assertEquals('172.32.0.1', sslIpCom('172.32.0.1', '198.51.100.7', '172.16.0.0/12'), 'IPv4 fora da faixa');
    TestKit::assertEquals('198.51.100.7', sslIpCom('2001:db8:1::5', '198.51.100.7', '2001:db8::/32'), 'IPv6 dentro da faixa');
    TestKit::assertEquals('2001:db9::1', sslIpCom('2001:db9::1', '198.51.100.7', '2001:db8::/32'), 'IPv6 fora da faixa');
});

TestKit::run('cabeçalho com lixo não derruba nem engana: cai no IP da conexão', function () {
    TestKit::assertEquals('10.0.0.5', sslIpCom('10.0.0.5', 'não-é-ip, ???', '10.0.0.5'));
    TestKit::assertEquals('10.0.0.5', sslIpCom('10.0.0.5', '', '10.0.0.5'));
});

echo "\n--- Testando RateLimiter::zerar() ---\n";

TestKit::run('zerar() libera quem estava bloqueado', function () {
    $limite = new RateLimiter();
    $id = TESTE_MARCADOR . '_zerar_' . uniqid();

    TestKit::assertTrue($limite->permitido('teste_zerar', 1, 10, 30, $id));
    TestKit::assertFalse($limite->permitido('teste_zerar', 1, 10, 30, $id), 'deveria estourar');
    TestKit::assertTrue($limite->minutosRestantes('teste_zerar', $id) > 0);

    $limite->zerar('teste_zerar', $id);

    TestKit::assertEquals(0, $limite->minutosRestantes('teste_zerar', $id), 'não deveria mais estar bloqueado');
    TestKit::assertTrue($limite->permitido('teste_zerar', 1, 10, 30, $id), 'deveria voltar a passar');
});

TestKit::run('zerar() não mexe no contador de outra origem nem de outra ação', function () {
    $limite = new RateLimiter();
    $a = TESTE_MARCADOR . '_zerar_a_' . uniqid();
    $b = TESTE_MARCADOR . '_zerar_b_' . uniqid();

    $limite->permitido('teste_zerar', 1, 10, 30, $a);
    $limite->permitido('teste_zerar', 1, 10, 30, $a);   // a: bloqueado
    $limite->permitido('teste_zerar', 1, 10, 30, $b);
    $limite->permitido('teste_zerar', 1, 10, 30, $b);   // b: bloqueado

    $limite->zerar('teste_zerar', $a);

    TestKit::assertEquals(0, $limite->minutosRestantes('teste_zerar', $a));
    TestKit::assertTrue($limite->minutosRestantes('teste_zerar', $b) > 0, 'b deveria continuar bloqueado');
    $limite->zerar('teste_zerar', $b);
});

echo "\n--- Testando LimiteLogin (janela e identificador) ---\n";

TestKit::run('chave() trata maiúsculas e espaços como o mesmo e-mail e tem tamanho fixo', function () {
    $base = LimiteLogin::chave('pessoa@exemplo.com');

    TestKit::assertEquals($base, LimiteLogin::chave('  Pessoa@Exemplo.COM '));
    TestKit::assertTrue($base !== LimiteLogin::chave('outra@exemplo.com'));
    TestKit::assertEquals(64, strlen($base));
    TestKit::assertEquals(64, strlen(LimiteLogin::chave(str_repeat('a', 5000) . '@x.com')), 'texto enorme não pode estourar a coluna');
});

TestKit::run('variar a caixa do e-mail não dá tentativas extras', function () {
    $limite = new LimiteLogin();
    $email = TESTE_MARCADOR . '_Caixa_' . uniqid() . '@petfinder.test';
    $variacoes = [$email, strtoupper($email), strtolower($email), ' ' . $email, $email . ' '];

    foreach ($variacoes as $tentativa) {
        $limite->registrarFalha(LimiteLogin::chave($tentativa));
    }

    TestKit::assertTrue($limite->estaBloqueado(LimiteLogin::chave($email)), '5 variações da caixa devem contar como 5 erros do mesmo e-mail');
    $limite->registrarSucesso(LimiteLogin::chave($email));
});

TestKit::run('erros antigos (fora da janela) não contam para o bloqueio', function () {
    $pdo = Database::conectar();
    $limite = new LimiteLogin();
    $chave = LimiteLogin::chave(TESTE_MARCADOR . '_janela_' . uniqid() . '@petfinder.test');

    for ($i = 0; $i < 4; $i++) {
        $limite->registrarFalha($chave);
    }
    TestKit::assertEquals(1, $limite->tentativasRestantes($chave), 'após 4 erros resta 1 tentativa');

    // "Envelhece" os erros: a última tentativa foi há 20 minutos.
    $pdo->prepare("UPDATE tentativas_login SET ultima_tentativa = NOW() - INTERVAL 20 MINUTE WHERE email = :e")
        ->execute([':e' => $chave]);

    $limite->registrarFalha($chave);

    TestKit::assertFalse($limite->estaBloqueado($chave), 'o 5º erro, depois de 20 min, não pode bloquear');
    TestKit::assertEquals(4, $limite->tentativasRestantes($chave), 'a contagem recomeça do 1');
    $limite->registrarSucesso($chave);
});

TestKit::run('limparAntigas() remove só o que está velho e sem bloqueio ativo', function () {
    $pdo = Database::conectar();
    $limite = new LimiteLogin();
    $velha = LimiteLogin::chave(TESTE_MARCADOR . '_velha_' . uniqid());
    $recente = LimiteLogin::chave(TESTE_MARCADOR . '_recente_' . uniqid());

    $limite->registrarFalha($velha);
    $limite->registrarFalha($recente);
    $pdo->prepare("UPDATE tentativas_login SET ultima_tentativa = NOW() - INTERVAL 3 DAY WHERE email = :e")->execute([':e' => $velha]);

    $limite->limparAntigas(1);

    $existe = $pdo->prepare('SELECT COUNT(*) FROM tentativas_login WHERE email = :e');
    $existe->execute([':e' => $velha]);
    TestKit::assertEquals(0, (int) $existe->fetchColumn(), 'a linha velha deveria ter sido removida');
    $existe->execute([':e' => $recente]);
    TestKit::assertEquals(1, (int) $existe->fetchColumn(), 'a linha recente deveria continuar');
    $limite->registrarSucesso($recente);
});

echo "\n--- Conferindo que cada tela usa o seu limite ---\n";

TestKit::run('login.php usa limite por e-mail (LimiteLogin) e por IP (RateLimiter)', function () {
    $codigo = sslCodigoFonte('public/login.php');

    TestKit::assertTrue(str_contains($codigo, 'LimiteLogin::chave('), 'login deveria usar LimiteLogin');
    TestKit::assertTrue(str_contains($codigo, '->registrarFalha('), 'login deveria registrar as falhas');
    TestKit::assertTrue(str_contains($codigo, '->registrarSucesso('), 'login deveria limpar o histórico ao acertar');
    TestKit::assertTrue(str_contains($codigo, "->permitido('login'"), 'login deveria ter limite por IP');
});

TestKit::run('esqueci_senha.php limita por IP e por e-mail', function () {
    $codigo = sslCodigoFonte('public/conta/esqueci_senha.php');

    TestKit::assertTrue(str_contains($codigo, "->permitido('esqueci_senha_ip'"), 'faltou o limite por IP');
    TestKit::assertTrue(str_contains($codigo, "->permitido('esqueci_senha_email'"), 'faltou o limite por e-mail');
});

TestKit::run('2fa.php limita as tentativas por usuário e zera ao acertar', function () {
    $codigo = sslCodigoFonte('public/conta/2fa.php');

    TestKit::assertTrue(str_contains($codigo, "->permitido('2fa'"), 'faltou o limite de tentativas do código');
    TestKit::assertTrue(str_contains($codigo, "->zerar('2fa'"), 'deveria zerar o contador ao acertar');
    TestKit::assertTrue(str_contains($codigo, 'dois_fatores_codigo = NULL'), 'deveria descartar o código ao bloquear');
});
