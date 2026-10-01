<?php

declare(strict_types=1);

namespace Ecommerce\Api\Controllers;

use Ecommerce\Api\Core\Auth;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Services\UsuarioService;

final class UsuarioController
{
    private UsuarioService $service;

    public function __construct()
    {
        $this->service = new UsuarioService();
    }

    public function index(Request $request): Response
    {
        $pagina = max(1, (int) $request->query('pagina', 1));
        $limite = min(100, max(1, (int) $request->query('limite', 20)));

        return Response::json($this->service->listar($pagina, $limite));
    }

    public function show(Request $request): Response
    {
        return Response::json($this->service->buscar((int) $request->param('id')));
    }

    public function store(Request $request): Response
    {
        return Response::created($this->service->criar($request->json(), permitirPapel: true));
    }

    public function update(Request $request): Response
    {
        return Response::json($this->service->atualizar((int) $request->param('id'), $request->json()));
    }

    public function destroy(Request $request): Response
    {
        $this->service->remover((int) $request->param('id'), (int) Auth::user($request)['id']);

        return Response::noContent();
    }
}