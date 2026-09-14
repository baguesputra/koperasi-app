<?php

namespace App\Services\Gate;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GateClient
{
    public function ambilKaryawan(array $filter = []): array
    {
        return $this->ambil('/api/users', $filter, 'karyawan');
    }

    public function ambilPerusahaan(): array
    {
        return $this->ambil('/api/companies', [], 'perusahaan');
    }

    public function ambilPerusahaanSatu(int|string $companyId): ?array
    {
        try {
            $respon = $this->get(
                rtrim(config('services.gate.base_url'), '/'),
                config('services.gate.token'),
                "/api/companies/{$companyId}",
                []
            )->throw()->json();
        } catch (ConnectionException|RequestException $e) {
            Log::warning('GATE gagal', ['jenis' => 'perusahaan', 'path' => "/api/companies/{$companyId}", 'error' => $e->getMessage()]);

            return null;
        }

        $data = $respon['data'] ?? null;

        return is_array($data) && isset($data['id']) ? $data : null;
    }

    public function ambilDepartemen(int|string|null $companyId = null): array
    {
        $query = $companyId ? ['company_id' => $companyId] : [];

        return $this->ambil('/api/departments', $query, 'departemen');
    }

    public function ambilDivisi(int|string|null $companyId = null): array
    {
        $query = $companyId ? ['company_id' => $companyId] : [];

        return $this->ambil('/api/divisions', $query, 'divisi');
    }

    public function ambilJabatan(int|string|null $companyId = null, array $filter = []): array
    {
        $query = array_merge($filter, $companyId ? ['company_id' => $companyId] : []);

        return $this->ambil('/api/positions', $query, 'jabatan');
    }

    private function ambil(string $path, array $query, string $jenis): array
    {
        $token = config('services.gate.token');
        $baseUrl = rtrim(config('services.gate.base_url'), '/');

        if (! $token) {
            throw new RuntimeException('Token GATE belum dikonfigurasi (GATE_TOKEN).');
        }

        $query = array_filter($query);

        try {
            $respon = $this->get($baseUrl, $token, $path, $query)->throw()->json();
        } catch (ConnectionException|RequestException $e) {
            $status = $e instanceof RequestException ? $e->response?->status() : null;
            Log::warning('GATE gagal', ['jenis' => $jenis, 'path' => $path, 'status' => $status, 'error' => $e->getMessage()]);

            if ($status !== null && $status >= 500) {
                if ($query) {
                    try {
                        $respon = $this->get($baseUrl, $token, $path, [])->throw()->json();
                    } catch (ConnectionException|RequestException $eUlang) {
                        Log::warning('GATE gagal (tanpa filter)', ['jenis' => $jenis, 'path' => $path, 'error' => $eUlang->getMessage()]);
                    }
                }
                if (! isset($respon)) {
                    throw new RuntimeException("Server GATE bermasalah saat mengambil data {$jenis}. Coba lagi nanti atau hubungi admin GATE.", 0, $e);
                }
            } else {
                throw new RuntimeException("Gagal mengambil data {$jenis} dari GATE: ".$e->getMessage(), 0, $e);
            }
        }

        $data = $respon['data'] ?? [];

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }

    private function get(string $baseUrl, string $token, string $path, array $query)
    {
        return Http::baseUrl($baseUrl)
            ->withToken($token)
            ->timeout(config('services.gate.timeout'))
            ->acceptJson()
            ->get($path, $query);
    }
}
