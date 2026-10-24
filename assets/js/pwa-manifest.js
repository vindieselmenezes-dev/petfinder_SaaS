/*
 * Manifest do app (PWA) com plano B para hospedagens que bloqueiam o arquivo.
 *
 * Problema: o InfinityFree (plano gratuito) coloca uma verificação de segurança
 * (desafio de JavaScript) na frente de TODOS os arquivos. O navegador busca o
 * manifest.json sozinho, sem executar JavaScript, e recebe a página de
 * verificação (HTML) no lugar do JSON. Sem manifest válido o site deixa de ser
 * "instalável" e o botão de instalar nunca aparece.
 *
 * Solução (nesta ordem):
 *  1. Tenta o manifest.json de verdade, enviando o cookie da sessão
 *     (crossorigin="use-credentials"). Se vier JSON, usa esse arquivo.
 *  2. Se vier HTML (bloqueado) ou der erro, monta o mesmo manifest aqui em
 *     JavaScript e entrega ao navegador como um arquivo em memória (Blob).
 *
 * O prefixo do site (raiz na hospedagem, /petfinder-SaaS/ no XAMPP) é descoberto
 * a partir do endereço deste próprio script, então nada fica fixo no código.
 *
 * ATENÇÃO: o conteúdo de construirManifest() deve ficar igual ao do
 * manifest.json (a diferença é só que aqui os endereços são absolutos).
 * Textos com acento ficam escritos como \uXXXX de propósito: assim o resultado
 * não depende da codificação com que a hospedagem entrega este arquivo.
 */
(function () {
    "use strict";

    var CAMINHO_DESTE_SCRIPT = "assets/js/pwa-manifest.js";
    var TEMPO_MAXIMO_MS = 5000;

    function descobrirRaiz() {
        var el = document.currentScript || document.querySelector('script[src*="pwa-manifest.js"]');
        if (!el || !el.src) {
            return null;
        }

        var endereco = new URL(el.src, document.baseURI);
        var posicao = endereco.pathname.lastIndexOf(CAMINHO_DESTE_SCRIPT);
        if (posicao === -1) {
            return null;
        }

        // Ex.: https://site.com/  ou  http://localhost/petfinder-SaaS/  (termina com "/")
        return endereco.origin + endereco.pathname.substring(0, posicao);
    }

    function construirManifest(raiz) {
        function url(relativo) {
            return new URL(relativo, raiz).href;
        }

        function icone(arquivo, tamanho, proposito) {
            return {
                src: url("assets/img/icons/" + arquivo),
                sizes: tamanho + "x" + tamanho,
                type: "image/png",
                purpose: proposito
            };
        }

        return {
            id: raiz,
            name: "EcoSistemPet",
            short_name: "EcoSistemPet",
            description: "Ado\u00e7\u00e3o, pets perdidos, cl\u00ednicas, produtos e servi\u00e7os para o seu pet em um s\u00f3 lugar.",
            start_url: url("index.html?pwa=1"),
            scope: raiz,
            display: "standalone",
            orientation: "portrait-primary",
            background_color: "#FFFFFF",
            theme_color: "#015C1E",
            lang: "pt-BR",
            dir: "ltr",
            icons: [
                icone("icon-72v2.png", 72, "any"),
                icone("icon-96v2.png", 96, "any"),
                icone("icon-128v2.png", 128, "any"),
                icone("icon-144v2.png", 144, "any"),
                icone("icon-152v2.png", 152, "any"),
                icone("icon-192v2.png", 192, "any"),
                icone("icon-384v2.png", 384, "any"),
                icone("icon-512v2.png", 512, "any"),
                icone("icon-maskable-192v2.png", 192, "maskable"),
                icone("icon-maskable-512v2.png", 512, "maskable")
            ],
            shortcuts: [
                {
                    name: "Pets para ado\u00e7\u00e3o",
                    url: url("index.html?pwa=1#adocao"),
                    icons: [{ src: url("assets/img/icons/icon-96v2.png"), sizes: "96x96" }]
                },
                {
                    name: "Pet perdido",
                    url: url("public/pets/pets_perdidos.php"),
                    icons: [{ src: url("assets/img/icons/icon-96v2.png"), sizes: "96x96" }]
                },
                {
                    name: "Meu perfil",
                    url: url("public/conta/meu_perfil.php"),
                    icons: [{ src: url("assets/img/icons/icon-96v2.png"), sizes: "96x96" }]
                }
            ]
        };
    }

    var raiz = descobrirRaiz();
    if (!raiz) {
        return;
    }

    // Exposto para facilitar testes automáticos e diagnóstico no console.
    window.EcoPwaManifest = { raiz: raiz, construir: construirManifest, origem: null };

    function ligar(href, comCredenciais, origem) {
        var antigos = document.querySelectorAll('link[rel="manifest"]');
        for (var i = 0; i < antigos.length; i++) {
            antigos[i].parentNode.removeChild(antigos[i]);
        }

        var link = document.createElement("link");
        link.rel = "manifest";
        link.href = href;
        if (comCredenciais) {
            link.crossOrigin = "use-credentials";
        }
        document.head.appendChild(link);
        window.EcoPwaManifest.origem = origem;
    }

    function ligarEmMemoria() {
        try {
            var arquivo = new Blob([JSON.stringify(construirManifest(raiz))], { type: "application/manifest+json" });
            ligar(URL.createObjectURL(arquivo), false, "blob");
        } catch (erro) {
            // Sem manifest o site continua funcionando; só não oferece instalar.
        }
    }

    var decidido = false;

    function decidir(funcao) {
        if (decidido) {
            return;
        }
        decidido = true;
        funcao();
    }

    if (typeof fetch !== "function") {
        ligarEmMemoria();
        return;
    }

    var urlArquivo = raiz + "manifest.json";
    var relogio = setTimeout(function () { decidir(ligarEmMemoria); }, TEMPO_MAXIMO_MS);

    fetch(urlArquivo, { credentials: "include", cache: "no-store" })
        .then(function (resposta) {
            var tipo = resposta.headers.get("content-type") || "";
            if (!resposta.ok || tipo.indexOf("json") === -1) {
                throw new Error("manifest bloqueado ou inválido");
            }
            return resposta.json();
        })
        .then(function () {
            clearTimeout(relogio);
            decidir(function () { ligar(urlArquivo, true, "arquivo"); });
        })
        .catch(function () {
            clearTimeout(relogio);
            decidir(ligarEmMemoria);
        });
})();
