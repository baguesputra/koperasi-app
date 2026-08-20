<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'no_karyawan' => ['required', 'string', 'max:50', 'unique:users,no_karyawan'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'cabang' => ['required', 'string', 'in:Banjarmasin,Samarinda,Palangka'],
            'unit_bisnis' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'in:staff,hod'],
            'tanggal_mulai_kerja' => ['required', 'date', 'before_or_equal:today'],
            'tanggal_jadi_anggota' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama anggota wajib diisi.',
            'no_karyawan.required' => 'No karyawan wajib diisi (dipakai untuk akun login).',
            'no_karyawan.unique' => 'No karyawan sudah terdaftar sebagai akun pengguna.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai akun pengguna lain.',
            'cabang.required' => 'Cabang wajib dipilih.',
            'unit_bisnis.required' => 'Unit bisnis wajib diisi.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
            'tanggal_mulai_kerja.required' => 'Tanggal mulai kerja wajib diisi.',
            'tanggal_jadi_anggota.required' => 'Tanggal jadi anggota wajib diisi.',
        ];
    }
}
