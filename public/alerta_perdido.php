<?php
require_once __DIR__ . '/../app/Controllers/PetController.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';
$petController = new PetController();
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_GET['pet_id'])) {
    header("Location: index.php");
    exit();
}

$petId = (int) $_GET['pet_id'];
$usuarioId = (int) $_SESSION['usuario_id'];

// Verificar se o pet realmente pertence a esse usuário
$pet = $petController->buscarPorId($petId);

if (!$pet || (int) $pet['usuario_id'] !== $usuarioId) {
    die("Pet não encontrado ou você não tem permissão.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emitir Alerta de Pet Perdido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px 0;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }

        h2 {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn:hover {
            background: #c0392b;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .back-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .back-link a {
            color: #7f8c8d;
            text-decoration: none;
        }

        .status-localizacao {
            font-size: 13px;
            margin: -8px 0 15px 0;
            padding: 8px 10px;
            border-radius: 6px;
        }

        .status-localizacao.buscando {
            background: #fff8e1;
            color: #8a6d00;
        }

        .status-localizacao.ok {
            background: #e8f9f0;
            color: #1a8449;
        }

        .status-localizacao.erro {
            background: #fdecea;
            color: #b02a1f;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>🚨 Emitir Alerta: <?php echo htmlspecialchars($pet['nome']); ?> Sumiu</h2>

        <div id="statusLocalizacao" class="status-localizacao buscando">
            📍 Capturando a localização de onde ele foi visto pela última vez...
        </div>

        <form action="processa_alerta.php" method="POST" id="formAlerta">
            <?= Csrf::campoHtml() ?>
            <input type="hidden" name="pet_id" value="<?php echo $petId; ?>">
            <input type="hidden" name="lost_latitude" id="lost_latitude" value="">
            <input type="hidden" name="lost_longitude" id="lost_longitude" value="">

            <div class="form-group">
                <label for="last_seen_location">Último lugar visto (Endereço/Ponto de referência)</label>
                <input type="text" id="last_seen_location" name="last_seen_location" required
                    placeholder="Ex: Próximo à Praça Central, Bairro Centro">
            </div>

            <div class="form-group">
                <label for="description">Características marcantes / Detalhes adicionais</label>
                <textarea id="description" name="description" rows="3"
                    placeholder="Ex: Usava coleira vermelha, é muito dócil mas está assustado."></textarea>
            </div>

            <button type="submit" class="btn" id="btnEnviarAlerta" data-petfinder-som="alerta">Disparar Alerta na
                Comunidade</button>
        </form>
        <div class="back-link">
            <a href="index.php">⬅ Cancelar e Voltar</a>
        </div>
    </div>

    <script src="../assets/js/click-sounds.js"></script>
    <script>
        // Captura a localização real de onde o pet foi visto pela última vez.
        // Isso é o que permite o sistema avisar automaticamente quem está
        // num raio de 5km. Sem isso, o alerta ainda é publicado, só que
        // sem o aviso automático pros vizinhos.
        const statusDiv = document.getElementById('statusLocalizacao');

        if (!navigator.geolocation) {
            statusDiv.className = 'status-localizacao erro';
            statusDiv.textContent = '⚠️ Seu navegador não suporta localização. O alerta será publicado, mas sem aviso automático por proximidade.';
        } else {
            navigator.geolocation.getCurrentPosition(
                function (posicao) {
                    document.getElementById('lost_latitude').value = posicao.coords.latitude;
                    document.getElementById('lost_longitude').value = posicao.coords.longitude;
                    statusDiv.className = 'status-localizacao ok';
                    statusDiv.textContent = '✅ Localização capturada! Vizinhos num raio de 5km serão avisados automaticamente.';
                },
                function () {
                    statusDiv.className = 'status-localizacao erro';
                    statusDiv.textContent = '⚠️ Não conseguimos acessar sua localização. O alerta será publicado, mas sem aviso automático por proximidade — digite o endereço com o máximo de detalhe possível.';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
    </script>
</body>

</html>