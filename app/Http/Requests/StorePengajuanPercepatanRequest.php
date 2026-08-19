<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengajuanPercepatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pinjaman_id' => ['required', 'integer', 'exists:pinjaman,id'],
            'tipe' => ['required', Rule::in(['ubah_tenor', 'lunas_total'])],
            'tenor_baru' => ['nullable', 'required_if:tipe,ubah_tenor', 'integer', 'min:1', 'max:120'],
            'keterangan' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
