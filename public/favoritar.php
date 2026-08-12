<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/Models/Favorito.php';
require_once '../app/Controllers/PetController.php';
require_once '../app/Controllers/NotificacaoController.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$petId = (int) ($_GET['pet_id'] ?? 0);
$acao = $_GET['acao'] ?? 'adicionar';

if ($petId > 0) {

    $favorito = new Favorito();

    if ($acao === 'remover') {

        $favorito->remover($usuarioId, $petId);

    } else {

        $jaEraFavorito = $favorito->existe($usuarioId, $petId);

        $favorito->adicionar($usuarioId, $petId);

        /*
        |--------------------------------------------------------------
        | Notifica o dono do pet (só na primeira vez, e só se não for
        | o próprio dono favoritando o próprio pet)
        |--------------------------------------------------------------
        */

        if (!$jaEraFavorito) {

            $petController = new PetController();
            $pet = $petController->buscarPorId($petId);

            if ($pet && (int) $pet['usuario_id'] !== $usuarioId) {

                $nomeQuemFavoritou = $_SESSION['usuario_nome'] ?? 'Alguém';

                $notificacaoController = new NotificacaoController();
                $notificacaoController->criar(
                    (int) $pet['usuario_id'],
                    'Seu pet foi favoritado! ⭐',
                    $nomeQuemFavoritou . ' favoritou o seu pet ' . $pet['nome'] . '.',
                    'Sistema'
                );

            }

        }

    }

}

header('Location: meus_favoritos.php');
exit;
