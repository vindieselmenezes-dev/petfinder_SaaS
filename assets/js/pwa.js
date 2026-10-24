(function () {
    "use strict";

    // ---------------------------------------------------------------
    // Registro do Service Worker
    // ---------------------------------------------------------------
    if ("serviceWorker" in navigator) {
        window.addEventListener("load", function () {
            // Sem "scope" explícito de propósito: o navegador usa a pasta
            // onde o sw.js está como escopo automaticamente, o que é
            // sempre válido. Um "scope" calculado à mão (ex: "./") depende
            // de como o navegador resolve caminho relativo a partir da URL
            // exata da página (com ou sem barra final), o que já causou
            // erro de "scope not under max allowed" em alguns acessos.
            navigator.serviceWorker.register("sw.js").catch(function () {
                // sem SW o site continua funcionando normalmente, só sem
                // cache offline — não precisa incomodar o usuário com isso.
            });
        });
    }

    // ---------------------------------------------------------------
    // Seção "Baixe o App"
    // ---------------------------------------------------------------
    // O app NÃO está nas lojas (App Store / Google Play): ele é um PWA,
    // instalado direto pelo navegador. Por isso:
    //  - o botão principal sempre aparece;
    //  - se o navegador oferece a instalação nativa (Chrome/Edge no Android
    //    e no computador), o clique instala na hora;
    //  - senão, o clique abre um passo a passo para o aparelho/navegador da
    //    pessoa (iPhone, Samsung Internet, Firefox, navegador dentro de outro
    //    app, navegador sem suporte...).
    var btnInstalar = document.getElementById("btnInstalarApp");
    var dicaInstalar = document.getElementById("btnInstalarDica");
    var btnAppStore = document.getElementById("btnAppStore");
    var btnGooglePlay = document.getElementById("btnGooglePlay");
    var msgJaInstalado = document.getElementById("msgJaInstalado");
    var modalEl = document.getElementById("modalInstalarApp");

    if (!btnInstalar) {
        // esta página não tem a seção "Baixe o App" — nada a fazer
        return;
    }

    function esconder(el) {
        if (el) el.style.display = "none";
    }

    function mostrar(el, tipo) {
        if (el) el.style.display = tipo || "inline-flex";
    }

    function estaInstalado() {
        var standalone = window.matchMedia && window.matchMedia("(display-mode: standalone)").matches;
        return standalone || window.navigator.standalone === true;
    }

    function marcarComoInstalado() {
        esconder(btnInstalar);
        esconder(btnAppStore);
        esconder(btnGooglePlay);
        if (msgJaInstalado) msgJaInstalado.style.display = "block";
    }

    if (estaInstalado()) {
        // já é um app instalado — não faz sentido mostrar botão de baixar
        marcarComoInstalado();
        return;
    }

    // ---------------------------------------------------------------
    // Que passo a passo mostrar?
    // ---------------------------------------------------------------
    function detectarPlataforma() {
        var ua = navigator.userAgent || "";

        var navegadorDentroDeApp = /FBAN|FBAV|FB_IAB|Instagram|Line\/|MicroMessenger|Snapchat|TikTok|musical_ly|Twitter|LinkedInApp|Pinterest|; wv\)/i.test(ua);
        var semSuporte = !("serviceWorker" in navigator) || /MSIE|Trident/i.test(ua);

        // iPadOS 13+ se apresenta como "Mac"; o toque na tela entrega que é iPad.
        var ios = /iphone|ipad|ipod/i.test(ua) ||
            (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
        var android = /android/i.test(ua);

        var chave;

        if (semSuporte) {
            chave = "nao-suportado";
        } else if (ios) {
            chave = /CriOS|FxiOS|EdgiOS|OPiOS|OPT\//i.test(ua) ? "ios-outro" : "ios-safari";
        } else if (android) {
            if (/SamsungBrowser/i.test(ua)) {
                chave = "android-samsung";
            } else if (/Firefox/i.test(ua)) {
                chave = "android-firefox";
            } else {
                chave = "android-chrome";
            }
        } else {
            chave = "desktop";
        }

        return { chave: chave, dentroDeApp: navegadorDentroDeApp };
    }

    function abrirPassoAPasso() {
        if (!modalEl) {
            return;
        }

        var plataforma = detectarPlataforma();

        var secoes = modalEl.querySelectorAll("[data-plataforma]");
        for (var i = 0; i < secoes.length; i++) {
            var ehDestaSecao = secoes[i].getAttribute("data-plataforma") === plataforma.chave;
            secoes[i].style.display = ehDestaSecao ? "block" : "none";
        }

        var avisoApp = modalEl.querySelector("[data-aviso-app]");
        if (avisoApp) {
            avisoApp.style.display = plataforma.dentroDeApp ? "block" : "none";
        }

        var botaoOutros = modalEl.querySelector("[data-ver-todos]");
        if (botaoOutros) {
            botaoOutros.style.display = "inline-block";
            botaoOutros.onclick = function () {
                for (var j = 0; j < secoes.length; j++) {
                    secoes[j].style.display = "block";
                }
                botaoOutros.style.display = "none";
            };
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    // ---------------------------------------------------------------
    // Instalação nativa (quando o navegador oferece)
    // ---------------------------------------------------------------
    var promptDeInstalacao = null;

    window.addEventListener("beforeinstallprompt", function (evento) {
        evento.preventDefault();
        promptDeInstalacao = evento;
        if (dicaInstalar) dicaInstalar.textContent = "Instalar agora";
    });

    btnInstalar.addEventListener("click", function () {
        if (promptDeInstalacao) {
            var oferta = promptDeInstalacao;
            promptDeInstalacao = null;
            oferta.prompt();
            oferta.userChoice.finally(function () {
                if (dicaInstalar) dicaInstalar.textContent = "Passo a passo";
            });
            return;
        }

        abrirPassoAPasso();
    });

    window.addEventListener("appinstalled", function () {
        marcarComoInstalado();
    });
})();
