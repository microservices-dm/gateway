<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProxyService implements ProxyServiceInterface
{
    private const array FORWARDED_HEADERS = ['X-User-Id', 'X-User-Email', 'X-User-Role'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $internalApiKey,
    ) {}

    public function forward(
        Request $request,
        string $serviceUrl,
        string $path,
        bool $requireAuth = true,
    ): Response {
        if ($requireAuth && !$request->attributes->has('user_id')) {
            return new JsonResponse(
                ['error' => 'Missing or invalid authorization token'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $url = rtrim($serviceUrl, '/') . '/' . ltrim($path, '/');
        $queryString = $request->getQueryString();
        if ($queryString) {
            $url .= '?' . $queryString;
        }

        try {
            $response = $this->httpClient->request($request->getMethod(), $url, [
                'headers' => $this->filterProxyHeaders($request),
                'body' => $request->getContent(),
            ]);

            return new Response(
                $response->getContent(false),
                $response->getStatusCode(),
                $response->getHeaders(false),
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Service unavailable', 'message' => $e->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }
    }

    private function filterProxyHeaders(Request $request): array
    {
        // Общий секрет gateway↔сервисы: подтверждает, что запрос пришёл через gateway,
        // а не напрямую в сервис в обход аутентификации.
        $headers = [
            'Content-Type' => 'application/json',
            'X-Internal-Key' => $this->internalApiKey,
        ];

        foreach (self::FORWARDED_HEADERS as $header) {
            $value = $request->headers->get($header);
            if ($value !== null) {
                $headers[$header] = $value;
            }
        }

        return $headers;
    }
}
