<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\JwtValidationServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class JwtToHeaderListener
{
    public function __construct(
        private readonly JwtValidationServiceInterface $jwtValidator,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Identity-заголовки имеет право выставлять только сам gateway по валидному JWT.
        // Снимаем всё, что клиент мог прислать сам, чтобы их нельзя было подделать.
        foreach (['X-User-Id', 'X-User-Email', 'X-User-Role', 'X-Internal-Key'] as $protectedHeader) {
            $request->headers->remove($protectedHeader);
        }

        $token = $this->extractToken($request);

        if (!$token || !$this->jwtValidator->validate($token)) {
            return;
        }

        $user = $this->jwtValidator->getUserFromToken($token);
        if (!$user) {
            return;
        }

        $request->headers->set('X-User-Id', (string) $user['id']);
        $request->headers->set('X-User-Email', $user['email']);
        $request->headers->set('X-User-Role', implode(',', (array) $user['roles']));

        $request->attributes->set('user_id', $user['id']);
        $request->attributes->set('user_email', $user['email']);
        $request->attributes->set('user_roles', $user['roles']);
    }

    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }
}
