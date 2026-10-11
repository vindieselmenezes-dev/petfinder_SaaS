<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Modo suporte: o administrador entra numa empresa e volta
 * ==========================================================
 * Garante que o administrador consegue voltar a ser administrador sem
 * fazer login de novo, e que ninguém além de um administrador consegue
 * entrar (nem "voltar") para um tipo que não tinha.
 */

require_once __DIR__ . '/../app/Core/Auth.php';

/** Executa $teste com uma sessão simulada e restaura a sessão original depois. */
function comSessaoSimulada(array $sessao, callable $teste): void
{
    $original = $_SESSION ?? [];
    $_SESSION = $sessao;
    try {
        $teste();
    } finally {
        $_SESSION = $original;
    }
}

$sessaoAdmin = ['usuario_id' => 58, 'usuario_nome' => 'Admin', 'usuario_email' => 'a@teste.invalid', 'perfil_tipo' => 'administrador'];

echo "\n--- Testando o modo suporte (entrar na empresa e voltar) ---\n";

TestKit::run('administrador entra no modo suporte: vira "empresa" e guarda quem era', function () use ($sessaoAdmin) {
    comSessaoSimulada($sessaoAdmin, function () {
        TestKit::assertTrue(Auth::iniciarModoSuporte(7, 'Pet Shop X'), 'administrador deveria conseguir entrar');
        TestKit::assertTrue(Auth::emModoSuporte());
        TestKit::assertEquals('empresa', Auth::tipo());
        TestKit::assertFalse(Auth::ehAdministrador(), 'dentro da empresa, o menu e as páginas são os da empresa');
        TestKit::assertEquals(7, (int) $_SESSION['impersonated_empresa_id']);
        TestKit::assertEquals('Pet Shop X', Auth::nomeEmpresaSuporte());
    });
});

TestKit::run('encerrarModoSuporte() devolve o tipo de administrador sem novo login', function () use ($sessaoAdmin) {
    comSessaoSimulada($sessaoAdmin, function () {
        Auth::iniciarModoSuporte(7, 'Pet Shop X');

        TestKit::assertTrue(Auth::encerrarModoSuporte(), 'deveria sair do modo suporte');
        TestKit::assertEquals('administrador', Auth::tipo(), 'o tipo de administrador precisa voltar');
        TestKit::assertTrue(Auth::ehAdministrador());
        TestKit::assertFalse(Auth::emModoSuporte());
        TestKit::assertEquals(58, Auth::id(), 'continua sendo o mesmo usuário');
        foreach (['is_impersonating', 'impersonated_by', 'impersonated_empresa_id', 'impersonated_empresa_nome', 'perfil_tipo_original'] as $chave) {
            TestKit::assertFalse(array_key_exists($chave, $_SESSION), "a marca '{$chave}' deveria ter sido limpa");
        }
    });
});

TestKit::run('depois de voltar, o administrador pode entrar em outra empresa', function () use ($sessaoAdmin) {
    comSessaoSimulada($sessaoAdmin, function () {
        Auth::iniciarModoSuporte(7, 'Empresa A');
        Auth::encerrarModoSuporte();

        TestKit::assertTrue(Auth::iniciarModoSuporte(9, 'Empresa B'));
        TestKit::assertEquals(9, (int) $_SESSION['impersonated_empresa_id']);
        TestKit::assertTrue(Auth::encerrarModoSuporte());
        TestKit::assertEquals('administrador', Auth::tipo());
    });
});

TestKit::run('quem não é administrador não consegue entrar no modo suporte', function () {
    foreach (['cliente', 'empresa', 'veterinario'] as $tipo) {
        comSessaoSimulada(['usuario_id' => 10, 'perfil_tipo' => $tipo], function () use ($tipo) {
            TestKit::assertFalse(Auth::iniciarModoSuporte(7, 'X'), "o tipo '{$tipo}' não pode iniciar o modo suporte");
            TestKit::assertEquals($tipo, Auth::tipo(), 'o tipo não pode mudar');
            TestKit::assertFalse(Auth::emModoSuporte());
        });
    }
    comSessaoSimulada([], function () {
        TestKit::assertFalse(Auth::iniciarModoSuporte(7, 'X'), 'sem login também não');
    });
});

TestKit::run('encerrarModoSuporte() NUNCA promove quem não estava em modo suporte', function () {
    comSessaoSimulada(['usuario_id' => 10, 'perfil_tipo' => 'cliente'], function () {
        TestKit::assertFalse(Auth::encerrarModoSuporte());
        TestKit::assertEquals('cliente', Auth::tipo());
    });
});

TestKit::run('marcas de suporte forjadas por outro usuário não devolvem o tipo de administrador', function () {
    // Sessão de um usuário comum com marcas copiadas de uma sessão de suporte alheia
    comSessaoSimulada([
        'usuario_id' => 10, 'perfil_tipo' => 'empresa',
        'is_impersonating' => true, 'impersonated_by' => 58,          // quem iniciou foi OUTRO usuário
        'perfil_tipo_original' => 'administrador', 'impersonated_empresa_id' => 7,
    ], function () {
        TestKit::assertFalse(Auth::emModoSuporte(), 'as marcas pertencem a outro usuário');
        TestKit::assertFalse(Auth::encerrarModoSuporte());
        TestKit::assertEquals('empresa', Auth::tipo(), 'não pode ganhar o tipo de administrador');
    });

    // Marca de "tipo original" que não é administrador também não vale
    comSessaoSimulada([
        'usuario_id' => 10, 'perfil_tipo' => 'empresa',
        'is_impersonating' => true, 'impersonated_by' => 10, 'perfil_tipo_original' => 'cliente',
    ], function () {
        TestKit::assertFalse(Auth::encerrarModoSuporte());
        TestKit::assertEquals('empresa', Auth::tipo());
    });
});

TestKit::run('não é possível entrar no modo suporte duas vezes (o tipo original não é sobrescrito)', function () use ($sessaoAdmin) {
    comSessaoSimulada($sessaoAdmin, function () {
        Auth::iniciarModoSuporte(7, 'Empresa A');
        TestKit::assertFalse(Auth::iniciarModoSuporte(9, 'Empresa B'), 'já está em modo suporte');
        TestKit::assertEquals(7, (int) $_SESSION['impersonated_empresa_id'], 'continua na primeira empresa');
        TestKit::assertTrue(Auth::encerrarModoSuporte());
        TestKit::assertEquals('administrador', Auth::tipo());
    });
});

echo "\n--- Testando a trilha de auditoria do modo suporte (banco) ---\n";

TestKit::run('auditoria.acao aceita IMPERSONATE e IMPERSONATE_FIM (migration 028)', function () {
    $pdo = Database::conectar();
    $detalhe = TESTE_MARCADOR . '_auditoria_suporte';

    foreach (['IMPERSONATE', 'IMPERSONATE_FIM'] as $acao) {
        $pdo->prepare("INSERT INTO auditoria (usuario_id, tabela, acao, registro_id, detalhes) VALUES (?, 'empresas', ?, 1, ?)")
            ->execute([$GLOBALS['TESTE_USUARIO_ID'], $acao, $detalhe]);
    }

    $stmt = $pdo->prepare('SELECT acao FROM auditoria WHERE detalhes = ? ORDER BY id');
    $stmt->execute([$detalhe]);
    $gravadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $pdo->prepare('DELETE FROM auditoria WHERE detalhes = ?')->execute([$detalhe]);

    TestKit::assertEquals(['IMPERSONATE', 'IMPERSONATE_FIM'], $gravadas, 'as duas ações precisam ser gravadas com o nome certo (aplique a migration 028)');
});
