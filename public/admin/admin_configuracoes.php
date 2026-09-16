<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Configurações gerais (admin)
 * ==========================================================
 * Tela pequena e propositalmente simples: hoje só liga/desliga a
 * moderação de campanhas por parceiro (antes só dava pra mudar
 * editando a tabela `configuracoes` direto no banco). Se no futuro
 * mais chaves de `configuracoes` precisarem de uma tela, esse é o
 * lugar natural pra crescer — mas comecei só com o que foi pedido,
 * pra não criar um "gerenciador genérico de config" que ninguém usa.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirTipo(Auth::TIPO_ADMINISTRADOR);

$pdo = Database::conectar();

const CHAVE_MODERACAO_CAMPANHAS = 'moderacao_campanhas_ativa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Middleware::exigirCsrfValido();

    $novoValor = ($_POST['moderacao_campanhas_ativa'] ?? '0') === '1' ? '1' : '0';

    $stmt = $pdo->prepare("
        UPDATE configuracoes SET valor_config = :valor WHERE chave_config = :chave
    ");
    $stmt->execute([':valor' => $novoValor, ':chave' => CHAVE_MODERACAO_CAMPANHAS]);

    if ($stmt->rowCount() === 0) {
        // A chave ainda não existe (migration_026 não aplicada) — cria
        // na hora, pra essa tela funcionar mesmo assim.
        $pdo->prepare("
            INSERT INTO configuracoes (chave_config, valor_config, descricao)
            VALUES (:chave, :valor, 'Se \"1\", toda campanha/evento/doação nova de um parceiro entra como pendente até um administrador aprovar.')
        ")->execute([':chave' => CHAVE_MODERACAO_CAMPANHAS, ':valor' => $novoValor]);
    }

    Flash::sucesso($novoValor === '1' ? 'Moderação de campanhas ativada.' : 'Moderação de campanhas desativada.');
    header('Location: ' . Url::pagina('admin_configuracoes.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT valor_config FROM configuracoes WHERE chave_config = :chave");
$stmt->execute([':chave' => CHAVE_MODERACAO_CAMPANHAS]);
$valorAtual = $stmt->fetchColumn();

// false = a linha nem existe ainda (migration_026 não aplicada nesse banco)
$migrationAplicada = $valorAtual !== false;
$moderacaoAtiva = $migrationAplicada && (string) $valorAtual === '1';

$tituloPagina = 'Configurações gerais';

require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';

?>

<main class="container" style="margin-top:100px; margin-left:240px; padding:20px; max-width:800px;">

    <div style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">

        <h1 class="fw-bold" style="font-size:26px; margin-bottom:6px;">⚙️ Configurações gerais</h1>
        <p style="color:#7f8c8d; margin-bottom:30px;">
            Chaves globais do site (tabela <code>configuracoes</code>).
        </p>

        <?php if (!$migrationAplicada): ?>
            <div class="alert alert-warning">
                A <code>migration_026_moderacao_campanhas.sql</code> ainda não foi aplicada neste banco.
                Salvar aqui já cria a chave <code>moderacao_campanhas_ativa</code>, mas o restante da
                moderação (colunas <code>status_moderacao</code> em <code>parceiro_campanhas</code>) só
                funciona depois que a migration rodar.
            </div>
        <?php endif; ?>

        <hr>

        <h2 class="h5 fw-bold">Moderação de campanhas por parceiro</h2>
        <p class="small text-muted">
            Desligada (padrão): uma campanha/evento/doação nova já publica na hora, assim que o parceiro
            que a criou estiver aprovado. Ligada: toda publicação nova (e toda edição de uma já aprovada)
            entra como pendente e só fica visível no site depois que um administrador aprovar em
            <a href="<?= Url::pagina('admin_campanhas.php') ?>">Moderação de Campanhas</a>.
        </p>

        <form method="POST">
            <?= Csrf::campoHtml() ?>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch"
                    id="moderacao_campanhas_ativa" name="moderacao_campanhas_ativa" value="1"
                    <?= $moderacaoAtiva ? 'checked' : '' ?>>
                <label class="form-check-label" for="moderacao_campanhas_ativa">
                    Exigir aprovação de admin para campanhas
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>

    </div>

</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>

</div>

</body>

</html>
