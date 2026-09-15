<?php

namespace App\Services\Gate;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\Departemen;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Perusahaan;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SinkronisasiAnggotaService
{
    public function __construct(
        private GateClient $gate,
        private JurnalKasService $jurnalKas,
        private SinkronisasiMasterService $master,
    ) {}

    public function sinkron(array $filter = [], bool $kering = false, ?int $aktorId = null, ?int $batas = null): array
    {
        if (! empty($filter['company_id']) && (strlen((string) $filter['company_id']) > 50 || ! preg_match('/^[A-Za-z0-9-]+$/', (string) $filter['company_id']))) {
            return ['baru' => [], 'diperbarui' => [], 'gagal' => ['company_id tidak valid.'], 'dilewati' => 0];
        }

        try {
            $this->master->sinkron($filter['company_id'] ?? null, $kering, $aktorId);
        } catch (\Throwable $e) {
            report($e);
        }

        $daftar = $this->gate->ambilKaryawan($filter);
        if ($batas) {
            $daftar = array_slice($daftar, 0, $batas);
        }

        $hasil = ['baru' => [], 'diperbarui' => [], 'gagal' => [], 'dilewati' => 0];

        foreach ($daftar as $baris) {
            $nik = trim((string) ($baris['nik'] ?? ''));
            $nama = trim((string) ($baris['name'] ?? ''));
            $gateId = $baris['id'] ?? null;

            if (! $nik || ! $nama || ! $gateId) {
                $hasil['dilewati']++;

                continue;
            }

            if ($kering) {
                $ada = Anggota::where('gate_id', $gateId)->orWhere('no_karyawan', $nik)->exists();
                $hasil[$ada ? 'diperbarui' : 'baru'][] = "{$nama} ({$nik})";

                continue;
            }

            try {
                $hasil[$this->sinkronSatu($baris, $aktorId)][] = "{$nama} ({$nik})";
            } catch (\Throwable $e) {
                report($e);
                $hasil['gagal'][] = "{$nama} ({$nik}): {$e->getMessage()}";
            }
        }

        if (! $kering && ($hasil['baru'] || $hasil['diperbarui'])) {
            AuditLog::catat(
                'sinkron_gate',
                'Sinkron karyawan GATE: '.count($hasil['baru']).' baru, '.count($hasil['diperbarui']).' diperbarui.',
                null,
                ['baru' => $hasil['baru'], 'diperbarui' => $hasil['diperbarui'], 'gagal' => $hasil['gagal']],
                $aktorId
            );
        }

        return $hasil;
    }

    private function sinkronSatu(array $baris, ?int $aktorId): string
    {
        return DB::transaction(function () use ($baris, $aktorId) {
            $nik = trim((string) $baris['nik']);
            $nama = trim((string) $baris['name']);
            $email = trim((string) ($baris['email'] ?? ''));
            $noHp = $this->normalisasiHp($baris['whatsapp_number'] ?? null);
            $fotoUrl = $this->normalisasiFoto($baris['photo_url'] ?? $baris['photo'] ?? null);
            $posisi = $baris['position'] ?? null;
            if (is_array($posisi)) {
                $department = $posisi['department']['name'] ?? $posisi['department']['code'] ?? null;
                $deptGateId = $posisi['department']['id'] ?? null;
                $jabGateId = $posisi['id'] ?? null;
                $namaJabatan = trim((string) ($posisi['name'] ?? ''));
                $compGateId = $baris['company_id'] ?? null;
            } else {
                $jabatanRow = ! empty($baris['position_id']) ? Jabatan::where('gate_id', $baris['position_id'])->first() : null;
                $deptRow = ! empty($baris['department_id'])
                    ? Departemen::where('gate_id', $baris['department_id'])->first()
                    : ($jabatanRow?->departemen_id ? Departemen::find($jabatanRow->departemen_id) : null);
                $department = $deptRow?->nama;
                $deptGateId = $deptRow?->gate_id;
                $jabGateId = $jabatanRow?->gate_id;
                $namaJabatan = $jabatanRow?->nama ?? '';
                $compGateId = $baris['company_id'] ?? $deptRow?->perusahaan?->gate_id ?? $jabatanRow?->perusahaan?->gate_id;
            }
            $departemenRow = $deptGateId ? Departemen::where('gate_id', $deptGateId)->first() : null;
            $departemenId = $departemenRow?->id;
            $jabatanRow = $jabGateId ? Jabatan::where('gate_id', $jabGateId)->first() : ($jabatanRow ?? null);
            $jabatanId = $jabatanRow?->id;
            if (! $namaJabatan && $jabatanRow) {
                $namaJabatan = $jabatanRow->nama;
            }
            if (! $department && $departemenRow) {
                $department = $departemenRow->nama;
            }

            $pemilikEmail = $email ? User::where('email', $email)->first() : null;
            if ($pemilikEmail && $pemilikEmail->no_karyawan !== $nik) {
                $pemilikEmail->loadMissing('anggota');
                if ($pemilikEmail->anggota?->gate_id) {
                    throw new \RuntimeException("Email {$email} sudah dipakai akun lain.");
                }
                $pemilikEmail->update(['email' => null]);
            }

            $user = User::where('no_karyawan', $nik)->first();
            if (! $user) {
                $user = User::create([
                    'name' => $nama,
                    'email' => $email ?: null,
                    'no_karyawan' => $nik,
                    'password' => Hash::make($nik),
                    'harus_ganti_password' => true,
                ]);
                $user->assignRole('anggota');
            } else {
                $user->update([
                    'name' => $nama,
                    'email' => $email ?: $user->email,
                ]);
            }

            $anggota = Anggota::where('gate_id', $baris['id'])->lockForUpdate()->first()
                ?? Anggota::where('no_karyawan', $nik)->lockForUpdate()->first();

            if ($anggota && $anggota->gate_id && $anggota->gate_id !== $baris['id']) {
                throw new \RuntimeException("NIK {$nik} terdaftar pada data GATE lain.");
            }

            $perusahaan = ! empty($compGateId)
                ? Perusahaan::where('gate_id', $compGateId)->first()
                : null;
            $divisiId = $jabatanRow?->division_id
                ? Divisi::whereKey($jabatanRow->division_id)->value('id')
                : null;

            $baru = ! $anggota;
            if ($baru) {
                $anggota = Anggota::create([
                    'user_id' => $user->id,
                    'gate_id' => $baris['id'],
                    'perusahaan_id' => $perusahaan?->id,
                    'departemen_id' => $departemenId,
                    'divisi_id' => $divisiId,
                    'jabatan_id' => $jabatanId,
                    'no_anggota' => Anggota::generateNoAnggota(),
                    'no_karyawan' => $nik,
                    'no_ktp' => preg_match('/^\d{16}$/', $nik) ? $nik : null,
                    'nama' => $nama,
                    'cabang' => 'Banjarmasin',
                    'unit_bisnis' => $perusahaan?->nama ?? $department ?? 'Operasional',
                    'department' => $department,
                    'jabatan' => $namaJabatan ?: 'staff',
                    'tanggal_mulai_kerja' => now(),
                    'tanggal_jadi_anggota' => now(),
                    'no_hp' => $noHp,
                    'foto_url' => $fotoUrl,
                    'status' => 'aktif',
                ]);
                $this->catatSimpananPokok($anggota, $aktorId);
            } else {
                $anggota->update([
                    'user_id' => $anggota->user_id ?? $user->id,
                    'gate_id' => $anggota->gate_id ?? $baris['id'],
                    'perusahaan_id' => $perusahaan?->id ?? $anggota->perusahaan_id,
                    'departemen_id' => $departemenId ?? $anggota->departemen_id,
                    'divisi_id' => $divisiId ?? $anggota->divisi_id,
                    'jabatan_id' => $jabatanId ?? $anggota->jabatan_id,
                    'no_karyawan' => $nik,
                    'nama' => $nama,
                    'unit_bisnis' => $perusahaan?->nama ?? $department ?? $anggota->unit_bisnis,
                    'department' => $department ?? $anggota->department,
                    'jabatan' => $namaJabatan ?: $anggota->jabatan,
                    'no_hp' => $noHp ?? $anggota->no_hp,
                    'foto_url' => $fotoUrl ?? $anggota->foto_url,
                ]);
            }

            return $baru ? 'baru' : 'diperbarui';
        });
    }

    private function normalisasiFoto(mixed $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || strlen($url) > 500) {
            return null;
        }

        return preg_match('#^https?://#i', $url) ? $url : null;
    }

    private function normalisasiHp(mixed $nomor): ?string
    {
        $bersih = preg_replace('/[^0-9+]/', '', (string) $nomor);
        if (! $bersih) {
            return null;
        }
        if (str_starts_with($bersih, '+62')) {
            return $bersih;
        }
        if (str_starts_with($bersih, '62')) {
            return '+'.$bersih;
        }
        if (str_starts_with($bersih, '0')) {
            return $bersih;
        }

        return null;
    }

    private function catatSimpananPokok(Anggota $anggota, ?int $aktorId): void
    {
        $nominalPokok = SettingSimpanan::where('jenis', 'pokok')->value('nominal') ?? 50_000;

        Simpanan::firstOrCreate(
            ['anggota_id' => $anggota->id, 'jenis' => 'pokok'],
            [
                'jumlah' => $nominalPokok,
                'bulan_periode' => now()->format('Y-m'),
                'tanggal_input' => now(),
                'input_by' => $aktorId,
            ]
        );

        $this->jurnalKas->catat(
            tipe: 'masuk',
            kategori: 'simpanan_pokok_masuk',
            kantong: 'simpanan',
            jumlah: $nominalPokok,
            keterangan: "Simpanan pokok {$anggota->nama}",
            referensiId: $anggota->id,
            tanggal: now()->format('Y-m-d'),
            userId: $aktorId ?? $anggota->user_id,
            subJudul: 'Simpanan pokok masuk',
        );
    }
}
