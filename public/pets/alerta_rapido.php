<?php

declare(strict_types=1);

/**
 * Alerta Pet rápido (usado pelo cartão "Alerta Pet" da página inicial).
 *
 *  GET  ?acao=contexto  -> { logado, csrf, pets: [{id, nome}] }
 *  POST acao=achei      -> registra um pet ENCONTRADO (foto + descrição + GPS)
 *                          e avisa quem está num raio de 5 km.
 *
 * "Perdi meu pet" continua usando processa_alerta.php (já existente).
 * Sempre responde JSON.
 */

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Controllers/PetController.php';
require_once __DIR__ . '/../../app/Controllers/NotificacaoController.php';
require_once __DIR__ . '/../../app/Helpers/Csrf.php';
require_once __DIR__ . '/../../app/Models/RateLimiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Guarda a foto em uploads/pets. Com a extensão GD ativa: reduz (máx. 1280 px),
 * corrige a rotação do celular e regrava em JPEG (isso descarta EXIF/GPS).
 * Sem GD (comum em XAMPP/hospedagens): guarda o arquivo já validado como veio —
 * o navegador costuma ter reduzido e limpado a foto antes de enviar.
 */
function salvarFotoReduzida(array $arquivo, string $tipoReal): ?string
{
    $diretorio = dirname(__DIR__, 2) . '/uploads/pets';
    if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true)) {
        error_log('[alerta_rapido] não foi possível criar ' . $diretorio);
        return null;
    }

    $imagem = function_exists('imagecreatefromstring')
        ? @imagecreatefromstring((string) file_get_contents($arquivo['tmp_name']))
        : false;

    if (!$imagem) {
        error_log('[alerta_rapido] GD indisponível ou imagem não decodificada; guardando o arquivo original.');
        $extensao = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$tipoReal] ?? null;
        if ($extensao === null) {
            return null;
        }
        $nome = 'pet_' . bin2hex(random_bytes(8)) . '.' . $extensao;

        return move_uploaded_file($arquivo['tmp_name'], $diretorio . '/' . $nome) ? $nome : null;
    }

    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($arquivo['tmp_name']);
        $giro = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;
        if ($giro !== 0) {
            $girada = imagerotate($imagem, $giro, 0);
            if ($girada) {
                imagedestroy($imagem);
                $imagem = $girada;
            }
        }
    }

    $largura = imagesx($imagem);
    $altura = imagesy($imagem);
    $maximo = 1280;

    if ($largura > $maximo || $altura > $maximo) {
        $escala = $maximo / max($largura, $altura);
        $nova = imagecreatetruecolor((int) round($largura * $escala), (int) round($altura * $escala));
        imagecopyresampled($nova, $imagem, 0, 0, 0, 0, imagesx($nova), imagesy($nova), $largura, $altura);
        imagedestroy($imagem);
        $imagem = $nova;
    }

    $nome = 'pet_' . bin2hex(random_bytes(8)) . '.jpg';
    $ok = imagejpeg($imagem, $diretorio . '/' . $nome, 82);
    imagedestroy($imagem);

    if (!$ok) {
        error_log('[alerta_rapido] imagejpeg falhou em ' . $diretorio);
    }

    return $ok ? $nome : null;
}

$usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

// ------------------------------------------------------------------ contexto
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pets = [];

    if ($usuarioId > 0) {
        foreach ((new PetController())->listarPorUsuario($usuarioId) as $pet) {
            if (in_array($pet['status'] ?? '', ['Perdido', 'Encontrado', 'Adotado'], true)) {
                continue;
            }
            $pets[] = ['id' => (int) $pet['id'], 'nome' => (string) $pet['nome']];
        }
    }

    responder(200, [
        'logado' => $usuarioId > 0,
        'csrf' => $usuarioId > 0 ? Csrf::gerarToken() : null,
        'vapid' => $usuarioId > 0 ? (Env::get('PUSH_VAPID_PUBLIC_KEY') ?: null) : null,
        'pets' => $pets,
    ]);
}

// ------------------------------------------------------------- achei um pet
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['acao'] ?? '') !== 'achei') {
    responder(405, ['mensagem' => 'Pedido inválido.']);
}

if ($usuarioId <= 0) {
    responder(401, ['erro' => 'login', 'mensagem' => 'Entre na sua conta para publicar o alerta.']);
}

if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
    responder(403, ['mensagem' => 'Sessão expirada. Atualize a página e tente de novo.']);
}

// No máximo 5 alertas por hora por pessoa (evita alerta falso em massa).
if (!(new RateLimiter())->permitido('alerta_achei', 5, 60, 60, 'u' . $usuarioId)) {
    responder(429, ['mensagem' => 'Você enviou muitos alertas seguidos. Tente de novo mais tarde.']);
}

$foto = $_FILES['foto'] ?? null;
if (!$foto || ($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $foto['size'] > 10 * 1024 * 1024) {
    responder(422, ['mensagem' => 'Envie uma foto do pet (até 10 MB).']);
}

$tipoReal = (new finfo(FILEINFO_MIME_TYPE))->file($foto['tmp_name']);
if (!in_array($tipoReal, ['image/jpeg', 'image/png', 'image/webp'], true) || !@getimagesize($foto['tmp_name'])) {
    responder(422, ['mensagem' => 'A foto precisa ser JPG, PNG ou WebP.']);
}

$especieId = match ($_POST['especie'] ?? '') {
    'cachorro' => 1,
    'gato' => 2,
    default => max(3, min(10, (int) ($_POST['especie_id'] ?? 10))),
};

$descricao = mb_substr(trim((string) ($_POST['descricao'] ?? '')), 0, 500);

$lat = is_numeric($_POST['latitude'] ?? null) ? (float) $_POST['latitude'] : null;
$lng = is_numeric($_POST['longitude'] ?? null) ? (float) $_POST['longitude'] : null;
if ($lat === null || $lng === null || abs($lat) > 90 || abs($lng) > 180) {
    $lat = $lng = null;
}

try {
    $pdo = Database::conectar();

    // Raça: "Vira-lata (SRD)" / "Sem raça definida", conforme a espécie.
    $stmt = $pdo->prepare(
        "SELECT id FROM racas WHERE especie_id = ? AND ativo = 1
         ORDER BY (nome LIKE '%SRD%' OR nome LIKE 'Sem raça%') DESC, id LIMIT 1"
    );
    $stmt->execute([$especieId]);
    $racaId = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT nome FROM especies WHERE id = ?');
    $stmt->execute([$especieId]);
    $nomeEspecie = (string) ($stmt->fetchColumn() ?: 'pet');

    $arquivo = salvarFotoReduzida($foto, $tipoReal);
    if ($racaId <= 0) {
        error_log('[alerta_rapido] nenhuma raça ativa para a espécie ' . $especieId);
        responder(500, ['mensagem' => 'Não foi possível publicar o alerta agora. Tente de novo.']);
    }
    if ($arquivo === null) {
        responder(500, ['mensagem' => 'Não foi possível guardar a foto no servidor agora. Tente de novo.']);
    }

    if ($lat !== null) {
        $descricao .= ($descricao !== '' ? "\n\n" : '')
            . 'Local aproximado: https://www.google.com/maps?q=' . round($lat, 5) . ',' . round($lng, 5);
    }

    $cadastrado = (new PetController())->cadastrar([
        'usuario_id' => $usuarioId,
        'nome' => 'Sem nome (encontrado)',
        'especie_id' => $especieId,
        'raca_id' => $racaId,
        'sexo' => 'Indefinido',
        'status' => 'Encontrado',
        'observacoes' => $descricao,
        'foto' => $arquivo,
    ], [$arquivo]);

    if (!$cadastrado) {
        @unlink(dirname(__DIR__, 2) . '/uploads/pets/' . $arquivo);
        responder(500, ['mensagem' => 'Não foi possível publicar o alerta agora. Tente de novo.']);
    }

    // Avisa quem tem localização salva num raio de 5 km (máx. 200 pessoas).
    $notificados = 0;
    if ($lat !== null) {
        $stmt = $pdo->prepare(
            "SELECT id, (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?))
                    + SIN(RADIANS(?)) * SIN(RADIANS(latitude)))) AS distancia
             FROM usuarios
             WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND id <> ?
             HAVING distancia <= 5
             LIMIT 200"
        );
        $stmt->execute([$lat, $lng, $lat, $usuarioId]);

        $notificacoes = new NotificacaoController();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $vizinho) {
            $notificacoes->criar(
                (int) $vizinho['id'],
                'Pet encontrado perto de você',
                'Alguém encontrou um animal (' . mb_strtolower($nomeEspecie) . ') perto de você. Veja se é o seu.',
                'Sistema',
                'pets_encontrados.php'
            );
            $notificados++;
        }
    }

    responder(200, [
        'ok' => true,
        'notificados' => $notificados,
        'url' => Url::pagina('pets_encontrados.php'),
        'mensagem' => $notificados > 0
            ? "Alerta publicado! $notificados pessoas perto de você foram avisadas."
            : 'Alerta publicado! Ele já aparece em "Pets Encontrados".',
    ]);
} catch (Throwable $erro) {
    error_log('[alerta_rapido] ' . $erro->getMessage());
    responder(500, ['mensagem' => 'Não foi possível publicar o alerta agora. Tente de novo.']);
}
