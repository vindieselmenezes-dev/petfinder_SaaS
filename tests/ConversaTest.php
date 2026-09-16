<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Controllers/ConversaController.php
 * ==========================================================
 */

echo "\n--- Testando Conversas e Mensagens ---\n";

TestKit::run('criar conversa e enviar mensagem inicial', function () {
    $conversa = new Conversa();
    $mensagem = new Mensagem();

    $conversaId = $conversa->criar($GLOBALS['TESTE_USUARIO_ID'], $GLOBALS['TESTE_USUARIO2_ID'], 'Assunto de teste');
    $mensagem->enviar($conversaId, $GLOBALS['TESTE_USUARIO_ID'], 'Olá, tudo bem?');

    $mensagens = $mensagem->listarPorConversa($conversaId);

    TestKit::assertEquals(1, count($mensagens));
    TestKit::assertEquals('Olá, tudo bem?', $mensagens[0]['mensagem']);
});

TestKit::run('conversa aparece pra ambos os participantes (origem e destino)', function () {
    $conversa = new Conversa();

    $conversaId = $conversa->criar($GLOBALS['TESTE_USUARIO_ID'], $GLOBALS['TESTE_USUARIO2_ID'], 'Assunto visível pros dois');

    $doOrigem = $conversa->buscarPorId($conversaId, $GLOBALS['TESTE_USUARIO_ID']);
    $doDestino = $conversa->buscarPorId($conversaId, $GLOBALS['TESTE_USUARIO2_ID']);

    TestKit::assertNotNull($doOrigem, 'quem iniciou a conversa deveria conseguir vê-la');
    TestKit::assertNotNull($doDestino, 'quem recebeu a conversa também deveria conseguir vê-la');
});

TestKit::run('pessoa de fora não consegue ver a conversa', function () {
    $conversa = new Conversa();

    $conversaId = $conversa->criar($GLOBALS['TESTE_USUARIO_ID'], $GLOBALS['TESTE_USUARIO2_ID'], 'Assunto privado');

    // Usa um ID que certamente não é participante (0 nunca é um usuário real)
    $deFora = $conversa->buscarPorId($conversaId, 0);

    TestKit::assertNull($deFora, 'quem não participa da conversa não deveria conseguir ver ela');
});

TestKit::run('ConversaController::enviarMensagem() notifica e bloqueia mensagem vazia', function () {
    $conversa = new Conversa();
    $controller = new ConversaController();

    $conversaId = $conversa->criar($GLOBALS['TESTE_USUARIO_ID'], $GLOBALS['TESTE_USUARIO2_ID'], 'Assunto com controller');

    $vazia = $controller->enviarMensagem($conversaId, $GLOBALS['TESTE_USUARIO_ID'], '   ');
    TestKit::assertFalse($vazia['sucesso'], 'não deveria aceitar mensagem vazia/só espaços');

    $comTexto = $controller->enviarMensagem($conversaId, $GLOBALS['TESTE_USUARIO_ID'], 'Mensagem de verdade');
    TestKit::assertTrue($comTexto['sucesso'], $comTexto['erro'] ?? '');

    $notificacaoController = new NotificacaoController();
    $notificacoes = $notificacaoController->listarPorUsuario($GLOBALS['TESTE_USUARIO2_ID']);
    $temNotificacaoDeMensagem = false;
    foreach ($notificacoes as $n) {
        if (str_contains($n['titulo'], 'mensagem')) {
            $temNotificacaoDeMensagem = true;
            break;
        }
    }
    TestKit::assertTrue($temNotificacaoDeMensagem, 'o destinatário deveria ter recebido uma notificação de nova mensagem');
});

TestKit::run('marcarComoLidas() só marca as mensagens do outro participante', function () {
    $conversa = new Conversa();
    $mensagem = new Mensagem();

    $conversaId = $conversa->criar($GLOBALS['TESTE_USUARIO_ID'], $GLOBALS['TESTE_USUARIO2_ID'], 'Assunto leitura');
    $mensagem->enviar($conversaId, $GLOBALS['TESTE_USUARIO_ID'], 'Mensagem do usuário 1');
    $mensagem->enviar($conversaId, $GLOBALS['TESTE_USUARIO2_ID'], 'Mensagem do usuário 2');

    // Usuário 1 abre a conversa e "lê" as mensagens que não são dele
    $mensagem->marcarComoLidas($conversaId, $GLOBALS['TESTE_USUARIO_ID']);

    $mensagens = $mensagem->listarPorConversa($conversaId);

    foreach ($mensagens as $m) {
        if ((int) $m['remetente_id'] === $GLOBALS['TESTE_USUARIO_ID']) {
            TestKit::assertEquals(0, (int) $m['lida'], 'a própria mensagem de quem abriu não deveria mudar de "lida"');
        } else {
            TestKit::assertEquals(1, (int) $m['lida'], 'a mensagem do outro participante deveria ter sido marcada como lida');
        }
    }
});
