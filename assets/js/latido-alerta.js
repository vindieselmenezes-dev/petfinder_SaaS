/**
 * Latido do Alerta Pet.
 * Quando chega um push de pet perdido/encontrado e o site (ou o app) está
 * ABERTO, o service worker avisa a página e ela toca o latido.
 *
 * Limites: o som da notificação com o app fechado é do sistema e não pode ser
 * trocado; e o navegador só deixa tocar som depois que a pessoa tocou na tela
 * ao menos uma vez nesta visita.
 */
(function () {
    'use strict';

    var tag = document.currentScript;
    var origem = tag && tag.getAttribute('data-audio');
    if (!origem || !('serviceWorker' in navigator)) { return; }

    var audio = new Audio(origem);
    audio.preload = 'auto';
    var liberado = false;
    var ultimoToque = 0;
    var arquivoComErro = false;

    audio.addEventListener('error', function () {
        arquivoComErro = true;
        if (window.console) { console.warn('[latido] o arquivo de áudio não carregou: ' + origem); }
    });

    function tocar() {
        // evita tocar duas vezes (aviso do service worker + conferência da página)
        if (Date.now() - ultimoToque < 8000) { return; }
        ultimoToque = Date.now();

        audio.currentTime = 0;
        var tentativa = audio.play();
        if (tentativa && tentativa.catch) { tentativa.catch(function () { /* som bloqueado pelo navegador */ }); }
        if (navigator.vibrate) { navigator.vibrate([300, 100, 300, 100, 600]); }
    }

    // Primeiro toque na tela: "destrava" o áudio tocando-o mudo por um instante.
    function liberar() {
        if (liberado) { return; }
        liberado = true;
        audio.muted = true;
        var tentativa = audio.play();
        if (tentativa && tentativa.then) {
            tentativa.then(function () {
                audio.pause();
                audio.currentTime = 0;
                audio.muted = false;
            }).catch(function () {
                liberado = false;
                audio.muted = false;
            });
        } else {
            audio.muted = false;
        }
    }

    ['pointerdown', 'keydown', 'touchstart'].forEach(function (evento) {
        window.addEventListener(evento, liberar, { once: true, passive: true });
    });

    navigator.serviceWorker.addEventListener('message', function (e) {
        if (e.data && e.data.tipo === 'alerta-pet') { tocar(); }
    });

    // Segunda via, que não depende do service worker: com a página aberta, confere
    // a cada 20 s se surgiu um alerta de pet novo (o primeiro valor só serve de base).
    // Teste manual (botão da página Notificações): toca, vibra e diz o que impediu.
    window.PetfinderLatido = {
        testar: function () {
            return new Promise(function (resolve) {
                if (arquivoComErro) { resolve({ ok: false, motivo: 'arquivo' }); return; }

                audio.muted = false;
                audio.currentTime = 0;

                // 'sem-suporte': o navegador não tem vibração (iPhone/Safari, Firefox, computador)
                // 'recusou':     o navegador tem, mas não aceitou o pedido
                // 'pediu':       o navegador mandou vibrar (se o celular não mexeu, é configuração dele)
                var vibracao = !navigator.vibrate ? 'sem-suporte'
                    : (navigator.vibrate([300, 100, 300, 100, 600]) ? 'pediu' : 'recusou');
                var tentativa = audio.play();

                if (!tentativa || !tentativa.then) { resolve({ ok: true, vibracao: vibracao }); return; }
                tentativa.then(function () {
                    resolve({ ok: true, vibracao: vibracao });
                }).catch(function (erro) {
                    var nome = erro && erro.name;
                    resolve({ ok: false, vibracao: vibracao, motivo: nome === 'NotAllowedError' ? 'bloqueado' : (nome === 'NotSupportedError' ? 'arquivo' : 'erro') });
                });
            });
        }
    };

    var urlContagem = tag.getAttribute('data-contagem');
    var CHAVE = 'latidoUltimoAlerta';

    function conferir() {
        if (!urlContagem || document.visibilityState !== 'visible') { return; }

        fetch(urlContagem, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d || !d.logado) { return; }
                var atual = Number(d.alerta_pet) || 0;
                var anterior = null;
                try { anterior = sessionStorage.getItem(CHAVE); } catch (erro) { /* sem base */ }

                if (anterior !== null && atual > Number(anterior)) { tocar(); }
                try { sessionStorage.setItem(CHAVE, String(atual)); } catch (erro) { /* sem base */ }
            })
            .catch(function () { /* sem conexão: tenta de novo no próximo ciclo */ });
    }

    if (urlContagem) {
        conferir();
        setInterval(conferir, 20000);
        document.addEventListener('visibilitychange', conferir);
    }
}());
