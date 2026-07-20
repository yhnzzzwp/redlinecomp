# 02 — Database & ERD

Basis data mengikuti **class diagram SRS (§3.2.1)**: 9 entitas + 3 enum.
Engine: **MySQL 8** (wajib §3.4). Uang disimpan sebagai **integer Rupiah** (hindari float).

## Entitas & relasi
| Tabel | Ringkas | Relasi |
|-------|---------|--------|
| `kategori_produk` | kategori untuk filter katalog | hasMany produk |
| `produk` | stok, harga, show_katalog | belongsTo kategori |
| `pegawai` | akun internal (role, password hash) | hasMany transaksi, service |
| `promo` | diskon + periode + kode | hasMany transaksi |
| `transaksi` | penjualan (subtotal, diskon, total, bayar, kembalian, kode_nota) | belongsTo pegawai/promo; hasMany item |
| `item_transaksi` | rincian per item (tipe Produk/Servis) | belongsTo transaksi/produk/service |
| `service` | tiket servis (nomor_resi, status) | belongsTo pegawai; hasMany part & riwayat |
| `part_service` | sparepart yang dipakai servis | belongsTo service/produk |
| `service_status` | riwayat perubahan status (tidak ditimpa) | belongsTo service/pegawai |

## Enum
- `role_pegawai`: Karyawan · Owner
- `tipe_item`: Produk · Servis
- `tipe_promo`: Persen · Nominal
- (bonus) `StatusService`: Diterima → Dikerjakan → Menunggu Sparepart → Selesai → Sudah Diambil (§2.5)

## Deviasi terdokumentasi dari class diagram (alasan praktis)
| Tambahan | Alasan |
|----------|--------|
| `produk.sku` | identitas produk di UI inventory (Figma) |
| `produk.show_katalog` | ada di class diagram; jadi toggle tampil katalog |
| `promo.kode_promo` | kasir memasukkan **kode** promo; class diagram belum punya |
| `promo.aktif` | toggle Aktif/Nonaktif (Figma) selain cek periode |
| `transaksi.kode_nota` | dipakai fitur publik "Cek Nota" |
| `service.estimasi_selesai` | ada di Figma (Service Detail) |
| `item/part.nama_item/nama_part` | snapshot nama saat transaksi (histori tak berubah bila produk di-rename) |

> Catatan temuan desain: kolom **COGS/HPP** di mock "Manajemen Stok" Figma **tidak** dibuat —
> memang tidak ada di ERD. Konsisten dengan SRS.
