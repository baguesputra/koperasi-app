<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RiwayatPinjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:diajukan,approved_admin,approved_bendahara,approved_ketua,aktif,ditolak,lunas',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
