<?php

declare(strict_types=1);

namespace Ecommerce\Api\Repositories;

use Ecommerce\Api\Config\Database;
use PDO;

final class EnderecoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function findAllByUser(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM endereco WHERE usuario_id = :usuario_id AND ativo = 1 ORDER BY padrao DESC, id');
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function findByIdForUser(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM endereco WHERE id = :id AND usuario_id = :usuario_id AND ativo = 1');
        $stmt->execute(['id' => $id, 'usuario_id' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $usuarioId, array $data): int
    {
        if ($data['padrao']) {
            $this->clearDefault($usuarioId);
        }
        $stmt = $this->pdo->prepare('INSERT INTO endereco (usuario_id, destinatario, cep, logradouro, numero, complemento, bairro, cidade, uf, padrao) VALUES (:usuario_id, :destinatario, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf, :padrao)');
        $stmt->execute(['usuario_id' => $usuarioId] + $data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $usuarioId, array $data): void
    {
        if ($data['padrao'] ?? false) {
            $this->clearDefault($usuarioId);
        }
        $sets = implode(', ', array_map(static fn(string $key): string => "{$key} = :{$key}", array_keys($data)));
        $stmt = $this->pdo->prepare("UPDATE endereco SET {$sets} WHERE id = :id AND usuario_id = :usuario_id AND ativo = 1");
        $stmt->execute($data + ['id' => $id, 'usuario_id' => $usuarioId]);
    }

    public function deactivate(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE endereco SET ativo = 0, padrao = 0 WHERE id = :id AND usuario_id = :usuario_id AND ativo = 1');
        $stmt->execute(['id' => $id, 'usuario_id' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    private function clearDefault(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare('UPDATE endereco SET padrao = 0 WHERE usuario_id = :usuario_id AND ativo = 1');
        $stmt->execute(['usuario_id' => $usuarioId]);
    }
}
