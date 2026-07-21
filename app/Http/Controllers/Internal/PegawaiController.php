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
        ]);
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
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $pegawai->update($data);

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil diperbarui.');
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
