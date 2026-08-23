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
            'no_karyawan' => ['required', 'string', 'max:50', 'regex:/^TOP-\d{6}$/', 'unique:users,no_karyawan'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'cabang' => ['required', 'string', 'in:Banjarmasin,Samarinda,Palangka'],
            'unit_bisnis' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'in:staff,hod'],
            'department' => ['required', 'string', 'max:255'],
            'tanggal_mulai_kerja' => ['required', 'date', 'before_or_equal:today'],
            'tanggal_jadi_anggota' => ['required', 'date', 'before_or_equal:today'],
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^(\+62|0)8\d{8,11}$/'],
            'alamat' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama anggota wajib diisi.',
            'no_karyawan.required' => 'No karyawan wajib diisi (dipakai untuk akun login).',
            'no_karyawan.regex' => 'Format no karyawan harus TOP-XXXXXX (contoh: TOP-123456).',
            'no_karyawan.unique' => 'No karyawan sudah terdaftar sebagai akun pengguna.',
            'email.required' => 'Email wajib diisi untuk notifikasi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai akun pengguna lain.',
            'cabang.required' => 'Cabang wajib dipilih.',
            'unit_bisnis.required' => 'Unit bisnis wajib diisi.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
            'department.required' => 'Department wajib dipilih.',
            'tanggal_mulai_kerja.required' => 'Tanggal mulai kerja wajib diisi.',
            'tanggal_jadi_anggota.required' => 'Tanggal jadi anggota wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid (contoh: 081234567890 atau +6281234567890).',
        ];
    }
}
