<?php
declare(strict_types=1);
namespace Ecommerce\Api\Repositories;
use Ecommerce\Api\Config\Database;
use PDO;

final class CategoriaRepository
{
    private PDO $pdo;
    public function __construct(?PDO $pdo = null) { $this->pdo = $pdo ?? Database::connection(); }
    public function findAll(): array { return $this->pdo->query('SELECT id, nome, slug FROM categoria ORDER BY nome')->fetchAll(); }
    public function findById(int $id): ?array { $stmt = $this->pdo->prepare('SELECT id, nome, slug FROM categoria WHERE id = :id'); $stmt->execute(['id' => $id]); return $stmt->fetch() ?: null; }
}
