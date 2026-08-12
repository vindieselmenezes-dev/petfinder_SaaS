/* ===========================================================
   PetFinder Brasil
   assets/js/script.js
   Parte 1
=========================================================== */

"use strict";

/* ===========================================================
   EXECUTA APÓS CARREGAR A PÁGINA
=========================================================== */

document.addEventListener("DOMContentLoaded", () => {

    iniciarSistema();

});

/* ===========================================================
   INICIALIZAÇÃO
=========================================================== */

function iniciarSistema() {

    esconderLoader();

    configurarBotaoTopo();

    configurarHeader();

    configurarLupa();

    // ativa comportamentos de navegação em botões que mantêm a estrutura HTML
    configurarAcoesBotoes();

    console.log("PetFinder Brasil iniciado.");

}

/**
 * Ativa navegação para botões que possuem o atributo `data-href`.
 * Mantém os elementos `<button>` como estão na marcação e apenas adiciona
 * o comportamento de navegação ao clique.
 */
function configurarAcoesBotoes() {

    try {
        const botoes = document.querySelectorAll('button[data-href]');
        if (!botoes || botoes.length === 0) return;

        botoes.forEach(function (btn) {
            btn.style.cursor = 'pointer';
            btn.addEventListener('click', function (e) {
                const href = btn.getAttribute('data-href');
                if (!href) return;
                // navega para o destino (relativo à raiz do projeto)
                window.location.href = href;
            });
        });
    } catch (e) {
        console.warn('Erro em configurarAcoesBotoes', e);
    }

}

/* ===========================================================
   LUPA CLICÁVEL
=========================================================== */
function configurarLupa() {
    // torna ícones de busca clicáveis: acionam o submit do formulário mais próximo
    const lupas = document.querySelectorAll('.input-group-text .bi-search');
    lupas.forEach(function (icon) {
        icon.style.cursor = 'pointer';
        icon.addEventListener('click', function (e) {
            // procura o form mais próximo
            let el = icon;
            while (el && el !== document.body) {
                if (el.tagName && el.tagName.toLowerCase() === 'form') break;
                el = el.parentElement;
            }
            const form = el && el.tagName && el.tagName.toLowerCase() === 'form' ? el : document.querySelector('#formBuscaAdocao');
            if (form) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.dispatchEvent(new Event('submit', { cancelable: true }));
                }
            } else {
                // fallback: se estiver em hero-search, tenta disparar botão de busca local
                const heroBtn = icon.closest('.hero-search')?.querySelector('button');
                if (heroBtn) heroBtn.click();
            }
        });
    });
}

// Debounce utility to limit AJAX calls
function debounce(fn, delay) {
    let timer = null;
    return function () {
        const context = this;
        const args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            fn.apply(context, args);
        }, delay);
    };
}

// Fallback: garante que o campo do header dispare sugestões (útil se iniciarBusca falhar)
window.addEventListener('load', function () {
    try {
        const headerInput = document.getElementById('inputBuscaAdocao');
        if (!headerInput) return;
        const parent = headerInput.parentElement;
        let lista = parent.querySelector('.lista-sugestoes');
        if (!lista) {
            lista = document.createElement('ul');
            lista.className = 'list-group lista-sugestoes';
            lista.style.position = 'absolute';
            lista.style.zIndex = '2000';
            lista.style.width = '100%';
            lista.style.maxHeight = '240px';
            lista.style.overflow = 'auto';
            parent.style.position = 'relative';
            parent.appendChild(lista);
        }

        const debounced = debounce(function () { pesquisar(headerInput.value.trim(), lista, headerInput); }, 250);

        console.log('fallback: listener attached to #inputBuscaAdocao');

        headerInput.addEventListener('input', function () {
            console.log('fallback header input (input event):', headerInput.value);
            debounced();
        });

        // keyup como redundância (alguns navegadores/IME podem não disparar input imediatamente)
        headerInput.addEventListener('keyup', function () {
            console.log('fallback header input (keyup):', headerInput.value);
            debounced();
        });

        headerInput.addEventListener('focus', function () {
            pesquisar(headerInput.value.trim(), lista, headerInput);
        });
    } catch (e) {
        console.warn('Erro fallback inputBuscaAdocao', e);
    }
});

/* ===========================================================
   LOADER
=========================================================== */

function esconderLoader() {

    const loader = document.getElementById("loader");

    if (!loader) return;

    window.addEventListener("load", () => {

        setTimeout(() => {

            loader.style.opacity = "0";

            loader.style.visibility = "hidden";

            loader.style.transition = "0.5s";

        }, 800);

    });

}

/* ===========================================================
   HEADER STICKY
=========================================================== */

function configurarHeader() {

    const header = document.getElementById("header");

    if (!header) return;

    window.addEventListener("scroll", () => {

        if (window.scrollY > 80) {

            header.classList.add("scrolled");

        }

        else {

            header.classList.remove("scrolled");

        }

    });

}

/* ===========================================================
   BOTÃO VOLTAR AO TOPO
=========================================================== */

function configurarBotaoTopo() {

    const botao = document.getElementById("btnTopo");

    if (!botao) return;

    window.addEventListener("scroll", () => {

        if (window.scrollY > 400) {

            botao.style.display = "flex";

        }

        else {

            botao.style.display = "none";

        }

    });

    botao.addEventListener("click", () => {

        window.scrollTo({

            top: 0,

            behavior: "smooth"

        });

    });

}

/* ===========================================================
   UTILITÁRIO
=========================================================== */

function selecionar(id) {

    return document.querySelector(id);

}

function selecionarTodos(id) {

    return document.querySelectorAll(id);

}

/* ===========================================================
   MENSAGEM DE BOAS-VINDAS
=========================================================== */

console.log("%cPetFinder Brasil",

    "color:#ffffff;background:#1B365D;padding:10px;font-size:18px;border-radius:5px;");

console.log("Sistema carregado com sucesso.");

/* ===========================================================
   FIM DA PARTE 1
=========================================================== */

/* ===========================================================
   PARTE 2
   Carousel • Contadores • Animações
=========================================================== */

/* ===========================================================
   INICIALIZA COMPONENTES
=========================================================== */

document.addEventListener("DOMContentLoaded", () => {

    configurarCarousel();

    configurarContadores();

    configurarAnimacoes();

});

/* ===========================================================
   CAROUSEL
=========================================================== */

function configurarCarousel() {

    const carousel = document.querySelector("#heroCarousel");

    if (!carousel) return;

    if (typeof bootstrap !== "undefined") {

        new bootstrap.Carousel(carousel, {

            interval: 5000,

            pause: "hover",

            ride: "carousel",

            wrap: true,

            touch: true

        });

    }

}

/* ===========================================================
   CONTADORES ANIMADOS
=========================================================== */

function configurarContadores() {

    const numeros = document.querySelectorAll("[data-counter]");

    if (!numeros.length) return;

    const observer = new IntersectionObserver((entries) => {

        entries.forEach((entry) => {

            if (!entry.isIntersecting) return;

            animarNumero(entry.target);

            observer.unobserve(entry.target);

        });

    }, {

        threshold: .4

    });

    numeros.forEach((item) => observer.observe(item));

}

function animarNumero(elemento) {

    const destino = Number(elemento.dataset.counter);

    const duracao = 2000;

    const inicio = performance.now();

    function atualizar(tempo) {

        const progresso = Math.min(

            (tempo - inicio) / duracao,

            1

        );

        const valor = Math.floor(

            progresso * destino

        );

        elemento.textContent = valor.toLocaleString("pt-BR");

        if (progresso < 1) {

            requestAnimationFrame(atualizar);

        }

        else {

            elemento.textContent = destino.toLocaleString("pt-BR");

        }

    }

    requestAnimationFrame(atualizar);

}

/* ===========================================================
   ANIMAÇÕES AO APARECER
=========================================================== */

function configurarAnimacoes() {

    const elementos = document.querySelectorAll(

        ".fade-up,.fade-left,.fade-right,.fade-scale"

    );

    if (!elementos.length) return;

    const observer = new IntersectionObserver(

        (entries) => {

            entries.forEach((entry) => {

                if (entry.isIntersecting) {

                    entry.target.classList.add("show");

                    observer.unobserve(entry.target);

                }

            });

        },

        {

            threshold: .15

        }

    );

    elementos.forEach((item) => {

        observer.observe(item);

    });

}

/* ===========================================================
   REVELAR ELEMENTOS
=========================================================== */

document.addEventListener("DOMContentLoaded", () => {

    document.querySelectorAll(

        ".fade-up,.fade-left,.fade-right,.fade-scale"

    ).forEach((item) => {

        item.style.opacity = "0";

    });

});

window.addEventListener("load", () => {

    document.querySelectorAll(

        ".fade-up,.fade-left,.fade-right,.fade-scale"

    ).forEach((item) => {

        item.classList.add("animate-ready");

    });

});

/* ===========================================================
   EFEITO SUAVE PARA LINKS INTERNOS
=========================================================== */

document.querySelectorAll('a[href^="#"]').forEach((link) => {

    link.addEventListener("click", function (e) {

        const destino = document.querySelector(

            this.getAttribute("href")

        );

        if (!destino) return;

        e.preventDefault();

        destino.scrollIntoView({

            behavior: "smooth",

            block: "start"

        });

    });

});

/* ===========================================================
   OTIMIZAÇÃO DE SCROLL
=========================================================== */

let ultimoScroll = 0;

window.addEventListener("scroll", () => {

    ultimoScroll = window.scrollY;

}, {

    passive: true

});

/* ===========================================================
   LOG
=========================================================== */

console.log("Parte 2 carregada.");

/* ===========================================================
   FIM DA PARTE 2
=========================================================== */

/* ===========================================================
   PARTE 3
   Busca Inteligente
=========================================================== */

/* ===========================================================
   DADOS DE EXEMPLO
   (Posteriormente serão carregados do banco de dados)
=========================================================== */

const sugestoesPesquisa = [

    "Ração para Cachorro",
    "Ração para Gato",
    "Banho e Tosa",
    "Veterinário",
    "Pet Shop",
    "Hotel para Pets",
    "Adestrador",
    "Creche Pet",
    "Medicamentos",
    "Brinquedos",
    "Coleiras",
    "Areia para Gatos",
    "Aquários",
    "Pássaros",
    "Peixes Ornamentais",
    "Consulta Veterinária",
    "Vacinas",
    "Adoção",
    "Clínica Veterinária",
    "Hospedagem"

];

/* ===========================================================
   INICIALIZAÇÃO
=========================================================== */

document.addEventListener("DOMContentLoaded", () => {

    iniciarBusca();

});

/* ===========================================================
   BUSCA
=========================================================== */

function iniciarBusca() {

    // Suporta múltiplos campos de busca (campo principal e barra do header)
    const campos = Array.from(document.querySelectorAll("#campoPesquisa, #inputBuscaAdocao, .hero-search input"));

    if (!campos.length) return;

    // Para cada campo, cria/usa uma lista de sugestões logo abaixo do campo
    campos.forEach(function (campo) {

        // procura um container de lista já existente próximo ao campo
        let lista = campo.parentElement.querySelector('.lista-sugestoes');

        if (!lista) {
            lista = document.createElement('ul');
            lista.className = 'list-group lista-sugestoes';
            lista.style.position = 'absolute';
            lista.style.zIndex = '2000';
            lista.style.width = '100%';
            lista.style.maxHeight = '240px';
            lista.style.overflow = 'auto';
            campo.parentElement.style.position = 'relative';
            campo.parentElement.appendChild(lista);
        }

        const debounced = debounce(function () { pesquisar(campo.value.trim(), lista, campo); }, 250);
        campo.addEventListener('input', debounced);

        campo.addEventListener('focus', function () {
            pesquisar(campo.value.trim(), lista, campo);
        });

    });

    // Clique fora fecha todas as listas
    document.addEventListener('click', function (e) {
        campos.forEach(function (campo) {
            const lista = campo.parentElement.querySelector('.lista-sugestoes');
            if (!lista) return;
            if (!lista.contains(e.target) && e.target !== campo) {
                lista.innerHTML = '';
            }
        });
    });

}

/* ===========================================================
   PESQUISA
=========================================================== */

function pesquisar(texto, lista, campo) {

    console.log('pesquisar: texto=', texto);

    lista.innerHTML = '';

    if (texto.length === 0) return;

    // mostra item de carregamento
    const carregando = document.createElement('li');
    carregando.className = 'list-group-item text-muted';
    carregando.textContent = 'Buscando...';
    lista.appendChild(carregando);

    const url = 'app/ajax/sugestoes_pesquisa.php?q=' + encodeURIComponent(texto);
    console.log('pesquisar: fetch ->', url);

    fetch(url)
        .then(function (res) {
            if (!res.ok) throw new Error('Erro na requisição: ' + res.status);
            return res.json();
        })
        .then(function (dados) {
            console.log('pesquisar: recebido', dados);
            lista.innerHTML = '';
            if (!Array.isArray(dados) || dados.length === 0) return;
            dados.forEach(function (item) {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action';
                li.style.cursor = 'pointer';
                li.innerHTML = `<i class="bi bi-search me-2"></i> ${item}`;
                li.addEventListener('click', function () {
                    selecionarPesquisa(item, lista);
                });
                lista.appendChild(li);
            });
        })
        .catch(function (err) {
            console.error('Erro sugestões:', err);
            lista.innerHTML = '';
        });

}

/* ===========================================================
   SELEÇÃO
=========================================================== */

function selecionarPesquisa(valor, lista) {

    // tenta encontrar o campo associado à lista (o campo é irmão/child do mesmo parent)
    let campo = null;
    if (lista && lista.parentElement) {
        campo = lista.parentElement.querySelector('input');
    }

    // fallback para o campo principal
    if (!campo) campo = document.querySelector('#campoPesquisa');

    if (!campo) return;

    campo.value = valor;
    lista.innerHTML = '';
    salvarHistorico(valor);
    console.log('Pesquisa:', valor);

    if (campo.id === 'inputBuscaAdocao') {
        if (typeof window.buscarPets === 'function') {
            try { window.buscarPets(); } catch (e) { /* ignore */ }
        }
        return;
    }

    if (campo.id === 'campoPesquisa') {
        window.location.href = "public/pesquisa.php?q=" + encodeURIComponent(valor);
        return;
    }

}

/* ===========================================================
   HISTÓRICO
=========================================================== */

function salvarHistorico(valor) {

    let historico = JSON.parse(

        localStorage.getItem("historicoPesquisa")

    ) || [];

    historico = historico.filter(item => item !== valor);

    historico.unshift(valor);

    if (historico.length > 10) {

        historico.pop();

    }

    localStorage.setItem(

        "historicoPesquisa",

        JSON.stringify(historico)

    );

}

/* ===========================================================
   EXIBIR HISTÓRICO
=========================================================== */

function carregarHistorico() {

    const historico = JSON.parse(

        localStorage.getItem("historicoPesquisa")

    ) || [];

    console.table(historico);

}

/* ===========================================================
   BOTÃO PESQUISAR
=========================================================== */

document.addEventListener("DOMContentLoaded", () => {

    const botao = document.querySelector("#btnPesquisar");

    if (!botao) return;

    botao.addEventListener("click", () => {

        const campo = document.querySelector("#campoPesquisa");
        const texto = campo ? campo.value.trim() : "";

        if (texto === "") {
            alert("Digite o que deseja pesquisar.");
            return;
        }

        salvarHistorico(texto);
        window.location.href = "public/pesquisa.php?q=" + encodeURIComponent(texto);

    });

});

/* ===========================================================
   PESQUISA AO PRESSIONAR ENTER
=========================================================== */

document.addEventListener("DOMContentLoaded", () => {

    const campo = document.querySelector("#campoPesquisa");

    if (!campo) return;

    campo.addEventListener("keypress", (e) => {

        if (e.key === "Enter") {

            e.preventDefault();

            document
                .querySelector("#btnPesquisar")
                ?.click();

        }

    });

});

/* ===========================================================
   LOG
=========================================================== */

console.log("Busca Inteligente carregada.");

/* ===========================================================
   FIM DA PARTE 3
=========================================================== */