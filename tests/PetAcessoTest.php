<?php

declare(strict_types=1);

/**
 * Testes: quem pode ver um pet (PetAcesso)
 */

echo "\n--- Testando Acesso aos Pets (PetAcesso) ---\n";

TestKit::run('perfil: tutor e admin sempre veem; terceiros só em status público', function () {
    $pet = ['usuario_id' => 10, 'status' => 'Com Tutor'];

    TestKit::assertTrue(PetAcesso::podeVerPerfilPara($pet, 10, false), 'Tutor deve ver o próprio pet');
    TestKit::assertTrue(PetAcesso::podeVerPerfilPara($pet, 99, true), 'Admin deve ver');
    TestKit::assertFalse(PetAcesso::podeVerPerfilPara($pet, 99, false), 'Outro usuário não pode ver pet Com Tutor');
    TestKit::assertFalse(PetAcesso::podeVerPerfilPara($pet, null, false), 'Visitante não pode ver pet Com Tutor');
    TestKit::assertFalse(PetAcesso::podeVerPerfilPara(['usuario_id' => 10, 'status' => 'Adotado'], 99, false));

    foreach (['Para Adoção', 'Perdido', 'Encontrado'] as $status) {
        TestKit::assertTrue(
            PetAcesso::podeVerPerfilPara(['usuario_id' => 10, 'status' => $status], null, false),
            "Pet $status deve ser público"
        );
    }
});

TestKit::run('histórico/vacinas: só tutor e admin, mesmo em pet para adoção', function () {
    $pet = ['usuario_id' => 10, 'status' => 'Para Adoção'];

    TestKit::assertTrue(PetAcesso::podeVerPrivadoPara($pet, 10, false));
    TestKit::assertTrue(PetAcesso::podeVerPrivadoPara($pet, 99, true));
    TestKit::assertFalse(PetAcesso::podeVerPrivadoPara($pet, 99, false), 'Terceiro não vê o histórico');
    TestKit::assertFalse(PetAcesso::podeVerPrivadoPara($pet, null, false), 'Visitante não vê o histórico');
    TestKit::assertFalse(PetAcesso::podeVerPrivadoPara($pet, 0, false), 'Id 0 não é dono');
});
