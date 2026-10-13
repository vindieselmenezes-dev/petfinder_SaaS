<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Segurança complementar: tokens de redefinição, cadastro e
 * troca de senha
 * ==========================================================
 *   1. o token de redefinição não pode ficar em texto puro no banco;
 *   2. trocar a senha invalida todos os links de redefinição abertos;
 *   3. o cadastro não mostra o erro técnico do banco ao visitante e
 *      tem limite por IP;
 *   4. a troca de senha tem limite na checagem da senha atual.
 */

require_once __DIR__ . '/../app/Models/ResetSenha.php';

/** Cria um usuário temporário só para os testes e devolve o id. */
function scCriarUsuarioTemporario(): int
{
    $pdo = Database::conectar();
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nome, sobrenome, email, senha, status, tipo_usuario)
        VALUES ('Teste', 'Complementar', :email, :senha, 'ativo', 'cliente')
    ");
    $stmt->execute([
        ':email' => TESTE_MARCADOR . '_sc_' . uniqid() . '@petfinder.test',
        ':senha' => password_hash('Teste1234', PASSWORD_DEFAULT),
    ]);

    return (int) $pdo->lastInsertId();
}

function scApagarUsuario(int $id): void
{
    Database::conectar()->prepare('DELETE FROM usuarios WHERE id = :id')->execute([':id' => $id]);
}

function scCodigoFonte(string $caminhoRelativo): string
{
    $conteudo = file_get_contents(__DIR__ . '/../' . $caminhoRelativo);

    return $conteudo === false ? '' : $conteudo;
}

echo "\n--- Testando tokens de redefinição (hash e invalidação) ---\n";

TestKit::run('o banco guarda só o hash do token, nunca o token em texto puro', function () {
    $pdo = Database::conectar();
    $reset = new ResetSenha();

    $token = $reset->gerarToken($GLOBALS['TESTE_USUARIO_ID']);

    $cru = $pdo->prepare('SELECT COUNT(*) FROM reset_senha_tokens WHERE token = :t');
    $cru->execute([':t' => $token]);
    TestKit::assertEquals(0, (int) $cru->fetchColumn(), 'o token em texto puro não pode estar no banco');

    $hash = $pdo->prepare('SELECT COUNT(*) FROM reset_senha_tokens WHERE token = :t');
    $hash->execute([':t' => ResetSenha::hashDoToken($token)]);
    TestKit::assertEquals(1, (int) $hash->fetchColumn(), 'o hash do token deveria estar no banco');

    TestKit::assertEquals(64, strlen(ResetSenha::hashDoToken($token)), 'o hash tem que caber na coluna VARCHAR(64)');
    TestKit::assertNotNull($reset->validarToken($token), 'e o token verdadeiro continua valendo');
});

TestKit::run('usar o hash do banco como se fosse o token NÃO funciona', function () {
    $reset = new ResetSenha();
    $token = $reset->gerarToken($GLOBALS['TESTE_USUARIO_ID']);

    // Quem lê o banco (vazamento) só enxerga o hash: ele não pode servir de link.
    TestKit::assertNull($reset->validarToken(ResetSenha::hashDoToken($token)));
});

TestKit::run('token antigo gravado em texto puro (antes da mudança) deixa de valer', function () {
    $pdo = Database::conectar();
    $reset = new ResetSenha();
    $antigo = bin2hex(random_bytes(32));

    $pdo->prepare("INSERT INTO reset_senha_tokens (usuario_id, token, expira_em) VALUES (:u, :t, DATE_ADD(NOW(), INTERVAL 1 HOUR))")
        ->execute([':u' => $GLOBALS['TESTE_USUARIO_ID'], ':t' => $antigo]);

    TestKit::assertNull($reset->validarToken($antigo), 'link antigo não deve mais funcionar (o usuário pede outro)');
});

TestKit::run('invalidarTodosDoUsuario() derruba todos os links em aberto daquele usuário', function () {
    $reset = new ResetSenha();
    $id = $GLOBALS['TESTE_USUARIO_ID'];

    $t1 = $reset->gerarToken($id);
    $t2 = $reset->gerarToken($id);
    TestKit::assertNotNull($reset->validarToken($t1));
    TestKit::assertNotNull($reset->validarToken($t2));

    $reset->invalidarTodosDoUsuario($id);

    TestKit::assertNull($reset->validarToken($t1), 'primeiro link deveria ter sido invalidado');
    TestKit::assertNull($reset->validarToken($t2), 'segundo link deveria ter sido invalidado');
});

TestKit::run('invalidarTodosDoUsuario() não mexe nos links de OUTRO usuário', function () {
    $reset = new ResetSenha();
    $outro = scCriarUsuarioTemporario();

    try {
        $tokenOutro = $reset->gerarToken($outro);
        $tokenDele = $reset->gerarToken($GLOBALS['TESTE_USUARIO_ID']);

        $reset->invalidarTodosDoUsuario($GLOBALS['TESTE_USUARIO_ID']);

        TestKit::assertNull($reset->validarToken($tokenDele));
        TestKit::assertEquals($outro, $reset->validarToken($tokenOutro), 'o link do outro usuário deveria continuar válido');
    } finally {
        scApagarUsuario($outro);
    }
});

TestKit::run('o link de redefinição vale mesmo com o banco em outro fuso horário que o PHP', function () {
    $pdo = Database::conectar();
    $reset = new ResetSenha();
    $antes = $pdo->query('SELECT @@session.time_zone')->fetchColumn();

    try {
        // O app usa America/Sao_Paulo (UTC-3). Com o banco 12 h à frente, a validade
        // calculada no PHP já nasceria vencida; calculada no banco, continua valendo.
        foreach (['+09:00', '-11:00', '+00:00'] as $fuso) {
            $pdo->exec("SET time_zone = '{$fuso}'");
            $token = $reset->gerarToken($GLOBALS['TESTE_USUARIO_ID']);
            TestKit::assertNotNull($reset->validarToken($token), "o link deveria valer com o banco em {$fuso}");
        }
    } finally {
        $pdo->exec("SET time_zone = " . $pdo->quote((string) $antes));
    }
});

echo "\n--- Conferindo cadastro, redefinição e troca de senha ---\n";

TestKit::run('cadastro.php não mostra o erro técnico do banco ao visitante', function () {
    $codigo = scCodigoFonte('public/cadastro.php');

    TestKit::assertTrue($codigo !== '', 'cadastro.php não foi encontrado');
    TestKit::assertFalse((bool) preg_match('/\$mensagem\s*=[^;]*getMessage\(\)/', $codigo), 'a mensagem exibida não pode concatenar getMessage()');
    TestKit::assertTrue(str_contains($codigo, "error_log('Falha ao cadastrar usuário: '"), 'o detalhe deveria ir para o log do servidor');
});

TestKit::run('cadastro.php limita cadastros por IP', function () {
    $codigo = scCodigoFonte('public/cadastro.php');

    TestKit::assertTrue(str_contains($codigo, "->permitido('cadastro'"), 'faltou o limite por IP no cadastro');
    TestKit::assertTrue(str_contains($codigo, 'minutosDeBloqueioDoCadastro()'), 'o limite deveria ser aplicado no fluxo do POST');
});

TestKit::run('alterar_senha.php limita tentativas da senha atual e zera ao acertar', function () {
    $codigo = scCodigoFonte('public/conta/alterar_senha.php');

    TestKit::assertTrue(str_contains($codigo, "->permitido('alterar_senha'"), 'faltou o limite da senha atual');
    TestKit::assertTrue(str_contains($codigo, "->zerar('alterar_senha'"), 'deveria zerar o contador ao trocar a senha com sucesso');

    $posLimite = strpos($codigo, 'minutosDeBloqueioDaSenhaAtual($usuarioId)) > 0');
    $posVerify = strpos($codigo, 'password_verify(');
    TestKit::assertTrue($posLimite !== false && $posVerify !== false && $posLimite < $posVerify, 'o limite precisa vir ANTES do password_verify');
});

TestKit::run('redefinir_senha.php invalida todos os links depois de trocar a senha', function () {
    $codigo = scCodigoFonte('public/conta/redefinir_senha.php');

    TestKit::assertTrue(str_contains($codigo, 'invalidarTodosDoUsuario('), 'deveria invalidar todos os links em aberto');
});
