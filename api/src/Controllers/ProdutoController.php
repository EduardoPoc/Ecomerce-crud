<?php

declare(strict_types=1);

namespace Ecommerce\Api\Controllers;

use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Services\ProdutoService;

class ProdutoController
{
    private ProdutoService $service;

    public function __construct()
    {
        $this->service = new ProdutoService();
    }

    /** GET /produtos (público): só ativos. */
    public function index(Request $request): Response
    {
        return Response::json($this->service->listarAtivos());
    }

    /** GET /admin/produtos (admin): todos, inclusive inativos. */
    public function indexAdmin(Request $request): Response
    {
        return Response::json($this->service->listarTodos());
    }

    /** GET /produtos/{id} (público). */
    public function show(Request $request): Response
    {
        return Response::json($this->service->buscarAtivo((int) $request->param('id')));
    }

    /** POST /produtos (admin). */
    public function store(Request $request): Response
    {
        return Response::created($this->service->criar($request->json()));
    }

    /** PUT/PATCH /produtos/{id} (admin). */
    public function update(Request $request): Response
    {
        return Response::json($this->service->atualizar((int) $request->param('id'), $request->json()));
    }

    /** DELETE /produtos/{id} (admin). */
    public function destroy(Request $request): Response
    {
        $this->service->remover((int) $request->param('id'));

        return Response::noContent();
    }
}