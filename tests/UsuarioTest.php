<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/Usuario.php
 * ==========================================================
 */

echo "\n--- Testando Usuario.php ---\n";

TestKit::run('emailExiste() retorna false para e-mail que não existe', function () {
    $usuario = new Usuario();
    $emailFalso = 'nao_existe_' . uniqid() . '@petfinder.test';

    TestKit::assertFalse($usuario->emailExiste($emailFalso));
});

TestKit::run('cadastrar() cria usuário e emailExiste() passa a retornar true', function () {
    $usuario = new Usuario();
    $email = TESTE_MARCADOR . '_' . uniqid() . '@petfinder.test';

    $ok = $usuario->cadastrar([
        'nome'      => 'Teste',
        'sobrenome' => 'Automatizado',
        'email'     => $email,
        'senha'     => 'senha123',
        'telefone'  => '31999999999'
    ]);

    TestKit::assertTrue($ok, 'cadastrar() deveria retornar true');
    TestKit::assertTrue($usuario->emailExiste($email), 'e-mail deveria existir após cadastro');

    $dados = $usuario->buscarPorEmail($email);
    if ($dados) {
        $usuario->deletar((int) $dados['id']);
    }
});

TestKit::run('senha nunca é armazenada em texto puro (sempre com hash)', function () {
    $usuario = new Usuario();
    $email = TESTE_MARCADOR . '_' . uniqid() . '@petfinder.test';
    $senhaPura = 'minhaSenh@123';

    $usuario->cadastrar([
        'nome'      => 'Teste',
        'sobrenome' => 'Hash',
        'email'     => $email,
        'senha'     => $senhaPura,
        'telefone'  => ''
    ]);

    $dados = $usuario->buscarPorEmail($email);

    TestKit::assertNotNull($dados, 'usuário deveria ter sido encontrado');
    TestKit::assertTrue($dados['senha'] !== $senhaPura, 'a senha NUNCA deveria estar em texto puro no banco');
    TestKit::assertTrue(password_verify($senhaPura, $dados['senha']), 'o hash deveria bater com a senha original');

    $usuario->deletar((int) $dados['id']);
});

TestKit::run('atualizarSenha() troca o hash e invalida a senha antiga', function () {
    $usuario = new Usuario();
    $email = TESTE_MARCADOR . '_' . uniqid() . '@petfinder.test';

    $usuario->cadastrar([
        'nome'      => 'Teste',
        'sobrenome' => 'Senha',
        'email'     => $email,
        'senha'     => 'senhaAntiga1',
        'telefone'  => ''
    ]);

    $dados = $usuario->buscarPorEmail($email);
    $id = (int) $dados['id'];

    $novoHash = password_hash('senhaNova123', PASSWORD_DEFAULT);
    $usuario->atualizarSenha($id, $novoHash);

    $atualizado = $usuario->buscarPorId($id);

    TestKit::assertTrue(password_verify('senhaNova123', $atualizado['senha']), 'a nova senha deveria funcionar');
    TestKit::assertFalse(password_verify('senhaAntiga1', $atualizado['senha']), 'a senha antiga não deveria mais funcionar');

    $usuario->deletar($id);
});
