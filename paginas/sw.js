// Service worker do PedirJá (PWA).
// Guarda só os arquivos estáticos (CSS, JS, ícone) para abrir rápido.
// Páginas PHP e API sempre vão ao servidor: dados de pedido nunca vêm do cache.
const VERSAO = "pedirja-v3";
const ESTATICOS = [
    "../assets/css/app.css",
    "../assets/js/app.js",
    "../assets/js/cliente.js",
    "../assets/js/fila.js",
    "../assets/js/cozinha.js",
    "../assets/js/garcom.js",
    "../assets/js/admin.js",
    "../assets/img/icone.svg",
    "manifest.json",
];

self.addEventListener("install", evento => {
    evento.waitUntil(caches.open(VERSAO).then(c => c.addAll(ESTATICOS)).then(() => self.skipWaiting()));
});

self.addEventListener("activate", evento => {
    evento.waitUntil(
        caches.keys()
            .then(chaves => Promise.all(chaves.filter(k => k !== VERSAO).map(k => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener("fetch", evento => {
    const url = new URL(evento.request.url);
    const estatico = evento.request.method === "GET" && url.origin === location.origin && /\/(assets\/|manifest\.json)/.test(url.pathname);
    if (!estatico) return;   // PHP e API: direto na rede

    // Rede primeiro (pega a versão nova), cache se estiver sem internet
    evento.respondWith(
        fetch(evento.request)
            .then(resp => {
                const copia = resp.clone();
                caches.open(VERSAO).then(c => c.put(evento.request, copia));
                return resp;
            })
            .catch(() => caches.match(evento.request))
    );
});
