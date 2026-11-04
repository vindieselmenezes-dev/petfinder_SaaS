/*
 * Service Worker do EcoSistemPet.
 *
 * Estratégia:
 * - CSS, JS e manifest: network-first (sempre confere na rede; o cache só
 *   entra se estiver sem internet). Assim a página nova nunca aparece com
 *   CSS/JS antigo e não é preciso recarregar duas vezes.
 * - Imagens e ícones: cache-first, com atualização em segundo plano.
 * - Páginas (navegação / *.php / index.html): network-first. Nunca servimos
 *   página PHP do cache (CSRF e sessão ficam velhos). Sem rede, cai na
 *   página offline.
 * - Requisições que não sejam GET e chamadas a app/ (ajax) vão direto
 *   para a rede, sem cache.
 * - O prefixo do site (ex.: /petfinder-SaaS/ no XAMPP, / na hospedagem)
 *   vem do escopo do service worker, então o arquivo é o mesmo nos dois.
 */

const VERSION = "petfinder-v26";
const STATIC_CACHE = `${VERSION}-static`;
const OFFLINE_URL = "offline.html";
const BASE = new URL(self.registration.scope).pathname; // termina com "/"

const STATIC_ASSETS = [
    "assets/css/style.css",
    "assets/css/dashboard.css",
    "assets/img/logo.png",
    "assets/img/icons/icon-192v2.png",
    "assets/img/icons/icon-512v2.png",
    "manifest.json",
    OFFLINE_URL,
];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) =>
            Promise.all(
                STATIC_ASSETS.map((url) =>
                    cache.add(new Request(url, { cache: "reload" })).catch(() => {
                        /* arquivo ausente não derruba a instalação */
                    })
                )
            )
        )
    );
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key.startsWith("petfinder-") && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener("push", (event) => {
    let dados = {};
    try {
        dados = event.data ? event.data.json() : {};
    } catch (erro) {
        dados = { body: event.data ? event.data.text() : "" };
    }

    const url = dados.url || BASE + "public/notificacoes.php";
    // Alertas de pet perdido/encontrado: vibração longa e sem agrupar com as outras.
    const alertaPet = /pets_(encontrados|perdidos)/.test(url);

    event.waitUntil(Promise.all([
        self.registration.showNotification(dados.title || "EcoSistemPet", {
            body: dados.body || "Você tem uma nova atualização.",
            icon: BASE + "assets/img/icons/icon-192v2.png",
            vibrate: alertaPet ? [300, 100, 300, 100, 600] : [150],
            tag: alertaPet ? "alerta-pet" : undefined,
            renotify: alertaPet,
            data: { url }
        }),
        // Se o site/app estiver aberto, a página toca o latido (o som da
        // notificação em si é do sistema e não pode ser trocado).
        self.clients.matchAll({ type: "window", includeUncontrolled: true }).then((abas) =>
            abas.forEach((aba) => aba.postMessage({ tipo: alertaPet ? "alerta-pet" : "notificacao" }))
        )
    ]));
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});

function isCodeAsset(url) {
    return /\.(css|js)$/.test(url.pathname) || url.pathname.endsWith("manifest.json");
}

function isStaticAsset(url) {
    return (
        url.pathname.startsWith(BASE + "assets/") ||
        url.pathname.endsWith("manifest.json")
    );
}

self.addEventListener("fetch", (event) => {
    const { request } = event;

    if (request.method !== "GET") {
        return;
    }

    const url = new URL(request.url);

    // Outra origem (CDN do Bootstrap etc.): não mexe.
    if (url.origin !== self.location.origin) {
        return;
    }

    // Chamadas ajax e demais arquivos de app/: sempre direto na rede.
    if (url.pathname.startsWith(BASE + "app/")) {
        return;
    }

    if (isStaticAsset(url) && isCodeAsset(url)) {
        event.respondWith(
            fetch(request, { cache: "no-cache" })
                .then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() => caches.match(request).then((cached) => cached || Response.error()))
        );
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fetchPromise = fetch(request, { cache: "no-cache" })
                    .then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
                        }
                        return response;
                    })
                    .catch(() => cached || Response.error());
                return cached || fetchPromise;
            })
        );
        return;
    }

    if (request.mode === "navigate") {
        event.respondWith(
            fetch(request).catch(() =>
                caches.match(OFFLINE_URL).then((offline) => offline || Response.error())
            )
        );
    }
});