<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

echo "==============================" . PHP_EOL;
echo "PETFINDER BRASIL - TESTES AUTOMATIZADOS" . PHP_EOL;
echo "==============================" . PHP_EOL;

$usuarioModel = new Usuario();
$petModel = new Pet();

$emailTeste = TESTE_MARCADOR . '_setup_' . uniqid() . '@petfinder.test';

$usuarioModel->cadastrar([
    'nome'      => 'Usuario',
    'sobrenome' => 'DeTeste',
    'email'     => $emailTeste,
    'senha'     => 'senhaTeste123',
    'telefone'  => ''
]);

$dadosUsuarioTeste = $usuarioModel->buscarPorEmail($emailTeste);

if (!$dadosUsuarioTeste) {
    echo "ERRO CRITICO: nao foi possivel criar o usuario de teste." . PHP_EOL;
    exit(1);
}

$GLOBALS['TESTE_USUARIO_ID'] = (int) $dadosUsuarioTeste['id'];

// Segundo usuário de teste — necessário pra testar solicitação de
// adoção, conversas e chamados de suporte, que sempre envolvem 2 pessoas
$emailTeste2 = TESTE_MARCADOR . '_setup2_' . uniqid() . '@petfinder.test';

$usuarioModel->cadastrar([
    'nome'      => 'Usuario',
    'sobrenome' => 'DeTesteDois',
    'email'     => $emailTeste2,
    'senha'     => 'senhaTeste123',
    'telefone'  => ''
]);

$dadosUsuarioTeste2 = $usuarioModel->buscarPorEmail($emailTeste2);

if (!$dadosUsuarioTeste2) {
    echo "ERRO CRITICO: nao foi possivel criar o segundo usuario de teste." . PHP_EOL;
    $usuarioModel->deletar($GLOBALS['TESTE_USUARIO_ID']);
    exit(1);
}

$GLOBALS['TESTE_USUARIO2_ID'] = (int) $dadosUsuarioTeste2['id'];

$especies = $petModel->listarEspecies();

if (count($especies) === 0) {
    echo "ERRO CRITICO: nao ha nenhuma especie cadastrada no banco." . PHP_EOL;
    $usuarioModel->deletar($GLOBALS['TESTE_USUARIO_ID']);
    $usuarioModel->deletar($GLOBALS['TESTE_USUARIO2_ID']);
    exit(1);
}

$especieEscolhida = null;
$racaEscolhida = null;

foreach ($especies as $especie) {
    $racas = $petModel->listarRacas((int) $especie['id']);
    if (count($racas) > 0) {
        $especieEscolhida = $especie;
        $racaEscolhida = $racas[0];
        break;
    }
}

if ($especieEscolhida === null) {
    echo "ERRO CRITICO: nenhuma das especies cadastradas tem raca associada." . PHP_EOL;
    $usuarioModel->deletar($GLOBALS['TESTE_USUARIO_ID']);
    $usuarioModel->deletar($GLOBALS['TESTE_USUARIO2_ID']);
    exit(1);
}

$GLOBALS['TESTE_ESPECIE_ID'] = (int) $especieEscolhida['id'];
$GLOBALS['TESTE_RACA_ID'] = (int) $racaEscolhida['id'];

echo "Cenario de teste montado:" . PHP_EOL;
echo "  Usuario 1: ID {$GLOBALS['TESTE_USUARIO_ID']} ({$emailTeste})" . PHP_EOL;
echo "  Usuario 2: ID {$GLOBALS['TESTE_USUARIO2_ID']} ({$emailTeste2})" . PHP_EOL;
echo "  Especie: {$especieEscolhida['nome']} (ID {$GLOBALS['TESTE_ESPECIE_ID']})" . PHP_EOL;
echo "  Raca:    {$racaEscolhida['nome']} (ID {$GLOBALS['TESTE_RACA_ID']})" . PHP_EOL;

require __DIR__ . '/UsuarioTest.php';
require __DIR__ . '/PetTest.php';
require __DIR__ . '/FavoritoTest.php';
require __DIR__ . '/EnderecoTest.php';
require __DIR__ . '/LimiteLoginTest.php';
require __DIR__ . '/HistoricoStatusTest.php';
require __DIR__ . '/SolicitacaoAdocaoTest.php';
require __DIR__ . '/ConversaTest.php';
require __DIR__ . '/SuporteTest.php';
require __DIR__ . '/ResetSenhaTest.php';

$pdo = Database::conectar();

// Limpeza de tudo que os testes possam ter criado, pros dois usuários
foreach ([$GLOBALS['TESTE_USUARIO_ID'], $GLOBALS['TESTE_USUARIO2_ID']] as $idUsuarioLimpeza) {
    $pdo->prepare("DELETE FROM mensagens WHERE remetente_id = :id")->execute([':id' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM conversas WHERE usuario_origem = :id OR usuario_destino = :id2")
        ->execute([':id' => $idUsuarioLimpeza, ':id2' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM solicitacoes_adocao WHERE usuario_solicitante_id = :id")->execute([':id' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM suporte_respostas WHERE usuario_id = :id")->execute([':id' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM suporte WHERE usuario_id = :id")->execute([':id' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM reset_senha_tokens WHERE usuario_id = :id")->execute([':id' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM notificacoes WHERE usuario_id = :id")->execute([':id' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM pets_status_historico WHERE alterado_por = :id")->execute([':id' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = :id")->execute([':id' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM pets WHERE usuario_id = :id")->execute([':id' => $idUsuarioLimpeza]);
    $pdo->prepare("DELETE FROM enderecos WHERE usuario_id = :id")->execute([':id' => $idUsuarioLimpeza]);
    $usuarioModel->deletar($idUsuarioLimpeza);
}

echo PHP_EOL . "Cenario de teste limpo do banco de dados." . PHP_EOL;

TestKit::resumo();

exit(TestKit::houveFalha() ? 1 : 0);
