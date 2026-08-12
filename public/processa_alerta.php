<?php declare(strict_types=1); 
session_start(); 

if (!isset($_SESSION["usuario_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") { 
    die("Acesso não autorizado."); 
} 

// Correção da Conexão: Usa o padrão unificado do ecossistema atual
require_once "../app/Models/Usuario.php"; 
$pdo = Database::conectar(); 

$petId       = (int)($_POST["pet_id"] ?? 0); 
$userId      = (int)$_SESSION["usuario_id"]; 
$location    = trim($_POST["last_seen_location"] ?? ""); 
$description = trim($_POST["description"] ?? ""); 

// Correção das Coordenadas: Se o formulário não enviar, assume o padrão fixo de teste do PRD
$lat = isset($_POST["lost_latitude"]) ? (float)$_POST["lost_latitude"] : -23.553000; 
$lng = isset($_POST["lost_longitude"]) ? (float)$_POST["lost_longitude"] : -46.635000; 

if ($petId <= 0 || empty($location)) { 
    die("Erro: Dados obrigatórios do sumiço não foram preenchidos."); 
} 

try { 
    $pdo->beginTransaction(); 

    // 1. Inserir o alerta na tabela de desaparecidos
    $stmt = $pdo->prepare("INSERT INTO pet_alertas_perdidos (pet_id, user_id, last_seen_location, lost_latitude, lost_longitude, description) VALUES (?, ?, ?, ?, ?, ?)"); 
    $stmt->execute([$petId, $userId, $location, $lat, $lng, $description]); 

    // Atualiza o status do pet para 'Perdido' na tabela central de animais
    $stmtUpdatePet = $pdo->prepare("UPDATE pets SET status = 'Perdido' WHERE id = ?");
    $stmtUpdatePet->execute([$petId]);

    // Buscar o nome do pet para colocar na mensagem da notificação
    $stmtPetName = $pdo->prepare("SELECT nome FROM pets WHERE id = ?"); 
    $stmtPetName->execute([$petId]); 
    $petName = $stmtPetName->fetchColumn() ?: "Animal"; 

    // 2. REGRA DO PRD: Mecanismo de Geofencing (Raio de 5 km) usando a Fórmula de Haversine na tabela usuarios
    $stmtGeofence = $pdo->prepare("
        SELECT id, (6371 * ACOS( COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude)) )) AS distancia 
        FROM usuarios 
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
        HAVING distancia <= 5
    ");
    $stmtGeofence->execute([$lat, $lng, $lat]);
    $usuariosNoRaio = $stmtGeofence->fetchAll(PDO::FETCH_ASSOC);

    // 3. ENGENHARIA DE MENSAGERIA: Disparar as notificações em massa no banco
    $titleNotification = "🚨 Alerta de Emergência Pet!"; 
    $messageNotification = "O pet chamado '$petName' desapareceu perto de você (no local: $location). Fique atento para ajudar!"; 
    
    $stmtNotify = $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, status) VALUES (?, ?, ?, 'Não Lida')"); 
    $totalNotificados = 0; 

    foreach ($usuariosNoRaio as $userNoRaio) { 
        // Regra de segurança: Não enviar a notificação para o próprio dono que perdeu o bicho
        if ((int)$userNoRaio['id'] !== $userId) { 
            $stmtNotify->execute([(int)$userNoRaio['id'], $titleNotification, $messageNotification]); 
            $totalNotificados++; 
        } 
    } 

    $pdo->commit(); 

    echo "<script> 
        alert('ALERTA ENVIADO! O sistema acionou a fila e inseriu " . $totalNotificados . " notificações push em tempo real na conta dos vizinhos pelo raio de Haversine.'); 
        window.location.href='meus_pets.php'; 
    </script>"; 
    exit;

} catch (Exception $e) { 
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Erro ao processar alerta e mensageria: " . $e->getMessage()); 
}
