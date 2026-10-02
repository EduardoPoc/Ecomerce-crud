<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Repositories\CarrinhoRepository;

final class CarrinhoService
{
    private CarrinhoRepository $repository;

    public function __construct(?CarrinhoRepository $repository = null)
    {
        $this->repository = $repository ?? new CarrinhoRepository();
    }

    public function buscar(int $usuarioId): array
    {
        $cartId = $this->repository->findOrCreate($usuarioId);
        $items = $this->repository->items($cartId);
        return ['id' => $cartId, 'itens' => $items, 'quantidade' => array_sum(array_column($items, 'quantidade')), 'subtotal' => array_sum(array_map(static fn(array $item): float => (float) $item['subtotal'], $items))];
    }

    public function adicionar(int $usuarioId, array $data): array
    {
        $productId = (int) ($data['produto_id'] ?? 0);
        $quantity = (int) ($data['quantidade'] ?? 1);
        if ($productId <= 0 || $quantity <= 0) throw new HttpException(422, 'produto_id e quantidade devem ser positivos.');
        $cartId = $this->repository->findOrCreate($usuarioId);
        $current = $this->repository->findItem($cartId, $productId);
        $product = $this->product($productId);
        $next = (int) ($current['quantidade'] ?? 0) + $quantity;
        if (!$product['ativo'] || $next > (int) $product['estoque']) throw new HttpException(422, 'Quantidade indisponível em estoque.');
        $this->repository->add($cartId, $productId, $quantity);
        return $this->buscar($usuarioId);
    }

    public function atualizar(int $usuarioId, array $data): array
    {
        $productId = (int) ($data['produto_id'] ?? 0);
        $quantity = (int) ($data['quantidade'] ?? 0);
        if ($productId <= 0 || $quantity <= 0) throw new HttpException(422, 'produto_id e quantidade devem ser positivos.');
        $product = $this->product($productId);
        if (!$product['ativo'] || $quantity > (int) $product['estoque']) throw new HttpException(422, 'Quantidade indisponível em estoque.');
        $cartId = $this->repository->findOrCreate($usuarioId);
        if ($this->repository->findItem($cartId, $productId) === null) throw new HttpException(404, 'Item não encontrado no carrinho.');
        $this->repository->setQuantity($cartId, $productId, $quantity);
        return $this->buscar($usuarioId);
    }

    public function remover(int $usuarioId, int $productId): array
    {
        $cartId = $this->repository->findOrCreate($usuarioId);
        if (!$this->repository->remove($cartId, $productId)) throw new HttpException(404, 'Item não encontrado no carrinho.');
        return $this->buscar($usuarioId);
    }

    private function product(int $id): array
    {
        $stmt = $this->repository->getPdo()->prepare('SELECT id, ativo, estoque FROM produto WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
        if (!$product) throw new HttpException(404, 'Produto não encontrado.');
        return $product;
    }
}
