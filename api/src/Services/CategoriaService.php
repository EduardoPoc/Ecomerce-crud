<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Repositories\CategoriaRepository;

final class CategoriaService
{
    private CategoriaRepository $repository;

    public function __construct(?CategoriaRepository $repository = null)
    {
        $this->repository = $repository ?? new CategoriaRepository();
    }

    public function listar(): array
    {
        return $this->repository->findAll();
    }

    public function buscar(int $id): array
    {
        if ($id <= 0) {
            throw new HttpException(400, 'ID da categoria inválido.');
        }

        $categoria = $this->repository->findById($id);
        if ($categoria === null) {
            throw new HttpException(404, 'Categoria não encontrada.');
        }

        return $categoria;
    }
}