<?php

namespace App\Services\SSO;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User as SocialiteUser;

class PerusahaanProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = [];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(config('services.sso.authorize_url'), $state);
    }

    protected function getTokenUrl(): string
    {
        return config('services.sso.token_url');
    }

    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
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
        return (new SocialiteUser())->setRaw($user)->map([
            'id' => $user['sub'] ?? null,
            'nickname' => $user['nickname'] ?? null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
        ]);
    }
}