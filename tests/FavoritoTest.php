<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/Favorito.php
 * ==========================================================
 */

echo "\n--- Testando Favorito.php ---\n";

TestKit::run('adicionar → existe → remover (ciclo completo)', function () {
    $favorito = new Favorito();
    $pet = new Pet();
    $usuarioId = $GLOBALS['TESTE_USUARIO_ID'];

    $petId = $pet->cadastrar([
        'usuario_id'      => $usuarioId,
        'nome'            => TESTE_MARCADOR . '_PetFavorito',
        'especie_id'      => $GLOBALS['TESTE_ESPECIE_ID'],
        'raca_id'         => $GLOBALS['TESTE_RACA_ID'],
        'sexo'            => 'Macho',
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

    TestKit::assertFalse($favorito->existe($usuarioId, $petId), 'não deveria ser favorito ainda');

    $favorito->adicionar($usuarioId, $petId);
    TestKit::assertTrue($favorito->existe($usuarioId, $petId), 'deveria ser favorito após adicionar()');

    $lista = $favorito->listarPorUsuario($usuarioId);
    $idsFavoritados = array_column($lista, 'pet_id');
    TestKit::assertTrue(in_array($petId, $idsFavoritados), 'pet deveria aparecer na lista de favoritos');

    $favorito->remover($usuarioId, $petId);
    TestKit::assertFalse($favorito->existe($usuarioId, $petId), 'não deveria mais ser favorito após remover()');

    $pet->excluir($petId, $usuarioId);
});

TestKit::run('adicionar() duas vezes não duplica o favorito', function () {
    $favorito = new Favorito();
    $pet = new Pet();
    $usuarioId = $GLOBALS['TESTE_USUARIO_ID'];

    $petId = $pet->cadastrar([
        'usuario_id'      => $usuarioId,
        'nome'            => TESTE_MARCADOR . '_PetDuplicado',
        'especie_id'      => $GLOBALS['TESTE_ESPECIE_ID'],
        'raca_id'         => $GLOBALS['TESTE_RACA_ID'],
        'sexo'            => 'Macho',
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

    $favorito->adicionar($usuarioId, $petId);
    $favorito->adicionar($usuarioId, $petId);

    $lista = $favorito->listarPorUsuario($usuarioId);
    $vezes = count(array_filter($lista, fn($f) => (int) $f['pet_id'] === $petId));

    TestKit::assertEquals(1, $vezes, 'o pet não deveria aparecer duplicado na lista de favoritos');

    $favorito->remover($usuarioId, $petId);
    $pet->excluir($petId, $usuarioId);
});
