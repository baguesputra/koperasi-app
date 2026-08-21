<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResignAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'alasan_resign' => ['required', 'string', 'min:10', 'max:500'],
            'tanggal_resign' => ['required', 'date', 'before_or_equal:today'],
            'konfirmasi_pelunasan' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan_resign.required' => 'Alasan resign wajib diisi.',
            'alasan_resign.min' => 'Alasan resign minimal 10 karakter.',
            'alasan_resign.max' => 'Alasan resign maksimal 500 karakter.',
            'tanggal_resign.required' => 'Tanggal resign wajib diisi.',
            'tanggal_resign.before_or_equal' => 'Tanggal resign tidak boleh di masa depan.',
            'konfirmasi_pelunasan.accepted' => 'Anda harus menyetujui pelunasan otomatis dari simpanan.',
        ];
    }
}
