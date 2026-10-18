(function () {
    "use strict";

    var chavePublica = document.body && document.body.dataset.pushVapidKey;
    var csrfToken = window.PetfinderCsrfToken;
    var suportado = !!(chavePublica && csrfToken && 'serviceWorker' in navigator &&
        'PushManager' in window && 'Notification' in window);

    // Mostra/esconde o bloco "Ativar notificações" (flex, como o alert do Bootstrap).
    function exibirBloco(bloco, visivel) {
        bloco.style.display = visivel ? 'flex' : 'none';
    }

    if (!suportado) {
        // Servidor com chave VAPID, mas este navegador não consegue: explica em vez
        // de ficar sem nada na tela. (iPhone: só funciona com o app instalado.)
        var blocoAviso = document.getElementById('blocoAtivarPush');
        if (chavePublica && blocoAviso) {
            var botaoAviso = document.getElementById('btnAtivarPush');
            var textoAviso = document.getElementById('textoAtivarPush');
            if (botaoAviso) botaoAviso.style.display = 'none';
            if (textoAviso) {
                textoAviso.textContent = 'Este navegador não permite notificações. No iPhone, instale o app na tela inicial e abra por ele; no Android, use o Chrome.';
            }
            exibirBloco(blocoAviso, true);
        }
        return;
    }

    function base64ParaBytes(valor) {
        var padding = '='.repeat((4 - valor.length % 4) % 4);
        var base64 = (valor + padding).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(base64), function (caractere) { return caractere.charCodeAt(0); });
    }

    // navigator.serviceWorker.ready nunca resolve se o service worker não
    // estiver registrado: com prazo, o botão avisa em vez de ficar parado.
    function esperarServiceWorker(ms) {
        return Promise.race([
            navigator.serviceWorker.ready,
            new Promise(function (_, rejeitar) {
                setTimeout(function () { rejeitar(new Error('sw-timeout')); }, ms);
            })
        ]);
    }

    function salvarAssinatura(assinatura) {
        return fetch(window.PetfinderPushSubscriptionUrl || '/app/ajax/salvar_push_subscription.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(assinatura.toJSON())
        }).then(function (resposta) {
            if (!resposta.ok) {
                throw new Error('salvar-' + resposta.status);
            }
            return assinatura;
        });
    }

    function criarAssinatura(registro) {
        var opcoes = {
            userVisibleOnly: true,
            applicationServerKey: base64ParaBytes(chavePublica)
        };
        return registro.pushManager.getSubscription().then(function (existente) {
            if (existente) {
                return existente;
            }
            return registro.pushManager.subscribe(opcoes);
        }).catch(function (erro) {
            // Assinatura antiga criada com outra chave VAPID: refaz com a atual.
            if (erro && erro.name === 'InvalidStateError') {
                return registro.pushManager.getSubscription().then(function (antiga) {
                    return (antiga ? antiga.unsubscribe() : Promise.resolve());
                }).then(function () {
                    return registro.pushManager.subscribe(opcoes);
                });
            }
            throw erro;
        });
    }

    function assinar() {
        return esperarServiceWorker(8000)
            .then(criarAssinatura)
            .then(salvarAssinatura);
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

    function mensagemDeErro(erro) {
        var nome = (erro && (erro.message || erro.name)) || '';
        if (nome === 'sw-timeout') {
            return 'O app ainda não terminou de carregar. Atualize a página e tente de novo.';
        }
        if (nome.indexOf('salvar-') === 0) {
            return 'O aparelho aceitou, mas o servidor não conseguiu salvar (erro ' + nome.replace('salvar-', '') + '). Avise o suporte.';
        }
        if (erro && erro.name === 'AbortError') {
            return 'O serviço de notificações do navegador recusou o registro. Tente novamente em instantes.';
        }
        if (erro && erro.name === 'NotAllowedError') {
            return 'Permissão negada. Libere as notificações nas configurações do site.';
        }
        return 'Não foi possível ativar agora (' + (nome || 'erro desconhecido') + ').';
    }

    window.PetfinderPush = { ativar: ativar };

    // Bloco opcional "Ativar notificações no celular" (página de notificações).
    function iniciarBloco() {
        var bloco = document.getElementById('blocoAtivarPush');
        var botao = document.getElementById('btnAtivarPush');
        var texto = document.getElementById('textoAtivarPush');
        if (!bloco || !botao) return;

        function mostrar(mensagem) {
            if (texto) texto.textContent = mensagem;
        }

        function atualizar() {
            if (Notification.permission === 'granted') {
                exibirBloco(bloco, false);
            } else if (Notification.permission === 'denied') {
                exibirBloco(bloco, true);
                botao.style.display = 'none';
                mostrar('As notificações estão bloqueadas neste navegador. Libere nas configurações do site para receber avisos no celular.');
            } else {
                exibirBloco(bloco, true);
            }
        }

        botao.addEventListener('click', function () {
            botao.disabled = true;
            mostrar('Aguardando sua permissão...');

            ativar().then(function (resultado) {
                if (resultado === 'granted') {
                    botao.style.display = 'none';
                    mostrar('Pronto! As notificações foram ativadas neste aparelho.');
                    return;
                }
                if (resultado === 'denied') {
                    atualizar();
                    return;
                }
                mostrar('Você não autorizou. Toque em Ativar de novo e escolha "Permitir".');
            }).catch(function (erro) {
                if (window.console) console.error('[push]', erro);
                mostrar(mensagemDeErro(erro));
            }).then(function () {
                botao.disabled = false;
            });
        });

        atualizar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarBloco);
    } else {
        iniciarBloco();
    }
})();
