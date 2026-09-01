<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: histórico de status do pet (Pet::atualizarStatus /
 * Pet::atualizar registrando em pets_status_historico)
 * ==========================================================
 */

echo "\n--- Testando Historico de Status do Pet ---\n";

function criarPetTesteHistorico(Pet $pet, string $sufixo): int
{
    return $pet->cadastrar([
        'usuario_id'      => $GLOBALS['TESTE_USUARIO_ID'],
        'nome'            => TESTE_MARCADOR . '_PetHistorico_' . $sufixo,
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
}

TestKit::run('cadastrar() já registra o primeiro evento do histórico', function () {
    $pet = new Pet();
    $petId = criarPetTesteHistorico($pet, 'Cadastro');

    $historico = $pet->buscarHistoricoStatus($petId);

    TestKit::assertEquals(1, count($historico), 'deveria ter exatamente 1 evento logo após o cadastro');
    TestKit::assertNull($historico[0]['status_anterior'], 'o primeiro evento não deveria ter status anterior');
    TestKit::assertEquals('Com Tutor', $historico[0]['status_novo']);

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('atualizarStatus() registra a mudança quando o status realmente muda', function () {
    $pet = new Pet();
    $petId = criarPetTesteHistorico($pet, 'Mudanca');

    $pet->atualizarStatus($petId, 'Perdido', $GLOBALS['TESTE_USUARIO_ID'], 'Teste automatizado');

    $historico = $pet->buscarHistoricoStatus($petId);

    TestKit::assertEquals(2, count($historico), 'deveria ter 2 eventos: cadastro + mudança pra Perdido');
    TestKit::assertEquals('Perdido', $historico[0]['status_novo'], 'o mais recente deveria vir primeiro');
    TestKit::assertEquals('Com Tutor', $historico[0]['status_anterior']);
    TestKit::assertEquals('Teste automatizado', $historico[0]['motivo']);

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('atualizarStatus() NÃO registra nada quando o status não muda', function () {
    $pet = new Pet();
    $petId = criarPetTesteHistorico($pet, 'SemMudanca');

    // Manda o mesmo status que o pet já tem
    $pet->atualizarStatus($petId, 'Com Tutor', $GLOBALS['TESTE_USUARIO_ID']);

    $historico = $pet->buscarHistoricoStatus($petId);

    TestKit::assertEquals(1, count($historico), 'não deveria criar evento novo se o status não mudou de verdade');

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('atualizar() geral também registra mudança de status', function () {
    $pet = new Pet();
    $petId = criarPetTesteHistorico($pet, 'EdicaoGeral');

    $petAtual = $pet->buscarPorId($petId);

    $pet->atualizar($petId, [
        'nome'            => $petAtual['nome'],
        'especie_id'      => $petAtual['especie_id'],
        'raca_id'         => $petAtual['raca_id'],
        'sexo'            => $petAtual['sexo'],
        'cor'             => $petAtual['cor'],
        'status'          => 'Para Adoção',
        'peso'            => null,
        'altura'          => null,
        'data_nascimento' => null,
        'microchip'       => null,
        'castrado'        => 0,
        'observacoes'     => '',
        'foto'            => $petAtual['foto'],
        'usuario_id'      => $GLOBALS['TESTE_USUARIO_ID'],
    ]);

    $historico = $pet->buscarHistoricoStatus($petId);

    TestKit::assertEquals(2, count($historico), 'edição geral mudando o status deveria logar 1 evento novo');
    TestKit::assertEquals('Para Adoção', $historico[0]['status_novo']);
    TestKit::assertEquals('Com Tutor', $historico[0]['status_anterior']);

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});

TestKit::run('histórico registra quem fez a mudança', function () {
    $pet = new Pet();
    $petId = criarPetTesteHistorico($pet, 'Autoria');

    $pet->atualizarStatus($petId, 'Perdido', $GLOBALS['TESTE_USUARIO_ID']);

    $historico = $pet->buscarHistoricoStatus($petId);

    TestKit::assertEquals($GLOBALS['TESTE_USUARIO_ID'], (int) $historico[0]['alterado_por']);
    TestKit::assertNotNull($historico[0]['alterado_por_nome'], 'deveria trazer o nome de quem alterou');

    $pet->excluir($petId, $GLOBALS['TESTE_USUARIO_ID']);
});
