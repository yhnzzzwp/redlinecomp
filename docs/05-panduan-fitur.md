# 05 — Panduan Fitur

Dokumen ini tumbuh seiring fitur diselesaikan. Tiap fitur: alur normal + alur alternatif (mengacu skenario SRS).

## Status implementasi
| Fitur | Zona | Status |
|-------|------|--------|
| Login + role gate | Internal | ✅ |
| Dashboard | Internal | ✅ |
| POS / Pengkasiran | Internal | ✅ |
| Manajemen Produk | Internal | ⏳ |
| Manajemen Servis + status | Internal | ⏳ |
| Manajemen Promo | Internal (Owner) | ⏳ |
| Laporan Penjualan | Internal (Owner) | ⏳ |
| Akun Pegawai | Internal (Owner) | ⏳ |
| Daftar Transaksi | Internal | ⏳ |
| Nota PDF | Internal | ✅ |
| Landing + Katalog + Detail | Publik | ⏳ |
| Cek Servis / Cek Nota | Publik | ⏳ |

_(diperbarui saat tiap fitur selesai)_


## POS / Pengkasiran (selesai)
**Alur:** pilih produk (filter kategori) → keranjang → (opsional) kode promo → pilih metode bayar → Process Transaction → nota PDF.

**Keamanan & integritas (server-side, tidak percaya input klien):**
- Harga & subtotal **dihitung ulang dari DB** di `PosService`, bukan dari input browser.
- `DB::transaction` + `lockForUpdate` pada baris produk → mencegah **oversell** saat 2 kasir bersamaan.
- Stok kurang → `StokTidakCukupException`, transaksi dibatalkan penuh (atomic), stok utuh.
- Promo divalidasi server-side (aktif, periode, minimal transaksi, batas maksimal diskon).
- Pembayaran < total → `PembayaranKurangException`.

**Bukti uji:** `tests/Feature/PosCheckoutTest.php` (5 kasus, lulus) + Larastan level 5 (0 error).
