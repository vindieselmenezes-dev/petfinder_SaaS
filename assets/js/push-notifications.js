(function () {
    "use strict";

    var chavePublica = document.body && document.body.dataset.pushVapidKey;
    var csrfToken = window.PetfinderCsrfToken;
    var suportado = !!(chavePublica && csrfToken && 'serviceWorker' in navigator &&
        'PushManager' in window && 'Notification' in window);

    if (!suportado) {
        return;
    }

    function base64ParaBytes(valor) {
        var padding = '='.repeat((4 - valor.length % 4) % 4);
        var base64 = (valor + padding).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(base64), function (caractere) { return caractere.charCodeAt(0); });
    }

    function salvarAssinatura(assinatura) {
        return fetch(window.PetfinderPushSubscriptionUrl || '/app/ajax/salvar_push_subscription.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(assinatura.toJSON())
        });
    }

    function assinar() {
        return navigator.serviceWorker.ready.then(function (registro) {
            return registro.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: base64ParaBytes(chavePublica)
            });
        }).then(function (assinatura) {
            return salvarAssinatura(assinatura).then(function () { return assinatura; });
        });
    }

    // Quem já autorizou antes: renova o registro em silêncio (a assinatura do
    // navegador pode mudar). NUNCA pede permissão sozinho: o iOS só aceita o
    // pedido a partir de um toque, e os navegadores penalizam sites que
    // pedem ao abrir a página.
    if (Notification.permission === 'granted') {
        assinar().catch(function () { /* push é opcional */ });
    }

    // Pedido de permissão: só roda a partir do clique do botão (gesto do usuário).
    function ativar() {
        return Notification.requestPermission().then(function (permissao) {
            if (permissao !== 'granted') {
                return permissao;
            }
            return assinar().then(function () { return 'granted'; });
        });
    }

    window.PetfinderPush = { ativar: ativar };

    // Bloco opcional "Ativar notificações no celular" (página de notificações).
    function iniciarBloco() {
        var bloco = document.getElementById('blocoAtivarPush');
        var botao = document.getElementById('btnAtivarPush');
        var texto = document.getElementById('textoAtivarPush');
        if (!bloco || !botao) return;

        function atualizar() {
            if (Notification.permission === 'granted') {
                bloco.hidden = true;
            } else if (Notification.permission === 'denied') {
                bloco.hidden = false;
                botao.hidden = true;
                if (texto) texto.textContent = 'As notificações estão bloqueadas neste navegador. Libere nas configurações do site para receber avisos no celular.';
            } else {
                bloco.hidden = false;
            }
        }

        botao.addEventListener('click', function () {
            botao.disabled = true;
            ativar().then(atualizar).catch(function () {
                if (texto) texto.textContent = 'Não foi possível ativar agora. Tente novamente em instantes.';
            }).then(function () { botao.disabled = false; });
        });

        atualizar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarBloco);
    } else {
        iniciarBloco();
    }
})();
