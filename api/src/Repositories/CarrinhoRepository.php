<?php

declare(strict_types=1);

namespace Ecommerce\Api\Repositories;

use Ecommerce\Api\Config\Database;
use PDO;

final class CarrinhoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function getPdo(): PDO { return $this->pdo; }

    public function findOrCreate(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM carrinho WHERE usuario_id = :usuario_id ORDER BY id LIMIT 1');
        $stmt->execute(['usuario_id' => $usuarioId]);
        $id = $stmt->fetchColumn();
        if ($id !== false) return (int) $id;
        $stmt = $this->pdo->prepare('INSERT INTO carrinho (usuario_id, token) VALUES (:usuario_id, UUID())');
        $stmt->execute(['usuario_id' => $usuarioId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function items(int $cartId): array
    {
        $stmt = $this->pdo->prepare('SELECT i.id, i.produto_id, i.quantidade, p.nome, p.descricao, p.preco, p.estoque, p.imagem_url, p.ativo, c.nome AS categoria_nome, (i.quantidade * p.preco) AS subtotal FROM item_carrinho i JOIN produto p ON p.id = i.produto_id JOIN categoria c ON c.id = p.categoria_id WHERE i.carrinho_id = :carrinho_id ORDER BY i.id');
        $stmt->execute(['carrinho_id' => $cartId]);
        return $stmt->fetchAll();
    }

    public function findItem(int $cartId, int $productId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM item_carrinho WHERE carrinho_id = :carrinho_id AND produto_id = :produto_id');
        $stmt->execute(['carrinho_id' => $cartId, 'produto_id' => $productId]);
        return $stmt->fetch() ?: null;
    }

    public function add(int $cartId, int $productId, int $quantity): void
    {
        $item = $this->findItem($cartId, $productId);
        if ($item) {
            $stmt = $this->pdo->prepare('UPDATE item_carrinho SET quantidade = quantidade + :quantidade WHERE id = :id');
            $stmt->execute(['quantidade' => $quantity, 'id' => $item['id']]);
            return;
        }
        $stmt = $this->pdo->prepare('INSERT INTO item_carrinho (carrinho_id, produto_id, quantidade) VALUES (:carrinho_id, :produto_id, :quantidade)');
        $stmt->execute(['carrinho_id' => $cartId, 'produto_id' => $productId, 'quantidade' => $quantity]);
    }

    public function setQuantity(int $cartId, int $productId, int $quantity): void
    {
        $stmt = $this->pdo->prepare('UPDATE item_carrinho SET quantidade = :quantidade WHERE carrinho_id = :carrinho_id AND produto_id = :produto_id');
        $stmt->execute(['quantidade' => $quantity, 'carrinho_id' => $cartId, 'produto_id' => $productId]);
    }

    public function remove(int $cartId, int $productId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM item_carrinho WHERE carrinho_id = :carrinho_id AND produto_id = :produto_id');
        $stmt->execute(['carrinho_id' => $cartId, 'produto_id' => $productId]);
        return $stmt->rowCount() > 0;
    }

    public function clear(int $cartId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM item_carrinho WHERE carrinho_id = :carrinho_id');
        $stmt->execute(['carrinho_id' => $cartId]);
    }
}
