/*
 * Service worker PWA POS — offline SHELL saja, bukan offline data.
 *
 * Keputusan sadar (lihat docs/09): POS bergantung pada stok & harga yang
 * SEGAR — HTML terautentikasi dan data transaksi TIDAK PERNAH di-cache,
 * dan checkout offline sengaja tidak dibuat (risiko oversell/stok basi).
 * Yang di-cache hanya aset statis tak berubah (build Vite ber-hash, font,
 * ikon) + satu halaman fallback ketika navigasi gagal karena offline.
 *
 * Vanilla JS tanpa build step — patuh CSP (worker-src 'self').
 */

const VERSI = 'sirc-v1';
const CACHE_ASET = `${VERSI}-aset`;
const CACHE_SHELL = `${VERSI}-shell`;
const HALAMAN_OFFLINE = '/offline.html';

/* Prefix path yang aman di-cache (immutable / jarang berubah). */
const PREFIX_ASET = ['/build/', '/fonts/', '/icons/'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_SHELL)
            .then((cache) => cache.add(new Request(HALAMAN_OFFLINE, { cache: 'reload' })))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((kunci) => Promise.all(
                kunci.filter((k) => ! k.startsWith(VERSI)).map((k) => caches.delete(k)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Hanya GET same-origin; POST/PUT (checkout, opname, …) tidak disentuh.
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Aset statis: cache-first — nama file build ber-hash, aman disimpan lama.
    if (PREFIX_ASET.some((p) => url.pathname.startsWith(p))) {
        event.respondWith(
            caches.open(CACHE_ASET).then((cache) =>
                cache.match(req).then((hit) => hit ?? fetch(req).then((res) => {
                    if (res.ok) cache.put(req, res.clone());
                    return res;
                })),
            ),
        );
        return;
    }

    // Navigasi halaman: SELALU jaringan (HTML terautentikasi tidak di-cache);
    // bila gagal karena offline, tampilkan halaman fallback bermerek.
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() =>
                caches.match(HALAMAN_OFFLINE).then((hit) => hit ?? Response.error()),
            ),
        );
    }

    // Request lain (manifest, robots, …): biarkan default browser.
});
