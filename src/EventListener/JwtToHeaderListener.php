<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class JwtToHeaderListener
{
    public function __construct(
        private JWTEncoderInterface $jwtEncoder
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $token = $this->extractToken($request);

        if (!$token) {
            return;
        }

        try {
            $payload = $this->jwtEncoder->decode($token);

            // Добавляем заголовки для проксирования в микросервисы
            $request->headers->set('X-User-Id', (string) $payload['user_id']);
            $request->headers->set('X-User-Email', $payload['email']);
            $request->headers->set('X-User-Role', $payload['roles']);

            // Сохраняем в атрибутах для использования в контроллерах
            $request->attributes->set('user_id', $payload['user_id']);
            $request->attributes->set('user_roles', $payload['roles']);

        } catch (\Exception $e) {
            // Токен невалидный - пусть микросервис обработает
        }
    }

    private function extractToken($request): ?string
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }
}
