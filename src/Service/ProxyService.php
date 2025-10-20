<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;

class ProxyService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly JwtValidationService $jwtValidator,
        private readonly CacheInterface $cache
    ) {}

    public function proxy(
        Request $request,
        string $serviceUrl,
        string $path,
        bool $requireAuth = true
    ): Response {
        // Валидация JWT если требуется
        if ($requireAuth) {
            $token = $this->extractToken($request);

            if (!$token) {
                return new Response(
                    json_encode(['error' => 'Missing authorization token']),
                    401,
                    ['Content-Type' => 'application/json']
                );
            }

            if (!$this->jwtValidator->validate($token)) {
                return new Response(
                    json_encode(['error' => 'Invalid or expired token']),
                    401,
                    ['Content-Type' => 'application/json']
                );
            }
        }

        // Подготовка запроса к микросервису
        $url = rtrim($serviceUrl, '/') . '/' . ltrim($path, '/');

        $options = [
            'headers' => $this->prepareHeaders($request),
            'body' => $request->getContent()
        ];

        try {
            $response = $this->httpClient->request(
                $request->getMethod(),
                $url,
                $options
            );

            return new Response(
                $response->getContent(false),
                $response->getStatusCode(),
                $response->getHeaders(false)
            );
        } catch (\Exception $e) {
            return new Response(
                json_encode(['error' => 'Service unavailable', 'message' => $e->getMessage()]),
                503,
                ['Content-Type' => 'application/json']
            );
        }
    }

    private function extractToken(Request $request): ?string
    {
        $authorization = $request->headers->get('Authorization');

        if (!$authorization || !str_starts_with($authorization, 'Bearer ')) {
            return null;
        }

        return substr($authorization, 7);
    }

    private function prepareHeaders(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $key => $value) {
            if (!in_array(strtolower($key), ['host', 'connection'])) {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}
