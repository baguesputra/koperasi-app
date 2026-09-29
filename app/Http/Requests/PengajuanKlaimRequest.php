<?php

namespace App\Http\Requests;

use App\Models\KlaimDanaSosial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengajuanKlaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(KlaimDanaSosial::JENIS)],
            'sub_tipe' => ['nullable', Rule::in(KlaimDanaSosial::SUB_TIPE_SAKIT), 'required_if:jenis,sakit'],
            'hubungan' => ['nullable', Rule::in(KlaimDanaSosial::HUBUNGAN_DUKA), 'required_if:jenis,duka'],
            'tanggal_kejadian' => ['required', 'date', 'before_or_equal:today'],
            'lama_hari' => ['nullable', 'integer', 'min:1', 'required_if:jenis,sakit'],
            'keterangan' => ['required', 'string', 'min:10', 'max:1000'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis santunan wajib dipilih.',
            'jenis.in' => 'Jenis santunan tidak dikenal.',
            'sub_tipe.required_if' => 'Jenis perawatan wajib dipilih (Rawat Jalan / Rawat Inap).',
            'hubungan.required_if' => 'Hubungan keluarga wajib dipilih.',
            'tanggal_kejadian.required' => 'Tanggal kejadian wajib diisi.',
            'tanggal_kejadian.before_or_equal' => 'Tanggal kejadian tidak boleh melebihi hari ini.',
            'lama_hari.required_if' => 'Lama perawatan wajib diisi.',
            'lama_hari.min' => 'Lama perawatan minimal 1 hari.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'keterangan.min' => 'Keterangan minimal 10 karakter, mohon dijelaskan secara rinci.',
            'foto.required' => 'Dokumen pendukung (foto/surat keterangan) wajib diunggah.',
            'foto.image' => 'Dokumen harus berupa berkas gambar.',
            'foto.mimes' => 'Dokumen harus berformat JPG atau PNG.',
            'foto.max' => 'Ukuran dokumen maksimal 5 MB.',
        ];
    }
}
