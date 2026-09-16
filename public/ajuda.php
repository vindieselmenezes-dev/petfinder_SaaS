<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Ajuda
 * ==========================================================
 * Central de ajuda pública (link "Ajuda" no rodapé): perguntas
 * frequentes + atalho para abrir um chamado de suporte de verdade
 * (esse último exige login, já que o suporte olha o histórico da
 * pessoa).
 */

require_once __DIR__ . '/../app/bootstrap.php';

$tituloPagina = 'Ajuda';

/**
 * Uma pergunta frequente: pergunta + resposta (pode ter HTML simples).
 *
 * @var array<int, array{pergunta: string, resposta: string}>
 */
$perguntas = [
    [
        'pergunta' => 'Como cadastro um pet que se perdeu?',
        'resposta' => 'Depois de entrar na sua conta, vá em "Meus Pets" e cadastre (ou edite) o pet
            mudando o status para "Perdido". Ele passa a aparecer no mural de pets perdidos, com a
            localização que você informar, para outras pessoas ajudarem a encontrar.',
    ],
    [
        'pergunta' => 'Encontrei um animal na rua, o que eu faço?',
        'resposta' => 'Você pode cadastrar um alerta de "pet encontrado" mesmo sem saber quem é o
            tutor. Ele fica visível no mural, e caso o tutor esteja usando o PetFinder para procurar
            o pet, os dois alertas ficam próximos um do outro na busca.',
    ],
    [
        'pergunta' => 'Como funciona a adoção pela plataforma?',
        'resposta' => 'ONGs, protetores e tutores podem colocar um pet como "Para Adoção". Quem tem
            interesse entra em contato pelo perfil do pet. O PetFinder não participa da entrevista
            nem da entrega do animal — isso é combinado diretamente entre as partes.',
    ],
    [
        'pergunta' => 'Como agendo uma consulta, banho e tosa ou adestramento?',
        'resposta' => 'Na área de agendamentos você escolhe o serviço, o profissional ou clínica, e
            um horário disponível. Você recebe a confirmação por notificação e pode acompanhar tudo
            pelo seu painel.',
    ],
    [
        'pergunta' => 'Como funciona a área de parceiros (ONGs e empresas)?',
        'resposta' => 'ONGs, protetores e empresas podem se cadastrar como parceiros e publicar
            campanhas de arrecadação, eventos e pedidos de doação. Toda parceria passa por uma
            aprovação da nossa equipe antes de aparecer no site.',
    ],
    [
        'pergunta' => 'Fiz uma compra na loja, como acompanho o pedido?',
        'resposta' => 'Pelo seu painel, em "Meus Pedidos", você vê o status de cada compra feita na
            loja do PetFinder. Qualquer problema com um pedido, o caminho mais rápido é abrir um
            chamado de suporte.',
    ],
    [
        'pergunta' => 'Como excluo minha conta ou meus dados?',
        'resposta' => 'Você pode solicitar a exclusão da sua conta e dos seus dados pessoais
            entrando em contato com a gente. Veja como isso funciona na nossa
            <a href="' . 'privacidade.php' . '">Política de Privacidade</a>.',
    ],
    [
        'pergunta' => 'Não encontrei o que procurava, e agora?',
        'resposta' => 'Sem problema — fale com a gente pela página de
            <a href="' . 'contato.php' . '">Contato</a>, ou, se já tiver conta, abra um chamado de
            suporte pelo botão abaixo.',
    ],
];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ajuda - PetFinder Brasil</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= Url::asset('css/style.css') ?>">

</head>

<body style="padding-top:38px;">

    <div
        style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;">
        <button type="button"
            onclick="if(window.history.length>1){history.back();}else{window.location.href='<?= Url::raiz('index.html') ?>';}"
            style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;"
            aria-label="Voltar para a página anterior">← Voltar</button>
    </div>

    <header class="border-bottom py-3">

        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <a href="<?= Url::raiz('index.html') ?>" class="d-flex align-items-center text-decoration-none">
                <img src="<?= Url::asset('img/logo.png') ?>" alt="PetFinder Brasil" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
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
            <h1 class="fw-bold">❓ Central de Ajuda</h1>
            <p class="text-muted">Respostas rápidas para as dúvidas mais comuns.</p>
        </div>

        <div class="row justify-content-center">

            <div class="col-lg-8">

                <div class="accordion" id="acordeaoAjuda">

                    <?php foreach ($perguntas as $indice => $item): ?>

                        <?php $idItem = 'pergunta' . $indice; ?>

                        <div class="accordion-item">

                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $indice > 0 ? 'collapsed' : '' ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#<?= $idItem ?>">
                                    <?= htmlspecialchars($item['pergunta']) ?>
                                </button>
                            </h2>

                            <div id="<?= $idItem ?>"
                                class="accordion-collapse collapse <?= $indice === 0 ? 'show' : '' ?>"
                                data-bs-parent="#acordeaoAjuda">
                                <div class="accordion-body text-muted">
                                    <?= $item['resposta'] ?>
                                </div>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <!-- ============ AINDA PRECISA DE AJUDA ============ -->

                <div class="text-center mt-5 p-4 rounded-4" style="background:#f8f9fa;">

                    <h2 class="h5 fw-bold">Não resolveu seu problema?</h2>

                    <p class="text-muted">
                        Fale direto com a nossa equipe — pra assuntos ligados à sua conta, um
                        chamado de suporte é o caminho mais rápido.
                    </p>

                    <div class="d-flex flex-wrap justify-content-center gap-2">

                        <?php if (Auth::check()): ?>
                            <a href="<?= Url::pagina('novo_chamado.php') ?>" class="btn btn-success">
                                <i class="bi bi-life-preserver"></i> Abrir um chamado
                            </a>
                        <?php else: ?>
                            <a href="<?= Url::pagina('login.php') ?>" class="btn btn-success">
                                <i class="bi bi-life-preserver"></i> Entrar para abrir um chamado
                            </a>
                        <?php endif; ?>

                        <a href="<?= Url::pagina('contato.php') ?>" class="btn btn-outline-primary">
                            Fale conosco
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </main>

    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            © <?= date('Y') ?> PetFinder Brasil ·
            <a href="<?= Url::raiz('index.html') ?>" class="text-light">Voltar para a home</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
