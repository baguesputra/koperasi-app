<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk user sesi untuk aplikasi mobile.
 *
 * Mirror: koperasi-mobile src/api/types.ts (SessionUser).
 */
class SessionUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['anggota', 'roles', 'permissions']);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'no_karyawan' => $this->no_karyawan,
            'email' => $this->email,
            'foto_url' => $this->anggota?->foto_url,
            'has_pin' => (bool) $this->hasPin(),
            'anggota_status' => $this->anggota?->status,
            'roles' => $this->getRoleNames()->values()->all(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
            'is_anggota' => $this->hasRole('anggota'),
            'jabatan_pengawas' => $this->levelPengawas(),
            'label_jabatan_pengawas' => $this->labelJabatanPengawas(),
        ];
    }
}
