/**
 * PetFinder Brasil — Compartilhamento
 * ==========================================================
 * Copia o link de uma campanha/evento/doação para a área de
 * transferência, com feedback visual no botão.
 *
 * Dois jeitos de marcar o botão (cobre o bloco completo da página
 * de detalhe e o menu compacto dos cards):
 *
 *   data-copiar-alvo="idDoInput"  -> copia o value de #idDoInput
 *   data-copiar-texto="https://…" -> copia o texto direto do atributo
 *
 * Usa delegação de evento no document, então funciona mesmo em botões
 * que só existem dentro de um dropdown do Bootstrap (mostrado depois
 * que este script já rodou).
 */
(function () {

    function copiar(texto) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(texto);
        }

        // Fallback para navegadores/contextos sem Clipboard API (ex: http:// local).
        var campoTemporario = document.createElement('textarea');
        campoTemporario.value = texto;
        campoTemporario.style.position = 'fixed';
        campoTemporario.style.opacity = '0';
        document.body.appendChild(campoTemporario);
        campoTemporario.focus();
        campoTemporario.select();

        try {
            document.execCommand('copy');
        } finally {
            document.body.removeChild(campoTemporario);
        }

        return Promise.resolve();
    }

    function avisarSucesso(botao) {
        var htmlOriginal = botao.innerHTML;
        var eraDropdownItem = botao.classList.contains('dropdown-item');

        botao.innerHTML = eraDropdownItem
            ? '<i class="bi bi-check2"></i> Link copiado!'
            : '<i class="bi bi-check2"></i>';

        setTimeout(function () {
            botao.innerHTML = htmlOriginal;
        }, 2000);
    }

    document.addEventListener('click', function (evento) {

        var botao = evento.target.closest('[data-copiar-alvo], [data-copiar-texto]');

        if (!botao) {
            return;
        }

        var texto = botao.hasAttribute('data-copiar-alvo')
            ? (document.getElementById(botao.getAttribute('data-copiar-alvo') || '') || {}).value
            : botao.getAttribute('data-copiar-texto');

        if (!texto) {
            return;
        }

        copiar(texto).then(function () {
            avisarSucesso(botao);
        });
    });

})();
