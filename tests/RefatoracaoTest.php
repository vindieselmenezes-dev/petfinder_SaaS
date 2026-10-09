<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes das classes criadas/alteradas na refatoração do SonarQube
 * ==========================================================
 * Cobre: busca de adoção (PetBuscaController), galeria e histórico
 * de pets, listagem de produtos com filtros (array de critérios),
 * estoque e galeria de produto, galeria de empresa e as funções
 * auxiliares (CampanhaApresentacao e PrestadorCadastroHelper).
 *
 * Os dados criados aqui usam o TESTE_MARCADOR. Pets e empresas são
 * limpos pela rotina do run_all.php (ao apagar os usuários de teste);
 * produtos, estoque e imagens são removidos no último teste deste arquivo.
 */

require_once __DIR__ . '/../app/Controllers/PetBuscaController.php';
require_once __DIR__ . '/../app/Controllers/PetImagemController.php';
require_once __DIR__ . '/../app/Controllers/PetHistoricoController.php';
require_once __DIR__ . '/../app/Controllers/EmpresaGaleriaController.php';
require_once __DIR__ . '/../app/Controllers/ProdutoImagemController.php';
require_once __DIR__ . '/../app/Helpers/CampanhaApresentacao.php';
require_once __DIR__ . '/../app/Helpers/PrestadorCadastroHelper.php';

echo "\n--- Testando funções auxiliares (CampanhaApresentacao e PrestadorCadastroHelper) ---\n";

TestKit::run('percentualMeta() trata meta ausente, zerada ou negativa como "sem meta"', function () {
    TestKit::assertNull(CampanhaApresentacao::percentualMeta(null, null));
    TestKit::assertNull(CampanhaApresentacao::percentualMeta(0.0, 5.0));
    TestKit::assertNull(CampanhaApresentacao::percentualMeta(-5.0, 5.0));
});

TestKit::run('percentualMeta() calcula o percentual e nunca passa de 100', function () {
    TestKit::assertEquals(0, CampanhaApresentacao::percentualMeta(100.0, null));
    TestKit::assertEquals(0, CampanhaApresentacao::percentualMeta(100.0, 0.0));
    TestKit::assertEquals(33, CampanhaApresentacao::percentualMeta(100.0, 33.3333));
    TestKit::assertEquals(33, CampanhaApresentacao::percentualMeta(3.0, 1.0));
    TestKit::assertEquals(100, CampanhaApresentacao::percentualMeta(100.0, 250.0), 'acima da meta deve travar em 100');
});

TestKit::run('rotuloTipo() e iconeTipo() conhecem os tipos e têm padrão para o resto', function () {
    TestKit::assertEquals('Campanha', CampanhaApresentacao::rotuloTipo('campanha'));
    TestKit::assertEquals('Evento', CampanhaApresentacao::rotuloTipo('evento'));
    TestKit::assertEquals('Doação', CampanhaApresentacao::rotuloTipo('doacao'));
    TestKit::assertEquals('Publicação', CampanhaApresentacao::rotuloTipo(null));
    TestKit::assertEquals('Publicação', CampanhaApresentacao::rotuloTipo('inexistente'));
    TestKit::assertEquals('🐾', CampanhaApresentacao::iconeTipo(null));
    TestKit::assertEquals('🐾', CampanhaApresentacao::iconeTipo('inexistente'));
});

TestKit::run('limparCpf() deixa só os dígitos', function () {
    TestKit::assertEquals('12345678909', PrestadorCadastroHelper::limparCpf('123.456.789-09'));
    TestKit::assertEquals('12345678909', PrestadorCadastroHelper::limparCpf('  12345678909 '));
    TestKit::assertEquals('', PrestadorCadastroHelper::limparCpf('abc'));
    TestKit::assertEquals('', PrestadorCadastroHelper::limparCpf(''));
});

TestKit::run('processarFoto() recusa upload com erro, vazio ou de tipo inválido', function () {
    TestKit::assertNull(PrestadorCadastroHelper::processarFoto([]));
    TestKit::assertNull(PrestadorCadastroHelper::processarFoto(['name' => 'a.png', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0]));
    TestKit::assertNull(PrestadorCadastroHelper::processarFoto(['name' => 'a.gif', 'tmp_name' => __FILE__, 'error' => UPLOAD_ERR_OK, 'size' => 100]));
});

echo "\n--- Testando busca de adoção (PetBuscaController) ---\n";

$GLOBALS['TESTE_REFAT_SUFIXO'] = uniqid();
$GLOBALS['TESTE_REFAT_PREFIXO_PET'] = TESTE_MARCADOR . '_Busca' . $GLOBALS['TESTE_REFAT_SUFIXO'];

TestKit::run('monta o cenário: 3 pets (2 para adoção e 1 perdido)', function () {
    $petController = new PetController();
    $base = [
        'usuario_id' => $GLOBALS['TESTE_USUARIO_ID'],
        'especie_id' => $GLOBALS['TESTE_ESPECIE_ID'],
        'raca_id' => $GLOBALS['TESTE_RACA_ID'],
        'cor' => 'Tigrado',
    ];

    $a = $petController->cadastrar($base + ['nome' => $GLOBALS['TESTE_REFAT_PREFIXO_PET'] . '_A', 'sexo' => 'Macho', 'status' => 'Para Adoção', 'castrado' => 1], ['a1.png', 'a2.png']);
    $b = $petController->cadastrar($base + ['nome' => $GLOBALS['TESTE_REFAT_PREFIXO_PET'] . '_B', 'sexo' => 'Fêmea', 'status' => 'Para Adoção', 'castrado' => 0]);
    $c = $petController->cadastrar($base + ['nome' => $GLOBALS['TESTE_REFAT_PREFIXO_PET'] . '_C', 'sexo' => 'Macho', 'status' => 'Perdido', 'castrado' => 0]);

    TestKit::assertTrue((bool) $a && (bool) $b && (bool) $c, 'os 3 pets deveriam ser criados');

    // cadastrar() devolve só sucesso/falha; os ids vêm da própria busca
    $busca = new PetBuscaController();
    foreach (['A', 'B', 'C'] as $letra) {
        $achados = $busca->buscarAdocaoPublico(['busca' => $GLOBALS['TESTE_REFAT_PREFIXO_PET'] . '_' . $letra, 'status' => 'Todos']);
        TestKit::assertEquals(1, count($achados), "o pet {$letra} deveria ser encontrado");
        $GLOBALS['TESTE_REFAT_PET_' . $letra] = (int) $achados[0]['id'];
    }
});

TestKit::run('buscarAdocaoPublico() usa "Para Adoção" por padrão e contarAdocaoPublico() concorda', function () {
    $busca = new PetBuscaController();
    $criterios = ['busca' => $GLOBALS['TESTE_REFAT_PREFIXO_PET']];

    TestKit::assertEquals(2, count($busca->buscarAdocaoPublico($criterios)), 'só os 2 pets "Para Adoção" deveriam aparecer');
    TestKit::assertEquals(2, $busca->contarAdocaoPublico($criterios));
});

TestKit::run('buscarAdocaoPublico() filtra por status, sexo e castração', function () {
    $busca = new PetBuscaController();
    $prefixo = ['busca' => $GLOBALS['TESTE_REFAT_PREFIXO_PET']];

    TestKit::assertEquals(3, count($busca->buscarAdocaoPublico($prefixo + ['status' => 'Todos'])), 'status "Todos" traz os 3');
    TestKit::assertEquals(1, count($busca->buscarAdocaoPublico($prefixo + ['status' => 'Perdido'])), 'só o perdido');
    TestKit::assertEquals(1, count($busca->buscarAdocaoPublico($prefixo + ['sexo' => 'Fêmea'])), 'só a fêmea');
    TestKit::assertEquals(1, count($busca->buscarAdocaoPublico($prefixo + ['castrado' => 1])), 'só o castrado');
    TestKit::assertEquals(2, count($busca->buscarAdocaoPublico($prefixo + ['castrado' => -1])), '-1 = qualquer');
    TestKit::assertEquals(3, $busca->contarAdocaoPublico($prefixo + ['status' => 'Todos']));
});

TestKit::run('buscarAdocaoPublico() ordena e pagina', function () {
    $busca = new PetBuscaController();
    $criterios = ['busca' => $GLOBALS['TESTE_REFAT_PREFIXO_PET'], 'status' => 'Todos', 'ordem' => 'nome_asc'];

    $todos = $busca->buscarAdocaoPublico($criterios);
    TestKit::assertEquals($GLOBALS['TESTE_REFAT_PREFIXO_PET'] . '_A', $todos[0]['nome']);
    TestKit::assertEquals($GLOBALS['TESTE_REFAT_PREFIXO_PET'] . '_C', $todos[2]['nome']);

    $pagina2 = $busca->buscarAdocaoPublico($criterios + ['pagina' => 2, 'porPagina' => 1]);
    TestKit::assertEquals(1, count($pagina2), 'cada página traz 1 pet');
    TestKit::assertEquals($GLOBALS['TESTE_REFAT_PREFIXO_PET'] . '_B', $pagina2[0]['nome'], 'a página 2 é o segundo pet');
});

TestKit::run('buscarAdocaoPublico() apara espaços da busca e aceita critérios vazios', function () {
    $busca = new PetBuscaController();

    $comEspacos = $busca->buscarAdocaoPublico(['busca' => '  ' . $GLOBALS['TESTE_REFAT_PREFIXO_PET'] . '_A  ']);
    TestKit::assertEquals(1, count($comEspacos), 'os espaços das pontas não devem atrapalhar a busca');

    TestKit::assertTrue(is_array($busca->buscarAdocaoPublico([])), 'sem critérios deve devolver uma lista');
    TestKit::assertTrue(is_int($busca->contarAdocaoPublico([])), 'sem critérios deve devolver um número');
});

echo "\n--- Testando galeria e histórico do pet (PetImagemController e PetHistoricoController) ---\n";

TestKit::run('galeria do pet: salvar na criação, buscar e excluir só do dono certo', function () {
    $imagens = new PetImagemController();
    $petId = $GLOBALS['TESTE_REFAT_PET_A'];

    $lista = $imagens->buscarImagens($petId);
    TestKit::assertEquals(2, count($lista), 'o pet foi criado com 2 imagens');

    $imagemId = (int) $lista[0]['id'];
    $imagens->excluirImagem($imagemId, $GLOBALS['TESTE_REFAT_PET_B']);
    TestKit::assertEquals(2, count($imagens->buscarImagens($petId)), 'excluir passando outro pet não pode apagar nada');

    $imagens->excluirImagem($imagemId, $petId);
    TestKit::assertEquals(1, count($imagens->buscarImagens($petId)), 'excluir com o pet certo remove 1');
});

TestKit::run('histórico do pet: eventos registrados aparecem no histórico completo', function () {
    $historico = new PetHistoricoController();
    $petId = $GLOBALS['TESTE_REFAT_PET_B'];

    $antes = count($historico->buscarHistoricoCompleto($petId));
    $historico->registrarEventoHistorico($petId, 'Vacina', 'V10 aplicada', 'lote 7', '2026-09-20', $GLOBALS['TESTE_USUARIO_ID']);
    $depois = $historico->buscarHistoricoCompleto($petId);

    TestKit::assertEquals($antes + 1, count($depois), 'o evento novo deveria entrar no histórico completo');
    TestKit::assertTrue(str_contains(json_encode($depois, JSON_UNESCAPED_UNICODE), 'V10 aplicada'), 'a descrição do evento deveria aparecer');
    TestKit::assertTrue(count($historico->buscarHistoricoStatus($petId)) >= 1, 'o cadastro já registra o primeiro status');
});

echo "\n--- Testando produtos (listagem com critérios, estoque e galeria) ---\n";

TestKit::run('monta o cenário: empresa com 3 produtos (100, 80 promo 60 e 30)', function () {
    $empresaController = new EmpresaController();
    $empresaId = $empresaController->cadastrar([
        'usuario_id' => $GLOBALS['TESTE_USUARIO_ID'],
        'categoria_id' => 1,
        'nome_fantasia' => TESTE_MARCADOR . '_EmpRefat' . $GLOBALS['TESTE_REFAT_SUFIXO'],
        'cidade' => TESTE_MARCADOR . '_CidadeRefat',
        'estado' => 'MG',
    ]);
    TestKit::assertTrue(is_int($empresaId) && $empresaId > 0, 'empresa de teste deveria ser criada');
    $GLOBALS['TESTE_REFAT_EMPRESA_ID'] = $empresaId;

    $produtoController = new ProdutoController();
    $prefixo = TESTE_MARCADOR . '_Prod' . $GLOBALS['TESTE_REFAT_SUFIXO'];
    $a = $produtoController->cadastrar(['empresa_id' => $empresaId, 'nome' => $prefixo . '_A', 'preco_venda' => '100.00']);
    $b = $produtoController->cadastrar(['empresa_id' => $empresaId, 'nome' => $prefixo . '_B', 'preco_venda' => '80.00', 'preco_promocional' => '60.00']);
    $c = $produtoController->cadastrar(['empresa_id' => $empresaId, 'nome' => $prefixo . '_C', 'preco_venda' => '30.00']);

    TestKit::assertTrue(is_int($a) && is_int($b) && is_int($c), 'os 3 produtos deveriam ser criados');
    $GLOBALS['TESTE_REFAT_PRODUTO_A'] = $a;
    $GLOBALS['TESTE_REFAT_PRODUTO_B'] = $b;
    $GLOBALS['TESTE_REFAT_PRODUTO_C'] = $c;
    $GLOBALS['TESTE_REFAT_PREFIXO_PROD'] = $prefixo;
});

TestKit::run('listarAtivos() com critérios: busca, faixa de preço (considera a promoção) e promoção', function () {
    $controller = new ProdutoController();
    $prefixo = $GLOBALS['TESTE_REFAT_PREFIXO_PROD'];
    $nomes = fn(array $lista): array => array_map(fn($p) => substr($p['nome'], -1), $lista);

    TestKit::assertEquals(3, count($controller->listarAtivos(['busca' => $prefixo])), 'a busca pelo prefixo traz os 3');
    TestKit::assertEquals(3, count($controller->listarAtivos(['busca' => '  ' . $prefixo . '  '])), 'espaços nas pontas são aparados');

    $faixa = $nomes($controller->listarAtivos(['busca' => $prefixo, 'preco_min' => 60.0, 'preco_max' => 70.0]));
    TestKit::assertEquals(['B'], $faixa, 'entre 60 e 70 só o B (preço promocional 60)');

    $promocao = $nomes($controller->listarAtivos(['busca' => $prefixo, 'apenas_promocao' => true]));
    TestKit::assertEquals(['B'], $promocao, 'só o B está em promoção');

    $empresa = $controller->listarAtivos(['empresa' => 'EmpRefat' . $GLOBALS['TESTE_REFAT_SUFIXO']]);
    TestKit::assertEquals(3, count($empresa), 'filtrar pelo nome da empresa traz os 3');
});

TestKit::run('listarAtivos() com critérios: ordenação por preço e por nome', function () {
    $controller = new ProdutoController();
    $prefixo = $GLOBALS['TESTE_REFAT_PREFIXO_PROD'];
    $ordem = fn(string $tipo): array => array_map(fn($p) => substr($p['nome'], -1), $controller->listarAtivos(['busca' => $prefixo, 'ordem' => $tipo]));

    TestKit::assertEquals(['C', 'B', 'A'], $ordem('menor_preco'), 'menor preço primeiro (C=30, B=60 promo, A=100)');
    TestKit::assertEquals(['A', 'B', 'C'], $ordem('maior_preco'), 'maior preço primeiro');
    TestKit::assertEquals(['A', 'B', 'C'], $ordem('nome'), 'ordem alfabética');
    TestKit::assertEquals(3, count($ordem('valor-inexistente')), 'ordem desconhecida usa a ordem padrão, sem erro');
});

TestKit::run('listarAtivos() aceita lista de subcategorias vazia ou inválida sem erro', function () {
    $controller = new ProdutoController();
    $prefixo = $GLOBALS['TESTE_REFAT_PREFIXO_PROD'];

    TestKit::assertTrue(is_array($controller->listarAtivos()), 'sem critérios deve devolver uma lista');
    TestKit::assertEquals(3, count($controller->listarAtivos(['busca' => $prefixo, 'subcategorias' => [0, '', 'abc']])), 'ids inválidos são ignorados');
});

TestKit::run('estoque: atualizar cria, atualiza sem duplicar e buscar devolve a quantidade', function () {
    $controller = new ProdutoController();
    $produtoId = $GLOBALS['TESTE_REFAT_PRODUTO_A'];

    TestKit::assertNull($controller->buscarEstoque($produtoId), 'produto novo ainda não tem registro de estoque');

    TestKit::assertTrue($controller->atualizarEstoque($produtoId, 7), 'primeira atualização deveria criar o registro');
    TestKit::assertEquals(7, (int) $controller->buscarEstoque($produtoId)['quantidade']);

    TestKit::assertTrue($controller->atualizarEstoque($produtoId, 12, 2, 50), 'segunda atualização deveria alterar o registro');
    TestKit::assertEquals(12, (int) $controller->buscarEstoque($produtoId)['quantidade']);

    $stmt = Database::conectar()->prepare('SELECT COUNT(*) FROM estoque WHERE produto_id = :id');
    $stmt->execute([':id' => $produtoId]);
    TestKit::assertEquals(1, (int) $stmt->fetchColumn(), 'não pode haver 2 registros de estoque do mesmo produto');
});

TestKit::run('galeria do produto: salvar, buscar (1 principal) e excluir só do produto certo', function () {
    $imagens = new ProdutoImagemController();
    $produtoId = $GLOBALS['TESTE_REFAT_PRODUTO_A'];

    TestKit::assertTrue($imagens->salvarImagens($produtoId, ['p1.png', 'p2.png']), 'deveria salvar as imagens');
    $lista = $imagens->buscarImagens($produtoId);
    TestKit::assertEquals(2, count($lista));

    $principais = array_filter($lista, fn($i) => (int) $i['principal'] === 1);
    TestKit::assertEquals(1, count($principais), 'exatamente uma imagem deve ser a principal');

    $imagemId = (int) $lista[0]['id'];
    $imagens->excluirImagem($imagemId, $GLOBALS['TESTE_REFAT_PRODUTO_B']);
    TestKit::assertEquals(2, count($imagens->buscarImagens($produtoId)), 'excluir passando outro produto não apaga nada');

    $imagens->excluirImagem($imagemId, $produtoId);
    TestKit::assertEquals(1, count($imagens->buscarImagens($produtoId)));
});

TestKit::run('processarImagens() ignora uploads com erro, tipo inválido ou lista vazia', function () {
    $imagens = new ProdutoImagemController();

    TestKit::assertEquals([], $imagens->processarImagens([]));
    TestKit::assertEquals([], $imagens->processarImagens(['name' => 'a.png']), 'estrutura que não é de múltiplos arquivos');
    TestKit::assertEquals([], $imagens->processarImagens([
        'name' => ['a.gif', 'b.png'],
        'tmp_name' => [__FILE__, __FILE__],
        'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE],
        'size' => [100, 0],
    ]), 'extensão inválida e upload com erro são descartados');
});

echo "\n--- Testando galeria da empresa (EmpresaGaleriaController) ---\n";

TestKit::run('galeria da empresa: salvar, buscar e excluir só da empresa certa', function () {
    $galeria = new EmpresaGaleriaController();
    $empresaId = $GLOBALS['TESTE_REFAT_EMPRESA_ID'];

    TestKit::assertTrue($galeria->salvarGaleria($empresaId, ['g1.png', 'g2.png']), 'deveria salvar as imagens');
    $lista = $galeria->buscarGaleria($empresaId);
    TestKit::assertEquals(2, count($lista));

    $imagemId = (int) $lista[0]['id'];
    $galeria->excluirImagemGaleria($imagemId, $empresaId + 999999);
    TestKit::assertEquals(2, count($galeria->buscarGaleria($empresaId)), 'excluir passando outra empresa não apaga nada');

    $galeria->excluirImagemGaleria($imagemId, $empresaId);
    TestKit::assertEquals(1, count($galeria->buscarGaleria($empresaId)));
});

TestKit::run('upload da empresa: recusa arquivo com erro e devolve lista vazia sem arquivos', function () {
    $galeria = new EmpresaGaleriaController();

    TestKit::assertNull($galeria->processarImagemUnica([]));
    TestKit::assertNull($galeria->processarImagemUnica(['name' => 'a.png', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0]));
    TestKit::assertEquals([], $galeria->processarGaleria([]));
});

TestKit::run('limpeza: remove produtos, estoque e imagens do cenário (sem FK em cascata)', function () {
    $pdo = Database::conectar();
    $ids = array_filter([
        $GLOBALS['TESTE_REFAT_PRODUTO_A'] ?? null,
        $GLOBALS['TESTE_REFAT_PRODUTO_B'] ?? null,
        $GLOBALS['TESTE_REFAT_PRODUTO_C'] ?? null,
    ]);

    foreach ($ids as $id) {
        $pdo->prepare('DELETE FROM estoque WHERE produto_id = :id')->execute([':id' => $id]);
        $pdo->prepare('DELETE FROM produto_imagens WHERE produto_id = :id')->execute([':id' => $id]);
        $pdo->prepare('DELETE FROM produtos WHERE id = :id')->execute([':id' => $id]);
    }
    if (isset($GLOBALS['TESTE_REFAT_EMPRESA_ID'])) {
        $pdo->prepare('DELETE FROM empresa_galeria WHERE empresa_id = :id')->execute([':id' => $GLOBALS['TESTE_REFAT_EMPRESA_ID']]);
    }
    if (isset($GLOBALS['TESTE_REFAT_PET_A'], $GLOBALS['TESTE_REFAT_PET_B'], $GLOBALS['TESTE_REFAT_PET_C'])) {
        foreach ([$GLOBALS['TESTE_REFAT_PET_A'], $GLOBALS['TESTE_REFAT_PET_B'], $GLOBALS['TESTE_REFAT_PET_C']] as $petId) {
            $pdo->prepare('DELETE FROM pet_imagens WHERE pet_id = :id')->execute([':id' => $petId]);
            $pdo->prepare('DELETE FROM pets_historico_eventos WHERE pet_id = :id')->execute([':id' => $petId]);
        }
    }

    $verifica = $pdo->prepare('SELECT COUNT(*) FROM produtos WHERE nome LIKE :n');
    $verifica->execute([':n' => TESTE_MARCADOR . '_Prod' . $GLOBALS['TESTE_REFAT_SUFIXO'] . '%']);
    TestKit::assertEquals(0, (int) $verifica->fetchColumn(), 'os produtos de teste deveriam ter sido removidos');
});
