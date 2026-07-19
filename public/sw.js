// Service worker mínimo de GestioDia (AGENT.md §10).
// - Shell estático (CSS/JS/fuentes): stale-while-revalidate.
// - Vistas y datos (HTML de navegación): SIEMPRE red, nunca caché — evita
//   mostrar tareas/jornadas desactualizadas. Sin sync offline en el MVP.
const SHELL_CACHE = 'gestiodia-shell-v1';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== SHELL_CACHE).map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    // Navegación / HTML: red pura, sin caché. Si falla, que el navegador
    // muestre su propia pantalla de sin conexión — no ocultamos un fallo
    // de red detrás de una vista vieja.
    if (request.mode === 'navigate' || request.destination === 'document') {
        event.respondWith(fetch(request));
        return;
    }

    // Shell estático: stale-while-revalidate.
    if (['style', 'script', 'font'].includes(request.destination)) {
        event.respondWith(
            caches.open(SHELL_CACHE).then((cache) => cache.match(request).then((cached) => {
                const network = fetch(request).then((response) => {
                    cache.put(request, response.clone());
                    return response;
                }).catch(() => cached);

                return cached || network;
            }))
        );
    }

    // Cualquier otra petición (imágenes, fotos de evidencia, etc.): red directa.
});
