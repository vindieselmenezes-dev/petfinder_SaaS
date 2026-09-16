(function () {
    "use strict";

    // ---------------------------------------------------------------
    // Registro do Service Worker
    // ---------------------------------------------------------------
    if ("serviceWorker" in navigator) {
        window.addEventListener("load", function () {
            navigator.serviceWorker.register("sw.js", { scope: "./" }).catch(function () {
                // sem SW o site continua funcionando normalmente, só sem
                // cache offline — não precisa incomodar o usuário com isso.
            });
        });
    }

    // ---------------------------------------------------------------
    // Botões "Baixe o App"
    // ---------------------------------------------------------------
    var btnInstalar = document.getElementById("btnInstalarApp");
    var btnAppStore = document.getElementById("btnAppStore");
    var btnGooglePlay = document.getElementById("btnGooglePlay");
    var msgJaInstalado = document.getElementById("msgJaInstalado");

    if (!btnInstalar || !btnAppStore || !btnGooglePlay) {
        // esta página não tem a seção "Baixe o App" — nada a fazer
        return;
    }

    var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
    var estaInstalado =
        window.matchMedia("(display-mode: standalone)").matches ||
        window.navigator.standalone === true;

    function esconder(el) {
        if (el) el.style.display = "none";
    }

    function mostrar(el) {
        if (el) el.style.display = "inline-flex";
    }

    if (estaInstalado) {
        // já é um app instalado — não faz sentido mostrar botão de baixar
        esconder(btnInstalar);
        esconder(btnAppStore);
        esconder(btnGooglePlay);
        if (msgJaInstalado) msgJaInstalado.style.display = "block";
        return;
    }

    // No iOS não existe o evento nativo de instalação (beforeinstallprompt).
    // A instalação é manual, via Safari > Compartilhar > Adicionar à Tela
    // de Início — por isso mostramos instruções em vez de tentar instalar.
    if (isIOS) {
        esconder(btnGooglePlay);
        btnAppStore.addEventListener("click", function (evento) {
            evento.preventDefault();
            var modalEl = document.getElementById("modalInstalarIos");
            if (modalEl && window.bootstrap) {
                new window.bootstrap.Modal(modalEl).show();
            }
        });
    }

    // Chrome/Edge no Android disparam este evento quando o app é
    // instalável de verdade. Aí trocamos os botões de loja por um botão
    // de instalação nativa (bem mais direto pro usuário).
    var promptDeInstalacao = null;

    window.addEventListener("beforeinstallprompt", function (evento) {
        evento.preventDefault();
        promptDeInstalacao = evento;

        esconder(btnAppStore);
        esconder(btnGooglePlay);
        mostrar(btnInstalar);
    });

    btnInstalar.addEventListener("click", function () {
        if (!promptDeInstalacao) {
            return;
        }
        promptDeInstalacao.prompt();
        promptDeInstalacao.userChoice.finally(function () {
            promptDeInstalacao = null;
            esconder(btnInstalar);
        });
    });

    window.addEventListener("appinstalled", function () {
        esconder(btnInstalar);
        esconder(btnAppStore);
        esconder(btnGooglePlay);
        if (msgJaInstalado) msgJaInstalado.style.display = "block";
    });
})();
