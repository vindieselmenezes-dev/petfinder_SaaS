<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/RateLimiter.php
 * ==========================================================
 * Usa identificadores fictícios (não IPs reais) só pra isolar cada
 * teste — o RateLimiter não faz ideia se "identificador" é um IP de
 * verdade ou não, então isso é seguro.
 */

echo "\n--- Testando RateLimiter.php ---\n";

TestKit::run('permitido() libera dentro do limite e bloqueia ao estourar', function () {

    $limite = new RateLimiter();
    $id = TESTE_MARCADOR . '_rl_' . uniqid();

    // 3 por janela de 10 min, bloqueia por 30 min ao estourar
    TestKit::assertTrue($limite->permitido('teste_acao', 3, 10, 30, $id), '1ª tentativa deveria passar');
    TestKit::assertTrue($limite->permitido('teste_acao', 3, 10, 30, $id), '2ª tentativa deveria passar');
    TestKit::assertTrue($limite->permitido('teste_acao', 3, 10, 30, $id), '3ª tentativa deveria passar (no limite)');
    TestKit::assertFalse($limite->permitido('teste_acao', 3, 10, 30, $id), '4ª tentativa deveria estourar o limite');

    // uma vez bloqueado, continua bloqueado mesmo chamando de novo
    TestKit::assertFalse($limite->permitido('teste_acao', 3, 10, 30, $id), 'deveria continuar bloqueado');
    TestKit::assertTrue($limite->minutosRestantes('teste_acao', $id) > 0, 'deveria ter minutos restantes de bloqueio');
});

TestKit::run('ações diferentes têm contadores independentes para o mesmo identificador', function () {

    $limite = new RateLimiter();
    $id = TESTE_MARCADOR . '_rl_' . uniqid();

    TestKit::assertTrue($limite->permitido('acao_a', 1, 10, 30, $id), 'acao_a deveria passar na 1ª vez');
    TestKit::assertFalse($limite->permitido('acao_a', 1, 10, 30, $id), 'acao_a deveria estourar na 2ª vez');

    // acao_b nunca foi chamada com esse identificador — não deveria
    // estar bloqueada só porque acao_a está.
    TestKit::assertTrue($limite->permitido('acao_b', 1, 10, 30, $id), 'acao_b deveria ser independente de acao_a');
});

TestKit::run('minutosRestantes() é 0 quando não há bloqueio ativo', function () {

    $limite = new RateLimiter();
    $id = TESTE_MARCADOR . '_rl_' . uniqid();

    TestKit::assertEquals(0, $limite->minutosRestantes('acao_qualquer', $id), 'sem nenhuma tentativa, não deveria ter bloqueio');

    $limite->permitido('acao_qualquer', 5, 10, 30, $id);
    TestKit::assertEquals(0, $limite->minutosRestantes('acao_qualquer', $id), 'dentro do limite, não deveria ter bloqueio');
});

TestKit::run('janela expirada reinicia a contagem em vez de manter o bloqueio', function () {

    $limite = new RateLimiter();
    $id = TESTE_MARCADOR . '_rl_' . uniqid();
    $pdo = Database::conectar();

    // estoura o limite (1 por janela)
    TestKit::assertTrue($limite->permitido('acao_janela', 1, 10, 30, $id));
    TestKit::assertFalse($limite->permitido('acao_janela', 1, 10, 30, $id));

    // simula o bloqueio já ter vencido (bloqueado_ate no passado) e a
    // janela também — como se os 30 minutos de bloqueio já tivessem
    // passado faz tempo.
    $pdo->prepare("
        UPDATE limite_requisicoes
           SET janela_inicio = DATE_SUB(NOW(), INTERVAL 1 HOUR),
               bloqueado_ate = DATE_SUB(NOW(), INTERVAL 30 MINUTE)
         WHERE acao = 'acao_janela' AND identificador = :id
    ")->execute([':id' => $id]);

    TestKit::assertTrue(
        $limite->permitido('acao_janela', 1, 10, 30, $id),
        'depois que a janela (e o bloqueio) vencem, deveria liberar de novo'
    );
});

TestKit::run('limparAntigas() remove só o que já venceu há dias, mantém o resto', function () {

    $limite = new RateLimiter();
    $pdo = Database::conectar();
    $idAntigo = TESTE_MARCADOR . '_rl_antigo_' . uniqid();
    $idRecente = TESTE_MARCADOR . '_rl_recente_' . uniqid();

    // uma linha "velha": janela de 3 dias atrás, sem bloqueio ativo
    $limite->permitido('acao_limpeza', 5, 10, 30, $idAntigo);
    $pdo->prepare("
        UPDATE limite_requisicoes SET janela_inicio = DATE_SUB(NOW(), INTERVAL 3 DAY), bloqueado_ate = NULL
         WHERE acao = 'acao_limpeza' AND identificador = :id
    ")->execute([':id' => $idAntigo]);

    // uma linha recente, que não deveria ser tocada
    $limite->permitido('acao_limpeza', 5, 10, 30, $idRecente);

    $limite->limparAntigas(2); // considera "obsoleto" o que passou de 2 dias

    $stmt = $pdo->prepare("SELECT identificador FROM limite_requisicoes WHERE acao = 'acao_limpeza'");
    $stmt->execute();
    $restantes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    TestKit::assertFalse(in_array($idAntigo, $restantes, true), 'linha com mais de 2 dias deveria ter sido removida');
    TestKit::assertTrue(in_array($idRecente, $restantes, true), 'linha recente não deveria ser removida');
});

// --- limpeza: remove tudo que os testes acima criaram -----------------------
$pdoLimpezaRateLimiter = Database::conectar();
$pdoLimpezaRateLimiter->prepare("DELETE FROM limite_requisicoes WHERE identificador LIKE :marcador")
    ->execute([':marcador' => TESTE_MARCADOR . '%']);
