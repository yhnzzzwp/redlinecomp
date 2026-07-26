import 'bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// View transition antar halaman: lewati bila tab tersembunyi, dan pasang
// watchdog — bila animasi tidak kunjung selesai (renderer di-throttle),
// overlay snapshot bisa menggantung menutupi halaman. skipTransition()
// aman dipanggil walau transisi sudah selesai.
const guardTransition = (e) => {
    if (! e.viewTransition) return;
    if (document.visibilityState === 'hidden') {
        e.viewTransition.skipTransition();
        return;
    }
    setTimeout(() => e.viewTransition.skipTransition(), 800);
};
window.addEventListener('pageswap', guardTransition);
window.addEventListener('pagereveal', guardTransition);

const initReveal = () => {
    const els = document.querySelectorAll('[data-reveal]');
    if (! els.length) return;

    document.documentElement.classList.add('reveal-ready');

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced || ! ('IntersectionObserver' in window)) {
        els.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    // rootMargin atas dibuat sangat besar: elemen yang sudah TERLEWAT
    // (di atas viewport, mis. karena lompat scroll/anchor) tetap dianggap
    // tampil — tanpa ini elemen bisa selamanya tinggal di opacity 0.
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '9999px 0px -40px 0px' });

    els.forEach((el) => io.observe(el));
};

// Interaksi zona publik dalam vanilla JS — zona ini berjalan di bawah CSP
// ketat tanpa 'unsafe-eval', jadi tidak boleh bergantung pada Alpine.
const initPublik = () => {
    // Drawer menu HP: meluncur dari kanan; tutup via overlay, tombol X, atau Escape.
    const navBtn = document.querySelector('[data-nav-toggle]');
    const menu = document.getElementById('menu-publik');
    const overlay = document.querySelector('[data-nav-overlay]');
    const closeBtn = document.querySelector('[data-nav-close]');
    if (navBtn && menu && overlay) {
        const setDrawer = (buka) => {
            menu.classList.toggle('open', buka);
            overlay.classList.toggle('open', buka);
            navBtn.setAttribute('aria-expanded', String(buka));
            document.body.classList.toggle('rl-no-scroll', buka);
            if (buka) menu.querySelector('a')?.focus();
        };
        navBtn.addEventListener('click', () => setDrawer(! menu.classList.contains('open')));
        overlay.addEventListener('click', () => setDrawer(false));
        closeBtn?.addEventListener('click', () => setDrawer(false));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && menu.classList.contains('open')) setDrawer(false);
        });
    }

    const filterBtn = document.querySelector('[data-filter-toggle]');
    const filterBody = document.getElementById('filter-katalog');
    if (filterBtn && filterBody) {
        filterBtn.addEventListener('click', () => {
            const terbuka = filterBody.classList.toggle('open');
            filterBtn.classList.toggle('open', terbuka);
            filterBtn.setAttribute('aria-expanded', String(terbuka));
        });
    }
};

// PWA POS: daftarkan service worker HANYA di halaman yang memasang link
// manifest (= portal internal; zona publik tidak pernah mendaftar).
// SW meng-cache app shell saja — HTML & data POS selalu dari jaringan.
const initPwa = () => {
    if (! ('serviceWorker' in navigator)) return;
    if (! document.querySelector('link[rel="manifest"]')) return;

    navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' })
        .catch(() => { /* gagal daftar (mis. mode privat) — aplikasi tetap normal */ });
};

// WA semi-otomatis: setelah ganti status servis, buka WhatsApp dengan pesan
// terisi tanpa klik tambahan. Bila popup diblokir, banner bertombol di halaman
// tetap jadi fallback 1-klik. Hanya jalan pada muat halaman biasa (bfcache
// tidak memicu DOMContentLoaded — tidak ada buka-ulang saat tombol back).
const initWaAuto = () => {
    const banner = document.querySelector('[data-wa-auto]');
    if (! banner) return;
    window.open(banner.dataset.waAuto, '_blank', 'noopener');
};

document.addEventListener('DOMContentLoaded', () => {
    initReveal();
    initPublik();
    initPwa();
    initWaAuto();
});
