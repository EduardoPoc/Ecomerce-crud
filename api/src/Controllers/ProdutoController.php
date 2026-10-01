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
        return Response::json($this->service->getAll());
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) {
            throw new HttpException(400, 'ID do produto inválido.');
        }

        return Response::json($this->service->getById($id));
    }

    public function store(array $data): array
    {
        $id = $this->service->create($data);

        return [
            'message' => 'Produto criado com sucesso.',
            'id' => $id
        ];
    }

    public function update(int $id, array $data): array
    {
        $this->service->update($id, $data);

        return [
            'message' => 'Produto atualizado com sucesso.'
        ];
    }

    public function destroy(int $id): array
    {
        $this->service->delete($id);

        return [
            'message' => 'Produto removido com sucesso.'
        ];
    }
}
