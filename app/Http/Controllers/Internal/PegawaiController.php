<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePegawaiRequest;
use App\Http\Requests\UpdatePegawaiRequest;
use App\Models\Pegawai;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class PegawaiController extends Controller
{
    public function index(): View
    {
        return view('internal.pegawai.index', [
            'pegawai' => Pegawai::query()->latest()->paginate(12),
            'aktif' => Pegawai::query()->where('masih_bekerja', true)->count(),
            'sesiAktif' => $this->jumlahSesiPerPegawai(),
        ]);
    }

    /**
     * Berapa perangkat yang sedang login per pegawai — dasar tombol
     * "Keluarkan Sesi" milik Owner. Baris kedaluwarsa (menunggu GC) tidak
     * dihitung, sama seperti halaman Sesi Aktif.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function jumlahSesiPerPegawai(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as jumlah')
            ->pluck('jumlah', 'user_id')
            ->map(fn ($j) => (int) $j);
    }

    public function create(): View
    {
        return view('internal.pegawai.form', ['pegawai' => new Pegawai()]);
    }

    public function store(StorePegawaiRequest $request): RedirectResponse
    {
        Pegawai::query()->create($request->validated());

        return redirect()->route('pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(Pegawai $pegawai): View
    {
        return view('internal.pegawai.form', ['pegawai' => $pegawai]);
    }

    public function update(UpdatePegawaiRequest $request, Pegawai $pegawai): RedirectResponse
    {
        $data = $request->validated();

        // Guard last owner
        if ($pegawai->isOwner() && $pegawai->masih_bekerja) {
            $isChangingRole = isset($data['role']) && $data['role'] !== \App\Enums\RolePegawai::Owner->value;
            $isBecomingInactive = isset($data['masih_bekerja']) && ! $data['masih_bekerja'];
            if ($isChangingRole || $isBecomingInactive) {
                $ownerCount = \App\Models\Pegawai::where('role', \App\Enums\RolePegawai::Owner)->where('masih_bekerja', true)->count();
                if ($ownerCount <= 1) {
                    return redirect()->back()->with('error', 'Tidak dapat mengubah role atau status Owner terakhir yang aktif.');
                }
            }
        }

        // Hanya ubah password bila diisi
        $gantiPassword = ! empty($data['password']);
        if (! $gantiPassword) {
            unset($data['password']);
        }

        $pegawai->update($data);

        // Reset password oleh Owner biasanya dilakukan justru karena akunnya
        // dicurigai bocor. Tanpa pencabutan ini, sesi web dan token API lama
        // pegawai tersebut tetap hidup dan password barunya tidak menolong.
        if ($gantiPassword) {
            $pegawai->apiTokens()->delete();
            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $pegawai->id)->delete();
            $pegawai->setRememberToken(\Illuminate\Support\Str::random(60));
            $pegawai->save();
        }

        return redirect()->route('pegawai.index')->with(
            'success',
            $gantiPassword
                ? 'Data pegawai diperbarui. Password diganti, seluruh sesi dan token pegawai itu dicabut.'
                : 'Data pegawai berhasil diperbarui.'
        );
    }

    public function destroy(Pegawai $pegawai): RedirectResponse
    {
        // Owner tidak boleh menghapus dirinya sendiri
        if ($pegawai->id === auth()->id()) {
            return redirect()->route('pegawai.index')->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        // Guard last owner
        if ($pegawai->isOwner() && $pegawai->masih_bekerja) {
            $ownerCount = \App\Models\Pegawai::where('role', \App\Enums\RolePegawai::Owner)->where('masih_bekerja', true)->count();
            if ($ownerCount <= 1) {
                return redirect()->back()->with('error', 'Tidak dapat menghapus Owner terakhir yang aktif.');
            }
        }

        $pegawai->delete();

        return redirect()->route('pegawai.index')->with('success', 'Pegawai berhasil dihapus.');
    }
}
