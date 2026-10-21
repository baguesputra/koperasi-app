<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $fillable = [
        'user_id',
        'aksi',
        'keterangan',
        'data_lama',
        'data_baru',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'data_lama' => 'array',
        'data_baru' => 'array',
    ];

    /**
     * Mencegah modifikasi data audit log (Immutable & Anti-tampering).
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Audit log bersifat permanen dan tidak boleh diubah (immutable).');
        });

        static::deleting(function () {
            if (! app()->runningInConsole()) {
                throw new \RuntimeException('Audit log dilindungi dan tidak boleh dihapus melalui aplikasi.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat log audit sistem dengan perlindungan integritas berlapis:
     * 1. Otomatis merekam IP Address dan User Agent.
     * 2. Jika di dalam DB transaction dan terjadi kegagalan catat,
     *    transaksi bisnis WAJIB di-rollback agar tidak ada manipulasi tanpa jejak.
     * 3. Jika gagal, catat ke Emergency Logger (storage/logs).
     */
    public static function catat(
        string $aksi,
        string $keterangan,
        $dataLama = null,
        $dataBaru = null,
        ?int $userId = null
    ): ?self {
        $ip = null;
        $ua = null;

        try {
            if (app()->bound('request')) {
                $req = request();
                $ip = $req?->ip();
                $ua = $req?->userAgent() ? substr($req->userAgent(), 0, 500) : null;
            }
        } catch (\Throwable) {
            // Diluar HTTP context (misal CLI/Queue)
        }

        $userIdFinal = $userId ?? Auth::id();

        try {
            return self::create([
                'user_id' => $userIdFinal,
                'aksi' => $aksi,
                'keterangan' => $keterangan,
                'data_lama' => $dataLama,
                'data_baru' => $dataBaru,
                'ip_address' => $ip,
                'user_agent' => $ua,
            ]);
        } catch (\Throwable $e) {
            // Emergency fallback logging agar tidak pernah hilang jejak jika DB audit bermasalah
            Log::emergency('KEGAGALAN PENCATATAN AUDIT LOG: ' . $e->getMessage(), [
                'aksi' => $aksi,
                'keterangan' => $keterangan,
                'user_id' => $userIdFinal,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'data_lama' => $dataLama,
                'data_baru' => $dataBaru,
                'exception' => $e->getMessage(),
            ]);

            // Jika sedang berada dalam transaksi database, batalkan transaksi agar tidak terjadi perubahan tanpa audit!
            if (DB::transactionLevel() > 0) {
                throw new \RuntimeException(
                    "Gagal menyimpan catatan audit log (transaksi dibatalkan demi keamanan data): {$e->getMessage()}",
                    0,
                    $e
                );
            }

            return null;
        }
    }

    /**
     * Daftar grup kategori audit log.
     */
    public static function daftarKategori(): array
    {
        return [
            'pinjaman' => 'Pinjaman',
            'percepatan' => 'Perubahan Tenor',
            'simpanan' => 'Simpanan',
            'angsuran' => 'Angsuran',
            'limit' => 'Limit Pinjaman',
            'keuangan' => 'Keuangan & Pengeluaran',
            'anggota' => 'Anggota & Aktivasi',
            'pengguna' => 'Manajemen Pengguna',
            'role' => 'Hak Akses & Role',
            'pengaturan' => 'Pengaturan Sistem',
            'auth' => 'Autentikasi & Gate',
        ];
    }

    /**
     * Tentukan kode kategori dari nama aksi.
     */
    public static function kategoriAksi(string $aksi): string
    {
        if (str_starts_with($aksi, 'pinjaman_')) return 'pinjaman';
        if (str_starts_with($aksi, 'percepatan_')) return 'percepatan';
        if (str_starts_with($aksi, 'simpanan_')) return 'simpanan';
        if (str_starts_with($aksi, 'angsuran_')) return 'angsuran';
        if (str_contains($aksi, 'limit') || $aksi === 'setujui_pengajuan_limit') return 'limit';
        if (str_starts_with($aksi, 'pengeluaran_') || $aksi === 'penuhi_pagu_kas_kecil' || $aksi === 'pinjaman_talangan_kembali') return 'keuangan';
        if (str_starts_with($aksi, 'aktivasi_') || str_starts_with($aksi, 'anggota_') || str_contains($aksi, 'resign')) return 'anggota';
        if (str_ends_with($aksi, '_pengguna')) return 'pengguna';
        if (str_ends_with($aksi, '_role')) return 'role';
        if (str_starts_with($aksi, 'update_') || str_starts_with($aksi, 'tambah_') || str_starts_with($aksi, 'hapus_')) return 'pengaturan';
        if (str_starts_with($aksi, 'sso_') || str_starts_with($aksi, 'sinkron_') || str_starts_with($aksi, 'migrasi_')) return 'auth';

        return 'pengaturan';
    }

    /**
     * Label manusiawi untuk kategori aksi.
     */
    public static function labelKategori(string $aksi): string
    {
        $kat = self::kategoriAksi($aksi);
        $daftar = self::daftarKategori();

        return $daftar[$kat] ?? ucfirst($kat);
    }

    /**
     * Scope filter pencarian audit log.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        // 1. Pencarian bebas (aksi, keterangan, ip_address, nama user, no_karyawan)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('aksi', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                            ->orWhere('no_karyawan', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Filter User / Aktor
        if (isset($filters['user_id']) && $filters['user_id'] !== '' && $filters['user_id'] !== null) {
            if ($filters['user_id'] === 'system') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', (int) $filters['user_id']);
            }
        }

        // 3. Filter Kategori Modul
        if (! empty($filters['kategori'])) {
            $kategori = $filters['kategori'];
            $query->where(function (Builder $q) use ($kategori) {
                match ($kategori) {
                    'pinjaman' => $q->where('aksi', 'like', 'pinjaman_%'),
                    'percepatan' => $q->where('aksi', 'like', 'percepatan_%'),
                    'simpanan' => $q->where('aksi', 'like', 'simpanan_%'),
                    'angsuran' => $q->where('aksi', 'like', 'angsuran_%'),
                    'limit' => $q->where('aksi', 'like', '%limit%')->orWhere('aksi', 'setujui_pengajuan_limit'),
                    'keuangan' => $q->where('aksi', 'like', 'pengeluaran_%')
                        ->orWhereIn('aksi', ['penuhi_pagu_kas_kecil', 'pinjaman_talangan_kembali']),
                    'anggota' => $q->where('aksi', 'like', 'aktivasi_%')
                        ->orWhere('aksi', 'like', 'anggota_%')
                        ->orWhere('aksi', 'like', '%resign%'),
                    'pengguna' => $q->where('aksi', 'like', '%_pengguna'),
                    'role' => $q->where('aksi', 'like', '%_role'),
                    'pengaturan' => $q->where('aksi', 'like', 'update_%')
                        ->orWhere('aksi', 'like', 'tambah_%')
                        ->orWhere('aksi', 'like', 'hapus_%'),
                    'auth' => $q->where('aksi', 'like', 'sso_%')
                        ->orWhere('aksi', 'like', 'sinkron_%')
                        ->orWhere('aksi', 'like', 'migrasi_%'),
                    default => null,
                };
            });
        }

        // 4. Filter Aksi Spesifik
        if (! empty($filters['aksi'])) {
            $query->where('aksi', $filters['aksi']);
        }

        // 5. Filter Rentang Tanggal
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
