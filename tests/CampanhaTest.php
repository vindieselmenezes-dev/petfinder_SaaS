<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Testes: app/Models/Campanha.php
 * Moderação de campanhas por parceiro (migration_026)
 * ==========================================================
 * Pré-requisito: migration_026_moderacao_campanhas.sql já aplicada
 * (colunas status_moderacao/observacao_moderacao em parceiro_campanhas
 * e a chave moderacao_campanhas_ativa em configuracoes). Sem isso,
 * todos os testes abaixo falham com erro de coluna/config inexistente
 * — o que é o comportamento esperado, não um bug do teste.
 */

echo "\n--- Testando Campanha.php (moderação) ---\n";

$pdoCampanhaTeste = Database::conectar();
$parceiroModelTeste = new Parceiro();
$campanhaModelTeste = new Campanha();

// --- Fixture: um parceiro aprovado para pendurar as campanhas de teste ------
$parceiroTesteId = $parceiroModelTeste->cadastrar([
    'usuario_id'          => $GLOBALS['TESTE_USUARIO_ID'],
    'empresa_id'          => null,
    'tipo'                => 'ong',
    'nome'                => TESTE_MARCADOR . '_Parceiro',
    'documento'           => '',
    'descricao'           => '',
    'como_ajuda'          => '',
    'logo'                => '',
    'cidade'              => 'Belo Horizonte',
    'estado'              => 'MG',
    'site'                => '',
    'instagram'           => '',
    'whatsapp'            => '',
    'email_contato'       => '',
    'chave_pix'           => '',
    'link_doacao'         => '',
    'aceita_voluntarios'  => false,
]);

if ($parceiroTesteId === false) {
    echo "ERRO CRITICO: nao foi possivel criar o parceiro de teste para CampanhaTest.\n";
} else {

    // cadastrar() sempre nasce 'pendente' — aprova pra poder publicar campanha
    $parceiroModelTeste->alterarStatus($parceiroTesteId, 'aprovado');

    // Guarda o valor original da config pra devolver como estava no final —
    // os testes ligam/desligam moderacao_campanhas_ativa de propósito.
    $configOriginal = $pdoCampanhaTeste
        ->query("SELECT valor_config FROM configuracoes WHERE chave_config = 'moderacao_campanhas_ativa'")
        ->fetchColumn();

    function definirModeracaoCampanhas(PDO $pdo, string $valor): void
    {
        $pdo->prepare("
            UPDATE configuracoes SET valor_config = :v WHERE chave_config = 'moderacao_campanhas_ativa'
        ")->execute([':v' => $valor]);
    }

    TestKit::run('com moderação desligada, criar() já nasce aprovada e aparece em listarAtivas()', function () use ($campanhaModelTeste, $parceiroTesteId, $pdoCampanhaTeste) {

        definirModeracaoCampanhas($pdoCampanhaTeste, '0');
        TestKit::assertFalse($campanhaModelTeste->moderacaoAtiva(), 'moderação deveria estar desligada');

        $id = $campanhaModelTeste->criar([
            'parceiro_id' => $parceiroTesteId,
            'tipo'        => 'campanha',
            'titulo'      => TESTE_MARCADOR . '_sem_moderacao',
            'status'      => 'ativa',
        ]);

        TestKit::assertTrue($id !== false, 'criar() deveria funcionar');

        $campanha = $campanhaModelTeste->buscarPorId((int) $id, false);
        TestKit::assertEquals('aprovada', $campanha['status_moderacao'], 'deveria nascer já aprovada com moderação desligada');

        $ativas = $campanhaModelTeste->listarAtivas();
        $idsAtivas = array_column($ativas, 'id');
        TestKit::assertTrue(in_array((int) $id, $idsAtivas, true), 'deveria aparecer em listarAtivas() (listagem pública)');
    });

    TestKit::run('com moderação ligada, criar() nasce pendente e NÃO aparece em listarAtivas()', function () use ($campanhaModelTeste, $parceiroTesteId, $pdoCampanhaTeste) {

        definirModeracaoCampanhas($pdoCampanhaTeste, '1');
        TestKit::assertTrue($campanhaModelTeste->moderacaoAtiva(), 'moderação deveria estar ligada');

        $id = $campanhaModelTeste->criar([
            'parceiro_id' => $parceiroTesteId,
            'tipo'        => 'evento',
            'titulo'      => TESTE_MARCADOR . '_com_moderacao',
            'status'      => 'ativa',
        ]);

        $campanha = $campanhaModelTeste->buscarPorId((int) $id, false);
        TestKit::assertEquals('pendente', $campanha['status_moderacao'], 'deveria nascer pendente com moderação ligada');

        $ativas = $campanhaModelTeste->listarAtivas();
        $idsAtivas = array_column($ativas, 'id');
        TestKit::assertFalse(in_array((int) $id, $idsAtivas, true), 'pendente não deveria aparecer na listagem pública');

        // buscarPorId com apenasPublicas=true (padrão) também não deveria achar
        TestKit::assertNull($campanhaModelTeste->buscarPorId((int) $id), 'pendente não deveria ser encontrada com apenasPublicas=true');
    });

    TestKit::run('alterarStatusModeracao() aprova e a campanha passa a aparecer publicamente', function () use ($campanhaModelTeste, $parceiroTesteId, $pdoCampanhaTeste) {

        definirModeracaoCampanhas($pdoCampanhaTeste, '1');

        $id = (int) $campanhaModelTeste->criar([
            'parceiro_id' => $parceiroTesteId,
            'titulo'      => TESTE_MARCADOR . '_para_aprovar',
            'status'      => 'ativa',
        ]);

        TestKit::assertTrue(
            $campanhaModelTeste->alterarStatusModeracao($id, 'aprovada', 'Ok, pode publicar'),
            'alterarStatusModeracao() deveria funcionar'
        );

        $campanha = $campanhaModelTeste->buscarPorId($id, false);
        TestKit::assertEquals('aprovada', $campanha['status_moderacao']);
        TestKit::assertEquals('Ok, pode publicar', $campanha['observacao_moderacao']);

        $ativas = $campanhaModelTeste->listarAtivas();
        TestKit::assertTrue(in_array($id, array_column($ativas, 'id'), true), 'agora deveria aparecer na listagem pública');
    });

    TestKit::run('alterarStatusModeracao() recusa esconde a campanha, e status inválido é rejeitado', function () use ($campanhaModelTeste, $parceiroTesteId) {

        $id = (int) $campanhaModelTeste->criar([
            'parceiro_id' => $parceiroTesteId,
            'titulo'      => TESTE_MARCADOR . '_para_recusar',
            'status'      => 'ativa',
        ]);

        TestKit::assertTrue($campanhaModelTeste->alterarStatusModeracao($id, 'recusada', 'Conteúdo impróprio'));

        $campanha = $campanhaModelTeste->buscarPorId($id, false);
        TestKit::assertEquals('recusada', $campanha['status_moderacao']);

        $ativas = $campanhaModelTeste->listarAtivas();
        TestKit::assertFalse(in_array($id, array_column($ativas, 'id'), true), 'recusada não deveria aparecer na listagem pública');

        TestKit::assertFalse(
            $campanhaModelTeste->alterarStatusModeracao($id, 'status-que-nao-existe'),
            'status inválido deveria ser rejeitado'
        );
    });

    TestKit::run('listarParaModeracao() filtra por status corretamente', function () use ($campanhaModelTeste, $parceiroTesteId, $pdoCampanhaTeste) {

        definirModeracaoCampanhas($pdoCampanhaTeste, '1');

        $idPendente = (int) $campanhaModelTeste->criar([
            'parceiro_id' => $parceiroTesteId,
            'titulo'      => TESTE_MARCADOR . '_fila_pendente',
        ]);

        $pendentes = $campanhaModelTeste->listarParaModeracao('pendente');
        TestKit::assertTrue(
            in_array($idPendente, array_column($pendentes, 'id'), true),
            'deveria aparecer na fila de pendentes'
        );

        foreach ($pendentes as $item) {
            TestKit::assertEquals('pendente', $item['status_moderacao'], 'listarParaModeracao(pendente) não deveria trazer outros status');
        }
    });

    TestKit::run('contarPendentesModeracao() reflete o total de pendentes', function () use ($campanhaModelTeste, $parceiroTesteId, $pdoCampanhaTeste) {

        definirModeracaoCampanhas($pdoCampanhaTeste, '1');

        $antes = $campanhaModelTeste->contarPendentesModeracao();

        $campanhaModelTeste->criar([
            'parceiro_id' => $parceiroTesteId,
            'titulo'      => TESTE_MARCADOR . '_contagem',
        ]);

        $depois = $campanhaModelTeste->contarPendentesModeracao();
        TestKit::assertEquals($antes + 1, $depois, 'contador deveria subir em 1 após criar outra pendente');
    });

    TestKit::run('atualizar() com moderação ligada reenvia pra análise; sem reenviar, mantém aprovada', function () use ($campanhaModelTeste, $parceiroTesteId, $pdoCampanhaTeste) {

        definirModeracaoCampanhas($pdoCampanhaTeste, '1');

        $id = (int) $campanhaModelTeste->criar([
            'parceiro_id' => $parceiroTesteId,
            'titulo'      => TESTE_MARCADOR . '_edicao',
            'status'      => 'ativa',
        ]);
        $campanhaModelTeste->alterarStatusModeracao($id, 'aprovada');

        // edição normal (parceiro editando pelo formulário) — deveria
        // voltar para pendente, senão a moderação vira decorativa
        $campanhaModelTeste->atualizar($id, [
            'titulo' => TESTE_MARCADOR . '_edicao_v2',
            'status' => 'ativa',
        ]);

        $campanha = $campanhaModelTeste->buscarPorId($id, false);
        TestKit::assertEquals(
            'pendente',
            $campanha['status_moderacao'],
            'editar o conteúdo de uma campanha aprovada deveria reenviar pra moderação'
        );

        // aprova de novo, e agora simula uma edição feita pelo ADMIN
        // (reenviarModeracao = false) — não deveria voltar a pendente
        $campanhaModelTeste->alterarStatusModeracao($id, 'aprovada');
        $campanhaModelTeste->atualizar($id, [
            'titulo' => TESTE_MARCADOR . '_edicao_v3',
            'status' => 'ativa',
        ], false);

        $campanhaAposEdicaoAdmin = $campanhaModelTeste->buscarPorId($id, false);
        TestKit::assertEquals(
            'aprovada',
            $campanhaAposEdicaoAdmin['status_moderacao'],
            'edição feita pelo admin (reenviarModeracao=false) não deveria voltar a pendente'
        );
    });

    // --- restaura a config original e limpa tudo que os testes criaram -----
    definirModeracaoCampanhas($pdoCampanhaTeste, $configOriginal !== false ? (string) $configOriginal : '0');

    $pdoCampanhaTeste->prepare("DELETE FROM parceiro_campanhas WHERE parceiro_id = :id")
        ->execute([':id' => $parceiroTesteId]);
    $pdoCampanhaTeste->prepare("DELETE FROM parceiros WHERE id = :id")
        ->execute([':id' => $parceiroTesteId]);
}
