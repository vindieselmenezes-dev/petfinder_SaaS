<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/Plano.php, Empresa.php (plano_id) e
 * EmpresaController::atualizarPlano()
 * ==========================================================
 * Empresas criadas aqui são limpas automaticamente pela rotina de
 * limpeza do run_all.php: ao deletar os usuários de teste no final,
 * fk_empresa_usuario (ON DELETE CASCADE) já apaga a empresa junto.
 */

echo "\n--- Testando Plano.php (planos de assinatura) ---\n";

TestKit::run('listarAtivos() encontra os 3 planos semeados pela migration', function () {
    $controller = new PlanoController();
    $planos = $controller->listarAtivos();

    TestKit::assertTrue(count($planos) >= 3, 'deveria haver ao menos os 3 planos padrão (Grátis, Profissional, Destaque)');
});

TestKit::run('buscarPlanoGratis() retorna o plano gratuito', function () {
    $controller = new PlanoController();
    $gratis = $controller->buscarPlanoGratis();

    TestKit::assertNotNull($gratis, 'deveria encontrar o plano Grátis');
    TestKit::assertEquals('gratis', $gratis['slug']);
    TestKit::assertEquals(0.0, (float) $gratis['preco_mensal']);
});

TestKit::run('cadastrar empresa sem informar plano_id cai automaticamente no plano Grátis', function () {
    $empresaController = new EmpresaController();

    $novoId = $empresaController->cadastrar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO_ID'],
        'categoria_id' => 1,
        'nome_fantasia' => TESTE_MARCADOR . '_EmpresaSemPlano',
        'cidade' => TESTE_MARCADOR . '_CidadePlano',
        'estado' => 'MG',
    ]);

    TestKit::assertTrue(is_int($novoId) && $novoId > 0, 'empresa deveria ser criada mesmo sem informar plano_id');
    $GLOBALS['TESTE_EMPRESA_PLANO_ID'] = $novoId;

    $empresa = $empresaController->buscarPorId($novoId);
    TestKit::assertEquals('gratis', $empresa['plano_slug'], 'empresa sem plano_id explícito deveria cair no plano Grátis');
});

TestKit::run('cadastrar empresa informando um plano_id explícito usa esse plano (escolha no cadastro)', function () {
    $empresaController = new EmpresaController();
    $planoController = new PlanoController();

    $planoProfissional = $planoController->buscarPorSlug('profissional');
    TestKit::assertNotNull($planoProfissional, 'plano Profissional deveria existir');

    $novoId = $empresaController->cadastrar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO2_ID'],
        'categoria_id' => 1,
        'nome_fantasia' => TESTE_MARCADOR . '_EmpresaComPlanoEscolhido',
        'cidade' => TESTE_MARCADOR . '_CidadeEscolhaPlano',
        'estado' => 'MG',
        'plano_id' => (int) $planoProfissional['id'],
    ]);

    TestKit::assertTrue(is_int($novoId) && $novoId > 0, 'empresa deveria ser criada com plano explícito');
    $GLOBALS['TESTE_EMPRESA_PLANO_ESCOLHIDO_ID'] = $novoId;

    $empresa = $empresaController->buscarPorId($novoId);
    TestKit::assertEquals('profissional', $empresa['plano_slug'], 'empresa deveria estar no plano escolhido no cadastro, não no Grátis padrão');
});

echo "\n--- Testando período de teste com prazo (empresas) ---\n";

TestKit::run('cadastro novo já sai com plano_expira_em no futuro (30 dias do Grátis)', function () {
    $empresaController = new EmpresaController();
    $empresa = $empresaController->buscarPorId($GLOBALS['TESTE_EMPRESA_PLANO_ID']);

    TestKit::assertNotNull($empresa['plano_expira_em'], 'empresa nova deveria ter uma data de expiração calculada, mesmo no Grátis');
    TestKit::assertTrue(strtotime($empresa['plano_expira_em']) > time(), 'a expiração deveria estar no futuro logo após o cadastro');

    TestKit::assertFalse($empresaController->trialVencido($GLOBALS['TESTE_EMPRESA_PLANO_ID']), 'trial recém-criado não deveria estar vencido');
});

TestKit::run('trial vencido: empresa some de listarAtivas() e trialVencido() vira true', function () {
    $pdoTrial = Database::conectar();
    $empresaController = new EmpresaController();
    $empresaId = $GLOBALS['TESTE_EMPRESA_PLANO_ID'];

    // Força a expiração pra ontem (não dá pra "viajar no tempo" de outro jeito)
    $pdoTrial->prepare("UPDATE empresas SET plano_expira_em = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE id = :id")
        ->execute([':id' => $empresaId]);

    TestKit::assertTrue($empresaController->trialVencido($empresaId), 'trialVencido() deveria ser true depois de forçar a data pro passado');

    $lista = $empresaController->listarAtivas(1, TESTE_MARCADOR . '_CidadePlano');
    $idsEncontrados = array_map(fn ($e) => (int) $e['id'], $lista);
    TestKit::assertFalse(in_array($empresaId, $idsEncontrados, true), 'empresa com trial vencido não deveria aparecer na busca pública');
});

TestKit::run('atualizarPlano() pra um plano pago tira a empresa do trial vencido (renovação de verdade)', function () {
    $empresaController = new EmpresaController();
    $planoController = new PlanoController();
    $empresaId = $GLOBALS['TESTE_EMPRESA_PLANO_ID'];

    $planoProfissional = $planoController->buscarPorSlug('profissional');
    $empresaController->atualizarPlano($empresaId, (int) $planoProfissional['id']);

    TestKit::assertFalse($empresaController->trialVencido($empresaId), 'depois de assinar um plano pago, o trial vencido deveria ser resolvido');

    $lista = $empresaController->listarAtivas(1, TESTE_MARCADOR . '_CidadePlano');
    $idsEncontrados = array_map(fn ($e) => (int) $e['id'], $lista);
    TestKit::assertTrue(in_array($empresaId, $idsEncontrados, true), 'empresa deveria voltar a aparecer na busca pública depois de assinar um plano');
});

TestKit::run('atualizarPlano() troca o plano e recalcula a data de expiração', function () {
    $empresaController = new EmpresaController();
    $planoController = new PlanoController();

    $planoDestaque = $planoController->buscarPorSlug('destaque');
    TestKit::assertNotNull($planoDestaque, 'plano Destaque deveria existir');

    $ok = $empresaController->atualizarPlano($GLOBALS['TESTE_EMPRESA_PLANO_ID'], (int) $planoDestaque['id']);
    TestKit::assertTrue($ok, 'atualizarPlano() deveria ter sucesso');

    $empresa = $empresaController->buscarPorId($GLOBALS['TESTE_EMPRESA_PLANO_ID']);
    TestKit::assertEquals('destaque', $empresa['plano_slug']);
    TestKit::assertTrue((bool) $empresa['plano_destaque'], 'plano_destaque deveria ser 1 depois da troca');
    TestKit::assertNotNull($empresa['plano_expira_em'], 'deveria ter calculado uma data de expiração (trial)');
});

TestKit::run('listarAtivas() prioriza empresas com plano em destaque', function () {
    $empresaController = new EmpresaController();

    // Segunda empresa, no plano Grátis, mesma cidade da empresa em destaque
    $segundoId = $empresaController->cadastrar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO2_ID'],
        'categoria_id' => 1,
        'nome_fantasia' => TESTE_MARCADOR . '_EmpresaGratis',
        'cidade' => TESTE_MARCADOR . '_CidadePlano',
        'estado' => 'MG',
    ]);
    TestKit::assertTrue(is_int($segundoId) && $segundoId > 0);
    $GLOBALS['TESTE_EMPRESA_GRATIS_ID'] = $segundoId;

    $lista = $empresaController->listarAtivas(1, TESTE_MARCADOR . '_CidadePlano');
    TestKit::assertTrue(count($lista) >= 2, 'deveria encontrar as 2 empresas de teste nessa cidade');

    // A empresa com plano Destaque deve vir antes da empresa Grátis
    TestKit::assertEquals((int) $GLOBALS['TESTE_EMPRESA_PLANO_ID'], (int) $lista[0]['id'], 'empresa com plano Destaque deveria aparecer primeiro na listagem');
});

echo "\n--- Testando limite de produtos por plano ---\n";

TestKit::run('empresa no plano Grátis consegue cadastrar até o limite (5 produtos)', function () {
    $produtoController = new ProdutoController();
    $empresaId = $GLOBALS['TESTE_EMPRESA_GRATIS_ID'];

    for ($i = 1; $i <= 5; $i++) {
        $antesLimite = $produtoController->limiteAtingido($empresaId);
        TestKit::assertFalse($antesLimite, "limite não deveria estar atingido antes do produto #{$i}");

        $novoId = $produtoController->cadastrar([
            'empresa_id' => $empresaId,
            'nome' => TESTE_MARCADOR . "_Produto{$i}",
            'preco_venda' => '10.00',
        ]);

        TestKit::assertTrue(is_int($novoId) && $novoId > 0, "produto #{$i} deveria ser cadastrado normalmente (dentro do limite Grátis)");
    }

    TestKit::assertEquals(5, $produtoController->contarProdutosPorEmpresa($empresaId), 'deveria ter exatamente 5 produtos cadastrados');
});

TestKit::run('empresa no plano Grátis é bloqueada no 6º produto', function () {
    $produtoController = new ProdutoController();
    $empresaId = $GLOBALS['TESTE_EMPRESA_GRATIS_ID'];

    TestKit::assertTrue($produtoController->limiteAtingido($empresaId), 'limite deveria estar atingido depois de 5 produtos no plano Grátis');

    $resultado = $produtoController->cadastrar([
        'empresa_id' => $empresaId,
        'nome' => TESTE_MARCADOR . '_ProdutoBloqueado',
        'preco_venda' => '10.00',
    ]);

    TestKit::assertFalse($resultado, 'cadastro do 6º produto deveria ser bloqueado pelo limite do plano Grátis');
    TestKit::assertEquals(5, $produtoController->contarProdutosPorEmpresa($empresaId), 'total de produtos não deveria ter aumentado');
});

TestKit::run('fazer upgrade pro plano Destaque libera o cadastro (limite ilimitado)', function () {
    $empresaController = new EmpresaController();
    $planoController = new PlanoController();
    $produtoController = new ProdutoController();
    $empresaId = $GLOBALS['TESTE_EMPRESA_GRATIS_ID'];

    $planoDestaque = $planoController->buscarPorSlug('destaque');
    $empresaController->atualizarPlano($empresaId, (int) $planoDestaque['id']);

    TestKit::assertFalse($produtoController->limiteAtingido($empresaId), 'depois do upgrade pra Destaque (limite ilimitado), não deveria estar mais bloqueado');

    $novoId = $produtoController->cadastrar([
        'empresa_id' => $empresaId,
        'nome' => TESTE_MARCADOR . '_ProdutoPosUpgrade',
        'preco_venda' => '10.00',
    ]);

    TestKit::assertTrue(is_int($novoId) && $novoId > 0, 'produto além do antigo limite deveria ser aceito depois do upgrade de plano');
});

TestKit::run('limpeza: remove os produtos de teste (produtos.empresa_id não tem FK/cascade)', function () {
    $pdoLimpezaProdutos = Database::conectar();

    $stmt = $pdoLimpezaProdutos->prepare("DELETE FROM produtos WHERE empresa_id = :empresa_id");
    $stmt->execute([':empresa_id' => $GLOBALS['TESTE_EMPRESA_GRATIS_ID']]);

    $verifica = $pdoLimpezaProdutos->prepare("SELECT COUNT(*) FROM produtos WHERE empresa_id = :empresa_id");
    $verifica->execute([':empresa_id' => $GLOBALS['TESTE_EMPRESA_GRATIS_ID']]);

    TestKit::assertEquals(0, (int) $verifica->fetchColumn(), 'produtos de teste deveriam ter sido removidos');
});
