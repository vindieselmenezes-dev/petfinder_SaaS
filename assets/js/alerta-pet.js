/**
 * Alerta Pet (cartão da página inicial).
 *  - "Achei um pet": foto (câmera), espécie, descrição e GPS -> alerta_rapido.php
 *  - "Perdi meu pet": escolhe um pet cadastrado -> processa_alerta.php (já existente)
 * O login só é pedido na hora de enviar; a foto continua na tela.
 */
(function () {
    'use strict';

    var card = document.getElementById('alertaPet');
    if (!card) { return; }

    var API = 'public/pets/alerta_rapido.php';
    var $ = function (id) { return document.getElementById(id); };
    var formAchei = $('formAchei');
    var formPerdi = $('formPerdi');
    var msg = $('alertaPetMsg');
    var botoes = card.querySelectorAll('[data-alerta-modo]');
    var coords = null;
    var foto = null;
    var gpsPedido = false;

    function mostrarMsg(html, erro) {
        msg.className = 'alerta-pet-msg' + (erro ? ' erro' : '');
        msg.innerHTML = html;
        msg.hidden = false;
    }

    function statusGps(classe, texto) {
        ['alertaStatusGps', 'perdiStatusGps'].forEach(function (id) {
            var el = $(id);
            el.className = 'alerta-pet-status ' + classe;
            el.textContent = texto;
        });
    }

    function pedirLocalizacao() {
        if (gpsPedido) { return; }
        gpsPedido = true;

        if (!navigator.geolocation) {
            statusGps('erro', 'Seu aparelho não informa a localização. O alerta será publicado, mas sem aviso automático a quem está por perto.');
            return;
        }

        navigator.geolocation.getCurrentPosition(function (pos) {
            coords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            $('perdiLat').value = coords.lat;
            $('perdiLng').value = coords.lng;
            statusGps('ok', 'Localização capturada. Quem está por perto será avisado.');
        }, function () {
            statusGps('erro', 'Sem acesso à localização. O alerta será publicado, mas sem aviso automático a quem está por perto.');
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    }

    function contexto() {
        return fetch(API + '?acao=contexto', { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .catch(function () { return { logado: false, pets: [] }; });
    }

    function pedirLogin() {
        mostrarMsg('Guardei sua foto e sua descrição. Entre na sua conta e volte para esta página: elas estarão aqui, é só tocar em <strong>Enviar alerta</strong>. ' +
            '<a href="public/login.php">Entrar</a> · <a href="public/cadastro.php">Criar conta</a>', true);
    }

    // ---- rascunho: a foto e o texto sobrevivem à ida até a tela de login (nesta mesma aba)
    var CHAVE = 'alertaPetRascunho';

    function salvarRascunho(blob) {
        return new Promise(function (resolve) {
            var leitor = new FileReader();
            leitor.onload = function () {
                try {
                    sessionStorage.setItem(CHAVE, JSON.stringify({
                        foto: leitor.result,
                        especie: formAchei.elements.especie.value,
                        especieId: $('alertaEspecieId').value,
                        descricao: $('alertaDescricaoAchei').value
                    }));
                } catch (erro) { /* armazenamento cheio ou bloqueado: segue sem rascunho */ }
                resolve();
            };
            leitor.onerror = resolve;
            leitor.readAsDataURL(blob);
        });
    }

    function restaurarRascunho() {
        var texto = null;
        try { texto = sessionStorage.getItem(CHAVE); } catch (erro) { return; }
        if (!texto) { return; }

        var d;
        try { d = JSON.parse(texto); } catch (erro) { return; }

        fetch(d.foto).then(function (r) { return r.blob(); }).then(function (blob) {
            abrir('achei');
            escolherFoto(new File([blob], 'pet.jpg', { type: blob.type || 'image/jpeg' }));
            var radio = formAchei.querySelector('input[name=especie][value="' + d.especie + '"]');
            if (radio) { radio.checked = true; }
            $('alertaEspecieId').hidden = d.especie !== 'outro';
            $('alertaEspecieId').value = d.especieId || '10';
            $('alertaDescricaoAchei').value = d.descricao || '';
            card.scrollIntoView({ block: 'center' });
            mostrarMsg('Recuperei sua foto e sua descrição. Confira e toque em <strong>Enviar alerta</strong>.', false);
        }).catch(function () {});
    }

    function prepararPerdi() {
        contexto().then(function (ctx) {
            var semLogin = $('perdiSemLogin');
            var campos = $('perdiCampos');
            var lista = $('perdiPet');

            semLogin.hidden = !!ctx.logado;
            campos.hidden = !ctx.logado;
            if (!ctx.logado) { return; }

            $('perdiCsrf').value = ctx.csrf || '';
            lista.innerHTML = '';
            ctx.pets.forEach(function (pet) {
                var o = document.createElement('option');
                o.value = pet.id;
                o.textContent = pet.nome;
                lista.appendChild(o);
            });

            if (!ctx.pets.length) {
                campos.hidden = true;
                mostrarMsg('Você ainda não tem pet cadastrado. <a href="public/pets/cadastrar_pet.php">Cadastre seu pet</a> para poder avisar quando ele sumir.', true);
            }
        });
    }

    function abrir(modo) {
        var jaAberto = botoes[0].getAttribute('aria-expanded') === 'true' && modo === 'achei' ||
            botoes[1].getAttribute('aria-expanded') === 'true' && modo === 'perdi';
        if (jaAberto) { modo = null; }

        botoes.forEach(function (b) { b.setAttribute('aria-expanded', String(b.getAttribute('data-alerta-modo') === modo)); });
        formAchei.hidden = modo !== 'achei';
        formPerdi.hidden = modo !== 'perdi';
        msg.hidden = true;

        if (modo) { pedirLocalizacao(); }
        if (modo === 'perdi') { prepararPerdi(); }
        if (modo === 'achei') {
            // A opção de mostrar o telefone só aparece para quem tem telefone cadastrado.
            contexto().then(function (ctx) {
                $('alertaTelefoneBloco').hidden = !(ctx.logado && ctx.tem_telefone);
            });
        }
    }

    botoes.forEach(function (b) {
        b.addEventListener('click', function () { abrir(b.getAttribute('data-alerta-modo')); });
    });

    // ------------------------------------------------------------ foto
    function escolherFoto(arquivo) {
        if (!arquivo || arquivo.type.indexOf('image/') !== 0) { return; }
        foto = arquivo;
        msg.hidden = true;
        var prev = $('alertaFotoPrev');
        prev.src = URL.createObjectURL(arquivo);
        prev.hidden = false;
        $('alertaFotoTxt').hidden = true;
    }

    $('alertaFotoBtn').addEventListener('click', function () { $('alertaFotoCamera').click(); });
    $('alertaFotoGaleriaBtn').addEventListener('click', function () { $('alertaFotoGaleria').click(); });
    $('alertaFotoCamera').addEventListener('change', function () { escolherFoto(this.files[0]); });
    $('alertaFotoGaleria').addEventListener('change', function () { escolherFoto(this.files[0]); });

    formAchei.addEventListener('change', function (e) {
        if (e.target.name === 'especie') { $('alertaEspecieId').hidden = e.target.value !== 'outro'; }
    });

    // Reduz a foto no próprio aparelho (menos dados, envio mais rápido).
    function reduzir(arquivo) {
        return new Promise(function (resolve) {
            function desenhar(origem, w, h) {
                var esc = Math.min(1, 1280 / Math.max(w, h));
                var c = document.createElement('canvas');
                c.width = Math.round(w * esc);
                c.height = Math.round(h * esc);
                c.getContext('2d').drawImage(origem, 0, 0, c.width, c.height);
                c.toBlob(function (b) { resolve(b || arquivo); }, 'image/jpeg', 0.82);
            }

            if (window.createImageBitmap) {
                createImageBitmap(arquivo, { imageOrientation: 'from-image' })
                    .then(function (bm) { desenhar(bm, bm.width, bm.height); })
                    .catch(function () { resolve(arquivo); });
            } else {
                var img = new Image();
                img.onload = function () { desenhar(img, img.naturalWidth, img.naturalHeight); };
                img.onerror = function () { resolve(arquivo); };
                img.src = URL.createObjectURL(arquivo);
            }
        });
    }

    // ---------------------------------------------------------- enviar
    formAchei.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!foto) { mostrarMsg('Tire ou escolha uma foto do pet para continuar.', true); return; }

        var botao = $('alertaEnviarAchei');
        botao.disabled = true;
        botao.textContent = 'Enviando...';
        msg.hidden = true;

        function liberar() { botao.disabled = false; botao.textContent = 'Enviar alerta'; }

        Promise.all([reduzir(foto), contexto()]).then(function (r) {
            var ctx = r[1];
            if (!ctx.logado) { salvarRascunho(r[0]).then(function () { pedirLogin(); liberar(); }); return; }

            var dados = new FormData();
            dados.append('acao', 'achei');
            dados.append('csrf_token', ctx.csrf);
            dados.append('foto', r[0], 'pet.jpg');
            dados.append('especie', formAchei.elements.especie.value);
            dados.append('especie_id', $('alertaEspecieId').value);
            dados.append('descricao', $('alertaDescricaoAchei').value);
            if (coords) { dados.append('latitude', coords.lat); dados.append('longitude', coords.lng); }
            if (!$('alertaTelefoneBloco').hidden && $('alertaMostrarTelefone').checked) { dados.append('mostrar_telefone', '1'); }

            return fetch(API, { method: 'POST', body: dados, credentials: 'same-origin' })
                .then(function (resp) { return resp.json().then(function (j) { return { status: resp.status, json: j }; }); })
                .then(function (res) {
                    if (res.status === 401) { return salvarRascunho(r[0]).then(pedirLogin); }
                    if (!res.json.ok) { mostrarMsg(res.json.mensagem || 'Não foi possível enviar agora.', true); return; }

                    try { sessionStorage.removeItem(CHAVE); } catch (erro) { /* sem rascunho */ }
                    mostrarMsg(res.json.mensagem + ' <a href="' + res.json.url + '">Ver pets encontrados</a>', false);
                    foto = null;
                    $('alertaFotoPrev').hidden = true;
                    $('alertaFotoTxt').hidden = false;
                    $('alertaDescricaoAchei').value = '';
                    formAchei.hidden = true;
                    botoes.forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
                })
                .then(liberar, liberar);
        }).catch(function () {
            mostrarMsg('Sem conexão. Verifique a internet e tente de novo.', true);
            liberar();
        });
    });

    // ------------------------------------------------ convite: notificações no celular
    // O pedido de permissão só acontece no toque do botão (exigência dos navegadores e do iPhone).
    var pushBloco = $('alertaPushBloco');
    var pushTxt = $('alertaPushTxt');
    var pushBtn = $('alertaPushBtn');

    function bytes(chave) {
        var base64 = (chave + '='.repeat((4 - chave.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(base64), function (c) { return c.charCodeAt(0); });
    }

    function assinarPush(ctx) {
        var espera = new Promise(function (_, rejeitar) { setTimeout(function () { rejeitar(new Error('sw-timeout')); }, 8000); });
        var opcoes = { userVisibleOnly: true, applicationServerKey: bytes(ctx.vapid) };

        return Promise.race([navigator.serviceWorker.ready, espera]).then(function (registro) {
            return registro.pushManager.getSubscription().then(function (atual) {
                return atual || registro.pushManager.subscribe(opcoes);
            }).catch(function (erro) {
                if (erro && erro.name === 'InvalidStateError') { // assinatura criada com outra chave
                    return registro.pushManager.getSubscription()
                        .then(function (antiga) { return antiga ? antiga.unsubscribe() : null; })
                        .then(function () { return registro.pushManager.subscribe(opcoes); });
                }
                throw erro;
            });
        }).then(function (assinatura) {
            return fetch('app/ajax/salvar_push_subscription.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': ctx.csrf },
                body: JSON.stringify(assinatura.toJSON())
            }).then(function (r) {
                if (!r.ok) { throw new Error('salvar-' + r.status); }
            });
        });
    }

    function iniciarConvitePush() {
        if (!pushBloco || !('Notification' in window)) { return; }

        contexto().then(function (ctx) {
            if (!ctx.logado || !ctx.vapid) { return; }

            var suportado = 'serviceWorker' in navigator && 'PushManager' in window;
            if (!suportado) {
                pushTxt.textContent = 'Este navegador não recebe notificações. No iPhone, instale o app na tela inicial e abra por ele; no Android, use o Chrome.';
                pushBtn.hidden = true;
                pushBloco.hidden = false;
                return;
            }

            if (Notification.permission === 'granted') {
                assinarPush(ctx).catch(function () { /* push é opcional */ });
                return;
            }

            if (Notification.permission === 'denied') {
                pushTxt.textContent = 'As notificações estão bloqueadas neste navegador. Libere nas configurações do site para receber os avisos de pets perto de você.';
                pushBtn.hidden = true;
                pushBloco.hidden = false;
                return;
            }

            pushBloco.hidden = false;
            pushBtn.addEventListener('click', function () {
                pushBtn.disabled = true;
                Notification.requestPermission().then(function (permissao) {
                    if (permissao !== 'granted') {
                        pushTxt.textContent = 'Sem permissão não conseguimos avisar você. Você pode ativar depois nas configurações do site.';
                        pushBtn.hidden = true;
                        return;
                    }
                    return assinarPush(ctx).then(function () {
                        pushTxt.textContent = 'Pronto! Você vai receber os avisos de pets perto de você neste aparelho.';
                        pushBtn.hidden = true;
                    });
                }).catch(function () {
                    pushTxt.textContent = 'Não foi possível ativar agora. Atualize a página e tente de novo.';
                    pushBtn.disabled = false;
                });
            });
        });
    }

    setTimeout(iniciarConvitePush, 800);
    restaurarRascunho();
}());
