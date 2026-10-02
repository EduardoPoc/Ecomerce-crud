<?php

declare(strict_types=1);

namespace Ecommerce\Api\Repositories;

use Ecommerce\Api\Config\Database;
use PDO;

/** Consultas SQL usadas na autenticação (login e validação do usuário do token) na tabela usuario. */
final class AuthRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /** Inclui senha_hash: use SOMENTE para login. Nunca devolva isso na API. */
    public function findByEmailComSenha(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, senha_hash, papel, criado_em, ativo FROM usuario WHERE email = :email'
        );
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    /** Usuário do token (sem senha_hash). Usado para confirmar que ele ainda existe e qual o papel atual. */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, papel, criado_em, ativo FROM usuario WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }
}