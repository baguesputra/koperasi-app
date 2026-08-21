<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReaktivasiAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'alasan_reaktivasi' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan_reaktivasi.required' => 'Alasan reaktivasi wajib diisi.',
            'alasan_reaktivasi.min' => 'Alasan reaktivasi minimal 10 karakter.',
            'alasan_reaktivasi.max' => 'Alasan reaktivasi maksimal 500 karakter.',
        ];
    }
}
