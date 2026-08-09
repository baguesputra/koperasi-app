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
            'nama' => ['required', 'string', 'max:255'],
            'cabang' => ['required', 'string', 'in:Banjarmasin,Samarinda,Palangka'],
            'unit_bisnis' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'in:staff,hod'],
            'tanggal_mulai_kerja' => ['required', 'date', 'before_or_equal:today'],
            'tanggal_jadi_anggota' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'string', 'in:aktif,nonaktif'],
            'limit_custom' => ['nullable', 'numeric', 'min:0'],
            'limit_custom_keterangan' => ['nullable', 'required_with:limit_custom', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama anggota wajib diisi.',
            'cabang.required' => 'Cabang wajib dipilih.',
            'unit_bisnis.required' => 'Unit bisnis wajib diisi.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
            'tanggal_mulai_kerja.required' => 'Tanggal mulai kerja wajib diisi.',
            'tanggal_jadi_anggota.required' => 'Tanggal jadi anggota wajib diisi.',
        ];
    }
}