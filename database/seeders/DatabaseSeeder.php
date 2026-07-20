<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StatusService;
use App\Enums\TipeItem;
use App\Enums\TipePromo;
use App\Models\ItemTransaksi;
use App\Models\KategoriProduk;
use App\Models\Pegawai;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Service;
use App\Models\ServiceStatus;
use App\Models\Transaksi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $owner = Pegawai::create([
            'nama_pegawai' => 'Adi Kusumo', 'username' => 'owner', 'email' => 'owner@redline.tech',
            'password' => Hash::make('password'), 'role' => 'Owner', 'nomor_hp' => '081200000001',
            'tanggal_masuk' => '2016-03-01', 'masih_bekerja' => true,
        ]);
        $rijal = Pegawai::create([
            'nama_pegawai' => 'Yth. Rijal', 'username' => 'rijal', 'email' => 'rijal@redline.tech',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'nomor_hp' => '081200000002',
            'tanggal_masuk' => '2022-01-10', 'masih_bekerja' => true,
        ]);
        foreach ([
            ['Budi Santoso', 'budi', 'budi.redline@tech.com'],
            ['Siti Aminah', 'siti', 'siti.cashier@tech.com'],
            ['Andi Wijaya', 'andi', 'andi.service@tech.com'],
        ] as [$nama, $u, $mail]) {
            Pegawai::create([
                'nama_pegawai' => $nama, 'username' => $u, 'email' => $mail,
                'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
                'tanggal_masuk' => '2023-06-01',
            ]);
        }

        $kat = [];
        foreach ([
            ['Laptops', 'Laptop kerja & gaming'],
            ['Graphics Cards', 'GPU / VGA add-in'],
            ['Processors', 'CPU Intel & AMD'],
            ['Motherboards', 'Mainboard semua socket'],
            ['Peripherals', 'Keyboard, mouse, headset'],
            ['Monitors', 'Layar & display'],
        ] as [$n, $d]) {
            $kat[$n] = KategoriProduk::create(['nama_kategori' => $n, 'deskripsi_kategori' => $d]);
        }

        $produk = [
            ['ROG Zephyrus G14 2024', 'Laptops', 'RL-ASUS-G14-001', 12, 20_000_000, true],
            ['RTX 4080 Super Founders', 'Graphics Cards', 'RL-NV-4080-FE', 12, 18_500_000, true],
            ['RTX 4090 Founders Edition', 'Graphics Cards', 'RL-NV-4090-FE', 3, 5_000_000, true],
            ['LG UltraGear 34"', 'Monitors', 'RL-LG-34GL-G', 8, 5_000_000, true],
            ['SteelSeries Apex Pro', 'Peripherals', 'RL-SS-APEX', 42, 2_000_000, true],
            ['Keychron Q1 Pro Wireless', 'Peripherals', 'RL-KC-Q1P-W', 0, 1_000_000, true],
            ['AMD Ryzen 9 7950X', 'Processors', 'RL-AMD-7950X', 2, 8_500_000, true],
            ['Samsung 990 Pro 2TB', 'Motherboards', 'RL-SS-990P-2T', 5, 3_200_000, true],
        ];
        $p = [];
        foreach ($produk as [$nama, $k, $sku, $stok, $harga, $show]) {
            $p[$sku] = Produk::create([
                'kategori_id' => $kat[$k]->id, 'sku' => $sku, 'nama_produk' => $nama,
                'jumlah_produk' => $stok, 'harga' => $harga, 'show_katalog' => $show,
                'deskripsi_produk' => 'Hardware premium bergaransi resmi Redline Komputer.',
            ]);
        }

        Promo::create(['nama_promo' => 'Flash Sale Akhir Tahun', 'kode_promo' => 'GAMING40',
            'tipe_promo' => TipePromo::Persen, 'besar_promo' => 40, 'minimal_transaksi' => 5_000_000,
            'maksimal_diskon' => 2_000_000, 'waktu_mulai' => '2026-07-01', 'waktu_berakhir' => '2026-12-31', 'aktif' => true]);
        Promo::create(['nama_promo' => 'Custom PC Build', 'kode_promo' => 'NEWPC500',
            'tipe_promo' => TipePromo::Nominal, 'besar_promo' => 500_000, 'minimal_transaksi' => 15_000_000,
            'waktu_mulai' => '2026-07-01', 'waktu_berakhir' => '2026-08-15', 'aktif' => true]);
        Promo::create(['nama_promo' => 'Reparasi Hardware', 'kode_promo' => 'SERVIS20',
            'tipe_promo' => TipePromo::Persen, 'besar_promo' => 20, 'minimal_transaksi' => 300_000,
            'maksimal_diskon' => 150_000, 'waktu_mulai' => '2026-07-01', 'waktu_berakhir' => '2026-07-24', 'aktif' => true]);

        $trx = Transaksi::create([
            'kode_nota' => '546234', 'pegawai_id' => $rijal->id, 'metode_bayar' => 'QRIS',
            'subtotal' => 25_000_000, 'diskon' => 0, 'total' => 25_000_000,
            'bayar' => 25_000_000, 'kembalian' => 0, 'nama_pembeli' => 'Umum',
        ]);
        ItemTransaksi::create(['transaksi_id' => $trx->id, 'tipe' => TipeItem::Produk,
            'produk_id' => $p['RL-ASUS-G14-001']->id, 'nama_item' => 'ROG Zephyrus G14 2024',
            'jumlah' => 1, 'harga' => 20_000_000, 'subtotal' => 20_000_000]);
        ItemTransaksi::create(['transaksi_id' => $trx->id, 'tipe' => TipeItem::Produk,
            'produk_id' => $p['RL-SS-APEX']->id, 'nama_item' => 'SteelSeries Apex Pro',
            'jumlah' => 1, 'harga' => 5_000_000, 'subtotal' => 5_000_000]);

        $svc = Service::create([
            'nomor_resi' => 'PK-2026-0001', 'pegawai_id' => $rijal->id,
            'nama_customer' => 'Budi Santoso', 'nomor_hp_customer' => '081298765432',
            'nama_barang' => 'Laptop Gaming ASUS ROG Strix', 'masalah' => 'Blue screen saat main game berat, overheating.',
            'biaya_service' => 450_000, 'status' => StatusService::MenungguSparepart,
            'tanggal_masuk' => '2026-07-12', 'estimasi_selesai' => '2026-07-15',
        ]);
        foreach ([
            [StatusService::Diterima, 'Unit diterima, kelengkapan dicek.'],
            [StatusService::Dikerjakan, 'Pembersihan & diagnosa awal.'],
            [StatusService::MenungguSparepart, 'Menunggu kedatangan thermal paste & PSU pengganti.'],
        ] as [$st, $cat]) {
            ServiceStatus::create(['service_id' => $svc->id, 'pegawai_id' => $rijal->id, 'status' => $st, 'catatan' => $cat]);
        }
    }
}
