<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Política de Privacidade
 * ==========================================================
 * Conteúdo-modelo, no espírito da LGPD (Lei 13.709/2018). Os trechos
 * marcados com [ ] são dados jurídicos reais (razão social, CNPJ,
 * endereço, encarregado de dados) que precisam ser preenchidos pelo
 * responsável pelo PetFinder Brasil antes da publicação — não são
 * inventados aqui de propósito. O ideal é ter um advogado revisando
 * antes de publicar oficialmente.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$tituloPagina = 'Política de Privacidade';
$dataAtualizacao = '15 de setembro de 2026';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Política de Privacidade - PetFinder Brasil</title>

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

        </div>

    </header>

    <main class="container my-5">

        <div class="row justify-content-center">

            <div class="col-lg-8">

                <h1 class="fw-bold mb-1">Política de Privacidade</h1>
                <p class="text-muted mb-5">Última atualização: <?= $dataAtualizacao ?></p>

                <p>
                    Esta política explica como o <strong>PetFinder Brasil</strong> coleta, usa,
                    armazena e protege os dados pessoais de quem usa a plataforma, em conformidade
                    com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018 — LGPD).
                </p>

                <h2 class="h5 fw-bold mt-4">1. Quem somos</h2>
                <p>
                    O PetFinder Brasil é operado por <strong>[razão social a definir]</strong>,
                    inscrita no CNPJ sob o nº <strong>[CNPJ a definir]</strong>, com sede em
                    <strong>[endereço a definir]</strong>. Para qualquer assunto relacionado a
                    dados pessoais, você pode falar com nosso encarregado de dados (DPO) pelo
                    e-mail <a href="mailto:privacidade@petfinder.com.br">privacidade@petfinder.com.br</a>.
                </p>

                <h2 class="h5 fw-bold mt-4">2. Quais dados coletamos</h2>
                <ul>
                    <li><strong>Dados de cadastro:</strong> nome, e-mail, telefone e senha (armazenada de forma criptografada).</li>
                    <li><strong>Dados de perfil:</strong> foto, endereço e localização, quando você opta por informá-los.</li>
                    <li><strong>Dados sobre pets:</strong> nome, espécie, raça, fotos e status (perdido, encontrado, para adoção, adotado).</li>
                    <li><strong>Dados de uso da plataforma:</strong> agendamentos, pedidos na loja, mensagens de suporte e avaliações que você publica.</li>
                    <li><strong>Dados técnicos:</strong> endereço IP, tipo de dispositivo e cookies, usados para segurança e para o funcionamento do site.</li>
                </ul>

                <h2 class="h5 fw-bold mt-4">3. Para que usamos seus dados</h2>
                <ul>
                    <li>Viabilizar o cadastro e o funcionamento da sua conta;</li>
                    <li>Exibir e divulgar pets perdidos, encontrados e disponíveis para adoção;</li>
                    <li>Processar agendamentos de serviços e pedidos feitos na loja;</li>
                    <li>Enviar notificações relevantes (status de pedidos, respostas de suporte, novidades — estas últimas só se você se inscrever);</li>
                    <li>Prevenir fraudes e garantir a segurança da plataforma;</li>
                    <li>Cumprir obrigações legais e regulatórias.</li>
                </ul>

                <h2 class="h5 fw-bold mt-4">4. Com quem compartilhamos</h2>
                <p>
                    Empresas, ONGs, clínicas e prestadores de serviço cadastrados no PetFinder têm
                    acesso apenas às informações necessárias para prestar o serviço que você
                    solicitou (por exemplo, uma clínica vê os dados do agendamento marcado com
                    ela). Não vendemos seus dados pessoais a terceiros. Podemos compartilhar dados
                    com autoridades públicas quando exigido por lei.
                </p>

                <h2 class="h5 fw-bold mt-4">5. Seus direitos (Art. 18 da LGPD)</h2>
                <p>Você pode, a qualquer momento, solicitar:</p>
                <ul>
                    <li>Confirmação de que tratamos seus dados, e acesso a eles;</li>
                    <li>Correção de dados incompletos, inexatos ou desatualizados;</li>
                    <li>Anonimização, bloqueio ou eliminação de dados desnecessários;</li>
                    <li>Portabilidade dos seus dados a outro fornecedor de serviço;</li>
                    <li>Eliminação dos dados tratados com seu consentimento;</li>
                    <li>Revogação do consentimento e informação sobre o compartilhamento de dados.</li>
                </ul>
                <p>
                    Para exercer qualquer um desses direitos, entre em contato pela nossa
                    <a href="<?= Url::pagina('contato.php') ?>">página de contato</a> ou pelo e-mail
                    do encarregado de dados informado acima.
                </p>

                <h2 class="h5 fw-bold mt-4">6. Cookies</h2>
                <p>
                    Usamos cookies essenciais para manter sua sessão ativa e lembrar suas
                    preferências. Não utilizamos cookies de rastreamento publicitário de
                    terceiros. Você pode desativar cookies nas configurações do seu navegador,
                    mas isso pode limitar o funcionamento de partes do site.
                </p>

                <h2 class="h5 fw-bold mt-4">7. Segurança e retenção</h2>
                <p>
                    Adotamos medidas técnicas e administrativas para proteger seus dados contra
                    acessos não autorizados e situações de destruição, perda, alteração ou
                    vazamento. Mantemos seus dados pelo tempo necessário para cumprir as
                    finalidades descritas aqui, ou pelo prazo exigido por lei.
                </p>

                <h2 class="h5 fw-bold mt-4">8. Alterações desta política</h2>
                <p>
                    Podemos atualizar esta política periodicamente. Mudanças relevantes serão
                    comunicadas na plataforma. A data no topo desta página sempre indica a versão
                    mais recente.
                </p>

                <hr class="my-4">

                <p class="text-muted small">
                    Dúvidas sobre esta política? Fale com a gente pela
                    <a href="<?= Url::pagina('contato.php') ?>">página de contato</a>.
                </p>

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
