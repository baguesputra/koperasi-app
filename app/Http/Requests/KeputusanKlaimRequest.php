<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KeputusanKlaimRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required' => 'Catatan wajib diisi sebagai alasan keputusan Bapak/Ibu.',
            'catatan.min' => 'Catatan minimal 5 karakter, mohon dijelaskan alasannya.',
            'nominal.required' => 'Nominal santunan yang disetujui wajib diisi.',
            'nominal.min' => 'Nominal santunan minimal Rp 1.',
        ];
    }
}
