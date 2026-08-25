<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Controllers/SolicitacaoAdocaoController.php
 * ==========================================================
 */

echo "\n--- Testando SolicitacaoAdocao ---\n";

function criarPetParaAdocaoTeste(Pet $pet, string $sufixo): int
{
    return $pet->cadastrar([
        'usuario_id'      => $GLOBALS['TESTE_USUARIO_ID'],
        'nome'            => TESTE_MARCADOR . '_PetAdocao_' . $sufixo,
        'especie_id'      => $GLOBALS['TESTE_ESPECIE_ID'],
        'raca_id'         => $GLOBALS['TESTE_RACA_ID'],
        'sexo'            => 'Fêmea',
        'cor'             => '',
        'status'          => 'Para Adoção',
        'peso'            => null,
        'altura'          => null,
        'data_nascimento' => null,
        'microchip'       => null,
        'castrado'        => 0,
        'observacoes'     => '',
        'foto'            => 'sem-foto.png'
    ]);
}

TestKit::run('solicitar() cria a solicitação e já abre uma conversa', function () {
    $pet = new Pet();
    $controller = new SolicitacaoAdocaoController();

    $petId = criarPetParaAdocaoTeste($pet, 'Fluxo');

    $resultado = $controller->solicitar($petId, $GLOBALS['TESTE_USUARIO2_ID'], 'Quero muito adotar esse pet, moro em casa com quintal.');

    TestKit::assertTrue($resultado['sucesso'], $resultado['erro'] ?? '');
    TestKit::assertNotNull($resultado['conversa_id'], 'deveria ter criado uma conversa junto');

    $solicitacao = $controller->buscarPorId($resultado['id']);
    TestKit::assertEquals('Pendente', $solicitacao['status']);
    TestKit::assertEquals($petId, (int) $solicitacao['pet_id']);

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('não deixa solicitar adoção do próprio pet', function () {
    $pet = new Pet();
    $controller = new SolicitacaoAdocaoController();

    $petId = criarPetParaAdocaoTeste($pet, 'ProprioPet');

    $resultado = $controller->solicitar($petId, $GLOBALS['TESTE_USUARIO_ID'], 'Quero adotar meu próprio pet');

    TestKit::assertFalse($resultado['sucesso'], 'não deveria deixar adotar o próprio pet');

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('não deixa solicitar adoção de pet que não está "Para Adoção"', function () {
    $pet = new Pet();
    $controller = new SolicitacaoAdocaoController();

    $petId = $pet->cadastrar([
        'usuario_id'      => $GLOBALS['TESTE_USUARIO_ID'],
        'nome'            => TESTE_MARCADOR . '_PetNaoDisponivel',
        'especie_id'      => $GLOBALS['TESTE_ESPECIE_ID'],
        'raca_id'         => $GLOBALS['TESTE_RACA_ID'],
        'sexo'            => 'Macho',
        'cor'             => '',
        'status'          => 'Com Tutor',
        'peso'            => null,
        'altura'          => null,
        'data_nascimento' => null,
        'microchip'       => null,
        'castrado'        => 0,
        'observacoes'     => '',
        'foto'            => 'sem-foto.png'
    ]);

    $resultado = $controller->solicitar($petId, $GLOBALS['TESTE_USUARIO2_ID'], 'Quero adotar');

    TestKit::assertFalse($resultado['sucesso'], 'não deveria deixar adotar pet que já tem tutor');

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('não deixa 2 solicitações pendentes da mesma pessoa pro mesmo pet', function () {
    $pet = new Pet();
    $controller = new SolicitacaoAdocaoController();

    $petId = criarPetParaAdocaoTeste($pet, 'Duplicada');

    $primeira = $controller->solicitar($petId, $GLOBALS['TESTE_USUARIO2_ID'], 'Primeira tentativa');
    TestKit::assertTrue($primeira['sucesso']);

    $segunda = $controller->solicitar($petId, $GLOBALS['TESTE_USUARIO2_ID'], 'Segunda tentativa');
    TestKit::assertFalse($segunda['sucesso'], 'não deveria deixar solicitar de novo enquanto a primeira está pendente');

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('aprovar() transfere a posse do pet e marca como Adotado', function () {
    $pet = new Pet();
    $controller = new SolicitacaoAdocaoController();

    $petId = criarPetParaAdocaoTeste($pet, 'Aprovar');
    $resultado = $controller->solicitar($petId, $GLOBALS['TESTE_USUARIO2_ID'], 'Quero adotar');

    $aprovacao = $controller->aprovar($resultado['id'], $GLOBALS['TESTE_USUARIO_ID']);
    TestKit::assertTrue($aprovacao['sucesso'], $aprovacao['erro'] ?? '');

    $petAtualizado = $pet->buscarPorId($petId);
    TestKit::assertEquals('Adotado', $petAtualizado['status']);
    TestKit::assertEquals($GLOBALS['TESTE_USUARIO2_ID'], (int) $petAtualizado['usuario_id'], 'a posse deveria ter sido transferida pro novo tutor');

    $solicitacao = $controller->buscarPorId($resultado['id']);
    TestKit::assertEquals('Aprovada', $solicitacao['status']);

    // Devolve a posse pro usuário 1 antes de excluir, já que excluir() checa o dono
    $pet->transferirTutor($petId, $GLOBALS['TESTE_USUARIO_ID']);
    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('não deixa outra pessoa (que não o dono) aprovar a solicitação', function () {
    $pet = new Pet();
    $controller = new SolicitacaoAdocaoController();

    $petId = criarPetParaAdocaoTeste($pet, 'AprovarErrado');
    $resultado = $controller->solicitar($petId, $GLOBALS['TESTE_USUARIO2_ID'], 'Quero adotar');

    // O próprio interessado tentando aprovar a própria solicitação
    $aprovacao = $controller->aprovar($resultado['id'], $GLOBALS['TESTE_USUARIO2_ID']);
    TestKit::assertFalse($aprovacao['sucesso'], 'só o dono do pet pode aprovar');

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('rejeitar() marca a solicitação como Rejeitada, sem transferir o pet', function () {
    $pet = new Pet();
    $controller = new SolicitacaoAdocaoController();

    $petId = criarPetParaAdocaoTeste($pet, 'Rejeitar');
    $resultado = $controller->solicitar($petId, $GLOBALS['TESTE_USUARIO2_ID'], 'Quero adotar');

    $rejeicao = $controller->rejeitar($resultado['id'], $GLOBALS['TESTE_USUARIO_ID']);
    TestKit::assertTrue($rejeicao['sucesso'], $rejeicao['erro'] ?? '');

    $solicitacao = $controller->buscarPorId($resultado['id']);
    TestKit::assertEquals('Rejeitada', $solicitacao['status']);

    $petAtualizado = $pet->buscarPorId($petId);
    TestKit::assertEquals($GLOBALS['TESTE_USUARIO_ID'], (int) $petAtualizado['usuario_id'], 'a posse não deveria mudar numa rejeição');

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('cancelar() só funciona se for o próprio solicitante e a solicitação estiver Pendente', function () {
    $pet = new Pet();
    $controller = new SolicitacaoAdocaoController();

    $petId = criarPetParaAdocaoTeste($pet, 'Cancelar');
    $resultado = $controller->solicitar($petId, $GLOBALS['TESTE_USUARIO2_ID'], 'Quero adotar');

    $tentativaErrada = $controller->cancelar($resultado['id'], $GLOBALS['TESTE_USUARIO_ID']);
    TestKit::assertFalse($tentativaErrada['sucesso'], 'o dono do pet não deveria poder cancelar a solicitação de outra pessoa');

    $cancelamento = $controller->cancelar($resultado['id'], $GLOBALS['TESTE_USUARIO2_ID']);
    TestKit::assertTrue($cancelamento['sucesso'], $cancelamento['erro'] ?? '');

    $solicitacao = $controller->buscarPorId($resultado['id']);
    TestKit::assertEquals('Cancelada', $solicitacao['status']);

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});
