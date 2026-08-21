<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KeputusanPinjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required' => 'Catatan wajib diisi sebagai alasan keputusan Anda.',
            'catatan.min' => 'Catatan minimal 5 karakter, jelaskan alasan Anda.',
        ];
    }
}
