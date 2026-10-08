<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Contato
 * ==========================================================
 * Página pública de contato (link "Contato" no rodapé). Funciona
 * tanto para visitantes sem conta quanto para usuários logados.
 *
 * A mensagem é: salva no banco (mensagens_contato), notifica todos
 * os administradores pelo sino de notificações, e também vai para
 * o "e-mail" configurado em EMAIL_CONTATO (o Mailer sempre grava um
 * log em /logs/emails.log, mesmo sem SMTP configurado).
 */

require_once __DIR__ . '/../app/bootstrap.php';

$mensagemModel = new MensagemContato();
$rateLimiter = new RateLimiter();

$erros = [];
$enviado = false;
$bloqueado = false;
$pedidoBanhoTosa = (($_POST['pedido'] ?? $_GET['pedido'] ?? '') === 'banho_e_tosa');
$dadosPedido = [
    'pet_nome' => '',
    'servico' => '',
    'data_desejada' => '',
    'periodo' => '',
    'detalhes' => '',
];

$form = [
    'nome' => Auth::check() ? Auth::nome() : '',
    'email' => Auth::check() ? Auth::email() : '',
    'assunto' => '',
    'mensagem' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Middleware::exigirCsrfValido();

    // No máximo 5 mensagens por IP a cada 10 minutos; estourando isso,
    // bloqueia o IP por 30 minutos. Roda antes de validar qualquer
    // campo, pra não gastar consulta/e-mail com quem está floodando.
    if (!$rateLimiter->permitido('contato', 5, 10, 30)) {
        $bloqueado = true;
        $erros[] = 'Muitas mensagens enviadas em pouco tempo. Tente novamente em '
            . $rateLimiter->minutosRestantes('contato') . ' minuto(s).';
    }

    // Honeypot: campo escondido no formulário que só um robô preencheria.
    $armadilha = trim($_POST['site'] ?? '');

    $form = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'assunto' => trim($_POST['assunto'] ?? ''),
        'mensagem' => trim($_POST['mensagem'] ?? ''),
    ];
    $dadosPedido = [
        'pet_nome' => trim($_POST['pet_nome'] ?? ''),
        'servico' => trim($_POST['servico'] ?? ''),
        'data_desejada' => trim($_POST['data_desejada'] ?? ''),
        'periodo' => trim($_POST['periodo'] ?? ''),
        'detalhes' => trim($_POST['detalhes'] ?? ''),
    ];

    if ($bloqueado) {
        // Não processa nem finge sucesso: a pessoa real que caiu no limite
        // precisa ver a mensagem de erro (já em $erros) e tentar depois.
    } elseif ($armadilha !== '') {
        // Preencheu o honeypot: finge sucesso, não grava nada.
        $enviado = true;
        $form = ['nome' => '', 'email' => '', 'assunto' => '', 'mensagem' => ''];
        $dadosPedido = ['pet_nome' => '', 'servico' => '', 'data_desejada' => '', 'periodo' => '', 'detalhes' => ''];
    } else {

        if (mb_strlen($form['nome']) < 2) {
            $erros[] = 'Informe seu nome.';
        }

        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um e-mail válido.';
        }

        if ($pedidoBanhoTosa) {
            if (mb_strlen($dadosPedido['pet_nome']) < 2 || mb_strlen($dadosPedido['pet_nome']) > 150) {
                $erros[] = 'Informe o nome do pet.';
            }

            if (!in_array($dadosPedido['servico'], ['Banho', 'Tosa', 'Banho e tosa completos', 'Higiene completa'], true)) {
                $erros[] = 'Selecione o serviço desejado.';
            }

            $dataDesejada = DateTimeImmutable::createFromFormat('!Y-m-d', $dadosPedido['data_desejada']);
            if (!$dataDesejada || $dataDesejada->format('Y-m-d') !== $dadosPedido['data_desejada'] || $dadosPedido['data_desejada'] < date('Y-m-d')) {
                $erros[] = 'Selecione uma data válida a partir de hoje.';
            }

            if (!in_array($dadosPedido['periodo'], ['', 'Manhã', 'Tarde', 'Noite'], true)) {
                $erros[] = 'Selecione um período válido.';
            }
        } elseif (mb_strlen($form['mensagem']) < 10) {
            $erros[] = 'Escreva uma mensagem um pouco mais detalhada (mínimo 10 caracteres).';
        }

        if (empty($erros)) {
            $assuntoContato = $pedidoBanhoTosa ? 'Pedido de banho e tosa' : $form['assunto'];
            $mensagemContato = $form['mensagem'];

            if ($pedidoBanhoTosa) {
                $mensagemContato = implode("\n", [
                    'Pedido geral de banho e tosa.',
                    'Pet: ' . $dadosPedido['pet_nome'],
                    'Serviço: ' . $dadosPedido['servico'],
                    'Data desejada: ' . $dadosPedido['data_desejada'],
                    'Período: ' . ($dadosPedido['periodo'] ?: 'Sem preferência'),
                ]);
                if ($dadosPedido['detalhes'] !== '') {
                    $mensagemContato .= "\nObservações: " . $dadosPedido['detalhes'];
                }
            }

            $id = $mensagemModel->salvar([
                'usuario_id' => Auth::check() ? Auth::id() : null,
                'nome' => $form['nome'],
                'email' => $form['email'],
                'assunto' => $assuntoContato,
                'mensagem' => $mensagemContato,
            ]);

            if ($id !== false) {

                // Avisa quem administra o site, pelo sino de notificações.
                $notificacaoModel = new Notificacao();
                foreach ((new Usuario())->listarIdsAdministradores() as $adminId) {
                    $notificacaoModel->criar(
                        (int) $adminId,
                        'Nova mensagem de contato',
                        $form['nome'] . ' enviou uma mensagem' . ($assuntoContato !== '' ? ': ' . $assuntoContato : '.'),
                        'Sistema',
                        Url::pagina('admin_contatos.php')
                    );
                }

                Mailer::enviar(
                    getenv('EMAIL_CONTATO') ?: 'contato@petfinder.local',
                    'Nova mensagem de contato: ' . ($assuntoContato ?: 'Sem assunto'),
                    '<p><strong>Nome:</strong> ' . htmlspecialchars($form['nome']) . '</p>'
                    . '<p><strong>E-mail:</strong> ' . htmlspecialchars($form['email']) . '</p>'
                    . '<p><strong>Mensagem:</strong><br>' . nl2br(htmlspecialchars($mensagemContato)) . '</p>'
                );

                $enviado = true;
                $form = ['nome' => '', 'email' => '', 'assunto' => '', 'mensagem' => ''];
                $dadosPedido = ['pet_nome' => '', 'servico' => '', 'data_desejada' => '', 'periodo' => '', 'detalhes' => ''];

            } else {
                $erros[] = 'Não foi possível enviar sua mensagem agora. Tente novamente em instantes.';
            }
        }
    }
}

$tituloPagina = 'Contato';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contato - EcoSistemPet</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" integrity="sha384-CK2SzKma4jA5H/MXDUU7i1TqZlCFaD4T01vtyDFvPlD97JQyS+IsSh1nI2EFbpyk" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= Url::asset('css/style.css') ?>">

</head>

<body style="padding-top:38px;">

    <div
        style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;">
        <button type="button"
            onclick="if(window.history.length>1){history.back();}else{window.location.href='<?= Url::raiz('index.html') ?>';}"
            style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;"
            aria-label="← Voltar para a página anterior">← Voltar</button>
    </div>

    <header class="border-bottom py-3">

        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <a href="<?= Url::raiz('index.html') ?>" class="d-flex align-items-center text-decoration-none">
                <img src="<?= Url::asset('img/logo.png') ?>" alt="EcoSistemPet" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">EcoSistemPet</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>

            <div class="d-flex gap-2 align-items-center">
                <?php if (Auth::check()): ?>
                    <a href="<?= Url::pagina('dashboard.php') ?>" class="btn btn-primary">Meu painel</a>
                <?php else: ?>
                    <a href="<?= Url::pagina('login.php') ?>" class="btn btn-primary">Entrar</a>
                    <a href="<?= Url::pagina('cadastro.php') ?>" class="btn btn-outline-primary">Cadastrar</a>
                <?php endif; ?>
            </div>

        </div>

    </header>

    <main class="container my-5">

        <div class="text-center mb-5">
            <h1 class="fw-bold"><?= $pedidoBanhoTosa ? 'Solicitar banho e tosa' : '📬 Fale com a gente' ?></h1>
            <p class="text-muted">
                <?= $pedidoBanhoTosa
                    ? 'Nossa equipe vai procurar um prestador e responder pelo e-mail informado. O envio não confirma o agendamento.'
                    : 'Dúvidas, sugestões, parcerias ou imprensa — escolha o assunto e mande sua mensagem.' ?>
            </p>
        </div>

        <div class="row g-4">

            <!-- ============ CANAIS DIRETOS ============ -->

            <div class="col-lg-4">

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 fw-bold"><i class="bi bi-envelope text-primary"></i> E-mail</h2>
                        <p class="mb-0 small">
                            <a href="mailto:contato@petfinder.com.br">contato@petfinder.com.br</a>
                        </p>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 fw-bold"><i class="bi bi-whatsapp text-success"></i> WhatsApp</h2>
                        <p class="mb-0 small">
                            Atendimento de segunda a sexta, das 9h às 18h.
                        </p>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 fw-bold"><i class="bi bi-question-circle text-primary"></i> Já tem conta?</h2>
                        <p class="small mb-2">
                            Para suporte sobre pedidos, pets ou agendamentos, abrir um chamado é
                            mais rápido — nossa equipe já vê seu histórico.
                        </p>
                        <?php if (Auth::check()): ?>
                            <a href="<?= Url::pagina('novo_chamado.php') ?>" class="btn btn-outline-primary btn-sm w-100">
                                Abrir um chamado
                            </a>
                        <?php else: ?>
                            <a href="<?= Url::pagina('login.php') ?>" class="btn btn-outline-primary btn-sm w-100">
                                Entrar para abrir um chamado
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- ============ FORMULÁRIO ============ -->

            <div class="col-lg-8">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <?php if ($enviado): ?>

                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i>
                                Mensagem enviada! Nossa equipe vai responder no e-mail informado.
                            </div>

                        <?php endif; ?>

                        <?php if (!empty($erros)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($erros as $erro): ?>
                                        <li><?= htmlspecialchars($erro) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="row g-3">

                            <?= Csrf::campoHtml() ?>
                            <?php if ($pedidoBanhoTosa): ?>
                                <input type="hidden" name="pedido" value="banho_e_tosa">
                            <?php endif; ?>

                            <!-- Honeypot: invisível para pessoas, tentador para robôs de spam -->
                            <div style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;"
                                aria-hidden="true">
                                <label for="site">Não preencha este campo</label>
                                <input type="text" name="site" id="site" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="nome">Nome *</label>
                                <input type="text" name="nome" id="nome" class="form-control" required maxlength="150"
                                    value="<?= htmlspecialchars($form['nome']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">E-mail *</label>
                                <input type="email" name="email" id="email" class="form-control" required
                                    value="<?= htmlspecialchars($form['email']) ?>">
                            </div>

                            <?php if ($pedidoBanhoTosa): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="pet_nome">Nome do pet *</label>
                                    <input type="text" name="pet_nome" id="pet_nome" class="form-control" required
                                        maxlength="150" value="<?= htmlspecialchars($dadosPedido['pet_nome']) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="servico">Serviço desejado *</label>
                                    <select name="servico" id="servico" class="form-select" required>
                                        <option value="">Selecione</option>
                                        <?php foreach (['Banho', 'Tosa', 'Banho e tosa completos', 'Higiene completa'] as $opcao): ?>
                                            <option value="<?= htmlspecialchars($opcao) ?>" <?= $dadosPedido['servico'] === $opcao ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($opcao) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="data_desejada">Data desejada *</label>
                                    <input type="date" name="data_desejada" id="data_desejada" class="form-control"
                                        min="<?= date('Y-m-d') ?>" required
                                        value="<?= htmlspecialchars($dadosPedido['data_desejada']) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="periodo">Período preferido</label>
                                    <select name="periodo" id="periodo" class="form-select">
                                        <option value="">Sem preferência</option>
                                        <?php foreach (['Manhã', 'Tarde', 'Noite'] as $opcao): ?>
                                            <option value="<?= htmlspecialchars($opcao) ?>" <?= $dadosPedido['periodo'] === $opcao ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($opcao) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="detalhes">Observações</label>
                                    <textarea name="detalhes" id="detalhes" class="form-control" rows="4"
                                        placeholder="Porte do pet, necessidades especiais ou outras informações"><?= htmlspecialchars($dadosPedido['detalhes']) ?></textarea>
                                </div>
                            <?php else: ?>
                                <div class="col-12">
                                    <label class="form-label" for="assunto">Assunto</label>
                                    <select name="assunto" id="assunto" class="form-select">
                                        <?php
                                        $opcoesAssunto = ['' => 'Selecione (opcional)', 'Dúvida' => 'Dúvida', 'Sugestão' => 'Sugestão', 'Parceria' => 'Quero ser parceiro', 'Imprensa' => 'Imprensa', 'Outro' => 'Outro'];
                                        foreach ($opcoesAssunto as $valor => $rotulo):
                                            ?>
                                            <option value="<?= htmlspecialchars($valor) ?>" <?= $form['assunto'] === $valor ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($rotulo) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="mensagem">Mensagem *</label>
                                    <textarea name="mensagem" id="mensagem" class="form-control" rows="5" required
                                        placeholder="Conte com detalhes o que você precisa..."><?= htmlspecialchars($form['mensagem']) ?></textarea>
                                </div>
                            <?php endif; ?>

                            <div class="col-12">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-send-fill"></i>
                                    <?= $pedidoBanhoTosa ? 'Enviar pedido à equipe' : 'Enviar mensagem' ?>
                                </button>
                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </main>

    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            © <?= date('Y') ?> EcoSistemPet ·
            <a href="<?= Url::raiz('index.html') ?>" class="text-light">Voltar para a home</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>

</body>

</html>
