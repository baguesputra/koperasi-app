<?php

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('anggota')) {
            return;
        }

        $profilBawaan = [
            [
                'no_karyawan' => 'WAS-000001',
                'no_anggota' => 'ANG-2020-0011',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Pengawas',
                'department' => 'Audit & Pengawasan',
                'divisi' => 'Pengawas',
                'jabatan' => 'hod',
                'tanggal_mulai_kerja' => now()->subYears(8),
                'tanggal_jadi_anggota' => now()->subYears(6),
            ],
            [
                'no_karyawan' => 'WAS-000002',
                'no_anggota' => 'ANG-2020-0012',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Pengawas',
                'department' => 'Audit & Pengawasan',
                'divisi' => 'Pengawas',
                'jabatan' => 'staff',
                'tanggal_mulai_kerja' => now()->subYears(6),
                'tanggal_jadi_anggota' => now()->subYears(4),
            ],
            [
                'no_karyawan' => 'WAS-000003',
                'no_anggota' => 'ANG-2020-0013',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Pengawas',
                'department' => 'Audit & Pengawasan',
                'divisi' => 'Pengawas',
                'jabatan' => 'staff',
                'tanggal_mulai_kerja' => now()->subYears(5),
                'tanggal_jadi_anggota' => now()->subYears(3),
            ],
            [
                'no_karyawan' => 'ADM-000001',
                'no_anggota' => 'ANG-2020-0002',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Operasional',
                'department' => 'Sekretariat',
                'divisi' => 'Administrasi',
                'jabatan' => 'staff',
                'tanggal_mulai_kerja' => now()->subYears(6),
                'tanggal_jadi_anggota' => now()->subYears(5),
            ],
        ];

        foreach ($profilBawaan as $row) {
            $user = User::where('no_karyawan', $row['no_karyawan'])->first();
            if (! $user || $user->anggota()->exists()) {
                continue;
            }

            $this->hubungkanAtauBuatAnggota($user, $row);
        }

        if (Schema::hasTable('roles') && Role::where('name', 'pengawas')->exists()) {
            User::role('pengawas')
                ->doesntHave('anggota')
                ->get()
                ->each(function (User $user): void {
                    if (empty($user->no_karyawan)) {
                        return;
                    }

                    $this->hubungkanAtauBuatAnggota($user, [
                        'no_anggota' => null,
                        'cabang' => 'Banjarmasin',
                        'unit_bisnis' => 'Pengawas',
                        'department' => 'Audit & Pengawasan',
                        'divisi' => 'Pengawas',
                        'jabatan' => $user->levelPengawas() === 1 ? 'hod' : 'staff',
                        'tanggal_mulai_kerja' => now()->subYears(4),
                        'tanggal_jadi_anggota' => now()->subYears(3),
                    ]);
                });
        }
    }

    public function down(): void
    {
        // Profil anggota tidak dihapus saat rollback agar riwayat pengajuan tidak rusak.
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function hubungkanAtauBuatAnggota(User $user, array $atribut): void
    {
        $anggotaAda = Anggota::where('no_karyawan', $user->no_karyawan)->first();
        if ($anggotaAda) {
            if ($anggotaAda->user_id === null) {
                $pembaruan = ['user_id' => $user->id];
                if ($anggotaAda->status !== 'resign') {
                    $pembaruan['status'] = 'aktif';
                }
                $anggotaAda->update($pembaruan);
            }

            return;
        }

        $kandidatNoAnggota = $atribut['no_anggota'] ?? null;
        $noAnggota = $kandidatNoAnggota && ! Anggota::where('no_anggota', $kandidatNoAnggota)->exists()
            ? $kandidatNoAnggota
            : Anggota::generateNoAnggota();

        Anggota::create([
            'user_id' => $user->id,
            'no_anggota' => $noAnggota,
            'no_karyawan' => $user->no_karyawan,
            'nama' => $user->name,
            'cabang' => $atribut['cabang'],
            'unit_bisnis' => $atribut['unit_bisnis'],
            'department' => $atribut['department'],
            'divisi' => $atribut['divisi'],
            'jabatan' => $atribut['jabatan'],
            'tanggal_mulai_kerja' => $atribut['tanggal_mulai_kerja'],
            'tanggal_jadi_anggota' => $atribut['tanggal_jadi_anggota'],
            'status' => 'aktif',
        ]);
    }
};
