<?php

declare(strict_types=1);

namespace Ecommerce\Api\Controllers;

use Ecommerce\Api\Core\Auth;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Services\AuthService;

final class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    public function login(Request $request): Response
    {
        return Response::json($this->service->login($request->json()));
    }

    public function cadastro(Request $request): Response
    {
        return Response::created($this->service->cadastro($request->json()));
    }

    /** Dados do usuário logado (o AuthMiddleware já validou o token). */
    public function me(Request $request): Response
    {
        return Response::json(Auth::user($request));
    }
}