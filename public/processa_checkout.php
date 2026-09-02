<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?voltar=checkout.php');
    exit;
}

require_once "../app/Helpers/Csrf.php";
require_once "../app/Models/Pedido.php";
require_once "../app/Models/Endereco.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validar($_POST['csrf_token'] ?? null)) {
    $_SESSION['checkout_erro'] = 'Sessão expirada. Atualize a página e tente novamente.';
    header('Location: checkout.php');
    exit;
}

$carrinho = $_SESSION['carrinho'] ?? [];

if (empty($carrinho)) {
    header('Location: carrinho.php');
    exit;
}

$formaPagamentoId = (int) ($_POST['forma_pagamento_id'] ?? 0);
$cupom = trim($_POST['cupom'] ?? '');

if ($formaPagamentoId <= 0) {
    $_SESSION['checkout_erro'] = 'Selecione uma forma de pagamento.';
    header('Location: checkout.php');
    exit;
}

$enderecoModel = new Endereco();
$endereco = $enderecoModel->buscarPorUsuario((int) $_SESSION['usuario_id']);

if (!$endereco) {
    $_SESSION['checkout_erro'] = 'Cadastre um endereço de entrega antes de continuar.';
    header('Location: checkout.php');
    exit;
}

$itens = [];
foreach ($carrinho as $produtoId => $quantidade) {
    $itens[] = ['produto_id' => (int) $produtoId, 'quantidade' => (int) $quantidade];
}

$pedidoModel = new Pedido();
$resultado = $pedidoModel->criar(
    (int) $_SESSION['usuario_id'],
    $itens,
    $formaPagamentoId,
    $cupom !== '' ? $cupom : null,
    (int) $endereco['id']
);

if (!$resultado['sucesso']) {
    $_SESSION['checkout_erro'] = $resultado['erro'] ?? 'Não foi possível concluir a compra.';
    header('Location: checkout.php');
    exit;
}

// Compra concluída — esvazia o carrinho
unset($_SESSION['carrinho']);

header('Location: pedido_confirmado.php?id=' . $resultado['pedido_id']);
exit;
