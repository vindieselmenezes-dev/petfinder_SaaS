(function () {
    "use strict";

    function registrar(elemento) {
        var empresaId = elemento.getAttribute('data-metrica-empresa');
        var tipo = elemento.getAttribute('data-metrica-tipo') || 'clique';
        if (!empresaId) return;

        var dados = JSON.stringify({
            empresa_id: Number(empresaId),
            tipo: tipo,
            pagina: elemento.getAttribute('data-metrica-pagina') || location.pathname,
            referencia_id: elemento.getAttribute('data-metrica-referencia') ? Number(elemento.getAttribute('data-metrica-referencia')) : null
        });
        if (navigator.sendBeacon) {
            navigator.sendBeacon('../app/ajax/registrar_metrica.php', new Blob([dados], { type: 'application/json' }));
        } else {
            fetch('../app/ajax/registrar_metrica.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: dados, keepalive: true });
        }
    }

    document.addEventListener('click', function (evento) {
        var elemento = evento.target.closest('[data-metrica-empresa]');
        if (elemento) registrar(elemento);
    }, true);
})();
