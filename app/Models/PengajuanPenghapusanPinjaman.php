<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanPenghapusanPinjaman extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_penghapusan_pinjaman';

    protected $fillable = [
        'pinjaman_id',
        'anggota_id',
        'nomor_dokumen',
        'pengaju_user_id',
        'tanggal_pengajuan',
        'alasan',
        'nominal_pinjaman',
        'total_pokok_terbayar',
        'total_bunga_terbayar',
        'sisa_cicilan',
        'sisa_pokok',
        'sisa_bunga',
        'sisa_total_tagihan',
        'status',
        'approved_bendahara_by',
        'approved_bendahara_at',
        'catatan_bendahara',
        'approved_ketua_by',
        'approved_ketua_at',
        'catatan_ketua',
        'approved_pengawas_3_by',
        'approved_pengawas_3_at',
        'catatan_pengawas_3',
        'approved_pengawas_2_by',
        'approved_pengawas_2_at',
        'catatan_pengawas_2',
        'approved_pengawas_1_by',
        'approved_pengawas_1_at',
        'catatan_pengawas_1',
        'ditolak_oleh_user_id',
        'ditolak_pada',
        'tahap_ditolak',
        'catatan_tolak',
        'disetujui_pada',
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'date',
        'nominal_pinjaman' => 'decimal:2',
        'total_pokok_terbayar' => 'decimal:2',
        'total_bunga_terbayar' => 'decimal:2',
        'sisa_cicilan' => 'integer',
        'sisa_pokok' => 'decimal:2',
        'sisa_bunga' => 'decimal:2',
        'sisa_total_tagihan' => 'decimal:2',
        'approved_bendahara_at' => 'datetime',
        'approved_ketua_at' => 'datetime',
        'approved_pengawas_3_at' => 'datetime',
        'approved_pengawas_2_at' => 'datetime',
        'approved_pengawas_1_at' => 'datetime',
        'ditolak_pada' => 'datetime',
        'disetujui_pada' => 'datetime',
    ];

    public const STATUS_LABEL = [
        'diajukan' => 'Menunggu Pengawas 3',
        'approved_pengawas_3' => 'Menunggu Pengawas 2',
        'approved_pengawas_2' => 'Menunggu Pengawas 1 (Ketua Pengawas)',
        'disetujui' => 'Disetujui (Dihapusbukukan)',
        'ditolak' => 'Ditolak',
    ];

    public const TAHAP_DITOLAK_LABEL = [
        'pengawas_3' => 'Pengawas 3',
        'pengawas_2' => 'Pengawas 2',
        'pengawas_1' => 'Pengawas 1 (Ketua Pengawas)',
        'bendahara' => 'Bendahara',
        'ketua' => 'Ketua Koperasi',
        'pengurus' => 'Pengurus',
    ];

    public function pinjaman(): BelongsTo
    {
        return $this->belongsTo(Pinjaman::class);
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengaju_user_id');
    }

    public function approverBendahara(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_bendahara_by');
    }

    public function approverKetua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_ketua_by');
    }

    public function approverPengawas3(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_pengawas_3_by');
    }

    public function approverPengawas2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_pengawas_2_by');
    }

    public function approverPengawas1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_pengawas_1_by');
    }

    public function penolak(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditolak_oleh_user_id');
    }

    public function getTimelineApproval(): array
    {
        $urutanStatus = [
            'diajukan' => 1,
            'approved_pengawas_3' => 2,
            'approved_pengawas_2' => 3,
            'disetujui' => 4,
        ];

        $langkahSekarang = $urutanStatus[$this->status] ?? 0;
        $ditolak = $this->status === 'ditolak';

        $tahapKeAngka = [
            'pengawas_3' => 2,
            'pengawas_2' => 3,
            'pengawas_1' => 4,
        ];
        $angkaTolak = $ditolak ? ($tahapKeAngka[$this->tahap_ditolak] ?? 2) : 0;

        $statusTahap = function (int $ke, $approvedAt) use ($langkahSekarang, $ditolak, $angkaTolak): string {
            if ($ditolak) {
                if ($ke === $angkaTolak) {
                    return 'rejected';
                }

                return $approvedAt !== null || $ke === 1 ? 'done' : 'pending';
            }

            if ($langkahSekarang >= $ke) {
                return 'done';
            }

            if ($langkahSekarang + 1 === $ke) {
                return 'current';
            }

            return 'pending';
        };

        return [
            [
                'tahap' => 'pengajuan',
                'label' => '1. Pengajuan (Ketua Koperasi / Super Admin)',
                'user' => $this->pengaju?->name ?? '-',
                'tanggal' => $this->tanggal_pengajuan?->translatedFormat('d M Y') ?? '-',
                'catatan' => $this->alasan,
                'status' => 'done',
            ],
            [
                'tahap' => 'pengawas_3',
                'label' => '2. Persetujuan Pengawas 3',
                'user' => $this->approverPengawas3?->name ?? ($ditolak && $this->tahap_ditolak === 'pengawas_3' ? ($this->penolak?->name ?? '-') : 'Menunggu'),
                'tanggal' => $this->approved_pengawas_3_at?->translatedFormat('d M Y, H:i')
                    ?? ($ditolak && $this->tahap_ditolak === 'pengawas_3' ? ($this->ditolak_pada?->translatedFormat('d M Y, H:i') ?? '-') : '-'),
                'catatan' => $ditolak && $this->tahap_ditolak === 'pengawas_3' ? $this->catatan_tolak : $this->catatan_pengawas_3,
                'status' => $statusTahap(2, $this->approved_pengawas_3_at),
            ],
            [
                'tahap' => 'pengawas_2',
                'label' => '3. Persetujuan Pengawas 2',
                'user' => $this->approverPengawas2?->name ?? ($ditolak && $this->tahap_ditolak === 'pengawas_2' ? ($this->penolak?->name ?? '-') : 'Menunggu'),
                'tanggal' => $this->approved_pengawas_2_at?->translatedFormat('d M Y, H:i')
                    ?? ($ditolak && $this->tahap_ditolak === 'pengawas_2' ? ($this->ditolak_pada?->translatedFormat('d M Y, H:i') ?? '-') : '-'),
                'catatan' => $ditolak && $this->tahap_ditolak === 'pengawas_2' ? $this->catatan_tolak : $this->catatan_pengawas_2,
                'status' => $statusTahap(3, $this->approved_pengawas_2_at),
            ],
            [
                'tahap' => 'pengawas_1',
                'label' => '4. Persetujuan Pengawas 1 (Ketua Pengawas)',
                'user' => $this->approverPengawas1?->name ?? ($ditolak && $this->tahap_ditolak === 'pengawas_1' ? ($this->penolak?->name ?? '-') : 'Menunggu'),
                'tanggal' => $this->approved_pengawas_1_at?->translatedFormat('d M Y, H:i')
                    ?? ($ditolak && $this->tahap_ditolak === 'pengawas_1' ? ($this->ditolak_pada?->translatedFormat('d M Y, H:i') ?? '-') : '-'),
                'catatan' => $ditolak && $this->tahap_ditolak === 'pengawas_1' ? $this->catatan_tolak : $this->catatan_pengawas_1,
                'status' => $statusTahap(4, $this->approved_pengawas_1_at),
            ],
        ];
    }
}
