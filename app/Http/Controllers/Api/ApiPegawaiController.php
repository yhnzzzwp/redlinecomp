<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\RolePegawai;
use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiPegawaiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Pegawai::query()->withCount(['transaksi', 'service']);

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('masih_bekerja') && $request->input('masih_bekerja') !== '') {
            $query->where('masih_bekerja', filter_var($request->input('masih_bekerja'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('cari')) {
            $cari = trim((string) $request->input('cari'));
            $query->where(function ($q) use ($cari) {
                $q->where('nama_pegawai', 'like', "%{$cari}%")
                  ->orWhere('username', 'like', "%{$cari}%")
                  ->orWhere('email', 'like', "%{$cari}%")
                  ->orWhere('nomor_hp', 'like', "%{$cari}%");
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $pegawai = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $pegawai->map(fn (Pegawai $p) => $this->formatPegawai($p)),
            'pagination' => [
                'current_page' => $pegawai->currentPage(),
                'last_page'    => $pegawai->lastPage(),
                'per_page'     => $pegawai->perPage(),
                'total'        => $pegawai->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_pegawai'   => ['required', 'string', 'max:255'],
            'username'       => ['required', 'string', 'max:50', 'unique:pegawai,username'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:pegawai,email'],
            'password'       => ['required', 'string', 'min:8'],
            'role'           => ['required', Rule::enum(RolePegawai::class)],
            'nomor_hp'       => ['nullable', 'string', 'max:30'],
            'alamat_pegawai' => ['nullable', 'string', 'max:500'],
            'tanggal_masuk'  => ['nullable', 'date'],
            'masih_bekerja'  => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data pegawai tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $pegawai = Pegawai::create([
            'nama_pegawai'   => $request->input('nama_pegawai'),
            'username'       => $request->input('username'),
            'email'          => $request->input('email'),
            'password'       => $request->input('password'),
            'role'           => $request->input('role'),
            'nomor_hp'       => $request->input('nomor_hp'),
            'alamat_pegawai' => $request->input('alamat_pegawai'),
            'tanggal_masuk'  => $request->input('tanggal_masuk', now()),
            'masih_bekerja'  => $request->boolean('masih_bekerja', true),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pegawai berhasil ditambahkan.',
            'data'    => $this->formatPegawai($pegawai),
        ], 201);
    }

    public function show(Pegawai $pegawai): JsonResponse
    {
        $pegawai->loadCount(['transaksi', 'service']);

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatPegawai($pegawai),
        ]);
    }

    public function update(Request $request, Pegawai $pegawai): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_pegawai'   => ['required', 'string', 'max:255'],
            'username'       => ['required', 'string', 'max:50', Rule::unique('pegawai', 'username')->ignore($pegawai->id)],
            'email'          => ['required', 'string', 'email', 'max:255', Rule::unique('pegawai', 'email')->ignore($pegawai->id)],
            'password'       => ['nullable', 'string', 'min:8'],
            'role'           => ['required', Rule::enum(RolePegawai::class)],
            'nomor_hp'       => ['nullable', 'string', 'max:30'],
            'alamat_pegawai' => ['nullable', 'string', 'max:500'],
            'tanggal_masuk'  => ['nullable', 'date'],
            'masih_bekerja'  => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data pegawai tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = [
            'nama_pegawai'   => $request->input('nama_pegawai'),
            'username'       => $request->input('username'),
            'email'          => $request->input('email'),
            'role'           => $request->input('role'),
            'nomor_hp'       => $request->input('nomor_hp'),
            'alamat_pegawai' => $request->input('alamat_pegawai'),
            'tanggal_masuk'  => $request->input('tanggal_masuk', $pegawai->tanggal_masuk),
            'masih_bekerja'  => $request->has('masih_bekerja') ? $request->boolean('masih_bekerja') : $pegawai->masih_bekerja,
        ];

        if ($request->filled('password')) {
            $data['password'] = (string) $request->input('password');
        }

        $pegawai->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data pegawai berhasil diperbarui.',
            'data'    => $this->formatPegawai($pegawai),
        ]);
    }

    public function destroy(Pegawai $pegawai): JsonResponse
    {
        $currentUser = auth()->user();
        if ($currentUser && $currentUser->id === $pegawai->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        // Deactivate or delete
        $pegawai->apiTokens()->delete();
        $pegawai->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Pegawai berhasil dihapus.',
        ]);
    }

    public function revokeSessions(Pegawai $pegawai): JsonResponse
    {
        $pegawai->apiTokens()->delete();
        $pegawai->update(['remember_token' => \Illuminate\Support\Str::random(60)]);

        // Also delete web session records from sessions table if exists
        try {
            DB::table('sessions')->where('user_id', $pegawai->id)->delete();
        } catch (\Throwable $e) {
            // sessions table might use different column or driver
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Seluruh sesi dan token aktif untuk pegawai {$pegawai->nama_pegawai} telah dicabut.",
        ]);
    }

    private function formatPegawai(Pegawai $p): array
    {
        return [
            'id'             => $p->id,
            'nama_pegawai'   => $p->nama_pegawai,
            'username'       => $p->username,
            'email'          => $p->email,
            'role'           => $p->role->value ?? (string) $p->role,
            'nomor_hp'       => $p->nomor_hp,
            'alamat_pegawai' => $p->alamat_pegawai,
            'tanggal_masuk'  => $p->tanggal_masuk?->format('Y-m-d'),
            'masih_bekerja'  => (bool) $p->masih_bekerja,
            'is_owner'       => $p->isOwner(),
            'total_transaksi'=> $p->transaksi_count ?? 0,
            'total_servis'   => $p->service_count ?? 0,
            'created_at'     => $p->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
