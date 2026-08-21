<?php
require_once __DIR__ . '/../app/Models/Usuario.php';
$pdo = Database::conectar();
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Acesso não autorizado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $species = $_POST['species'];
    $breed = trim($_POST['breed']);
    $birth_date = $_POST['birth_date'];
    $userId = $_SESSION['user_id'];

    if (empty($name) || empty($species)) {
        die("Nome e Espécie são obrigatórios.");
    }
    
    if (empty($breed)) {
        $breed = 'SRD (Sem Raça Definida)';
    }

    try {
        $pdo->beginTransaction();

        // 1. Inserir os dados na tabela central de pets
        $stmtPet = $pdo->prepare("INSERT INTO pets (name, species, breed, birth_date) VALUES (?, ?, ?, ?)");
        $stmtPet->execute([$name, $species, $breed,   !empty($birth_date) ? $birth_date : null]);

        $petId = $pdo->lastInsertId();

        // 2. Vincular o criador como "Tutor Principal" na tabela intermediária
        $stmtVinculo = $pdo->prepare("INSERT INTO pet_tutores (pet_id, user_id, access_level) VALUES (?, ?, 'tutor_principal')");
        $stmtVinculo->execute([$petId, $userId]);

        $pdo->commit();

        echo "<script>alert('Seu pet foi cadastrado com sucesso!'); window.location.href='index.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao cadastrar pet: " . $e->getMessage());
    }
}
