(function () {
    "use strict";

    // Lido do próprio <script src="..." data-endpoint="...">, calculado em
    // PHP (Url::ajax()) na página que inclui este arquivo — assim o caminho
    // sempre acerta, não importa a profundidade de pastas de quem incluiu.
    var scriptAtual = document.currentScript;
    var endpoint = (scriptAtual && scriptAtual.dataset.endpoint) || '/app/ajax/registrar_metrica.php';

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
            navigator.sendBeacon(endpoint, new Blob([dados], { type: 'application/json' }));
        } else {
            fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: dados, keepalive: true });
        }
    }

    document.addEventListener('click', function (evento) {
        var elemento = evento.target.closest('[data-metrica-empresa]');
        if (elemento) registrar(elemento);
    }, true);
})();
