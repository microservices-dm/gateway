<?php

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Contracts\Cache\CacheInterface;

class JwtValidationService
{
    private string $publicKey;

    public function __construct(
        private readonly CacheInterface $cache,
        string $jwtPublicKey
    ) {
        $this->publicKey = file_get_contents($jwtPublicKey);
    }

    public function validate(string $token): bool
    {
        try {
            // Проверка blacklist
            $isBlacklisted = $this->cache->get(
                'jwt_blacklist_' . md5($token),
                fn() => null
            );

            if ($isBlacklisted) {
                return false;
            }

            // Валидация JWT
            $decoded = JWT::decode($token, new Key($this->publicKey, 'RS256'));

            // Проверка expiration
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserFromToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->publicKey, 'RS256'));

            return [
                'id' => $decoded->sub ?? null,
                'email' => $decoded->email ?? null,
                'roles' => $decoded->roles ?? []
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
