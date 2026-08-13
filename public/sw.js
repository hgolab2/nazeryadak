/**
 * Service Worker — ناظر یدک
 *
 * Strategies
 *   - navigations (HTML) : network-first, falls back to cache, then /offline.html
 *   - static assets      : stale-while-revalidate (css, js, fonts, images)
 *   - everything else    : straight to network, never cached
 *
 * Bump CACHE_VERSION whenever the precached shell changes — old caches are
 * dropped on activate.
 */

const CACHE_VERSION = 'v1';
const STATIC_CACHE = `nazeryadak-static-${CACHE_VERSION}`;
const PAGES_CACHE = `nazeryadak-pages-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

/** Cap on stored pages — a catalogue this size would otherwise fill the quota. */
const MAX_CACHED_PAGES = 60;

/**
 * Shell assets fetched up front: the offline page plus the stylesheets every
 * page loads, so a cached page still renders correctly with no connection.
 */
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/assets/css/bootstrap.rtl.css',
    '/assets/css/style.css',
    '/assets/css/home-digikala.css',
    '/assets/css/mobile-appbar.css',
    '/assets/fontawesome/css/all.min.css',
    '/assets/images/logo.png',
    '/assets/images/pwa/icon-192.png',
    '/assets/images/pwa/icon-512.png',
];

/**
 * Paths that must always hit the network: anything user-specific,
 * transactional, or admin-only. Caching these would leak one user's data
 * to the next or serve a stale cart/order state.
 */
const NEVER_CACHE = [
    /^\/admin(\/|$)/,
    /^\/cart(\/|$)/,
    /^\/order(\/|$)/,
    /^\/profile(\/|$)/,
    /^\/payment(\/|$)/,
    /^\/login(\/|$)/,
    /^\/register(\/|$)/,
    /^\/logout(\/|$)/,
    /^\/dashboard(\/|$)/,
    /^\/favorite(\/|$)/,
    /^\/sitemap\.xml$/,
    /^\/product\/fetch-image\//,
];

const STATIC_ASSET = /\.(?:css|js|woff2?|ttf|eot|otf|png|jpe?g|gif|svg|webp|avif|ico)$/i;

function isNeverCached(pathname) {
    return NEVER_CACHE.some((pattern) => pattern.test(pathname));
}

/**
 * Drops the oldest entries once a cache exceeds `maxItems`. Cache keys come
 * back in insertion order, so the head of the list is the least recent.
 */
async function trimCache(cache, maxItems) {
    const keys = await cache.keys();
    if (keys.length <= maxItems) return;

    await Promise.all(
        keys.slice(0, keys.length - maxItems).map((key) => cache.delete(key))
    );
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) =>
            // addAll() is atomic — a single 404 would abort the whole install,
            // so each asset is added on its own and failures are tolerated.
            Promise.all(
                PRECACHE_URLS.map((url) =>
                    cache.add(new Request(url, { cache: 'reload' })).catch(() => null)
                )
            )
        ).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== STATIC_CACHE && key !== PAGES_CACHE)
                    .map((key) => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // Same-origin only — third-party requests (analytics, CDNs) pass through.
    if (url.origin !== self.location.origin) return;

    if (isNeverCached(url.pathname)) return;

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigation(request));
        return;
    }

    if (STATIC_ASSET.test(url.pathname)) {
        event.respondWith(handleStaticAsset(request));
    }
});

/**
 * Network-first: users always get fresh prices and stock when online, and a
 * previously visited page (or the offline notice) when they are not.
 */
async function handleNavigation(request) {
    try {
        const response = await fetch(request);

        if (response && response.ok && response.type === 'basic') {
            const cache = await caches.open(PAGES_CACHE);
            await cache.put(request, response.clone());
            trimCache(cache, MAX_CACHED_PAGES);
        }

        return response;
    } catch (error) {
        const cached = await caches.match(request, { ignoreSearch: true });
        if (cached) return cached;

        const offline = await caches.match(OFFLINE_URL);
        if (offline) return offline;

        return new Response('<h1>آفلاین هستید</h1>', {
            status: 503,
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        });
    }
}

/** Stale-while-revalidate: instant paint from cache, refreshed in the background. */
async function handleStaticAsset(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);

    const network = fetch(request)
        .then((response) => {
            if (response && response.ok && response.type === 'basic') {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => null);

    if (cached) return cached;

    const response = await network;
    if (response) return response;

    return new Response('', { status: 504, statusText: 'Gateway Timeout' });
}
