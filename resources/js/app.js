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
    const navBtn = document.querySelector('[data-nav-toggle]');
    const menu = document.getElementById('menu-publik');
    if (navBtn && menu) {
        navBtn.addEventListener('click', () => {
            const terbuka = menu.classList.toggle('d-flex');
            menu.classList.toggle('d-none', ! terbuka);
            navBtn.setAttribute('aria-expanded', String(terbuka));
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

document.addEventListener('DOMContentLoaded', () => {
    initReveal();
    initPublik();
});
