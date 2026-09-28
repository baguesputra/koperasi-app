<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KeputusanPinjamanNominalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $wajibNominal = str_contains((string) $this->route()?->getName(), '.approve');

        return [
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
            'nominal' => [$wajibNominal ? 'required' : 'nullable', 'numeric', 'min:1'],
            'tenor_bulan' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required' => 'Catatan wajib diisi sebagai alasan keputusan Anda.',
            'catatan.min' => 'Catatan minimal 5 karakter, jelaskan alasan Anda.',
            'nominal.required' => 'Nominal yang disetujui wajib diisi.',
            'nominal.min' => 'Nominal yang disetujui minimal Rp 1.',
        ];
    }
}
