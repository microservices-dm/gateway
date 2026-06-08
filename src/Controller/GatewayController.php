<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ProxyServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GatewayController extends AbstractController
{
    public function __construct(
        private readonly ProxyServiceInterface $proxyService,
        private readonly string $userServiceUrl,
        private readonly string $productServiceUrl,
        private readonly string $orderServiceUrl,
    ) {}

    #[Route('/api/v1/users/{path}', requirements: ['path' => '.*'], methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function userService(Request $request, string $path = ''): Response
    {
        return $this->proxyService->forward($request, $this->userServiceUrl, '/api/v1/users/' . $path);
    }

    #[Route('/api/v1/products/{path}', requirements: ['path' => '.*'], methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function productService(Request $request, string $path = ''): Response
    {
        return $this->proxyService->forward($request, $this->productServiceUrl, '/api/v1/products/' . $path);
    }

    #[Route('/api/v1/orders/{path}', requirements: ['path' => '.*'], methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function orderService(Request $request, string $path = ''): Response
    {
        return $this->proxyService->forward($request, $this->orderServiceUrl, '/api/v1/orders/' . $path);
    }

    #[Route('/api/v1/auth/{path}', requirements: ['path' => '.*'], methods: ['POST'])]
    public function authService(Request $request, string $path = ''): Response
    {
        return $this->proxyService->forward($request, $this->userServiceUrl, '/api/v1/auth/' . $path, false);
    }
}
