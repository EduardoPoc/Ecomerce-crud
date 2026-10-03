<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Repositories\ProdutoRepository;
use Ecommerce\Api\Core\HttpException;

class ProdutoService
{
    private ProdutoRepository $repository;

    public function __construct()
    {
        $this->repository = new ProdutoRepository();
    }

    public function getAll(array $filters = [], bool $includeInactive = false): array
    {
        $page = (int) ($filters['pagina'] ?? 1);
        $limit = (int) ($filters['limite'] ?? 12);
        $search = trim((string) ($filters['busca'] ?? ''));
        $categoryId = $filters['categoria_id'] ?? null;
        $categoryId = $categoryId === null || $categoryId === '' ? null : (int) $categoryId;
        $sort = (string) ($filters['ordenar'] ?? ($search !== '' ? 'relevancia' : 'nome'));

        if ($page < 1) throw new HttpException(422, 'Página deve ser maior que zero.');
        if ($limit < 1 || $limit > 100) throw new HttpException(422, 'Limite deve estar entre 1 e 100.');
        if ($categoryId !== null && $categoryId < 1) throw new HttpException(422, 'Categoria inválida.');
        if (mb_strlen($search) > 100) throw new HttpException(422, 'Busca deve ter no máximo 100 caracteres.');
        if (!in_array($sort, ['nome', 'preco_asc', 'preco_desc', 'recentes', 'relevancia'], true)) {
            throw new HttpException(422, 'Ordenação inválida.');
        }

        $result = $this->repository->search($page, $limit, $search ?: null, $categoryId, $sort, $includeInactive);
        return [
            'itens' => $result['itens'],
            'pagina' => $page,
            'limite' => $limit,
            'total' => $result['total'],
            'paginas' => $result['total'] === 0 ? 0 : (int) ceil($result['total'] / $limit),
        ];
    }

    public function getById(int $id, bool $onlyActive = false): array
    {
        if ($id <= 0) {
            throw new HttpException(400, 'ID inválido.');
        }

        $produto = $this->repository->findById($id, $onlyActive);

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
            throw new HttpException(422,
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
            throw new HttpException(400, 'ID inválido.');
        }

        $produto = $this->repository->findById($id);

        if ($produto === null) {
            throw new HttpException(404, 'Produto não encontrado.');
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
            throw new HttpException(422,
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
            throw new HttpException(400, 'ID inválido.');
        }

        $produto = $this->repository->findById($id);

        if ($produto === null) {
            throw new HttpException(404,
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
            throw new HttpException(422,
                'Categoria é obrigatória.'
            );
        }

        if ($nome === '') {
            throw new HttpException(422,
                'Nome é obrigatório.'
            );
        }

        if ($preco === null || !is_numeric($preco)) {
            throw new HttpException(422,
                'Preço deve ser numérico.'
            );
        }

        if ((float) $preco < 0) {
            throw new HttpException(422,
                'Preço não pode ser negativo.'
            );
        }

        if ($estoque === null || !is_numeric($estoque)) {
            throw new HttpException(422,
                'Estoque deve ser numérico.'
            );
        }

        if ((int) $estoque < 0) {
            throw new HttpException(422,
                'Estoque não pode ser negativo.'
            );
        }
    }
}
