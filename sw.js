/*
 * Service Worker do PetFinder Brasil.
 *
 * Estratégia:
 * - Assets estáticos (css/js/imagens/ícones): cache-first, com atualização
 *   em segundo plano. São arquivos que raramente mudam e não têm nada
 *   pessoal do usuário.
 * - Páginas (navegação / *.php / index.html): network-first. NUNCA servimos
 *   uma página PHP do cache como resposta principal, porque elas carregam
 *   token CSRF e estado de sessão (login, carrinho etc.) que fica velho
 *   instantaneamente. Só caímos pra uma página "offline" simples quando
 *   não há rede.
 * - Qualquer requisição que não seja GET (POST de formulário, por exemplo)
 *   nunca passa pelo cache — vai direto pra rede, do jeito que já era.
 */

const VERSION = "petfinder-v2";
const STATIC_CACHE = `${VERSION}-static`;
const OFFLINE_URL = "offline.html";

const STATIC_ASSETS = [
    "assets/css/style.css",
    "assets/css/dashboard.css",
    "assets/img/logo.png",
    "assets/img/icons/icon-192.png",
    "assets/img/icons/icon-512.png",
    "manifest.json",
    OFFLINE_URL,
];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            // addAll falha inteiro se 1 arquivo faltar; adiciona um a um
            // pra um asset ausente não derrubar a instalação toda.
            return Promise.all(
                STATIC_ASSETS.map((url) =>
                    cache.add(url).catch(() => {
                        /* arquivo pode não existir nesta página; ignora */
                    })
                )
            );
        })
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

function isStaticAsset(url) {
    return (
        url.pathname.startsWith("/petfinder-SaaS/assets/") ||
        url.pathname.endsWith("manifest.json")
    );
}

self.addEventListener("fetch", (event) => {
    const { request } = event;

    // Só mexemos em GET. POST/PUT/DELETE (login, formulários com CSRF,
    // compras etc.) sempre vão direto pra rede, sem passar pelo SW.
    if (request.method !== "GET") {
        return;
    }

    const url = new URL(request.url);

    // Não mexe em requisições de outra origem (CDN do Bootstrap etc.)
    if (url.origin !== self.location.origin) {
        return;
    }

    if (isStaticAsset(url)) {
        // Cache-first para assets estáticos.
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

    // Navegação de página (index.html, telas .php): network-first.
    if (request.mode === "navigate") {
        event.respondWith(
            fetch(request).catch(() =>
                caches.match(OFFLINE_URL).then((offline) => offline || Response.error())
            )
        );
    }
});
