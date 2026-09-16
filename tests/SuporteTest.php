<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Controllers/SuporteController.php
 * ==========================================================
 */

echo "\n--- Testando Suporte (chamados) ---\n";

TestKit::run('abrirChamado() cria com status Aberto e prioridade padrão', function () {
    $controller = new SuporteController();

    $id = $controller->abrirChamado($GLOBALS['TESTE_USUARIO_ID'], 'Não consigo cadastrar meu pet', 'Descrição detalhada do problema', 'Alta');

    TestKit::assertNotNull($id);

    $chamado = $controller->buscarPorId($id);
    TestKit::assertEquals('Aberto', $chamado['status']);
    TestKit::assertEquals('Alta', $chamado['prioridade']);
    TestKit::assertEquals($GLOBALS['TESTE_USUARIO_ID'], (int) $chamado['usuario_id']);
});

TestKit::run('abrirChamado() rejeita assunto ou descrição vazios', function () {
    $controller = new SuporteController();

    $id = $controller->abrirChamado($GLOBALS['TESTE_USUARIO_ID'], '', 'Descrição sem assunto', 'Média');
    TestKit::assertFalse($id, 'não deveria abrir chamado sem assunto');

    $id2 = $controller->abrirChamado($GLOBALS['TESTE_USUARIO_ID'], 'Assunto sem descrição', '', 'Média');
    TestKit::assertFalse($id2, 'não deveria abrir chamado sem descrição');
});

TestKit::run('abrirChamado() cai pra prioridade Média se vier um valor inválido', function () {
    $controller = new SuporteController();

    $id = $controller->abrirChamado($GLOBALS['TESTE_USUARIO_ID'], 'Assunto teste', 'Descrição teste', 'PrioridadeQueNaoExiste');
    $chamado = $controller->buscarPorId($id);

    TestKit::assertEquals('Média', $chamado['prioridade']);
});

TestKit::run('responder() adiciona resposta e listarRespostas() retorna em ordem', function () {
    $controller = new SuporteController();

    $id = $controller->abrirChamado($GLOBALS['TESTE_USUARIO_ID'], 'Chamado com respostas', 'Descrição', 'Baixa');

    $controller->responder($id, $GLOBALS['TESTE_USUARIO_ID'], 'Primeira mensagem');
    $controller->responder($id, $GLOBALS['TESTE_USUARIO2_ID'], 'Segunda mensagem (resposta)');

    $respostas = $controller->listarRespostas($id);

    TestKit::assertEquals(2, count($respostas));
    TestKit::assertEquals('Primeira mensagem', $respostas[0]['resposta'], 'a mais antiga deveria vir primeiro');
    TestKit::assertEquals('Segunda mensagem (resposta)', $respostas[1]['resposta']);
});

TestKit::run('responder() não aceita resposta vazia', function () {
    $controller = new SuporteController();

    $id = $controller->abrirChamado($GLOBALS['TESTE_USUARIO_ID'], 'Chamado resposta vazia', 'Descrição', 'Baixa');

    $ok = $controller->responder($id, $GLOBALS['TESTE_USUARIO_ID'], '   ');
    TestKit::assertFalse($ok, 'não deveria aceitar resposta vazia/só espaços');
});

TestKit::run('atualizarStatus() só aceita valores válidos', function () {
    $controller = new SuporteController();

    $id = $controller->abrirChamado($GLOBALS['TESTE_USUARIO_ID'], 'Chamado de status', 'Descrição', 'Baixa');

    $ok = $controller->atualizarStatus($id, 'StatusInventado');
    TestKit::assertFalse($ok, 'não deveria aceitar um status fora da lista válida');

    $ok2 = $controller->atualizarStatus($id, 'Resolvido');
    TestKit::assertTrue($ok2);

    $chamado = $controller->buscarPorId($id);
    TestKit::assertEquals('Resolvido', $chamado['status']);
});

TestKit::run('listarPorUsuario() só traz os chamados daquele usuário', function () {
    $controller = new SuporteController();

    $controller->abrirChamado($GLOBALS['TESTE_USUARIO_ID'], 'Chamado exclusivo do usuário 1', 'Descrição', 'Baixa');

    $listaUsuario1 = $controller->listarPorUsuario($GLOBALS['TESTE_USUARIO_ID']);
    $listaUsuario2 = $controller->listarPorUsuario($GLOBALS['TESTE_USUARIO2_ID']);

    $assuntosUsuario2 = array_column($listaUsuario2, 'assunto');

    TestKit::assertTrue(count($listaUsuario1) > 0, 'usuário 1 deveria ter pelo menos 1 chamado');
    TestKit::assertFalse(in_array('Chamado exclusivo do usuário 1', $assuntosUsuario2, true), 'chamado do usuário 1 não deveria aparecer pro usuário 2');
});
