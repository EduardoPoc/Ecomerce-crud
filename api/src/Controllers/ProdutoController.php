<?php

declare(strict_types=1);

namespace Ecommerce\Api\Controllers;

use Ecommerce\Api\Services\ProdutoService;
use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;

class ProdutoController
{
    private ProdutoService $service;

    public function __construct()
    {
        $this->service = new ProdutoService();
    }

    public function index(Request $request): Response
    {
        return Response::json($this->service->getAll((array) $request->query()));
    }

    public function adminIndex(Request $request): Response
    {
        return Response::json($this->service->getAll((array) $request->query(), true));
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            throw new HttpException(400, 'ID do produto inválido.');
        }

        return Response::json($this->service->getById($id, true));
    }

    public function store(Request $request): Response
    {
        $id = $this->service->create($request->json());

        return Response::created([
            'message' => 'Produto criado com sucesso.',
            'id' => $id
        ]);
    }

    public function update(Request $request): Response
    {
        $this->service->update((int) $request->param('id'), $request->json());

        return Response::json([
            'message' => 'Produto atualizado com sucesso.'
        ]);
    }

    public function destroy(Request $request): Response
    {
        $this->service->delete((int) $request->param('id'));

        return Response::json([
            'message' => 'Produto desativado com sucesso.'
        ]);
    }
}
