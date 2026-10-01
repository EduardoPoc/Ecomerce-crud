<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Repositories\PedidoRepository;

final class PedidoService
{
    private PedidoRepository $repository;

    public function __construct()
    {
        $this->repository = new PedidoRepository();
    }

    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    public function getById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): int
    {
        // You can add validation here if needed
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}