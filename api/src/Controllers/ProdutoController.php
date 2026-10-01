<?php

declare(strict_types=1);

namespace Ecommerce\Api\Controllers;

use Ecommerce\Api\Services\ProdutoService;

class ProdutoController
{
    private ProdutoService $service;

    public function __construct()
    {
        $this->service = new ProdutoService();
    }

    public function index(): array
    {
        return $this->service->getAll();
    }

    public function show(int $id): array
    {
        return $this->service->getById($id);
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