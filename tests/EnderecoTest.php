<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/Endereco.php
 * ==========================================================
 */

echo "\n--- Testando Endereco.php ---\n";

TestKit::run('salvar() cria o endereço e depois atualiza, sem duplicar (endereço principal único)', function () {
    $endereco = new Endereco();
    $usuarioId = $GLOBALS['TESTE_USUARIO_ID'];

    $ok1 = $endereco->salvar([
        'usuario_id'  => $usuarioId,
        'cep'         => '35590-000',
        'logradouro'  => 'Rua Teste',
        'numero'      => '100',
        'complemento' => '',
        'bairro'      => 'Centro',
        'cidade'      => 'Ouro Branco',
        'estado'      => 'MG'
    ]);

    TestKit::assertTrue($ok1, 'primeiro salvar() deveria funcionar');

    $primeiro = $endereco->buscarPorUsuario($usuarioId);
    TestKit::assertNotNull($primeiro, 'endereço deveria existir após salvar()');
    TestKit::assertEquals('Ouro Branco', $primeiro['cidade']);

    // salva de novo, com cidade diferente -> deve ATUALIZAR, não duplicar
    $endereco->salvar([
        'usuario_id'  => $usuarioId,
        'cep'         => '35590-000',
        'logradouro'  => 'Rua Teste',
        'numero'      => '100',
        'complemento' => '',
        'bairro'      => 'Centro',
        'cidade'      => 'Congonhas',
        'estado'      => 'MG'
    ]);

    $segundo = $endereco->buscarPorUsuario($usuarioId);
    TestKit::assertEquals('Congonhas', $segundo['cidade'], 'cidade deveria ter sido atualizada');
    TestKit::assertEquals($primeiro['id'], $segundo['id'], 'deveria ser o mesmo registro, sem duplicar');
});
