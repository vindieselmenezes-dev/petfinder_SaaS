/**
 * ==========================================================
 * PETFINDER BRASIL
 * Solicitação de localização (GPS)
 *
 * Antes de disparar o popup nativo de permissão do navegador
 * (que tem um texto padronizado e não pode ser customizado),
 * mostramos nosso próprio banner explicando por que pedimos
 * a localização: alertas de pet perdido por perto e indicação
 * de empresas/serviços próximos.
 *
 * A localização, uma vez aceita, é salva em usuarios.latitude
 * /longitude via app/ajax/salvar_localizacao.php.
 * ==========================================================
 */

(function () {

    const CHAVE_STORAGE = 'petfinder_localizacao_resposta';

    function jaRespondeu() {
        try {
            return localStorage.getItem(CHAVE_STORAGE) !== null;
        } catch (e) {
            return false;
        }
    }

    function lembrarResposta(resposta) {
        try {
            localStorage.setItem(CHAVE_STORAGE, resposta);
        } catch (e) {
            // se o navegador bloquear localStorage, sem problema,
            // só vamos perguntar de novo na próxima visita
        }
    }

    function salvarLocalizacaoNoServidor(latitude, longitude, caminhoEndpoint) {
        fetch(caminhoEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ latitude: latitude, longitude: longitude })
        }).catch(function () {
            // falha silenciosa — não é crítico pro uso da página
        });
    }

    function criarBanner(caminhoEndpoint) {

        const banner = document.createElement('div');
        banner.id = 'bannerLocalizacao';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-label', 'Pedido de localização');
        banner.style.cssText = [
            'position:fixed', 'bottom:20px', 'left:50%', 'transform:translateX(-50%)',
            'max-width:420px', 'width:calc(100% - 40px)', 'background:#fff',
            'border-radius:14px', 'box-shadow:0 10px 30px rgba(0,0,0,.18)',
            'padding:20px', 'z-index:99999', 'font-family:Poppins,Arial,sans-serif',
            'border:1px solid #eee'
        ].join(';');

        banner.innerHTML = `
            <div style="display:flex; gap:12px; align-items:flex-start;">
                <div style="font-size:26px; line-height:1;">📍</div>
                <div style="flex:1;">
                    <strong style="display:block; margin-bottom:4px; color:#1B365D; font-size:15px;">
                        Podemos usar sua localização?
                    </strong>
                    <p style="margin:0 0 14px 0; font-size:13.5px; color:#555; line-height:1.4;">
                        Isso ajuda a te avisar sobre <strong>pets perdidos perto de você</strong>
                        e a indicar <strong>clínicas, petshops e serviços próximos</strong>.
                        Só usamos isso pra essas duas coisas.
                    </p>
                    <div style="display:flex; gap:8px;">
                        <button type="button" id="btnAceitarLocalizacao"
                            style="flex:1; background:#2ECC71; color:#fff; border:none; padding:9px 14px; border-radius:8px; font-weight:600; cursor:pointer; font-size:13.5px;">
                            Permitir
                        </button>
                        <button type="button" id="btnRecusarLocalizacao"
                            style="background:#f1f3f5; color:#555; border:none; padding:9px 14px; border-radius:8px; font-weight:600; cursor:pointer; font-size:13.5px;">
                            Agora não
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(banner);

        document.getElementById('btnAceitarLocalizacao').addEventListener('click', function () {
            lembrarResposta('aceito');
            banner.remove();

            if (!navigator.geolocation) {
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (posicao) {
                    salvarLocalizacaoNoServidor(posicao.coords.latitude, posicao.coords.longitude, caminhoEndpoint);
                },
                function () {
                    // usuário aceitou o banner mas negou o popup nativo, ou GPS indisponível — sem problema
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
            );
        });

        document.getElementById('btnRecusarLocalizacao').addEventListener('click', function () {
            lembrarResposta('recusado');
            banner.remove();
        });
    }

    // Só mostra o banner se: a pessoa está logada, o navegador suporta
    // geolocalização, e ela ainda não respondeu antes nesse navegador.
    window.PetfinderLocalizacao = {
        solicitarSeNecessario: function (caminhoEndpoint) {
            caminhoEndpoint = caminhoEndpoint || 'app/ajax/salvar_localizacao.php';
            if (jaRespondeu() || !navigator.geolocation) {
                return;
            }
            // pequeno atraso pra não competir com o carregamento inicial da página
            setTimeout(function () { criarBanner(caminhoEndpoint); }, 1200);
        }
    };

})();
