(function () {
    "use strict";

    var chavePublica = document.body && document.body.dataset.pushVapidKey;
    var csrfToken = window.PetfinderCsrfToken;
    if (!chavePublica || !csrfToken || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        return;
    }

    function base64ParaBytes(valor) {
        var padding = '='.repeat((4 - valor.length % 4) % 4);
        var base64 = (valor + padding).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(base64), function (caractere) { return caractere.charCodeAt(0); });
    }

    navigator.serviceWorker.ready.then(function (registro) {
        return Notification.requestPermission().then(function (permissao) {
            if (permissao !== 'granted') return null;
            return registro.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: base64ParaBytes(chavePublica)
            });
        });
    }).then(function (assinatura) {
        if (!assinatura) return;
        return fetch('../app/ajax/salvar_push_subscription.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(assinatura.toJSON())
        });
    }).catch(function () {
        // Push e opcional; falhas nao interrompem a pagina.
    });
})();
