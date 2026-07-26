# 05 — Panduan Fitur

Dokumen ini tumbuh seiring fitur diselesaikan. Tiap fitur: alur normal + alur alternatif (mengacu skenario SRS).

## Status implementasi
| Fitur | Zona | Status |
|-------|------|--------|
| Login + role gate (1 role ↔ 1 portal) | Internal | ✅ |
| 2FA TOTP untuk Owner | Internal (Owner) | ✖️ dihapus atas keputusan Owner (27 Jul 2026) |
| Dashboard | Internal | ✅ |
| POS / Pengkasiran | Internal | ✅ |
| Nota PDF + Struk thermal 80mm | Internal | ✅ |
| Daftar Transaksi (cari, void, export) | Internal | ✅ |
| Manajemen Produk + impor–ekspor Excel | Internal | ✅ |
| Stok opname + riwayat mutasi stok | Internal | ✅ |
| Manajemen Servis + status + Kirim Update WA | Internal | ✅ |
| Manajemen Promo | Internal (Owner) | ✅ |
| Analytics / Laporan Penjualan (laba, PDF, CSV) | Internal (Owner) | ✅ |
| Ekspor Jurnal Akuntansi (.xlsx) | Internal (Owner) | ✅ |
| Akun Pegawai | Internal (Owner) | ✅ |
| PWA — POS dapat di-install | Internal | ✅ |
| Landing (beranda = katalog) + Detail Produk | Publik | ✅ |
| Cek / Lacak Servis | Publik | ✅ |
| Cek Nota | Publik | ✖️ dihapus (`910d61b`) — bukan lagi bagian aplikasi |

_(diperbarui saat tiap fitur selesai — status detail per commit: lihat docs/09 §2)_


## POS / Pengkasiran (selesai)
**Alur:** pilih produk (filter kategori) → keranjang → (opsional) kode promo → pilih metode bayar → Process Transaction → nota PDF.

**Keamanan & integritas (server-side, tidak percaya input klien):**
- Harga & subtotal **dihitung ulang dari DB** di `PosService`, bukan dari input browser.
- `DB::transaction` + `lockForUpdate` pada baris produk → mencegah **oversell** saat 2 kasir bersamaan.
- Stok kurang → `StokTidakCukupException`, transaksi dibatalkan penuh (atomic), stok utuh.
- Promo divalidasi server-side (aktif, periode, minimal transaksi, batas maksimal diskon).
- Pembayaran < total → `PembayaranKurangException`.

**Bukti uji:** `tests/Feature/PosCheckoutTest.php` (5 kasus, lulus) + Larastan level 5 (0 error).
