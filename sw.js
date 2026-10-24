/*
 * Service Worker do EcoSistemPet.
 *
 * Estratégia:
 * - Assets estáticos (css/js/imagens/ícones): cache-first, com atualização
 *   em segundo plano.
 * - Páginas (navegação / *.php / index.html): network-first. Nunca servimos
 *   página PHP do cache (CSRF e sessão ficam velhos). Sem rede, cai na
 *   página offline.
 * - Requisições que não sejam GET e chamadas a app/ (ajax) vão direto
 *   para a rede, sem cache.
 * - O prefixo do site (ex.: /petfinder-SaaS/ no XAMPP, / na hospedagem)
 *   vem do escopo do service worker, então o arquivo é o mesmo nos dois.
 */

const VERSION = "petfinder-v11";
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
                    cache.add(url).catch(() => {
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
    const dados = event.data ? event.data.json() : {};
    event.waitUntil(self.registration.showNotification(dados.title || "EcoSistemPet", {
        body: dados.body || "Você tem uma nova atualização.",
        icon: BASE + "assets/img/icons/icon-192v2.png",
        data: { url: dados.url || BASE + "public/notificacoes.php" }
    }));
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});

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

    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fetchPromise = fetch(request)
                    .then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
                        }
                        return response;
                    })
                    .catch(() => cached);
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