<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KeputusanPengajuanPercepatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aksi' => ['required', Rule::in(['setuju', 'tolak'])],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'bulan_berlaku' => ['nullable', Rule::in(['bulan_ini', 'bulan_depan'])],
        ];
    }
}
