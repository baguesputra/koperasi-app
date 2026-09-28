<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Koperasi App API',
    version: '1.0.0',
    description: 'RESTful API untuk aplikasi mobile koperasi dan integrasi'
)]
#[OA\Tag(name: 'Authentication', description: 'Endpoint autentikasi')]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'no_karyawan', type: 'string', example: 'TOP-100001'),
        new OA\Property(property: 'sso_id', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'auth_provider', type: 'string', example: 'local'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'harus_ganti_password', type: 'boolean', example: false),
        new OA\Property(property: 'status', type: 'string', example: 'aktif'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Pinjaman',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'anggota_id', type: 'integer', example: 1),
        new OA\Property(property: 'pengaju_user_id', type: 'integer', example: 4),
        new OA\Property(property: 'nominal', type: 'string', example: '5000000.00'),
        new OA\Property(property: 'tenor_bulan', type: 'integer', example: 12),
        new OA\Property(property: 'keperluan', type: 'string', example: 'Renovasi rumah'),
        new OA\Property(property: 'snapshot_bank', type: 'string', nullable: true, example: 'BCA'),
        new OA\Property(property: 'snapshot_no_rekening', type: 'string', nullable: true, example: '1234567890'),
        new OA\Property(property: 'snapshot_atas_nama', type: 'string', nullable: true, example: 'John Doe'),
        new OA\Property(property: 'persentase_bunga', type: 'string', example: '1.00'),
        new OA\Property(property: 'status', type: 'string', enum: ['diajukan', 'approved_bendahara', 'approved_ketua', 'aktif', 'ditolak', 'lunas'], example: 'diajukan'),
        new OA\Property(property: 'cair_oleh_bendahara', type: 'boolean', example: false),
        new OA\Property(property: 'sudah_pakai_privilege_reloan', type: 'boolean', example: false),
        new OA\Property(property: 'sudah_pakai_percepatan', type: 'boolean', example: false),
        new OA\Property(property: 'tanggal_pengajuan', type: 'string', format: 'date-time'),
        new OA\Property(property: 'tanggal_pencairan', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'disetujui_pada', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'versi_syarat', type: 'string', nullable: true),
        new OA\Property(property: 'ip_address_setuju', type: 'string', nullable: true),
        new OA\Property(property: 'user_agent_setuju', type: 'string', nullable: true),
        new OA\Property(property: 'catatan_bendahara', type: 'string', nullable: true),
        new OA\Property(property: 'catatan_ketua', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'anggota', ref: '#/components/schemas/Anggota'),
        new OA\Property(property: 'pengaju', ref: '#/components/schemas/User'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Anggota',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 4),
        new OA\Property(property: 'no_anggota', type: 'string', example: 'ANG-2026-0001'),
        new OA\Property(property: 'no_karyawan', type: 'string', example: 'TOP-100001'),
        new OA\Property(property: 'no_ktp', type: 'string', nullable: true),
        new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
        new OA\Property(property: 'cabang', type: 'string', example: 'Banjarmasin'),
        new OA\Property(property: 'unit_bisnis', type: 'string', example: 'Operasional'),
        new OA\Property(property: 'department', type: 'string', example: 'Operasional'),
        new OA\Property(property: 'divisi', type: 'string', example: 'Lapangan'),
        new OA\Property(property: 'no_hp', type: 'string', nullable: true),
        new OA\Property(property: 'alamat', type: 'string', nullable: true),
        new OA\Property(property: 'jabatan', type: 'string', example: 'staff'),
        new OA\Property(property: 'tanggal_mulai_kerja', type: 'string', format: 'date'),
        new OA\Property(property: 'tanggal_jadi_anggota', type: 'string', format: 'date'),
        new OA\Property(property: 'status', type: 'string', example: 'aktif'),
        new OA\Property(property: 'tanggal_resign', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'alasan_resign', type: 'string', nullable: true),
        new OA\Property(property: 'resigned_by', type: 'integer', nullable: true),
        new OA\Property(property: 'resigned_settlement_json', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
        new OA\Property(property: 'reaktivasi_history_json', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
        new OA\Property(property: 'limit_custom', type: 'string', nullable: true),
        new OA\Property(property: 'limit_custom_keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PengajuanLimit',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'anggota_id', type: 'integer', example: 1),
        new OA\Property(property: 'limit_saat_ini', type: 'string', example: '0.00'),
        new OA\Property(property: 'limit_diminta', type: 'string', example: '10000000.00'),
        new OA\Property(property: 'limit_disetujui_bendahara', type: 'string', nullable: true),
        new OA\Property(property: 'limit_disetujui', type: 'string', nullable: true),
        new OA\Property(property: 'keterangan', type: 'string', example: 'Pengajuan via mobile app'),
        new OA\Property(property: 'status', type: 'string', enum: ['diajukan', 'approved_bendahara', 'disetujui', 'ditolak'], example: 'diajukan'),
        new OA\Property(property: 'catatan_bendahara', type: 'string', nullable: true),
        new OA\Property(property: 'catatan_ketua', type: 'string', nullable: true),
        new OA\Property(property: 'tanggal_pengajuan', type: 'string', format: 'date'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'anggota', ref: '#/components/schemas/Anggota'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PengajuanPercepatan',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'pinjaman_id', type: 'integer', example: 1),
        new OA\Property(property: 'tenor_lama', type: 'integer', example: 12),
        new OA\Property(property: 'tenor_baru', type: 'integer', example: 24),
        new OA\Property(property: 'status', type: 'string', enum: ['diajukan', 'approved_bendahara', 'approved_ketua', 'aktif', 'ditolak'], example: 'diajukan'),
        new OA\Property(property: 'tanggal_pengajuan', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'pinjaman', ref: '#/components/schemas/Pinjaman'),
    ],
    type: 'object'
)]
class Dokumentasi {}
