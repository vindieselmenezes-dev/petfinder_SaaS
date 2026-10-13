<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Helpers/Csrf.php';

if (!Auth::check()) {
    Url::redirecionar(Url::pagina('login.php'));
    exit;
}

// Sair do modo suporte muda o que a sessão pode fazer: só por POST, com token.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validar($_POST['csrf_token'] ?? null)) {
    Url::redirecionar(Url::pagina('dashboard.php'));
    exit;
}

$usuarioId = (int) Auth::id();
$empresaId = (int) ($_SESSION['impersonated_empresa_id'] ?? 0);
$nomeEmpresa = Auth::nomeEmpresaSuporte();

$encerrou = Auth::encerrarModoSuporte();

if ($encerrou) {
    // A trilha de auditoria registra a saída, mas uma falha aqui nunca pode
    // prender o administrador dentro da empresa.
    try {
        $stmt = Database::conectar()->prepare("
            INSERT INTO auditoria (usuario_id, tabela, acao, registro_id, detalhes)
            VALUES (?, 'empresas', 'IMPERSONATE_FIM', ?, ?)
        ");
        $stmt->execute([$usuarioId, $empresaId, 'Encerrou o acesso de suporte na empresa: ' . $nomeEmpresa]);
    } catch (Throwable $e) {
        error_log('Auditoria da saída do modo suporte falhou: ' . $e->getMessage());
    }
}

Url::redirecionar(Url::pagina($encerrou ? 'suporte_admin.php' : 'dashboard.php'));
exit;
