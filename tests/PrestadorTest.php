<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/Prestador.php e PrestadorController.php
 * Módulo novo: Passeador e Pet Sitter (fusão com projetointegrador)
 * ==========================================================
 */

echo "\n--- Testando Prestador.php ---\n";

TestKit::run('cadastrarCompleto() → buscarPorId() (ciclo básico de cadastro)', function () {
    $controller = new PrestadorController();

    $novoId = $controller->cadastrarCompleto(
        [
            "usuario_id" => $GLOBALS['TESTE_USUARIO_ID'],
            "tipo" => "passeador",
            "cpf" => "11122233344",
            "genero" => "nao-informar",
            "data_nascimento" => null,
            "telefone" => "",
            "whatsapp" => "",
            "email" => "",
            "cep" => "",
            "endereco" => "",
            "numero" => "",
            "complemento" => "",
            "bairro" => "",
            "cidade" => TESTE_MARCADOR . '_Cidade',
            "estado" => "MG",
            "foto" => null,
            "tempo_experiencia" => "1 ano",
            "formacao" => "",
            "experiencia" => "",
            "apresentacao" => TESTE_MARCADOR . '_apresentacao',
            "diferencial" => "",
            "valor_hora" => "20.00",
            "valor_diaria" => "",
            "forma_pagamento" => "",
            "area_atendimento" => "",
            "instagram" => "",
            "facebook" => "",
        ],
        ["Passeio individual"],
        ["Cães de pequeno porte"],
        ["Segunda"],
        ["Manhã"]
    );

    TestKit::assertTrue(is_int($novoId) && $novoId > 0, 'cadastrarCompleto() deveria retornar um ID válido');

    $GLOBALS['TESTE_PRESTADOR_ID'] = $novoId;

    $encontrado = $controller->buscarPorId($novoId);
    TestKit::assertNotNull($encontrado, 'prestador deveria ser encontrado logo após o cadastro');
    TestKit::assertEquals('passeador', $encontrado['tipo'], 'tipo deveria ser passeador');
});

TestKit::run('buscarPorUsuarioETipo() encontra o perfil recém-criado', function () {
    $controller = new PrestadorController();

    $encontrado = $controller->buscarPorUsuarioETipo($GLOBALS['TESTE_USUARIO_ID'], 'passeador');
    TestKit::assertNotNull($encontrado, 'deveria encontrar o perfil de passeador do usuário de teste');
});

TestKit::run('buscarServicos(), buscarAnimaisAtendidos() e buscarDisponibilidade() batem com o cadastro', function () {
    $controller = new PrestadorController();
    $prestadorId = $GLOBALS['TESTE_PRESTADOR_ID'];

    TestKit::assertEquals(['Passeio individual'], $controller->buscarServicos($prestadorId));
    TestKit::assertEquals(['Cães de pequeno porte'], $controller->buscarAnimaisAtendidos($prestadorId));

    $disponibilidade = $controller->buscarDisponibilidade($prestadorId);
    TestKit::assertTrue(count($disponibilidade) === 1, 'deveria ter exatamente 1 registro de disponibilidade');
    TestKit::assertEquals('Segunda', $disponibilidade[0]['dia_semana']);
    TestKit::assertEquals('Manhã', $disponibilidade[0]['periodo']);
});

TestKit::run('listarAtivos() encontra o prestador de teste pela cidade', function () {
    $controller = new PrestadorController();

    $lista = $controller->listarAtivos('passeador', TESTE_MARCADOR . '_Cidade');
    TestKit::assertTrue(count($lista) >= 1, 'deveria encontrar ao menos 1 prestador nessa cidade de teste');
});

TestKit::run('avaliar() registra nota e impede avaliação duplicada', function () {
    $controller = new PrestadorController();
    $prestadorId = $GLOBALS['TESTE_PRESTADOR_ID'];

    $sucesso = $controller->avaliar($prestadorId, $GLOBALS['TESTE_USUARIO2_ID'], 5, 'Muito bom!');
    TestKit::assertTrue($sucesso, 'primeira avaliação deveria ter sucesso');

    $duplicada = $controller->avaliar($prestadorId, $GLOBALS['TESTE_USUARIO2_ID'], 3, 'De novo');
    TestKit::assertFalse($duplicada, 'segunda avaliação do mesmo usuário deveria ser bloqueada');

    $atualizado = $controller->buscarPorId($prestadorId);
    TestKit::assertEquals(5.0, (float) $atualizado['avaliacao'], 'média deveria ser 5.0 após 1 avaliação de nota 5');
    TestKit::assertEquals(1, (int) $atualizado['total_avaliacoes']);
});

TestKit::run('criarSolicitacao() → listarSolicitacoesRecebidas()/Feitas() → atualizarStatusSolicitacao()', function () {
    $controller = new PrestadorController();
    $prestadorId = $GLOBALS['TESTE_PRESTADOR_ID'];

    $notificacaoController = new NotificacaoController();
    $notificacoesAntesPrestador = $notificacaoController->contarNaoLidas($GLOBALS['TESTE_USUARIO_ID']);

    $solicitacaoId = $controller->criarSolicitacao([
        'prestador_id' => $prestadorId,
        'usuario_id' => $GLOBALS['TESTE_USUARIO2_ID'],
        'pet_id' => null,
        'data_desejada' => date('Y-m-d', strtotime('+3 days')),
        'periodo' => 'Tarde',
        'mensagem' => TESTE_MARCADOR . '_mensagem',
    ]);

    TestKit::assertTrue(is_int($solicitacaoId) && $solicitacaoId > 0, 'criarSolicitacao() deveria retornar um ID válido');

    $recebidas = $controller->listarSolicitacoesRecebidas($prestadorId);
    TestKit::assertTrue(count($recebidas) >= 1, 'prestador deveria ter ao menos 1 solicitação recebida');

    $feitas = $controller->listarSolicitacoesFeitas($GLOBALS['TESTE_USUARIO2_ID']);
    TestKit::assertTrue(count($feitas) >= 1, 'usuário deveria ter ao menos 1 solicitação feita');

    $notificacoesDepoisPrestador = $notificacaoController->contarNaoLidas($GLOBALS['TESTE_USUARIO_ID']);
    TestKit::assertTrue($notificacoesDepoisPrestador > $notificacoesAntesPrestador, 'dono do perfil de prestador deveria ter recebido uma notificação da nova solicitação');

    $notificacoesAntesTutor = $notificacaoController->contarNaoLidas($GLOBALS['TESTE_USUARIO2_ID']);

    $ok = $controller->atualizarStatusSolicitacao($solicitacaoId, $prestadorId, 'aceita');
    TestKit::assertTrue($ok, 'atualizarStatusSolicitacao() deveria ter sucesso');

    $notificacoesDepoisTutor = $notificacaoController->contarNaoLidas($GLOBALS['TESTE_USUARIO2_ID']);
    TestKit::assertTrue($notificacoesDepoisTutor > $notificacoesAntesTutor, 'tutor deveria ter recebido uma notificação de que o pedido foi aceito');
});

TestKit::run('unicidade usuario+tipo: não deve permitir 2 perfis do mesmo tipo pro mesmo usuário', function () {
    $controller = new PrestadorController();

    $existente = $controller->buscarPorUsuarioETipo($GLOBALS['TESTE_USUARIO_ID'], 'passeador');
    TestKit::assertNotNull($existente, 'já deveria existir um perfil de passeador pro usuário de teste');

    $duplicado = false;

    try {
        (new PrestadorController())->cadastrarCompleto(
            [
                "usuario_id" => $GLOBALS['TESTE_USUARIO_ID'],
                "tipo" => "passeador",
                "cpf" => "99988877766",
                "genero" => "nao-informar",
                "data_nascimento" => null,
                "telefone" => "", "whatsapp" => "", "email" => "",
                "cep" => "", "endereco" => "", "numero" => "", "complemento" => "",
                "bairro" => "", "cidade" => "", "estado" => "", "foto" => null,
                "tempo_experiencia" => "", "formacao" => "", "experiencia" => "",
                "apresentacao" => "", "diferencial" => "", "valor_hora" => "",
                "valor_diaria" => "", "forma_pagamento" => "", "area_atendimento" => "",
                "instagram" => "", "facebook" => "",
            ],
            [],
            [],
            [],
            []
        );
    } catch (Throwable $e) {
        $duplicado = true; // esperado: a constraint UNIQUE(usuario_id, tipo) deve barrar
    }

    TestKit::assertTrue($duplicado, 'o banco deveria impedir 2 perfis do mesmo tipo pro mesmo usuário');
});

echo "\n--- Testando Identidade Pet (token público) ---\n";

TestKit::run('garantirTokenIdentidade() → buscarPorToken() encontra o pet certo', function () {
    $petModel = new Pet();
    $petController = new PetController();

    $petId = $petModel->cadastrar([
        'usuario_id'      => $GLOBALS['TESTE_USUARIO_ID'],
        'nome'            => TESTE_MARCADOR . '_PetIdentidade',
        'especie_id'      => $GLOBALS['TESTE_ESPECIE_ID'],
        'raca_id'         => $GLOBALS['TESTE_RACA_ID'],
        'sexo'            => 'Fêmea',
        'cor'             => 'Branca',
        'status'          => 'Com Tutor',
        'peso'            => 5,
        'altura'          => 20,
        'data_nascimento' => null,
        'microchip'       => null,
        'castrado'        => 0,
        'observacoes'     => '',
        'foto'            => 'sem-foto.png'
    ]);

    TestKit::assertTrue(is_int($petId) && $petId > 0, 'cadastro do pet de teste deveria funcionar');

    $token = $petController->garantirTokenIdentidade($petId);
    TestKit::assertTrue(is_string($token) && strlen($token) > 0, 'deveria gerar um token não vazio');

    $encontrado = $petController->buscarPorToken($token);
    TestKit::assertNotNull($encontrado, 'deveria encontrar o pet pelo token gerado');
    TestKit::assertEquals($petId, (int) $encontrado['id'], 'o pet encontrado pelo token deveria ser o mesmo cadastrado');
});

echo "\n--- Testando Táxi Pet (novo tipo de prestador + veículo) ---\n";

TestKit::run('cadastrarCompleto() com tipo=taxista_pet grava também os dados do veículo', function () {
    $controller = new PrestadorController();

    $novoId = $controller->cadastrarCompleto(
        [
            "usuario_id" => $GLOBALS['TESTE_USUARIO2_ID'],
            "tipo" => "taxista_pet",
            "cpf" => "55566677788",
            "genero" => "nao-informar",
            "data_nascimento" => null,
            "telefone" => "", "whatsapp" => "", "email" => "",
            "cep" => "", "endereco" => "", "numero" => "", "complemento" => "",
            "bairro" => "", "cidade" => TESTE_MARCADOR . '_CidadeTaxi', "estado" => "MG", "foto" => null,
            "tempo_experiencia" => "", "formacao" => "", "experiencia" => "",
            "apresentacao" => "", "diferencial" => "", "valor_hora" => "",
            "valor_diaria" => "", "forma_pagamento" => "", "area_atendimento" => "",
            "instagram" => "", "facebook" => "",
        ],
        [],
        ['Cães de pequeno porte', 'Gatos'],
        [],
        [],
        [
            "tipo_veiculo" => "Van",
            "modelo" => "Fiat Doblô",
            "placa" => "TST1234",
            "ano" => "2020",
            "capacidade_pets" => "4",
            "ar_condicionado" => true,
            "caixa_transporte" => true,
            "aceita_animais_grandes" => false,
            "valor_km" => "2.50",
            "valor_corrida_minima" => "20.00",
        ]
    );

    TestKit::assertTrue(is_int($novoId) && $novoId > 0, 'cadastro de taxista_pet deveria funcionar');
    $GLOBALS['TESTE_TAXISTA_ID'] = $novoId;

    $prestador = $controller->buscarPorId($novoId);
    TestKit::assertEquals('taxista_pet', $prestador['tipo']);
});

TestKit::run('buscarVeiculo() retorna os dados salvos do veículo', function () {
    $controller = new PrestadorController();

    $veiculo = $controller->buscarVeiculo($GLOBALS['TESTE_TAXISTA_ID']);
    TestKit::assertNotNull($veiculo, 'deveria encontrar o veículo cadastrado');
    TestKit::assertEquals('Van', $veiculo['tipo_veiculo']);
    TestKit::assertEquals(4, (int) $veiculo['capacidade_pets']);
    TestKit::assertTrue((bool) $veiculo['ar_condicionado'], 'ar_condicionado deveria ser 1/true');
});

TestKit::run('listarAtivos("taxista_pet") encontra o taxista pela cidade', function () {
    $controller = new PrestadorController();

    $lista = $controller->listarAtivos('taxista_pet', TESTE_MARCADOR . '_CidadeTaxi');
    TestKit::assertTrue(count($lista) >= 1, 'deveria encontrar ao menos 1 taxista nessa cidade de teste');
});

TestKit::run('salvarVeiculo() faz upsert (chamar de novo atualiza em vez de duplicar)', function () {
    $controller = new PrestadorController();

    $ok = $controller->salvarVeiculo($GLOBALS['TESTE_TAXISTA_ID'], [
        "tipo_veiculo" => "Carro",
        "modelo" => "Onix",
        "placa" => "NEW9999",
        "ano" => "2023",
        "capacidade_pets" => "2",
        "ar_condicionado" => false,
        "caixa_transporte" => false,
        "aceita_animais_grandes" => false,
        "valor_km" => "3.00",
        "valor_corrida_minima" => "",
    ]);

    TestKit::assertTrue($ok, 'atualização do veículo deveria ter sucesso');

    $veiculo = $controller->buscarVeiculo($GLOBALS['TESTE_TAXISTA_ID']);
    TestKit::assertEquals('Carro', $veiculo['tipo_veiculo'], 'tipo_veiculo deveria ter sido atualizado, não duplicado');
    TestKit::assertEquals('Onix', $veiculo['modelo']);
});

echo "\n--- Testando período de teste com prazo (prestadores) ---\n";

TestKit::run('prestador novo já sai com plano_expira_em no futuro e não está vencido', function () {
    $controller = new PrestadorController();
    $prestador = $controller->buscarPorId($GLOBALS['TESTE_PRESTADOR_ID']);

    TestKit::assertNotNull($prestador['plano_expira_em'], 'prestador novo deveria ter uma data de expiração calculada');
    TestKit::assertTrue(strtotime($prestador['plano_expira_em']) > time(), 'a expiração deveria estar no futuro logo após o cadastro');
    TestKit::assertFalse($controller->trialVencido($GLOBALS['TESTE_PRESTADOR_ID']), 'trial recém-criado não deveria estar vencido');
});

TestKit::run('trial vencido: prestador some de listarAtivos() e não recebe novas solicitações', function () {
    $pdoTrialPrestador = Database::conectar();
    $controller = new PrestadorController();
    $prestadorId = $GLOBALS['TESTE_PRESTADOR_ID'];

    $pdoTrialPrestador->prepare("UPDATE prestadores_servico SET plano_expira_em = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE id = :id")
        ->execute([':id' => $prestadorId]);

    TestKit::assertTrue($controller->trialVencido($prestadorId), 'trialVencido() deveria ser true depois de forçar a data pro passado');

    $lista = $controller->listarAtivos('passeador', TESTE_MARCADOR . '_Cidade');
    $idsEncontrados = array_map(fn ($p) => (int) $p['id'], $lista);
    TestKit::assertFalse(in_array($prestadorId, $idsEncontrados, true), 'prestador com trial vencido não deveria aparecer no diretório público');

    $resultado = $controller->criarSolicitacao([
        'prestador_id' => $prestadorId,
        'usuario_id' => $GLOBALS['TESTE_USUARIO2_ID'],
        'data_desejada' => date('Y-m-d', strtotime('+1 day')),
        'periodo' => 'Manhã',
        'mensagem' => TESTE_MARCADOR . '_deveria_ser_bloqueada',
    ]);
    TestKit::assertFalse($resultado, 'não deveria ser possível solicitar serviço de um prestador com trial vencido');

    // Restaura o prazo pra não atrapalhar os outros testes/limpeza
    $pdoTrialPrestador->prepare("UPDATE prestadores_servico SET plano_expira_em = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE id = :id")
        ->execute([':id' => $prestadorId]);
});

TestKit::run('atualizarPlano() do prestador pra um plano pago resolve o trial vencido', function () {
    $controller = new PrestadorController();
    $planoController = new PlanoController();
    $prestadorId = $GLOBALS['TESTE_PRESTADOR_ID'];

    $pdoTrialPrestador2 = Database::conectar();
    $pdoTrialPrestador2->prepare("UPDATE prestadores_servico SET plano_expira_em = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE id = :id")
        ->execute([':id' => $prestadorId]);
    TestKit::assertTrue($controller->trialVencido($prestadorId));

    $planoDestaque = $planoController->buscarPorSlug('destaque');
    $ok = $controller->atualizarPlano($prestadorId, (int) $planoDestaque['id']);
    TestKit::assertTrue($ok, 'atualizarPlano() do prestador deveria ter sucesso');

    TestKit::assertFalse($controller->trialVencido($prestadorId), 'depois de assinar um plano pago, o prestador não deveria mais estar com trial vencido');
});
