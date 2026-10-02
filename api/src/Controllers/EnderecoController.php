<?php

declare(strict_types=1);

namespace Ecommerce\Api\Controllers;

use Ecommerce\Api\Core\Auth;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Services\EnderecoService;

final class EnderecoController
{
    private EnderecoService $service;

    public function __construct()
    {
        $this->service = new EnderecoService();
    }

    public function index(Request $request): Response
    {
        return Response::json($this->service->listar((int) Auth::user($request)['id']));
    }

    public function store(Request $request): Response
    {
        return Response::created($this->service->criar((int) Auth::user($request)['id'], $request->json()));
    }

    public function update(Request $request): Response
    {
        return Response::json($this->service->atualizar((int) $request->param('id'), (int) Auth::user($request)['id'], $request->json()));
    }

    public function destroy(Request $request): Response
    {
        $this->service->excluir((int) $request->param('id'), (int) Auth::user($request)['id']);
        return Response::noContent();
    }
}
