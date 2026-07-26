/*
 * Service worker PWA POS — offline SHELL saja, bukan offline data.
 *
 * Keputusan sadar (lihat docs/09): POS bergantung pada stok & harga yang
 * SEGAR — HTML terautentikasi dan data transaksi TIDAK PERNAH di-cache,
 * dan checkout offline sengaja tidak dibuat (risiko oversell/stok basi).
 *
 * Strategi per jenis aset:
 *   /build/          cache-first  (nama file Vite ber-hash — immutable)
 *   /fonts/ /icons/  stale-while-revalidate (nama tetap, isi bisa diganti —
 *                    sajikan cache segera, perbarui diam-diam di belakang)
 *   navigasi         SELALU jaringan; fallback offline.html saat terputus
 *   selain GET       tidak disentuh sama sekali (checkout/opname aman)
 *
 * Vanilla JS tanpa build step — patuh CSP (worker-src 'self').
 * Sintaks dijaga kompatibel Safari/iOS >= 11.3 (tanpa `??` / optional chaining).
 *
 * Catatan bump VERSI: skipWaiting + clients.claim membuat versi baru langsung
 * mengambil alih tab yang terbuka dan menghapus cache versi lama — aman selama
 * strategi tetap "shell saja"; bila kelak menyimpan chunk yang di-lazy-import,
 * pertimbangkan menunda penghapusan cache lama satu versi.
 */

const VERSI = 'sirc-v1';
const CACHE_ASET = VERSI + '-aset';
const CACHE_SHELL = VERSI + '-shell';
const HALAMAN_OFFLINE = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_SHELL)
            .then((cache) => cache.add(new Request(HALAMAN_OFFLINE, { cache: 'reload' })))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    const dipakai = [CACHE_ASET, CACHE_SHELL];
    event.waitUntil(
        caches.keys()
            .then((kunci) => Promise.all(
                // Nama eksak, bukan prefix — 'sirc-v1' jangan ikut menghapus
                // 'sirc-v10-*' saat rollback versi.
                kunci.filter((k) => dipakai.indexOf(k) === -1).map((k) => caches.delete(k)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Hanya GET same-origin; POST/PUT (checkout, opname, …) tidak disentuh —
    // penjaga keputusan "transaksi offline tidak didukung".
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Layak disimpan: sukses DAN bukan hasil redirect — jangan sampai HTML
    // (mis. halaman login) ter-cache di bawah kunci URL aset. Penyimpanan
    // berjalan di belakang (waitUntil) dan kegagalannya (kuota penuh, 206)
    // tidak boleh mengganggu respons.
    const simpan = (cache, res) => {
        if (res.ok && ! res.redirected) {
            event.waitUntil(cache.put(req, res.clone()).catch(() => {}));
        }
        return res;
    };

    // Build Vite ber-hash: cache-first.
    if (url.pathname.indexOf('/build/') === 0) {
        event.respondWith(
            caches.open(CACHE_ASET).then((cache) =>
                cache.match(req).then((hit) => hit || fetch(req).then((res) => simpan(cache, res))),
            ),
        );
        return;
    }

    // Font & ikon (nama tetap): stale-while-revalidate.
    if (url.pathname.indexOf('/fonts/') === 0 || url.pathname.indexOf('/icons/') === 0) {
        event.respondWith(
            caches.open(CACHE_ASET).then((cache) =>
                cache.match(req).then((hit) => {
                    const revalidasi = fetch(req)
                        .then((res) => simpan(cache, res))
                        .catch(() => hit || Response.error());
                    if (hit) {
                        event.waitUntil(revalidasi.then(() => undefined, () => undefined));
                        return hit;
                    }
                    return revalidasi;
                }),
            ),
        );
        return;
    }

    // Navigasi halaman: SELALU jaringan (HTML terautentikasi tidak di-cache);
    // bila gagal karena offline, tampilkan halaman fallback bermerek.
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() =>
                caches.match(HALAMAN_OFFLINE).then((hit) => hit || Response.error()),
            ),
        );
    }

    // Request lain (manifest, robots, …): biarkan default browser.
});
