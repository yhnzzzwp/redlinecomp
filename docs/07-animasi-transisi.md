# 07 — Guideline Animasi & Transisi

Panduan menambah animasi transisi secara konsisten ke seluruh aplikasi.

## Prinsip dasar
1. **Hanya animasikan `transform` & `opacity`** — keduanya di-GPU, mulus 60fps. Hindari menganimasikan `width/height/top/left/margin` (memicu reflow, patah-patah).
2. **Cepat & halus** — durasi 120–350ms. Terlalu lama terasa lambat.
3. **Selalu hormati `prefers-reduced-motion`** — sebagian pengguna sensitif gerakan.
4. **Animasi memperjelas, bukan menghias** — tiap transisi harus punya alasan (menunjukkan perubahan state / kesinambungan halaman).

## Token durasi (sudah ada di `app.css`)
```css
--ease: cubic-bezier(.4, 0, .2, 1);
--dur-fast: .12s;   /* tekan tombol */
--dur: .2s;         /* hover, warna */
--dur-slow: .35s;   /* masuk halaman */
```

---

## Lapisan 1 — Transisi antar halaman (memperbaiki "kedip refresh")
**Penyebab keluhan:** aplikasi ini server-rendered (MPA). Tiap klik/submit = load halaman baru → terasa refresh.

**Solusi (sudah aktif):** **View Transitions API**. Cukup satu baris CSS membuat SEMUA navigasi & submit-form-yang-redirect (mis. update status servis) menjadi *cross-fade*:
```css
@view-transition { navigation: auto; }
```
Kustomisasi arah animasi:
```css
::view-transition-old(root) { animation: rl-vt-out .2s var(--ease) both; }
::view-transition-new(root) { animation: rl-vt-in .35s var(--ease) both; }
```
**Shell tetap diam** saat pindah halaman dengan memberi `view-transition-name` unik:
```css
.rl-side  { view-transition-name: rl-sidebar; }
.rl-topbar { view-transition-name: rl-topbar; }
```
Elemen bernama sama di dua halaman akan *dipertahankan* (tidak ikut fade) — sidebar & topbar terasa "tetap", hanya konten yang beralih. Ini yang bikin terasa seperti aplikasi, bukan web statis.

**Dukungan browser:** Chrome/Edge 126+, Safari 18.2+. Firefox belum → otomatis fallback ke navigasi biasa (tanpa error). Tidak perlu JavaScript.

**Ingin animasi berbeda per halaman?** Beri nama unik ke elemen yang ingin dianimasikan khusus, lalu targetkan `::view-transition-group(nama)`.

---

## Lapisan 2 — Transisi mikro elemen (hover / active / focus)
Sudah aktif untuk tombol, nav, kartu, baris tabel, dan input. Untuk **komponen baru**, ikut pola:
```css
.komponen-baru {
  transition: background-color var(--dur) var(--ease),
              box-shadow var(--dur) var(--ease),
              transform var(--dur-fast) var(--ease);
}
.komponen-baru:hover  { transform: translateY(-1px); }
.komponen-baru:active { transform: scale(.985); }
```
Aturan: hover = angkat halus, active = tekan halus, focus = ring (`box-shadow`).

---

## Lapisan 3 — Elemen dinamis in-page (Alpine `x-transition`)
Untuk modal, dropdown, toast, accordion — yang muncul/hilang **tanpa** pindah halaman. Alpine sudah terpasang.
```html
<div x-data="{ open: false }">
  <button @click="open = !open" class="btn-redline">Buka</button>

  <div x-show="open" x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 translate-y-2"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    Konten muncul dengan animasi.
  </div>
</div>
```
`x-cloak` (sudah ada di CSS) menyembunyikan elemen sebelum Alpine siap, mencegah "kedip".

**Contoh toast sukses** (ganti kartu flash statis di layout admin):
```html
<div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)">
  {{ session('success') }}
</div>
```

---

## Lapisan 4 (opsional, lanjutan) — Update tanpa reload sama sekali
Jika ingin aksi seperti "update status servis" mengubah data **tanpa** navigasi apa pun:
- **Livewire** atau **htmx** — mengganti sebagian halaman via AJAX, transisi diatur sendiri.
- Trade-off: menambah kompleksitas & dependensi. Untuk skala proyek ini (≤10 pengguna internal), **View Transitions (Lapisan 1) sudah cukup** dan paling sederhana. Pertimbangkan Lapisan 4 hanya bila benar-benar perlu interaksi real-time.

---

## Checklist saat menambah fitur/komponen baru
- [ ] Elemen interaktif punya `transition` (Lapisan 2).
- [ ] Elemen yang persist antar halaman diberi `view-transition-name` unik.
- [ ] Popup/dropdown/toast pakai `x-transition` + `x-cloak` (Lapisan 3).
- [ ] Hanya `transform`/`opacity` yang dianimasikan.
- [ ] Diuji dengan `prefers-reduced-motion` aktif (harus tetap fungsional tanpa gerak).

## Uji cepat
- Chrome: buka DevTools → Rendering → aktifkan "Emulate prefers-reduced-motion" untuk cek aksesibilitas.
- Coba klik antar menu / submit "Update Status" — konten kini cross-fade, sidebar tetap diam.
