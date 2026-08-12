<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/Pet.php
 * ==========================================================
 */

echo "\n--- Testando Pet.php ---\n";

TestKit::run('listarEspecies() retorna um array', function () {
    $pet = new Pet();
    $especies = $pet->listarEspecies();

    TestKit::assertTrue(is_array($especies), 'deveria retornar um array');
});

TestKit::run('contarPets() retorna um inteiro maior ou igual a zero', function () {
    $pet = new Pet();
    $total = $pet->contarPets();

    TestKit::assertTrue(is_int($total) && $total >= 0, 'contarPets deveria ser um inteiro >= 0');
});

TestKit::run('cadastrar → buscarPorId → atualizar → excluir (ciclo completo)', function () {
    $pet = new Pet();

    $novoId = $pet->cadastrar([
        'usuario_id'      => $GLOBALS['TESTE_USUARIO_ID'],
        'nome'            => TESTE_MARCADOR . '_Pet',
        'especie_id'      => $GLOBALS['TESTE_ESPECIE_ID'],
        'raca_id'         => $GLOBALS['TESTE_RACA_ID'],
        'sexo'            => 'Macho',
        'cor'             => 'Preto',
        'status'          => 'Com Tutor',
        'peso'            => 10.5,
        'altura'          => 40,
        'data_nascimento' => null,
        'microchip'       => null,
        'castrado'        => 0,
        'observacoes'     => 'Pet criado por teste automatizado',
        'foto'            => 'sem-foto.png'
    ]);

    TestKit::assertTrue(is_int($novoId) && $novoId > 0, 'cadastrar() deveria retornar um ID válido');

    $encontrado = $pet->buscarPorId($novoId);
    TestKit::assertNotNull($encontrado, 'pet deveria ser encontrado logo após o cadastro');
    TestKit::assertEquals(TESTE_MARCADOR . '_Pet', $encontrado['nome']);

    $atualizadoOk = $pet->atualizar($novoId, [
        'nome'            => TESTE_MARCADOR . '_PetEditado',
        'especie_id'      => $GLOBALS['TESTE_ESPECIE_ID'],
        'raca_id'         => $GLOBALS['TESTE_RACA_ID'],
        'sexo'            => 'Fêmea',
        'cor'             => 'Branco',
        'status'          => 'Para Adoção',
        'peso'            => 12,
        'altura'          => 42,
        'data_nascimento' => null,
        'microchip'       => null,
        'castrado'        => 1,
        'observacoes'     => 'Editado pelo teste',
        'foto'            => 'sem-foto.png',
        'usuario_id'      => $GLOBALS['TESTE_USUARIO_ID']
    ]);

    TestKit::assertTrue($atualizadoOk, 'atualizar() deveria retornar true');

    $reconsultado = $pet->buscarPorId($novoId);
    TestKit::assertEquals(TESTE_MARCADOR . '_PetEditado', $reconsultado['nome']);
    TestKit::assertEquals('Para Adoção', $reconsultado['status']);

    $excluidoOk = $pet->excluir($novoId, $GLOBALS['TESTE_USUARIO_ID']);
    TestKit::assertTrue($excluidoOk, 'excluir() deveria retornar true');

    $depoisDeExcluir = $pet->buscarPorId($novoId);
    TestKit::assertNull($depoisDeExcluir, 'pet não deveria mais existir após exclusão');
});

TestKit::run('excluir() não remove pet de outro usuário (proteção de propriedade)', function () {
    $pet = new Pet();

    $novoId = $pet->cadastrar([
        'usuario_id'      => $GLOBALS['TESTE_USUARIO_ID'],
        'nome'            => TESTE_MARCADOR . '_PetProtegido',
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

    $usuarioErrado = 999999999;

    $pet->excluir($novoId, $usuarioErrado);

    $aindaExiste = $pet->buscarPorId($novoId);
    TestKit::assertNotNull($aindaExiste, 'pet não deveria ter sido excluído por um usuário diferente do dono');

    // limpeza real, com o dono correto
    $pet->excluir($novoId, $GLOBALS['TESTE_USUARIO_ID']);
});
