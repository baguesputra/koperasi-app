<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal_jadi_anggota' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'string', 'in:aktif,nonaktif,resign_menunggu'],
            'limit_custom' => ['nullable', 'numeric', 'min:0'],
            'limit_custom_keterangan' => ['nullable', 'required_with:limit_custom', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_jadi_anggota.required' => 'Tanggal jadi anggota wajib diisi.',
            'limit_custom_keterangan.required_with' => 'Keterangan wajib diisi saat nominal limit khusus diisi.',
        ];
    }
}
