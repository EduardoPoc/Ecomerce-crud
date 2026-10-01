<?php

declare(strict_types=1);

namespace Ecommerce\Api\Repositories;

use PDO;
use RuntimeException;

final class PedidoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Ecommerce\Api\Config\Database::connection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM pedidos');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pedidos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO pedidos ($cols) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "$key = :$key, ";
        }
        $set = rtrim($set, ', ');

        $sql = "UPDATE pedidos SET $set WHERE id = :id";
        $data['id'] = $id;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM pedidos WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}