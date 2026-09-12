{{-- Kop formal bersama: logo + nama/unit/alamat/kontak + garis tunggal. --}}
@php
    $kopNama = $kopNama ?? config('koperasi.nama', 'KOPERASI KARYAWAN');
    $kopUnit = $kopUnit ?? config('koperasi.unit', 'KARYA MANDIRI DUTA MALL BANJARMASIN');
    $kopAlamat = $kopAlamat ?? config('koperasi.alamat', 'Jalan Jenderal Ahmad Yani KM 2 No. 98, Melayu');
    $kopKontak = $kopKontak ?? trim(collect([config('koperasi.telepon'), config('koperasi.email')])->filter()->join(' | '));
    if (! isset($logoBase64)) {
        $logoPath = public_path('images/logo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';
    }
@endphp
<div class="kop-row">
    <div class="kop-logo">
        @if(! empty($logoBase64))
            <img src="{{ $logoBase64 }}" alt="Logo Koperasi" />
        @else
            <div class="logo-fallback">KOP</div>
        @endif
    </div>
    <div class="kop-text">
        <h1 class="kop-nama">{{ $kopNama }}</h1>
        <p class="kop-unit">{{ $kopUnit }}</p>
        <p class="kop-alamat">{{ $kopAlamat }}</p>
        @if(! empty($kopKontak))
            <p class="kop-alamat">{{ $kopKontak }}</p>
        @endif
    </div>
</div>
<div class="kop-garis"></div>
