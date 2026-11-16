<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: quadro de vacinas do pet (PetVacina)
 * ==========================================================
 */

echo "\n--- Testando Quadro de Vacinas do Pet ---\n";

TestKit::run('situacao() cobre Pendente, Em dia, Vence em breve e Atrasada', function () {
    $hoje = '2026-11-11';
    TestKit::assertEquals('Pendente', PetVacina::situacao(null, false, $hoje));
    TestKit::assertEquals('Em dia', PetVacina::situacao(null, true, $hoje));
    TestKit::assertEquals('Atrasada', PetVacina::situacao('2026-11-10', true, $hoje));
    TestKit::assertEquals('Vence em breve', PetVacina::situacao('2026-11-11', true, $hoje));
    TestKit::assertEquals('Vence em breve', PetVacina::situacao('2026-12-11', true, $hoje));
    TestKit::assertEquals('Em dia', PetVacina::situacao('2026-12-12', true, $hoje));
});

TestKit::run('registrar() grava a dose e montarQuadro() reflete a situação', function () {
    $petModel = new Pet();
    $petId = $petModel->cadastrar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO_ID'], 'nome' => TESTE_MARCADOR . '_PetVacina',
        'especie_id' => $GLOBALS['TESTE_ESPECIE_ID'], 'raca_id' => $GLOBALS['TESTE_RACA_ID'],
        'sexo' => 'Macho', 'cor' => '', 'status' => 'Com Tutor', 'peso' => null, 'altura' => null,
        'data_nascimento' => null, 'microchip' => null, 'castrado' => 0, 'observacoes' => '',
        'foto' => 'sem-foto.png',
    ]);

    $vacinas = new PetVacina();
    $catalogo = $vacinas->listarCatalogo();
    TestKit::assertTrue(count($catalogo) > 0, 'Catálogo de vacinas vazio');

    $vacinaId = (int) $catalogo[0]['id'];
    $vacinas->registrar($petId, [
        'vacina_id' => $vacinaId, 'data_aplicacao' => '2025-01-10', 'proxima_dose' => '2025-01-10',
        'veterinario' => '', 'lote' => 'L1', 'observacoes' => '',
    ]);

    $linha = null;
    foreach ($vacinas->montarQuadro($petId, '2026-11-11') as $l) {
        if ($l['vacina_id'] === $vacinaId) {
            $linha = $l;
        }
    }
    TestKit::assertNotNull($linha, 'Vacina registrada não apareceu no quadro');
    TestKit::assertEquals('Atrasada', $linha['situacao']);

    TestKit::assertFalse($vacinas->excluir((int) $linha['dose']['id'], $petId + 999999), 'Não pode excluir dose de outro pet');
    TestKit::assertTrue($vacinas->excluir((int) $linha['dose']['id'], $petId));
});

TestKit::run('controller rejeita data futura e vacina inválida', function () {
    $c = new PetVacinaController();
    TestKit::assertNotNull($c->registrar(1, ['vacina_id' => 0, 'data_aplicacao' => '2025-01-01'], null));
    $catalogo = $c->listarCatalogo();
    TestKit::assertNotNull($c->registrar(1, ['vacina_id' => $catalogo[0]['id'], 'data_aplicacao' => '2999-01-01'], null));
});

TestKit::run('resumoPublico() não expõe veterinário, lote nem observações', function () {
    $petModel = new Pet();
    $petId = $petModel->cadastrar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO_ID'], 'nome' => TESTE_MARCADOR . '_PetVacinaPublico',
        'especie_id' => $GLOBALS['TESTE_ESPECIE_ID'], 'raca_id' => $GLOBALS['TESTE_RACA_ID'],
        'sexo' => 'Femea', 'cor' => '', 'status' => 'Com Tutor', 'peso' => null, 'altura' => null,
        'data_nascimento' => null, 'microchip' => null, 'castrado' => 0, 'observacoes' => '',
        'foto' => 'sem-foto.png',
    ]);

    $vacinas = new PetVacina();
    $vacinaId = (int) $vacinas->listarCatalogo()[0]['id'];
    $vacinas->registrar($petId, [
        'vacina_id' => $vacinaId, 'data_aplicacao' => '2026-01-10', 'proxima_dose' => '',
        'veterinario' => 'Dra. Segredo', 'lote' => 'LOTE-SECRETO', 'observacoes' => 'obs privada',
    ]);

    $resumo = $vacinas->resumoPublico($petId, '2026-11-11');
    TestKit::assertEquals(1, count($resumo));
    TestKit::assertEquals(['vacina', 'data_aplicacao', 'situacao'], array_keys($resumo[0]));
    TestKit::assertEquals('Em dia', $resumo[0]['situacao']);
});

TestKit::run('clínica não registra vacina em pet que ela não atendeu', function () {
    $c = new PetVacinaController();
    $catalogo = $c->listarCatalogo();
    $erro = $c->registrarPelaClinica(
        ['id' => 999999999, 'usuario_id' => 1, 'nome' => 'X'],
        999999999,
        ['vacina_id' => $catalogo[0]['id'], 'data_aplicacao' => '2026-01-01'],
        1,
        'Clínica Teste'
    );
    TestKit::assertNotNull($erro);
});
