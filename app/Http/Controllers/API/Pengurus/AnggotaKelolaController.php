<?php

namespace App\Http\Controllers\API\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReaktivasiAnggotaRequest;
use App\Http\Requests\ResignAnggotaRequest;
use App\Http\Requests\StoreAnggotaRequest;
use App\Http\Requests\UpdateAnggotaRequest;
use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Anggota\ReaktivasiService;
use App\Services\Anggota\ResignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AnggotaKelolaController extends Controller
{
    /**
     * Tambah anggota baru + akun login SSO + simpanan pokok awal.
     */
    public function store(StoreAnggotaRequest $request): JsonResponse
    {
        if (! $request->user()->can('anggota.kelola')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses menambah anggota.',
            ], 403));
        }

        $anggota = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->nama,
                'no_karyawan' => $request->no_karyawan,
                'email' => $request->email,
                'password' => Hash::make(Str::random(32)),
                'harus_ganti_password' => false,
            ]);
            $user->assignRole('anggota');

            $dataAnggota = $request->validated();
            unset($dataAnggota['email']);

            $anggota = Anggota::create([
                ...$dataAnggota,
                'user_id' => $user->id,
                'no_anggota' => Anggota::generateNoAnggota(),
                'status' => 'aktif',
            ]);

            Simpanan::create([
                'anggota_id' => $anggota->id,
                'jenis' => 'pokok',
                'jumlah' => SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000,
                'bulan_periode' => now()->format('Y-m'),
                'tanggal_input' => now(),
                'input_by' => $request->user()->id,
            ]);

            return $anggota;
        });

        return response()->json([
            'message' => "Anggota {$anggota->nama} berhasil ditambahkan ({$anggota->no_anggota}).",
            'data' => [
                'id' => $anggota->id,
                'no_anggota' => $anggota->no_anggota,
                'nama' => $anggota->nama,
                'status' => $anggota->status,
            ],
        ], 201);
    }

    /**
     * Perbarui status, tanggal jadi anggota, dan limit custom anggota.
     */
    public function update(UpdateAnggotaRequest $request, Anggota $anggota): JsonResponse
    {
        if (! $request->user()->can('anggota.kelola')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses mengubah data anggota.',
            ], 403));
        }

        $limitCustomLama = $anggota->limit_custom;
        $anggota->update($request->validated());

        if ($limitCustomLama != $request->limit_custom) {
            AuditLog::catat(
                'update_limit_custom_anggota',
                "Limit khusus untuk {$anggota->nama} diubah menjadi ".
                    ($request->limit_custom ? 'Rp '.number_format((float) $request->limit_custom, 0, ',', '.') : 'dihapus (kembali ke aturan umum)').
                    ". Alasan: {$request->limit_custom_keterangan}",
                ['limit_custom' => $limitCustomLama],
                ['limit_custom' => $request->limit_custom, 'keterangan' => $request->limit_custom_keterangan]
            );
        }

        return response()->json([
            'message' => "Data anggota {$anggota->nama} berhasil diperbarui.",
            'data' => [
                'id' => $anggota->id,
                'status' => $anggota->status,
                'limit_custom' => $anggota->limit_custom !== null ? (float) $anggota->limit_custom : null,
            ],
        ]);
    }

    /**
     * Ringkasan hak/kewajiban anggota sebelum proses resign.
     */
    public function ringkasanResign(Request $request, Anggota $anggota, ResignService $resignService): JsonResponse
    {
        if (! $request->user()->can('anggota.resign')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses melihat ringkasan resign anggota.',
            ], 403));
        }

        return response()->json([
            'anggota' => [
                'id' => $anggota->id,
                'no_anggota' => $anggota->no_anggota,
                'no_karyawan' => $anggota->no_karyawan,
                'nama' => $anggota->nama,
                'cabang' => $anggota->cabang,
                'status' => $anggota->status,
            ],
            'ringkasan' => $resignService->ringkasan($anggota),
        ]);
    }

    /**
     * Proses resign anggota (offset kewajiban terhadap simpanan + tabungan).
     */
    public function resign(ResignAnggotaRequest $request, Anggota $anggota, ResignService $resignService): JsonResponse
    {
        if (! $request->user()->can('anggota.resign')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses memproses resign anggota.',
            ], 403));
        }

        $rekeningTujuan = $request->filled('rekening_bank') || $request->filled('rekening_nomor')
            ? [
                'nama_bank' => $request->input('rekening_bank'),
                'no_rekening' => $request->input('rekening_nomor'),
                'atas_nama' => $request->input('rekening_atas_nama'),
            ]
            : null;

        try {
            $resignService->proses(
                $anggota,
                $request->string('alasan_resign')->toString(),
                $request->date('tanggal_resign')->format('Y-m-d'),
                $request->user(),
                $rekeningTujuan,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $anggota->refresh();
        if ($anggota->status === 'resign_menunggu') {
            $pesan = $anggota->status_pencairan_resign === 'menunggu'
                ? "Resign {$anggota->nama} menunggu pelunasan cicilan akhir. Pengembalian saldo tabungan masuk antrean Pencairan Bendahara."
                : "Resign {$anggota->nama} menunggu pelunasan cicilan akhir.";
        } elseif ($anggota->status_pencairan_resign === 'menunggu') {
            $pesan = "Resign {$anggota->nama} diproses. Pengembalian dana menunggu pencairan Bendahara.";
        } else {
            $pesan = "Anggota {$anggota->nama} berhasil di-resign.";
        }

        return response()->json([
            'message' => $pesan,
            'data' => [
                'id' => $anggota->id,
                'status' => $anggota->status,
                'status_pencairan_resign' => $anggota->status_pencairan_resign,
            ],
        ]);
    }

    /**
     * Reaktivasi anggota yang berstatus resign / nonaktif menjadi aktif kembali.
     */
    public function reaktivasi(ReaktivasiAnggotaRequest $request, Anggota $anggota, ReaktivasiService $service): JsonResponse
    {
        if (! $request->user()->can('anggota.resign') && ! $request->user()->can('anggota.kelola')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses reaktivasi anggota.',
            ], 403));
        }

        try {
            $service->proses(
                $anggota,
                $request->string('alasan_reaktivasi')->toString(),
                $request->user()
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $anggota->refresh();

        return response()->json([
            'message' => "Anggota {$anggota->nama} berhasil diaktifkan kembali.",
            'data' => [
                'id' => $anggota->id,
                'status' => $anggota->status,
            ],
        ]);
    }

    /**
     * Cairkan pengembalian simpanan/tabungan anggota resign oleh Bendahara.
     */
    public function cairkanResign(Request $request, Anggota $anggota, ResignService $resignService): JsonResponse
    {
        if (! $request->user()->can('pinjaman.cairkan')) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses pencairan pengembalian resign.',
            ], 403));
        }

        $data = $request->validate([
            'tanggal_pencairan' => ['required', 'date', 'before_or_equal:today'],
            'akun_kas' => ['required', 'in:bank,kas_kecil'],
            'bukti_pencairan' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal_pencairan.before_or_equal' => 'Tanggal pencairan tidak boleh melebihi hari ini.',
            'bukti_pencairan.required' => 'Bukti transfer pencairan resign wajib dilampirkan.',
            'bukti_pencairan.mimes' => 'Bukti transfer harus berupa JPG, PNG, WEBP, atau PDF.',
            'bukti_pencairan.max' => 'Ukuran bukti transfer maksimal 5 MB.',
        ]);

        try {
            $resignService->cairkanBendahara(
                anggota: $anggota,
                buktiTransfer: $request->file('bukti_pencairan'),
                tanggalPencairan: (string) $data['tanggal_pencairan'],
                akunKas: (string) $data['akun_kas'],
                catatan: $data['catatan'] ?? null,
                aktorId: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Pengembalian resign {$anggota->nama} berhasil dicairkan.",
            'data' => [
                'id' => $anggota->id,
                'status_pencairan_resign' => 'dicairkan',
            ],
        ]);
    }
}
