<?php declare(strict_types=1); 
session_start(); 

if (!isset($_SESSION["usuario_id"]) || $_SERVER["REQUEST_METHOD"] !== "POST") { 
    die("Acesso não autorizado."); 
} 

require_once "../app/Models/Usuario.php"; 
require_once "../app/Controllers/PetController.php";
require_once "../app/Controllers/NotificacaoController.php";
require_once "../app/Helpers/Csrf.php";
$pdo = Database::conectar(); 

if (!Csrf::validar($_POST["csrf_token"] ?? null)) {
    die("Erro: token de segurança inválido ou expirado. Atualize a página e tente novamente.");
}

$petId       = (int)($_POST["pet_id"] ?? 0); 
$usuarioId   = (int)$_SESSION["usuario_id"]; 
$location    = trim($_POST["last_seen_location"] ?? ""); 
$description = trim($_POST["description"] ?? ""); 

// As coordenadas agora vêm de verdade do GPS do navegador (ver
// alerta_perdido.php). Se a pessoa negou a permissão, chegam vazias
// aqui — nesse caso publicamos o alerta mesmo assim, só sem o aviso
// automático por proximidade (não dá pra inventar uma coordenada).
$latRaw = trim($_POST["lost_latitude"] ?? "");
$lngRaw = trim($_POST["lost_longitude"] ?? "");
$lat = $latRaw !== "" ? (float)$latRaw : null;
$lng = $lngRaw !== "" ? (float)$lngRaw : null;

if ($petId <= 0 || empty($location)) { 
    die("Erro: Dados obrigatórios do sumiço não foram preenchidos."); 
} 

$petController = new PetController();
$pet = $petController->buscarPorId($petId);

if (!$pet || (int)$pet['usuario_id'] !== $usuarioId) {
    die("Erro: pet não encontrado ou você não tem permissão sobre ele.");
}

try { 
    $pdo->beginTransaction(); 

    // 1. Inserir o alerta na tabela de desaparecidos
    $stmt = $pdo->prepare("INSERT INTO pet_alertas_perdidos (pet_id, user_id, last_seen_location, lost_latitude, lost_longitude, description) VALUES (?, ?, ?, ?, ?, ?)"); 
    $stmt->execute([$petId, $usuarioId, $location, $lat, $lng, $description]); 

    // Atualiza o status do pet para 'Perdido' na tabela central de animais
    $petController->atualizarStatus($petId, 'Perdido', $usuarioId, 'Alerta de pet perdido emitido pelo tutor');

    $petName = $pet['nome'] ?? 'Animal';

    $totalNotificados = 0;

    // 2. Mecanismo de Geofencing (Raio de 5 km) usando a Fórmula de Haversine.
    // Só roda se a gente tiver uma coordenada real de onde o pet sumiu —
    // sem isso não tem como calcular distância nenhuma.
    if ($lat !== null && $lng !== null) {

        $stmtGeofence = $pdo->prepare("
            SELECT id, (6371 * ACOS( COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude)) )) AS distancia 
            FROM usuarios 
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
            HAVING distancia <= 5
        ");
        $stmtGeofence->execute([$lat, $lng, $lat]);
        $usuariosNoRaio = $stmtGeofence->fetchAll(PDO::FETCH_ASSOC);

        // 3. Dispara as notificações pros vizinhos dentro do raio
        $notificacaoController = new NotificacaoController();
        $titleNotification = "🚨 Alerta de Emergência Pet!"; 
        $messageNotification = "O pet chamado '$petName' desapareceu perto de você (no local: $location). Fique atento para ajudar!"; 

        foreach ($usuariosNoRaio as $userNoRaio) { 
            // Regra de segurança: Não enviar a notificação para o próprio dono que perdeu o bicho
            if ((int)$userNoRaio['id'] !== $usuarioId) { 
                $notificacaoController->criar(
                    (int)$userNoRaio['id'],
                    $titleNotification,
                    $messageNotification,
                    'Sistema',
                    'pets_perdidos.php'
                );
                $totalNotificados++; 
            } 
        }

    }

    $pdo->commit(); 

    $mensagemFinal = $totalNotificados > 0
        ? "ALERTA ENVIADO! $totalNotificados vizinhos num raio de 5km foram notificados."
        : "Alerta publicado! Ainda não conseguimos notificar vizinhos automaticamente (localização indisponível ou ninguém com localização salva por perto), mas o alerta já está visível pra comunidade.";

    echo "<script> 
        alert('" . addslashes($mensagemFinal) . "'); 
        window.location.href='meus_pets.php'; 
    </script>"; 
    exit;

} catch (Exception $e) { 
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Erro ao processar alerta e mensageria: " . $e->getMessage()); 
}
