<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolePegawai;
use App\Models\ApiToken;
use App\Models\Pegawai;
use App\Models\Transaksi;
use App\Support\Csv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regresi untuk tiga lubang keamanan API yang ditutup:
 *  1. /api/v1/pos/sync dapat dipanggil tanpa autentikasi.
 *  2. Middleware menerima token mentah (isi tabel api_tokens langsung dipakai)
 *     dan menerima token lewat query string.
 *  3. /api/v1/auth/login tanpa batas laju sama sekali.
 */
final class ApiKeamananTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $owner;
    private Pegawai $karyawan;
    private string $karyawanToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = Pegawai::create([
            'nama_pegawai'  => 'Owner Redline',
            'username'      => 'owner',
            'email'         => 'owner@redline.test',
            'password'      => Hash::make('password123'),
            'role'          => RolePegawai::Owner,
            'masih_bekerja' => true,
            'tanggal_masuk' => now(),
        ]);

        $this->karyawan = Pegawai::create([
            'nama_pegawai'  => 'Karyawan Redline',
            'username'      => 'karyawan',
            'email'         => 'karyawan@redline.test',
            'password'      => Hash::make('password123'),
            'role'          => RolePegawai::Karyawan,
            'masih_bekerja' => true,
            'tanggal_masuk' => now(),
        ]);

        $this->karyawanToken = $this->karyawan->createApiToken('Perangkat Uji');
    }

    /** @return array<string, mixed> */
    private function payloadSync(string $localId = 'OFFLINE-001'): array
    {
        return [
            'local_id'     => $localId,
            'metode_bayar' => 'Tunai',
            'bayar'        => 50_000,
            'items'        => [[
                'tipe'      => 'produk',
                'nama_item' => 'Kabel HDMI',
                'jumlah'    => 1,
                'harga'     => 50_000,
            ]],
        ];
    }

    public function test_pos_sync_menolak_permintaan_tanpa_token(): void
    {
        $this->postJson('/api/v1/pos/sync', $this->payloadSync())
            ->assertStatus(401);

        $this->assertDatabaseCount('transaksi', 0);
    }

    public function test_pos_sync_berhasil_dengan_token_yang_sah(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->postJson('/api/v1/pos/sync', $this->payloadSync())
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseCount('transaksi', 1);
    }

    public function test_pos_sync_memakai_kasir_dari_token_bukan_dari_body(): void
    {
        $payload = $this->payloadSync();
        $payload['pegawai_id'] = $this->owner->id; // upaya mengatasnamakan Owner

        $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->postJson('/api/v1/pos/sync', $payload)
            ->assertStatus(200);

        $this->assertSame($this->karyawan->id, Transaksi::firstOrFail()->pegawai_id);
    }

    public function test_nilai_token_tersimpan_di_database_tidak_bisa_dipakai(): void
    {
        $tersimpan = ApiToken::where('pegawai_id', $this->karyawan->id)->firstOrFail()->token;

        $this->assertNotSame($this->karyawanToken, $tersimpan);

        $this->withHeader('Authorization', 'Bearer ' . $tersimpan)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_token_pada_query_string_ditolak(): void
    {
        $this->getJson('/api/v1/auth/me?token=' . $this->karyawanToken)
            ->assertStatus(401);
    }

    public function test_login_api_dibatasi_setelah_percobaan_gagal_berulang(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'username' => 'karyawan',
                'password' => 'salah-' . $i,
            ])->assertStatus(401);
        }

        // Percobaan ke-6 diblokir walau passwordnya benar.
        $this->postJson('/api/v1/auth/login', [
            'username' => 'karyawan',
            'password' => 'password123',
        ])->assertStatus(429);
    }

    // ─── Integritas uang ───────────────────────────────────────────

    public function test_sync_menolak_pembayaran_kurang_dari_total(): void
    {
        $payload = $this->payloadSync();
        $payload['bayar'] = 0; // total item = 50.000

        $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->postJson('/api/v1/pos/sync', $payload)
            ->assertStatus(200)
            ->assertJsonPath('status', 'partial');

        $this->assertDatabaseCount('transaksi', 0);
    }

    // ─── Siklus hidup token ────────────────────────────────────────

    public function test_token_diterbitkan_dengan_masa_berlaku(): void
    {
        $token = ApiToken::where('pegawai_id', $this->karyawan->id)->firstOrFail();

        $this->assertNotNull($token->expires_at, 'Token tanpa kedaluwarsa = akses selamanya bila bocor.');
        $this->assertTrue($token->expires_at->isFuture());
    }

    public function test_ganti_password_mencabut_token_lama(): void
    {
        $tokenLama = $this->karyawanToken;

        $this->withHeader('Authorization', 'Bearer ' . $tokenLama)
            ->putJson('/api/v1/auth/password', [
                'current_password'      => 'password123',
                'password'              => 'passwordBaru123',
                'password_confirmation' => 'passwordBaru123',
            ])->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $tokenLama)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_owner_mengeluarkan_pegawai_ikut_mencabut_token_api(): void
    {
        // Rute ini hidup di portal admin; tanpa ini EnsurePortal menolaknya.
        $this->usePortal('admin');

        $this->actingAs($this->owner)
            ->delete(route('pegawai.sesi.keluarkan', $this->karyawan));

        $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    // ─── Injeksi formula spreadsheet ───────────────────────────────

    public function test_helper_csv_menetralkan_formula(): void
    {
        $this->assertSame("'=cmd|'/c calc'!A1", Csv::aman("=cmd|'/c calc'!A1"));
        $this->assertSame("'+SUM(A1)", Csv::aman('+SUM(A1)'));
        $this->assertSame("'@SUM(A1)", Csv::aman('@SUM(A1)'));
        $this->assertSame("'=HYPERLINK(\"http://jahat\")", Csv::aman('=HYPERLINK("http://jahat")'));

        // Nilai wajar harus lolos apa adanya, termasuk angka negatif.
        $this->assertSame('Kabel HDMI', Csv::aman('Kabel HDMI'));
        $this->assertSame('-5000', Csv::aman('-5000'));
        $this->assertSame(1500, Csv::aman(1500));
    }
}
