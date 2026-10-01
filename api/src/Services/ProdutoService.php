<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Repositories\ProdutoRepository;
use Ecommerce\Api\Core\HttpException;
use InvalidArgumentException;
use RuntimeException;

class ProdutoService
{
    private ProdutoRepository $repository;

    public function __construct()
    {
        $this->repository = new ProdutoRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getById(int $id): array
    {
        if ($id <= 0) {
            throw new HttpException(400, 'ID inválido.');
        }

        $produto = $this->repository->findById($id);

        if ($produto === null) {
            throw new HttpException(404, 'Produto não encontrado.');
        }

        return $produto;
    }

    public function create(array $data): int
    {
        $categoriaId = (int) ($data['categoria_id'] ?? 0);
        $nome = trim($data['nome'] ?? '');
        $descricao = isset($data['descricao'])
            ? trim($data['descricao'])
            : null;

        $preco = $data['preco'] ?? null;
        $estoque = $data['estoque'] ?? null;
        $imagemUrl = isset($data['imagem_url'])
            ? trim($data['imagem_url'])
            : null;

        $ativo = $data['ativo'] ?? true;

        $this->validate(
            $categoriaId,
            $nome,
            $preco,
            $estoque
        );

        if (!$this->repository->categoryExists($categoriaId)) {
            throw new RuntimeException(
                'Categoria não encontrada.'
            );
        }

        return $this->repository->create(
            $categoriaId,
            $nome,
            $descricao,
            (float) $preco,
            (int) $estoque,
            $imagemUrl,
            (bool) $ativo
        );
    }

    public function update(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID inválido.');
        }

        $produto = $this->repository->findById($id);

        if ($produto === null) {
            throw new RuntimeException('Produto não encontrado.');
        }

        $categoriaId = (int) ($data['categoria_id'] ?? 0);
        $nome = trim($data['nome'] ?? '');
        $descricao = isset($data['descricao'])
            ? trim($data['descricao'])
            : null;

        $preco = $data['preco'] ?? null;
        $estoque = $data['estoque'] ?? null;
        $imagemUrl = isset($data['imagem_url'])
            ? trim($data['imagem_url'])
            : null;

        $ativo = $data['ativo'] ?? true;

        $this->validate(
            $categoriaId,
            $nome,
            $preco,
            $estoque
        );

        if (!$this->repository->categoryExists($categoriaId)) {
            throw new RuntimeException(
                'Categoria não encontrada.'
            );
        }

        $this->repository->update(
            $id,
            $categoriaId,
            $nome,
            $descricao,
            (float) $preco,
            (int) $estoque,
            $imagemUrl,
            (bool) $ativo
        );
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID inválido.');
        }

        $produto = $this->repository->findById($id);

        if ($produto === null) {
            throw new RuntimeException(
                'Produto não encontrado.'
            );
        }

        $this->repository->delete($id);
    }

    
    private function validate(
        int $categoriaId,
        string $nome,
        mixed $preco,
        mixed $estoque
    ): void {
        if ($categoriaId <= 0) {
            throw new InvalidArgumentException(
                'Categoria é obrigatória.'
            );
        }

        if ($nome === '') {
            throw new InvalidArgumentException(
                'Nome é obrigatório.'
            );
        }

        if ($preco === null || !is_numeric($preco)) {
            throw new InvalidArgumentException(
                'Preço deve ser numérico.'
            );
        }

        if ((float) $preco < 0) {
            throw new InvalidArgumentException(
                'Preço não pode ser negativo.'
            );
        }

        if ($estoque === null || !is_numeric($estoque)) {
            throw new InvalidArgumentException(
                'Estoque deve ser numérico.'
            );
        }

        if ((int) $estoque < 0) {
            throw new InvalidArgumentException(
                'Estoque não pode ser negativo.'
            );
        }
    }
}
