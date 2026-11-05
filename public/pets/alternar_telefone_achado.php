<?php

declare(strict_types=1);

/**
 * Liga/desliga "mostrar meu telefone" em um alerta de pet encontrado (botão do Meus Pets).
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

$voltar = Url::pagina('meus_pets.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $voltar);
    exit;
}

Middleware::exigirCsrfValido();

$usuarioId = (int) Auth::id();
$petId = (int) ($_POST['pet_id'] ?? 0);
$mostrar = ($_POST['mostrar'] ?? '') === '1';

if ($mostrar && !ContatoAchado::usuarioTemTelefone($usuarioId)) {
    Flash::erro('Cadastre um telefone no seu perfil para usar esta opção.');
} elseif (ContatoAchado::alternar($petId, $usuarioId, $mostrar)) {
    Flash::sucesso($mostrar
        ? 'Seu telefone agora aparece neste alerta, só para usuários logados.'
        : 'Seu telefone foi ocultado deste alerta.');
} else {
    Flash::erro('Não foi possível alterar agora.');
}

header('Location: ' . $voltar);
exit;
