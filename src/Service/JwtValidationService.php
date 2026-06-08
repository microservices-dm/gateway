<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Contracts\Cache\CacheInterface;

class JwtValidationService implements JwtValidationServiceInterface
{
    private readonly string $publicKey;

    public function __construct(
        private readonly CacheInterface $cache,
        string $jwtPublicKey,
    ) {
        $this->publicKey = file_get_contents($jwtPublicKey);
    }

    public function validate(string $token): bool
    {
        try {
            $isBlacklisted = $this->cache->get(
                'jwt_blacklist_' . md5($token),
                fn() => null,
            );

            if ($isBlacklisted) {
                return false;
            }

            JWT::decode($token, new Key($this->publicKey, 'RS256'));

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function getUserFromToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->publicKey, 'RS256'));

            return [
                // uid (UUID) — единый идентификатор пользователя для межсервисных ссылок
                'id' => $decoded->user_uid ?? null,
                'email' => $decoded->email ?? null,
                'roles' => $decoded->roles ?? [],
            ];
        } catch (\Exception) {
            return null;
        }
    }
}
