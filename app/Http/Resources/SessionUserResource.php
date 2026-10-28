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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'no_karyawan' => $this->no_karyawan,
            'email' => $this->email,
            'foto_url' => $this->anggota?->foto_url,
            'roles' => $this->getRoleNames()->values()->all(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
            'is_anggota' => $this->hasRole('anggota'),
        ];
    }
}
