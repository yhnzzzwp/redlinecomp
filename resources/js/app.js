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

document.addEventListener('DOMContentLoaded', initReveal);
