<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


require_once __DIR__ . '/../../app/Models/Usuario.php';
require_once __DIR__ . '/../../app/Helpers/EmpresaAcesso.php';
require_once __DIR__ . '/../../app/Controllers/EmpresaController.php';
require_once __DIR__ . '/../../app/Controllers/PrestadorController.php';
require_once __DIR__ . '/../../app/Controllers/PlanoController.php';
$pdo = Database::conectar();

$empresaId   = (int) ($_GET['empresa_id'] ?? 0);
$prestadorId = (int) ($_GET['prestador_id'] ?? 0);
$planoId     = (int) ($_GET['plano_id'] ?? 0);
$usuarioId   = (int) ($_SESSION['usuario_id'] ?? 0);

if (!isset($_SESSION['usuario_id']) || $planoId <= 0 || ($empresaId <= 0 && $prestadorId <= 0)) {
    header('Location: ' . Url::raiz('index.html'));
    exit();
}

$plano = (new PlanoController())->buscarPorId($planoId);

if (!$plano || !$plano['ativo']) {
    die("Plano inválido.");
}

// O plano Grátis só vale uma vez, no momento do cadastro -- ninguém pode
// "renovar" o período de teste de graça escolhendo Grátis de novo por
// aqui (o botão já vem desabilitado em planos.php, isso é só a trava
// de verdade do lado do servidor).
if ($plano['slug'] === 'gratis') {
    die("O plano Grátis só está disponível no momento do cadastro. Escolha um plano pago pra continuar.");
}

/*
|--------------------------------------------------------------------------
| CAMINHO 1: troca de plano de uma EMPRESA
|--------------------------------------------------------------------------
*/
if ($empresaId > 0) {

    $temAcessoDireto = EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId, ['proprietario', 'administrador']);
    $viaImpersonate  = isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true
        && (int) ($_SESSION['impersonated_empresa_id'] ?? 0) === $empresaId;

    if (!$temAcessoDireto && !$viaImpersonate) {
        die("Erro: você não tem permissão para alterar o plano desta empresa.");
    }

    try {
        $pdo->beginTransaction();

        $stmtAtual = $pdo->prepare("
            SELECT p.nome
            FROM empresas e
            LEFT JOIN planos p ON p.id = e.plano_id
            WHERE e.id = ?
        ");
        $stmtAtual->execute([$empresaId]);
        $planoAntigo = $stmtAtual->fetchColumn() ?: 'nenhum';

        $sucesso = (new EmpresaController())->atualizarPlano($empresaId, $planoId);

        if (!$sucesso) {
            throw new Exception("Não foi possível atualizar o plano.");
        }

        $payloadFinanceiro = json_encode([
            'motivo'       => "Simulação manual de troca de plano (sem gateway de pagamento real ligado ainda)",
            'plano_antigo' => $planoAntigo,
            'plano_novo'   => $plano['nome'],
            'preco_mensal' => $plano['preco_mensal'],
        ], JSON_UNESCAPED_UNICODE);

        $stmtAudit = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, tabela, acao, registro_id, detalhes)
            VALUES (?, 'empresas', 'UPDATE', ?, ?)
        ");
        $stmtAudit->execute([$usuarioId, $empresaId, $payloadFinanceiro]);

        $pdo->commit();

        echo "<script>
                alert('Plano atualizado! Agora sua empresa está no plano: " . htmlspecialchars($plano['nome']) . "');
                window.location.href='painel_b2b.php?empresa_id=" . $empresaId . "';
              </script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro na simulação de assinatura: " . $e->getMessage());
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| CAMINHO 2: troca de plano de um PRESTADOR (passeador/pet sitter/taxi pet)
|--------------------------------------------------------------------------
*/
$prestadorController = new PrestadorController();
$prestador = $prestadorController->buscarPorId($prestadorId);

if (!$prestador || (int) $prestador['usuario_id'] !== $usuarioId) {
    die("Erro: você não tem permissão para alterar o plano deste perfil.");
}

try {
    $pdo->beginTransaction();

    $stmtAtual = $pdo->prepare("
        SELECT p.nome
        FROM prestadores_servico ps
        LEFT JOIN planos p ON p.id = ps.plano_id
        WHERE ps.id = ?
    ");
    $stmtAtual->execute([$prestadorId]);
    $planoAntigo = $stmtAtual->fetchColumn() ?: 'nenhum';

    $sucesso = $prestadorController->atualizarPlano($prestadorId, $planoId);

    if (!$sucesso) {
        throw new Exception("Não foi possível atualizar o plano.");
    }

    $payloadFinanceiro = json_encode([
        'motivo'       => "Simulação manual de troca de plano (sem gateway de pagamento real ligado ainda)",
        'plano_antigo' => $planoAntigo,
        'plano_novo'   => $plano['nome'],
        'preco_mensal' => $plano['preco_mensal'],
    ], JSON_UNESCAPED_UNICODE);

    $stmtAudit = $pdo->prepare("
        INSERT INTO auditoria (usuario_id, tabela, acao, registro_id, detalhes)
        VALUES (?, 'prestadores_servico', 'UPDATE', ?, ?)
    ");
    $stmtAudit->execute([$usuarioId, $prestadorId, $payloadFinanceiro]);

    $pdo->commit();

    echo "<script>
            alert('Plano atualizado! Agora seu perfil está no plano: " . htmlspecialchars($plano['nome']) . "');
            window.location.href='" . Url::pagina('prestador.php') . "?id=" . $prestadorId . "&voce=1';
          </script>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro na simulação de assinatura: " . $e->getMessage());
}
