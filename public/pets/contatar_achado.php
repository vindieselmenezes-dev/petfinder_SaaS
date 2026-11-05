<?php

declare(strict_types=1);

/**
 * ==========================================================
 * EcoSistemPet — Falar com quem achou o pet
 * ==========================================================
 * Quem reconhece um pet em "Pets Encontrados" escreve uma mensagem aqui. O
 * sistema abre (ou reaproveita) uma conversa no chat do app com quem publicou o
 * alerta e avisa essa pessoa. Telefone e endereço de ninguém são mostrados.
 */

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Models/Conversa.php';
require_once __DIR__ . '/../../app/Controllers/ConversaController.php';
require_once __DIR__ . '/../../app/Models/RateLimiter.php';

Middleware::exigirLogin();

$usuarioId = (int) Auth::id();
$petId = (int) ($_GET['pet_id'] ?? $_POST['pet_id'] ?? 0);
$listaUrl = Url::pagina('pets_encontrados.php');
$pdo = Database::conectar();

$stmt = $pdo->prepare(
    "SELECT p.id, p.usuario_id, p.foto, e.nome AS especie
     FROM pets p
     LEFT JOIN especies e ON e.id = p.especie_id
     WHERE p.id = ? AND p.status = 'Encontrado'"
);
$stmt->execute([$petId]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    Flash::erro('Esse alerta não está mais disponível.');
    header('Location: ' . $listaUrl);
    exit;
}

if ((int) $pet['usuario_id'] === $usuarioId) {
    Flash::aviso('Esse alerta foi publicado por você.');
    header('Location: ' . $listaUrl);
    exit;
}

$especie = (string) ($pet['especie'] ?? 'Pet');
$assunto = 'Pet encontrado #' . $petId . ' (' . $especie . ')';
$textoPadrao = 'Olá! Acho que o pet que você encontrou (alerta #' . $petId . ') é meu. '
    . 'Posso te passar informações para confirmar?';
$mensagem = $textoPadrao;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Middleware::exigirCsrfValido();
    $mensagem = trim((string) ($_POST['mensagem'] ?? ''));

    if (mb_strlen($mensagem) < 5) {
        Flash::erro('Escreva uma mensagem com pelo menos 5 caracteres.');
    } elseif (mb_strlen($mensagem) > 1000) {
        Flash::erro('A mensagem pode ter no máximo 1000 caracteres.');
    } elseif (!(new RateLimiter())->permitido('contato_achado', 10, 60, 60, 'u' . $usuarioId)) {
        Flash::erro('Você enviou muitas mensagens seguidas. Tente de novo mais tarde.');
    } else {
        try {
            // Reaproveita a conversa aberta com a mesma pessoa sobre o mesmo alerta.
            $busca = $pdo->prepare(
                "SELECT id FROM conversas
                 WHERE usuario_origem = ? AND usuario_destino = ? AND assunto = ? AND status = 'Aberta'
                 ORDER BY id DESC LIMIT 1"
            );
            $busca->execute([$usuarioId, (int) $pet['usuario_id'], $assunto]);
            $conversaId = (int) $busca->fetchColumn();

            if ($conversaId <= 0) {
                $conversaId = (new Conversa())->criar($usuarioId, (int) $pet['usuario_id'], $assunto);
            }

            $resultado = (new ConversaController())->enviarMensagem($conversaId, $usuarioId, $mensagem);

            if (!empty($resultado['sucesso'])) {
                Flash::sucesso('Mensagem enviada! Quem achou o pet foi avisado.');
                header('Location: ' . Url::pagina('conversa.php') . '?id=' . $conversaId);
                exit;
            }

            Flash::erro($resultado['erro'] ?? 'Não foi possível enviar agora.');
        } catch (Throwable $erro) {
            error_log('[contatar_achado] ' . $erro->getMessage());
            Flash::erro('Não foi possível enviar agora. Tente novamente.');
        }
    }
}

$tituloPagina = 'Falar com quem achou o pet';
require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';
?>
<main class="container" style="margin-top:100px; padding:20px; max-width:640px;">
    <div class="painel-caixa" style="background:#fff; padding:24px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,.05);">
        <h1 class="fw-bold" style="font-size:24px;">💬 Falar com quem achou</h1>
        <div class="d-flex align-items-center gap-3 my-3">
            <?php if (Foto::existe($pet['foto'], 'pets')): ?>
                <img src="<?= htmlspecialchars(Foto::url($pet['foto'], 'pets')) ?>" alt="Foto do pet encontrado"
                    width="90" height="90" style="object-fit:cover; border-radius:10px;">
            <?php endif; ?>
            <div>
                <strong><?= htmlspecialchars($especie) ?> encontrado</strong>
                <div class="small text-muted">Alerta #<?= $petId ?></div>
            </div>
        </div>

        <p class="small text-muted">
            A conversa acontece aqui, dentro do app. Seu telefone e seu endereço <strong>não</strong> são mostrados
            a quem achou. Conte algo que ajude a confirmar que o pet é seu (nome, marcas, coleira).
        </p>

        <form method="POST">
            <?= Csrf::campoHtml() ?>
            <input type="hidden" name="pet_id" value="<?= $petId ?>">
            <label for="mensagem" class="form-label fw-semibold">Sua mensagem</label>
            <textarea id="mensagem" name="mensagem" rows="5" maxlength="1000" class="form-control mb-3" required><?= htmlspecialchars($mensagem) ?></textarea>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">Enviar mensagem</button>
                <a href="<?= htmlspecialchars($listaUrl) ?>" class="btn btn-outline-secondary">Voltar</a>
            </div>
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>
</div>
</body>
</html>
