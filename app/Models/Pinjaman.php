<?php

namespace App\Models;

use App\Helpers\TerbilangHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pinjaman extends Model
{
    use HasFactory;

    protected $table = 'pinjaman';

    protected $fillable = [
        'anggota_id',
        'pengaju_user_id',
        'nominal',
        'tenor_bulan',
        'keperluan',
        'snapshot_bank',
        'snapshot_no_rekening',
        'snapshot_atas_nama',
        'persentase_bunga',
        'status',
        'cair_oleh_bendahara',
        'sudah_pakai_privilege_reloan',
        'tanggal_pengajuan',
        'tanggal_pencairan',
        'disetujui_pada',
        'versi_syarat',
        'ip_address_setuju',
        'user_agent_setuju',
        'catatan_bendahara',
        'catatan_ketua',
        'sudah_pakai_percepatan',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'persentase_bunga' => 'decimal:2',
        'cair_oleh_bendahara' => 'boolean',
        'sudah_pakai_privilege_reloan' => 'boolean',
        'tanggal_pengajuan' => 'date',
        'tanggal_pencairan' => 'date',
        'disetujui_pada' => 'datetime',
        'sudah_pakai_percepatan' => 'boolean',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengaju_user_id');
    }

    public function angsuran(): HasMany
    {
        return $this->hasMany(Angsuran::class);
    }

    public function angsuranBelumBayar(): HasMany
    {
        return $this->angsuran()->where('status', 'belum_bayar');
    }

    public function sisaAngsuran(): int
    {
        return $this->angsuranBelumBayar()->count();
    }

    /**
     * Rata-rata nominal pokok per angsuran yang masih belum dibayar.
     * Hanya membaca tabel angsuran biasa (tidak termasuk angsuran_percepatan).
     * Gunakan cicilanPokokAktif() untuk kalkulasi limit reloan yang konsisten dengan jadwalAktif().
     */
    public function cicilanPokok(): float
    {
        return (float) ($this->angsuran()
            ->where('status', 'belum_bayar')
            ->avg('nominal_pokok') ?? 0);
    }

    /**
     * Angsuran-percepatan yang terkait langsung via pengajuan_percepatan.
     * Dipakai untuk filter hanya angsuran_percepatan dari pengajuan yang aktif.
     */
    public function angsuranPercepatan()
    {
        return $this->hasManyThrough(
            AngsuranPercepatan::class,
            PengajuanPercepatan::class,
            'pinjaman_id',
            'pengajuan_percepatan_id'
        );
    }

    public function pengajuanPercepatan()
    {
        return $this->hasMany(PengajuanPercepatan::class);
    }

    /**
     * Jadwal angsuran yang AKTIF sekarang - gabungan dari angsuran asli (yang belum digantikan)
     * dan angsuran_percepatan dari pengajuan yang sudah aktif (kalau ada).
     */
    public function jadwalAktif()
    {
        $pengajuanAktif = $this->pengajuanPercepatan()->where('status', 'aktif')->latest()->first();
        $lama = $this->angsuran()->where('status', '!=', 'digantikan')->orderBy('cicilan_ke')->get();

        if (! $pengajuanAktif) {
            return $lama;
        }

        $baru = $pengajuanAktif->angsuranBaru()->orderBy('cicilan_ke')->get();

        return $lama->concat($baru);
    }

    public function totalCicilanAktif(): int
    {
        return $this->jadwalAktif()->count();
    }

    public function sisaCicilanAktif(): int
    {
        return $this->jadwalAktif()->where('status', 'belum_bayar')->count();
    }

    public function sisaTotalBayarAktif(): float
    {
        return (float) $this->jadwalAktif()->where('status', 'belum_bayar')->sum('total_bayar');
    }

    /**
     * Rata-rata nominal pokok per cicilan aktif (gabungan angsuran biasa yang belum digantikan
     * + angsuran_percepatan dari pengajuan aktif). Konsisten dengan jadwalAktif() / sisaCicilanAktif().
     */
    public function cicilanPokokAktif(): float
    {
        return (float) ($this->jadwalAktif()
            ->where('status', 'belum_bayar')
            ->avg('nominal_pokok') ?? 0);
    }

    /**
     * Susun data lengkap untuk Bukti Peminjaman (dipakai halaman cetak & lampiran PDF WA).
     */
    public function dataBukti(): array
    {
        $this->loadMissing(['anggota', 'angsuran']);

        $angsuranList = $this->angsuran()
            ->orderBy('cicilan_ke')
            ->get()
            ->map(fn ($a) => [
                'cicilan_ke' => $a->cicilan_ke,
                'tanggal_jatuh_tempo' => $a->tanggal_jatuh_tempo?->format('d M Y'),
                'nominal_pokok' => (float) $a->nominal_pokok,
                'nominal_bunga' => (float) $a->nominal_bunga,
                'total_bayar' => (float) $a->total_bayar,
                'status' => $a->status,
            ]);

        return [
            'pinjaman' => [
                'id' => $this->id,
                'nominal' => (float) $this->nominal,
                'terbilang' => TerbilangHelper::angkaKeTerbilang($this->nominal),
                'tenor_bulan' => $this->tenor_bulan,
                'persentase_bunga' => (float) $this->persentase_bunga,
                'keperluan' => $this->keperluan,
                'tanggal_pengajuan' => $this->tanggal_pengajuan?->format('d M Y'),
                'tanggal_cair' => $this->tanggal_pencairan?->format('d M Y'),
                'rekening' => [
                    'bank' => $this->snapshot_bank,
                    'no_rekening' => $this->snapshot_no_rekening,
                    'atas_nama' => $this->snapshot_atas_nama,
                ],
                'anggota' => [
                    'id' => $this->anggota->id,
                    'no_anggota' => $this->anggota->no_anggota,
                    'no_karyawan' => $this->anggota->no_karyawan,
                    'nama' => $this->anggota->nama,
                    'cabang' => $this->anggota->cabang,
                    'unit_bisnis' => $this->anggota->unit_bisnis,
                    'jabatan' => $this->anggota->jabatan,
                ],
            ],
            'angsuran' => $angsuranList,
            'totals' => [
                'pokok' => $angsuranList->sum('nominal_pokok'),
                'bunga' => $angsuranList->sum('nominal_bunga'),
                'angsuran' => $angsuranList->sum('total_bayar'),
            ],
        ];
    }
}
