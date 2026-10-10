<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Segurança do cadastro público
 * ==========================================================
 * Garante que ninguém consegue se cadastrar como administrador
 * escolhendo (ou forjando) o tipo de conta no formulário.
 */

require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/SegurancaHttp.php';

echo "\n--- Testando o tipo de conta no cadastro público ---\n";

TestKit::run('cadastro público aceita tutor e empresa', function () {
    TestKit::assertEquals('tutor', Auth::tipoDeCadastroPublico('tutor'));
    TestKit::assertEquals('empresa', Auth::tipoDeCadastroPublico('empresa'));
});

TestKit::run('cadastro público NUNCA vira administrador, mesmo com o valor forjado', function () {
    foreach (['administrador', 'ADMINISTRADOR', 'Administrador', ' administrador', 'administrador ', 'admin', 'master'] as $forjado) {
        TestKit::assertEquals('tutor', Auth::tipoDeCadastroPublico($forjado), "o valor '{$forjado}' deveria virar tutor");
    }
});

TestKit::run('cadastro público transforma valor vazio, nulo ou desconhecido em tutor', function () {
    TestKit::assertEquals('tutor', Auth::tipoDeCadastroPublico(null));
    TestKit::assertEquals('tutor', Auth::tipoDeCadastroPublico(''));
    TestKit::assertEquals('tutor', Auth::tipoDeCadastroPublico('veterinario'));
    TestKit::assertEquals('tutor', Auth::tipoDeCadastroPublico('cliente'));
});

TestKit::run('a lista de tipos do cadastro público não contém administrador', function () {
    TestKit::assertFalse(in_array('administrador', Auth::TIPOS_CADASTRO_PUBLICO, true));
});

echo "\n--- Testando a recuperação de senha (link de teste só em ambiente local) ---\n";

TestKit::run('ehAmbienteLocal() só é verdadeiro com APP_ENV=local explícito', function () {
    $anterior = getenv('APP_ENV');
    try {
        putenv('APP_ENV=local');
        TestKit::assertTrue(SegurancaHttp::ehAmbienteLocal(), 'APP_ENV=local deveria ser local');

        foreach (['production', 'producao', 'staging', 'LOCAL', ' local', ''] as $valor) {
            putenv('APP_ENV=' . $valor);
            TestKit::assertFalse(SegurancaHttp::ehAmbienteLocal(), "APP_ENV='{$valor}' não pode contar como local");
        }

        putenv('APP_ENV');
        TestKit::assertFalse(SegurancaHttp::ehAmbienteLocal(), 'sem APP_ENV deve ser tratado como produção');
    } finally {
        putenv($anterior === false ? 'APP_ENV' : 'APP_ENV=' . $anterior);
    }
});

TestKit::run('esqueci_senha.php nunca mostra o link de redefinição sem checar o ambiente', function () {
    $codigo = (string) file_get_contents(__DIR__ . '/../public/conta/esqueci_senha.php');

    TestKit::assertTrue(
        preg_match('/\$linkDeTeste\s*=\s*\$link\s*;/', $codigo) === 1,
        'o link de teste ainda deveria existir para o ambiente local'
    );
    TestKit::assertTrue(
        preg_match('/if\s*\(\s*SegurancaHttp::ehAmbienteLocal\(\)\s*\)\s*\{\s*\$linkDeTeste\s*=\s*\$link\s*;/', $codigo) === 1,
        'a atribuição do link de teste precisa estar dentro de if (SegurancaHttp::ehAmbienteLocal())'
    );
});
