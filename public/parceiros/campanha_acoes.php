<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Ações rápidas sobre uma publicação
 * ==========================================================
 * Recebe os POSTs do painel do parceiro (excluir publicação e
 * atualizar o total arrecadado) e volta para o painel.
 *
 * Só aceita POST com CSRF válido, e sempre confere se quem está
 * pedindo administra mesmo o parceiro dono da publicação.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

$destino = Url::pagina('painel_parceiro.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $destino);
    exit;
}

Middleware::exigirCsrfValido();

$controller = new ParceiroController();
$parceiroModel = $controller->parceiros();
$campanhaModel = $controller->campanhas();

$campanhaId = isset($_POST['campanha_id']) ? (int) $_POST['campanha_id'] : 0;
$campanha = $campanhaId > 0 ? $campanhaModel->buscarPorId($campanhaId, false) : null;

if ($campanha === null) {
    Flash::erro('Publicação não encontrada.');
    header('Location: ' . $destino);
    exit;
}

$parceiroId = (int) $campanha['parceiro_id'];

if (!$parceiroModel->podeAdministrar($parceiroId, (int) Auth::id(), Auth::ehAdministrador())) {
    http_response_code(403);
    Flash::erro('Você não tem permissão para alterar esta publicação.');
    header('Location: ' . $destino);
    exit;
}

switch ($_POST['acao'] ?? '') {

    case 'excluir':
        if ($campanhaModel->excluir($campanhaId, $parceiroId)) {
            Flash::sucesso('Publicação excluída.');
        } else {
            Flash::erro('Não foi possível excluir a publicação.');
        }
        break;

    case 'arrecadado':
        // Aceita tanto "1.234,56" quanto "1234.56".
        $bruto = str_replace(['.', ','], ['', '.'], trim($_POST['valor'] ?? ''));

        if ($bruto === '' || !is_numeric($bruto)) {
            Flash::erro('Informe um valor numérico válido.');
            break;
        }

        if ($campanhaModel->atualizarArrecadado($campanhaId, $parceiroId, (float) $bruto)) {
            Flash::sucesso('Valor arrecadado atualizado.');
        } else {
            Flash::erro('Não foi possível atualizar o valor.');
        }
        break;

    default:
        Flash::erro('Ação desconhecida.');
}

header('Location: ' . $destino);
exit;
