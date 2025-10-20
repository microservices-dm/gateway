<?php

namespace App\Controller;

use App\Service\ProxyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GatewayController extends AbstractController
{
    public function __construct(
        private readonly ProxyService $proxyService
    ) {}

    #[Route('/api/users/{path}', requirements: ['path' => '.*'], methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function userService(Request $request, string $path = ''): Response
    {
        return $this->proxyService->proxy(
            $request,
            'http://user_service_nginx',
            '/api/users/' . $path
        );
    }

    #[Route('/api/products/{path}', requirements: ['path' => '.*'], methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function productService(Request $request, string $path = ''): Response
    {
        return $this->proxyService->proxy(
            $request,
            'http://product_service_nginx',
            '/api/products/' . $path
        );
    }

    #[Route('/api/orders/{path}', requirements: ['path' => '.*'], methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function orderService(Request $request, string $path = ''): Response
    {
        return $this->proxyService->proxy(
            $request,
            'http://order_service_nginx',
            '/api/orders/' . $path
        );
    }

    #[Route('/api/auth/{path}', requirements: ['path' => '.*'], methods: ['POST'])]
    public function authService(Request $request, string $path = ''): Response
    {
        //echo $path; die;
        // Маршруты авторизации проксируем без проверки токена
        return $this->proxyService->proxy(
            $request,
            'http://user_service_nginx',
            '/api/auth/' . $path,
            false // не проверять JWT
        );
    }
}
