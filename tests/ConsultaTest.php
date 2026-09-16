<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/Consulta.php e ConsultaController.php
 * Módulo novo: Agendamento de Consulta Veterinária
 * (fusão com projetointegrador: clinica.html / consultaveterinaria.html)
 * ==========================================================
 *
 * Diferente dos outros testes, este cria seus próprios dados de apoio
 * (empresa, veterinário, vínculo na equipe) e limpa tudo sozinho no final,
 * porque consultas tem uma FK RESTRICT em usuario_id/veterinario_id que
 * a limpeza genérica do run_all.php não cobre.
 */

echo "\n--- Testando Consulta.php (agendamento veterinário) ---\n";

$pdoConsultaTeste = Database::conectar();

TestKit::run('monta o cenário: empresa (clínica) + veterinário na equipe', function () use ($pdoConsultaTeste) {
    // Empresa de teste, categoria 2 = Clínica Veterinária
    $pdoConsultaTeste->prepare("
        INSERT INTO empresas (usuario_id, categoria_id, nome_fantasia, cidade, estado)
        VALUES (:usuario_id, 2, :nome, 'Cidade Teste', 'MG')
    ")->execute([
        ':usuario_id' => $GLOBALS['TESTE_USUARIO_ID'],
        ':nome' => TESTE_MARCADOR . '_ClinicaTeste',
    ]);
    $empresaId = (int) $pdoConsultaTeste->lastInsertId();
    TestKit::assertTrue($empresaId > 0, 'empresa de teste deveria ser criada');
    $GLOBALS['TESTE_EMPRESA_ID'] = $empresaId;

    // TESTE_USUARIO2_ID vira o veterinário dessa clínica
    $pdoConsultaTeste->prepare("
        INSERT INTO veterinarios (usuario_id, crmv, ativo)
        VALUES (:usuario_id, :crmv, 1)
    ")->execute([
        ':usuario_id' => $GLOBALS['TESTE_USUARIO2_ID'],
        ':crmv' => 'TESTE-12345',
    ]);
    $veterinarioId = (int) $pdoConsultaTeste->lastInsertId();
    TestKit::assertTrue($veterinarioId > 0, 'registro de veterinário deveria ser criado');
    $GLOBALS['TESTE_VETERINARIO_ID'] = $veterinarioId;

    $pdoConsultaTeste->prepare("
        INSERT INTO empresa_equipe (empresa_id, usuario_id, papel, status)
        VALUES (:empresa_id, :usuario_id, 'veterinario', 'ativo')
    ")->execute([
        ':empresa_id' => $empresaId,
        ':usuario_id' => $GLOBALS['TESTE_USUARIO2_ID'],
    ]);
});

TestKit::run('listarVeterinariosDaEmpresa() encontra o veterinário vinculado', function () {
    $controller = new ConsultaController();
    $veterinarios = $controller->listarVeterinariosDaEmpresa($GLOBALS['TESTE_EMPRESA_ID']);

    TestKit::assertTrue(count($veterinarios) === 1, 'deveria encontrar exatamente 1 veterinário na equipe da clínica de teste');
    TestKit::assertEquals($GLOBALS['TESTE_VETERINARIO_ID'], (int) $veterinarios[0]['veterinario_id']);
});

TestKit::run('agendar() cria a consulta com status Agendada e notifica o veterinário', function () {
    $controller = new ConsultaController();
    $notificacaoController = new NotificacaoController();

    $notificacoesAntes = $notificacaoController->contarNaoLidas($GLOBALS['TESTE_USUARIO2_ID']);

    $consultaId = $controller->agendar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO_ID'],
        'veterinario_id' => $GLOBALS['TESTE_VETERINARIO_ID'],
        'empresa_id' => $GLOBALS['TESTE_EMPRESA_ID'],
        'pet_id' => null,
        'data_consulta' => date('Y-m-d', strtotime('+2 days')),
        'hora_consulta' => '14:30',
        'motivo' => TESTE_MARCADOR . '_motivo_consulta',
    ]);

    TestKit::assertTrue(is_int($consultaId) && $consultaId > 0, 'agendar() deveria retornar um ID válido');
    $GLOBALS['TESTE_CONSULTA_ID'] = $consultaId;

    $consulta = $controller->buscarPorId($consultaId);
    TestKit::assertNotNull($consulta, 'consulta deveria ser encontrada logo após o agendamento');
    TestKit::assertEquals('Agendada', $consulta['status']);

    $notificacoesDepois = $notificacaoController->contarNaoLidas($GLOBALS['TESTE_USUARIO2_ID']);
    TestKit::assertTrue($notificacoesDepois > $notificacoesAntes, 'veterinário deveria ter recebido notificação da nova consulta agendada');
});

TestKit::run('agendar() rejeita dados incompletos (sem veterinário)', function () {
    $controller = new ConsultaController();

    $resultado = $controller->agendar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO_ID'],
        'veterinario_id' => 0,
        'empresa_id' => $GLOBALS['TESTE_EMPRESA_ID'],
        'data_consulta' => date('Y-m-d', strtotime('+2 days')),
        'hora_consulta' => '10:00',
    ]);

    TestKit::assertFalse($resultado, 'agendar() sem veterinario_id deveria falhar');
});

TestKit::run('listarPorTutor() e listarPorEmpresa() encontram a consulta agendada', function () {
    $controller = new ConsultaController();

    $doTutor = $controller->listarPorTutor($GLOBALS['TESTE_USUARIO_ID']);
    TestKit::assertTrue(count($doTutor) >= 1, 'tutor deveria ter ao menos 1 consulta');

    $daEmpresa = $controller->listarPorEmpresa($GLOBALS['TESTE_EMPRESA_ID']);
    TestKit::assertTrue(count($daEmpresa) >= 1, 'empresa deveria ter ao menos 1 consulta recebida');
});

TestKit::run('atualizarStatus() muda o status quando a empresa é a correta e notifica o tutor', function () {
    $controller = new ConsultaController();
    $notificacaoController = new NotificacaoController();

    $notificacoesAntes = $notificacaoController->contarNaoLidas($GLOBALS['TESTE_USUARIO_ID']);

    $ok = $controller->atualizarStatus($GLOBALS['TESTE_CONSULTA_ID'], $GLOBALS['TESTE_EMPRESA_ID'], 'Confirmada');
    TestKit::assertTrue($ok, 'atualizarStatus() deveria ter sucesso com a empresa certa');

    $consulta = $controller->buscarPorId($GLOBALS['TESTE_CONSULTA_ID']);
    TestKit::assertEquals('Confirmada', $consulta['status']);

    $notificacoesDepois = $notificacaoController->contarNaoLidas($GLOBALS['TESTE_USUARIO_ID']);
    TestKit::assertTrue($notificacoesDepois > $notificacoesAntes, 'tutor deveria ter recebido notificação da confirmação da consulta');
});

TestKit::run('atualizarStatus() NÃO muda o status quando a empresa é outra (proteção de propriedade)', function () {
    $controller = new ConsultaController();

    // empresa_id inexistente/errada não deve conseguir alterar a consulta de outra empresa
    $controller->atualizarStatus($GLOBALS['TESTE_CONSULTA_ID'], 999999, 'Cancelada');

    $consulta = $controller->buscarPorId($GLOBALS['TESTE_CONSULTA_ID']);
    TestKit::assertEquals('Confirmada', $consulta['status'], 'status não deveria ter mudado com empresa_id errado');
});

TestKit::run('cancelarPeloTutor() cancela uma consulta Agendada/Confirmada do próprio tutor', function () {
    $controller = new ConsultaController();

    $ok = $controller->cancelarPeloTutor($GLOBALS['TESTE_CONSULTA_ID'], $GLOBALS['TESTE_USUARIO_ID']);
    TestKit::assertTrue($ok, 'tutor deveria conseguir cancelar sua própria consulta confirmada');

    $consulta = $controller->buscarPorId($GLOBALS['TESTE_CONSULTA_ID']);
    TestKit::assertEquals('Cancelada', $consulta['status']);
});

TestKit::run('cancelarPeloTutor() não deixa outro usuário cancelar a consulta', function () {
    $controller = new ConsultaController();

    // Recria uma consulta ativa pra este teste
    $consultaId = $controller->agendar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO_ID'],
        'veterinario_id' => $GLOBALS['TESTE_VETERINARIO_ID'],
        'empresa_id' => $GLOBALS['TESTE_EMPRESA_ID'],
        'data_consulta' => date('Y-m-d', strtotime('+5 days')),
        'hora_consulta' => '09:00',
        'motivo' => TESTE_MARCADOR . '_segunda_consulta',
    ]);

    $ok = $controller->cancelarPeloTutor($consultaId, $GLOBALS['TESTE_USUARIO2_ID']);
    TestKit::assertFalse($ok, 'usuário que não é o tutor da consulta não deveria conseguir cancelar');

    $GLOBALS['TESTE_CONSULTA_ID_2'] = $consultaId;
});

TestKit::run('registrar prontuário numa consulta existente não cria consulta duplicada', function () use ($pdoConsultaTeste) {
    $controller = new ConsultaController();

    // Nova consulta específica pra esse teste, simulando o que
    // consultas_empresa.php faz até o botão "Iniciar atendimento"
    $consultaId = $controller->agendar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO_ID'],
        'veterinario_id' => $GLOBALS['TESTE_VETERINARIO_ID'],
        'empresa_id' => $GLOBALS['TESTE_EMPRESA_ID'],
        'data_consulta' => date('Y-m-d'),
        'hora_consulta' => '11:00',
        'motivo' => TESTE_MARCADOR . '_motivo_prontuario',
    ]);

    $totalConsultasAntes = (int) $pdoConsultaTeste->query("SELECT COUNT(*) FROM consultas")->fetchColumn();

    // Reproduz exatamente o que processa_prontuario.php faz quando recebe
    // um consulta_id (novo_prontuario.php?consulta_id=X): reaproveita a
    // consulta em vez de criar uma nova, e grava o prontuário nela.
    $pdoConsultaTeste->prepare("
        UPDATE consultas SET status = 'Concluída', motivo = :motivo, observacoes = :obs WHERE id = :id
    ")->execute([
        ':motivo' => TESTE_MARCADOR . '_motivo_prontuario',
        ':obs' => TESTE_MARCADOR . '_diagnostico',
        ':id' => $consultaId,
    ]);

    $pdoConsultaTeste->prepare("
        INSERT INTO prontuarios (consulta_id, diagnostico, tratamento, medicamentos, recomendacoes)
        VALUES (:consulta_id, :diagnostico, '', '', '')
    ")->execute([
        ':consulta_id' => $consultaId,
        ':diagnostico' => TESTE_MARCADOR . '_diagnostico',
    ]);

    $totalConsultasDepois = (int) $pdoConsultaTeste->query("SELECT COUNT(*) FROM consultas")->fetchColumn();
    TestKit::assertEquals($totalConsultasAntes, $totalConsultasDepois, 'não deveria ter criado uma consulta nova, só reaproveitado a existente');

    $consultaAtualizada = $controller->buscarPorId($consultaId);
    TestKit::assertEquals('Concluída', $consultaAtualizada['status'], 'a consulta original deveria estar Concluída');

    $stmtPront = $pdoConsultaTeste->prepare("SELECT COUNT(*) FROM prontuarios WHERE consulta_id = :id");
    $stmtPront->execute([':id' => $consultaId]);
    TestKit::assertEquals(1, (int) $stmtPront->fetchColumn(), 'deveria existir exatamente 1 prontuário pra essa consulta');
});

TestKit::run('limpeza: remove tudo que este teste criou', function () use ($pdoConsultaTeste) {
    $pdoConsultaTeste->prepare("DELETE FROM consultas WHERE empresa_id = :empresa_id")
        ->execute([':empresa_id' => $GLOBALS['TESTE_EMPRESA_ID']]);
    $pdoConsultaTeste->prepare("DELETE FROM empresa_equipe WHERE empresa_id = :empresa_id")
        ->execute([':empresa_id' => $GLOBALS['TESTE_EMPRESA_ID']]);
    $pdoConsultaTeste->prepare("DELETE FROM veterinarios WHERE id = :id")
        ->execute([':id' => $GLOBALS['TESTE_VETERINARIO_ID']]);
    $pdoConsultaTeste->prepare("DELETE FROM empresas WHERE id = :id")
        ->execute([':id' => $GLOBALS['TESTE_EMPRESA_ID']]);

    // Confirma que realmente sumiu, pra não deixar lixo nem dar falso-positivo
    $stmt = $pdoConsultaTeste->prepare("SELECT COUNT(*) FROM empresas WHERE id = :id");
    $stmt->execute([':id' => $GLOBALS['TESTE_EMPRESA_ID']]);
    TestKit::assertEquals(0, (int) $stmt->fetchColumn(), 'empresa de teste deveria ter sido removida');
});
