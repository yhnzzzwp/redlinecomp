<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\KategoriProduk;
use App\Models\Pegawai;
use App\Models\Produk;
use App\Models\Promo;
use App\Enums\TipePromo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Konfirmasi aksi destruktif harus benar-benar berfungsi:
 *  - pola CSP-safe (Alpine @submit.prevent), BUKAN onsubmit inline yang
 *    diblokir CSP internal (script-src tanpa 'unsafe-inline');
 *  - nama entitas disisipkan lewat @js() DI DALAM atribut kutip-ganda —
 *    @js() selalu berpembatas kutip tunggal, sehingga di atribut
 *    kutip-tunggal atribut akan putus (tombol mati + injeksi atribut).
 */
final class KonfirmasiDestruktifTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usePortal('admin');
        $this->owner = Pegawai::create([
            'nama_pegawai' => 'Owner', 'username' => 'owner', 'email' => 'owner@uji.test',
            'password' => Hash::make('password'), 'role' => 'Owner', 'masih_bekerja' => true,
        ]);
    }

    /** Tag <form …> pembuka yang action-nya persis $url (bukan sekadar substring). */
    private function formYangMemuat(string $html, string $url): string
    {
        $posisi = strpos($html, 'action="'.$url.'"');
        $this->assertNotFalse($posisi, "Form dengan action {$url} tidak ditemukan di halaman");

        $awal = strrpos(substr($html, 0, $posisi), '<form');
        $this->assertNotFalse($awal);

        return substr($html, (int) $awal, strpos($html, '>', (int) $awal) - (int) $awal + 1);
    }

    /**
     * Atribut nyata pada tag form: pasangan nama="nilai" berkutip-ganda.
     * (DOMDocument tidak dipakai — parser HTML4-nya membuang atribut
     * berawalan "@" seperti @submit.prevent milik Alpine.)
     *
     * @return array<string, string>
     */
    private function atributForm(string $tagForm): array
    {
        preg_match_all('/([^\s=<>"\']+)="([^"]*)"/', $tagForm, $cocok, PREG_SET_ORDER);

        $atribut = [];
        foreach ($cocok as [$utuh, $nama, $nilai]) {
            $atribut[$nama] = $nilai;
        }

        return $atribut;
    }

    /**
     * Sisa tag setelah seluruh atribut berkutip-ganda dibuang. Bila masih ada
     * token bermakna (mis. `x-init=alert(1)`), berarti sebuah atribut PUTUS
     * dan data bocor menjadi atribut HTML baru.
     */
    private function sisaTag(string $tagForm): string
    {
        $sisa = preg_replace('/([^\s=<>"\']+)="([^"]*)"/', '', $tagForm) ?? '';

        return trim(str_replace(['<form', '>'], '', $sisa));
    }

    private function assertKonfirmasiUtuh(string $html, string $url, string $nilaiBerbahaya): void
    {
        $tag = $this->formYangMemuat($html, $url);
        $atribut = $this->atributForm($tag);

        // Pola CSP-safe, bukan inline handler.
        $this->assertArrayNotHasKey('onsubmit', $atribut, 'Inline onsubmit diblokir CSP internal');

        // Direktif konfirmasi UTUH — bila @js() memutus atribut, ekspresi
        // terpotong dan tombol Hapus mati total (regresi nyata).
        $kunciSubmit = '@submit.prevent';
        $this->assertArrayHasKey($kunciSubmit, $atribut, 'Direktif konfirmasi hilang/terpotong');
        $this->assertStringContainsString('confirm(', $atribut[$kunciSubmit]);
        $this->assertStringEndsWith('$el.submit()', $atribut[$kunciSubmit], 'Ekspresi Alpine terpotong — atribut putus');

        // Nilai data tidak boleh lolos jadi ATRIBUT baru (injeksi atribut).
        $this->assertArrayNotHasKey('x-init', $atribut, 'Nilai data lolos menjadi atribut Alpine — injeksi atribut!');
        $this->assertSame('', $this->sisaTag($tag), 'Ada atribut tanpa kutip di tag form — atribut putus / injeksi');

        // Nilai tetap sampai ke Alpine (di dalam x-data), bukan hilang.
        $this->assertStringContainsString($nilaiBerbahaya, $atribut['x-data']);
    }

    public function test_konfirmasi_hapus_produk_utuh_walau_nama_menyerang(): void
    {
        $nama = 'Busi x-init=alert(1) NGK';
        $produk = Produk::create([
            'nama_produk' => $nama, 'sku' => 'BSI-1', 'harga' => 50_000,
            'jumlah_produk' => 3, 'show_katalog' => true,
        ]);

        $html = $this->actingAs($this->owner)->get(route('produk.index'))->assertOk()->getContent();
        $this->assertKonfirmasiUtuh((string) $html, route('produk.destroy', $produk), $nama);
    }

    public function test_konfirmasi_hapus_pegawai_utuh_walau_nama_menyerang(): void
    {
        $nama = 'Budi x-init=alert(1) Santoso';
        $pegawai = Pegawai::create([
            'nama_pegawai' => $nama, 'username' => 'budi', 'email' => 'budi@uji.test',
            'password' => Hash::make('password1'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);

        $html = $this->actingAs($this->owner)->get(route('pegawai.index'))->assertOk()->getContent();
        $this->assertKonfirmasiUtuh((string) $html, route('pegawai.destroy', $pegawai), $nama);
    }

    public function test_konfirmasi_hapus_promo_utuh(): void
    {
        $promo = Promo::create([
            'nama_promo' => 'Uji', 'kode_promo' => 'HEMAT20', 'tipe_promo' => TipePromo::Persen,
            'besar_promo' => 20, 'minimal_transaksi' => 0,
            'waktu_mulai' => now()->subDay(), 'waktu_berakhir' => now()->addDay(), 'aktif' => true,
        ]);

        $html = $this->actingAs($this->owner)->get(route('promo.index'))->assertOk()->getContent();
        $form = $this->formYangMemuat((string) $html, route('promo.destroy', $promo));

        $this->assertStringNotContainsString('onsubmit=', $form);
        $this->assertStringContainsString('@submit.prevent="', $form);
        $this->assertStringContainsString('HEMAT20', $form); // kode tetap tampil di dialog
    }

    public function test_tidak_ada_lagi_inline_onsubmit_di_seluruh_view(): void
    {
        // GLOB_BRACE adalah ekstensi GNU dan TIDAK tersedia di musl libc
        // (image php:8.3-fpm-alpine yang dipakai proyek ini), sehingga tes ini
        // selalu gagal dengan "Undefined constant GLOB_BRACE". Polanya pun
        // tidak memakai kurung kurawal, jadi flag itu memang tidak diperlukan.
        // Iterator rekursif sekaligus menjangkau SEMUA kedalaman, bukan hanya
        // dua dan tiga level seperti pasangan glob sebelumnya.
        $berkas = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && str_ends_with($item->getFilename(), '.blade.php')) {
                $berkas[] = $item->getPathname();
            }
        }

        $this->assertNotEmpty($berkas, 'Tidak ada berkas blade yang terbaca.');

        foreach ($berkas as $file) {
            $this->assertStringNotContainsString(
                'onsubmit=',
                (string) file_get_contents($file),
                basename($file).' memakai inline onsubmit yang diblokir CSP',
            );
        }
    }
}
