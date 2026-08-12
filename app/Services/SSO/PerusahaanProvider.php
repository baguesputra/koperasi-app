<?php

namespace App\Services\SSO;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User as SocialiteUser;

class PerusahaanProvider extends AbstractProvider implements ProviderInterface
{
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(config('services.sso.authorize_url'), $state);
    }

    protected function getTokenUrl(): string
    {
        return config('services.sso.token_url');
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(config('services.sso.userinfo_url'), [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): SocialiteUser
    {
        // TODO: sesuaikan field ini setelah dapat dokumentasi API dari tim perusahaan
        return (new SocialiteUser())->setRaw($user)->map([
            'id' => $user['id'] ?? null,
            'nickname' => $user['employee_id'] ?? $user['nik'] ?? null, // -> dipetakan ke no_karyawan
            'name' => $user['name'] ?? $user['nama'] ?? null,
            'email' => $user['email'] ?? null,
        ]);
    }
}