<?php

declare(strict_types=1);

namespace Ecommerce\Api\Repositories;

use Ecommerce\Api\Config\Database;
use PDO;

/** Todas as consultas SQL da tabela usuario. Só aqui existe SQL de usuários. */
final class UsuarioRepository
{
    private const CAMPOS_PUBLICOS = 'id, nome, email, papel, criado_em';
    private const CAMPOS_EDITAVEIS = ['nome', 'email', 'senha_hash', 'papel'];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /** @return array<int, array> */
    public function findAll(int $limite, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::CAMPOS_PUBLICOS . ' FROM usuario ORDER BY id LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::CAMPOS_PUBLICOS . ' FROM usuario WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** Inclui senha_hash: use SOMENTE para login. Nunca devolva isso na API. */
    public function findByEmailComSenha(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, email, senha_hash, papel, criado_em FROM usuario WHERE email = :email');
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    public function emailExiste(string $email, ?int $ignorarId = null): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuario WHERE email = :email AND id <> :id LIMIT 1');
        $stmt->execute(['email' => $email, 'id' => $ignorarId ?? 0]);

        return (bool) $stmt->fetchColumn();
    }

    /** Retorna o id criado. */
    public function create(string $nome, string $email, string $senhaHash, string $papel): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuario (nome, email, senha_hash, papel) VALUES (:nome, :email, :senha_hash, :papel)'
        );
        $stmt->execute(['nome' => $nome, 'email' => $email, 'senha_hash' => $senhaHash, 'papel' => $papel]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Atualiza apenas os campos informados (lista branca evita SQL injection nos nomes de coluna). */
    public function update(int $id, array $campos): void
    {
        $campos = array_intersect_key($campos, array_flip(self::CAMPOS_EDITAVEIS));
        if ($campos === []) {
            return;
        }

        $sets = implode(', ', array_map(static fn(string $c): string => "{$c} = :{$c}", array_keys($campos)));
        $stmt = $this->pdo->prepare("UPDATE usuario SET {$sets} WHERE id = :id");
        $stmt->execute($campos + ['id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuario WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}