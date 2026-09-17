<?php

namespace App\Services\Migrasi;

use App\Models\Anggota;

class PencocokanNamaService
{
    /**
     * Alias aman: token pertama m/m. selalu dibaca muhammad.
     * Di luar alias ini jangan tebak — kembalikan null (wajib manual).
     */
    public const SATU_TOKEN_BLOKIR = [
        'abdullah', 'juraimi', 'syahril', 'tarpan', 'sugito', 'badri', 'marlina',
        'yuri', 'isna', 'sarbini', 'jamalludin', 'topo', 'yanto', 'agus', 'dodi',
    ];

    private const MAKS_SELISIH_OTOMATIS = 1;

    private const MIN_PERSEN_OTOMATIS = 90;

    private $daftarAktif = null;

    public function normalisasi(string $nama): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($nama)));
    }

    public function ekspansiAlias(string $nama): string
    {
        $token = preg_split('/\s+/', trim($nama));

        if (count($token) > 1 && in_array(mb_strtolower(rtrim($token[0], '.')), ['m'], true)) {
            $token[0] = 'muhammad';
        }

        return implode(' ', $token);
    }

    /**
     * Cari anggota aktif dari nama bebas (hasil pivot tanpa no_karyawan).
     * Exact unik prioritas utama; fallback typo ringan (selisih 1 huruf,
     * kemiripan >= 90, kandidat tunggal) agar Eka Yogi -> Eka Yogie masuk.
     * Selain itu null (wajib manual via No Karyawan).
     */
    public function cocokkan(string $nama): ?Anggota
    {
        $kunci = $this->normalisasi($this->ekspansiAlias($nama));

        if (in_array($kunci, self::SATU_TOKEN_BLOKIR, true) || count(preg_split('/\s+/', $kunci)) < 2) {
            return null;
        }

        $semua = $this->anggotaAktif();
        $cocok = $semua->filter(fn ($a) => $this->normalisasi($a->nama) === $kunci)->values();

        if ($cocok->count() === 1) {
            return $cocok->first();
        }

        if ($cocok->count() > 1) {
            return null;
        }

        $kandidat = $this->kandidatTerurut($kunci, $semua);

        if (
            count($kandidat) === 1
            && $kandidat[0]['jarak'] <= self::MAKS_SELISIH_OTOMATIS
            && $kandidat[0]['persen'] >= self::MIN_PERSEN_OTOMATIS
        ) {
            return $kandidat[0]['anggota'];
        }

        return null;
    }

    /**
     * Kandidat terdekat untuk pesan gagal (maks 2 biar ambigu tetap ketahuan).
     */
    public function saran(string $nama, int $batas = 2): array
    {
        $kunci = $this->normalisasi($this->ekspansiAlias($nama));

        if ($kunci === '' || count(preg_split('/\s+/', $kunci)) < 2) {
            return [];
        }

        return array_slice($this->kandidatTerurut($kunci, $this->anggotaAktif()), 0, max(1, $batas));
    }

    public function lupakanCache(): void
    {
        $this->daftarAktif = null;
    }

    private function anggotaAktif()
    {
        if ($this->daftarAktif === null) {
            $this->daftarAktif = Anggota::where('status', 'aktif')->get(['id', 'nama', 'no_karyawan']);
        }

        return $this->daftarAktif;
    }

    private function kandidatTerurut(string $kunci, $semua): array
    {
        $hasil = [];

        foreach ($semua as $anggota) {
            $normal = $this->normalisasi((string) $anggota->nama);

            if ($normal === '' || $normal === $kunci) {
                continue;
            }

            $jarak = levenshtein($kunci, $normal);
            $panjang = max(mb_strlen($kunci), mb_strlen($normal));

            if ($panjang === 0) {
                continue;
            }

            $persen = round((1 - $jarak / $panjang) * 100, 1);

            if ($jarak <= 3 && $persen >= 80) {
                $hasil[] = ['anggota' => $anggota, 'jarak' => $jarak, 'persen' => $persen];
            }
        }

        usort($hasil, fn ($a, $b) => $a['jarak'] <=> $b['jarak'] ?: $b['persen'] <=> $a['persen']);

        return $hasil;
    }
}
