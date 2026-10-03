<?php

namespace App\Models;

use App\Helpers\TerbilangHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

class Pinjaman extends Model
{
    use HasFactory;

    protected $table = 'pinjaman';

    protected $fillable = [
        'anggota_id',
        'pengaju_user_id',
        'nomor_dokumen',
        'nominal',
        'tenor_bulan',
        'nominal_diminta',
        'tenor_diminta',
        'nominal_disetujui_bendahara',
        'tenor_disetujui_bendahara',
        'nominal_disetujui',
        'tenor_disetujui',
        'kas_saldo_bendahara',
        'kas_saldo_ketua',
        'kas_sisa_ketua',
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
        'verification_revoked_at',
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
        'verification_revoked_at' => 'datetime',
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

    public function cicilanPokok(): float
    {
        return (float) ($this->angsuran()
            ->where('status', 'belum_bayar')
            ->avg('nominal_pokok') ?? 0);
    }

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
     * Memo jadwal aktif per instance. Empat helper agregat (sisa/total/bayar/pokok)
     * membaca jadwal yang sama — tanpa memo, tiap helper = 2-3 query (N+1 fan-out).
     * Pakai refresh() + jadwalAktifCache = null bila butuh data fresh setelah update.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $jadwalAktifCache = null;

    public function jadwalAktif()
    {
        if ($this->jadwalAktifCache !== null) {
            return $this->jadwalAktifCache;
        }

        $pengajuanAktif = $this->pengajuanPercepatan()->where('status', 'aktif')->latest()->first();
        $lama = $this->angsuran()->where('status', '!=', 'digantikan')->orderBy('cicilan_ke')->get();

        if (! $pengajuanAktif) {
            return $this->jadwalAktifCache = $lama;
        }

        $baru = $pengajuanAktif->angsuranBaru()->orderBy('cicilan_ke')->get();

        return $this->jadwalAktifCache = $lama->concat($baru);
    }

    /**
     * Agregat jadwal aktif dalam 1x load (ganti 4 helper terpisah = 4x jadwalAktif).
     * Return: ['total' => int, 'sisa' => int, 'sisa_bayar' => float, 'cicilan_pokok' => float]
     */
    public function agregatJadwalAktif(): array
    {
        $jadwal = $this->jadwalAktif();
        $belum = $jadwal->where('status', 'belum_bayar');

        return [
            'total' => $jadwal->count(),
            'sisa' => $belum->count(),
            'sisa_bayar' => (float) $belum->sum('total_bayar'),
            'cicilan_pokok' => (float) ($belum->avg('nominal_pokok') ?? 0),
        ];
    }

    public function totalCicilanAktif(): int
    {
        return $this->agregatJadwalAktif()['total'];
    }

    public function sisaCicilanAktif(): int
    {
        return $this->agregatJadwalAktif()['sisa'];
    }

    public function sisaTotalBayarAktif(): float
    {
        return $this->agregatJadwalAktif()['sisa_bayar'];
    }

    public function cicilanPokokAktif(): float
    {
        return $this->agregatJadwalAktif()['cicilan_pokok'];
    }

    public function verificationUrl(): string
    {
        return URL::signedRoute('verifikasi.bukti', ['pinjaman' => $this->id]);
    }

    public function isVerificationRevoked(): bool
    {
        return $this->verification_revoked_at !== null;
    }

    public function getVerificationTimeline(): array
    {
        $timeline = [];

        $timeline[] = [
            'label' => 'Pengajuan',
            'date' => $this->tanggal_pengajuan?->format('d M Y H:i'),
            'user' => $this->pengaju?->name ?? '-',
            'status' => 'done',
            'icon' => 'file-text',
        ];

        $bendaharaData = $this->parseApprovalNote($this->catatan_bendahara);
        $timeline[] = [
            'label' => 'Approve Bendahara',
            'date' => $bendaharaData['date'] ?? ($this->tanggal_pencairan?->format('d M Y H:i') ?? '-'),
            'user' => $bendaharaData['user'] ?? '-',
            'status' => in_array($this->status, ['approved_bendahara', 'approved_ketua', 'aktif', 'lunas']) ? 'done' : 'pending',
            'icon' => 'shield-check',
        ];

        $ketuaData = $this->parseApprovalNote($this->catatan_ketua);
        $timeline[] = [
            'label' => 'Approve Ketua',
            'date' => $ketuaData['date'] ?? ($this->disetujui_pada?->format('d M Y H:i') ?? '-'),
            'user' => $ketuaData['user'] ?? '-',
            'status' => in_array($this->status, ['approved_ketua', 'aktif', 'lunas']) ? 'done' : 'pending',
            'icon' => 'user-check',
        ];

        $timeline[] = [
            'label' => 'Pencairan',
            'date' => $this->tanggal_pencairan?->format('d M Y H:i') ?? '-',
            'user' => $this->cairOlehBendahara?->name ?? '-',
            'status' => $this->status === 'aktif' ? 'done' : 'pending',
            'icon' => 'banknote',
        ];

        return $timeline;
    }

    private function parseApprovalNote(?string $note): array
    {
        if (! $note) {
            return [];
        }

        $result = ['date' => null, 'user' => null];

        if (preg_match('/pada\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2})/i', $note, $matches)) {
            $result['date'] = date('d M Y H:i', strtotime($matches[1]));
        } elseif (preg_match('/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2})/', $note, $matches)) {
            $result['date'] = date('d M Y H:i', strtotime($matches[1]));
        }

        if (preg_match('/oleh\s+([A-Za-z\s.]+?)(?:\s+pada|\s*$)/i', $note, $matches)) {
            $result['user'] = trim($matches[1]);
        } elseif (preg_match('/disetujui\s+oleh\s+([A-Za-z\s.]+)/i', $note, $matches)) {
            $result['user'] = trim($matches[1]);
        }

        return $result;
    }

    public function dataBukti(): array
    {
        $this->loadMissing(['anggota', 'angsuran', 'pengaju']);

        // Pakai relasi yang sudah di-load (ganti query ulang angsuran)
        $angsuranList = $this->getRelationValue('angsuran')->sortBy('cicilan_ke')->values()
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
                'nomor_dokumen' => $this->nomor_dokumen,
                'nominal' => (float) $this->nominal,
                'terbilang' => TerbilangHelper::angkaKeTerbilang($this->nominal),
                'tenor_bulan' => $this->tenor_bulan,
                'persentase_bunga' => (float) $this->persentase_bunga,
                'keperluan' => $this->keperluan,
                'tanggal_pengajuan' => $this->tanggal_pengajuan?->translatedFormat('d F Y'),
                'tanggal_cair' => $this->tanggal_pencairan?->translatedFormat('d F Y'),
                'rekening' => [
                    'bank' => $this->snapshot_bank,
                    'no_rekening' => $this->snapshot_no_rekening,
                    'atas_nama' => $this->snapshot_atas_nama,
                ],
                'anggota' => [
                    'id' => $this->anggota->id,
                    'no_karyawan' => $this->anggota->no_karyawan,
                    'nama' => $this->anggota->nama,
                    'cabang' => $this->anggota->cabang,
                    'unit_bisnis' => $this->anggota->unit_bisnis,
                    'jabatan' => $this->anggota->jabatan,
                ],
                'verification_url' => $this->verificationUrl(),
            ],
            'kota_ttd' => config('koperasi.kota_ttd', 'Banjarmasin'),
            'angsuran' => $angsuranList,
            'totals' => [
                'pokok' => $angsuranList->sum('nominal_pokok'),
                'bunga' => $angsuranList->sum('nominal_bunga'),
                'angsuran' => $angsuranList->sum('total_bayar'),
            ],
        ];
    }
}
