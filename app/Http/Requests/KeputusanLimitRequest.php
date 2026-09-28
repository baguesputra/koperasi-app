<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KeputusanLimitRequest extends FormRequest
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
            'limit_disetujui' => [$wajibNominal ? 'required' : 'nullable', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required' => 'Catatan wajib diisi sebagai alasan keputusan Anda.',
            'catatan.min' => 'Catatan minimal 5 karakter, jelaskan alasan Anda.',
            'limit_disetujui.required_if' => 'Nominal yang disetujui wajib diisi.',
            'limit_disetujui.min' => 'Nominal yang disetujui minimal Rp 1.',
        ];
    }
}
