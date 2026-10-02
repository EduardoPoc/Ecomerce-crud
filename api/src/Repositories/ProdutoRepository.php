<?php

declare(strict_types=1);

namespace Ecommerce\Api\Repositories;

use Ecommerce\Api\Config\Database;
use PDO;

class ProdutoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                p.id,
                p.categoria_id,
                p.nome,
                p.descricao,
                p.preco,
                p.estoque,
                p.imagem_url,
                p.ativo,
                p.criado_em,
                c.nome AS categoria_nome
            FROM produto p
            INNER JOIN categoria c
                ON c.id = p.categoria_id
            ORDER BY p.id
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                p.id,
                p.categoria_id,
                p.nome,
                p.descricao,
                p.preco,
                p.estoque,
                p.imagem_url,
                p.ativo,
                p.criado_em,
                c.nome AS categoria_nome
            FROM produto p
            INNER JOIN categoria c
                ON c.id = p.categoria_id
            WHERE p.id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $produto = $stmt->fetch();

        return $produto ?: null;
    }

    public function categoryExists(int $categoriaId): bool
    {
        $sql = "
            SELECT id
            FROM categoria
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $categoriaId
        ]);

        return $stmt->fetch() !== false;
    }

    public function create(
        int $categoriaId,
        string $nome,
        ?string $descricao,
        float $preco,
        int $estoque,
        ?string $imagemUrl,
        bool $ativo = true
    ): int {
        $sql = "
            INSERT INTO produto
                (
                    categoria_id,
                    nome,
                    descricao,
                    preco,
                    estoque,
                    imagem_url,
                    ativo
                )
            VALUES
                (
                    :categoria_id,
                    :nome,
                    :descricao,
                    :preco,
                    :estoque,
                    :imagem_url,
                    :ativo
                )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'categoria_id' => $categoriaId,
            'nome' => $nome,
            'descricao' => $descricao,
            'preco' => $preco,
            'estoque' => $estoque,
            'imagem_url' => $imagemUrl,
            'ativo' => $ativo
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(
        int $id,
        int $categoriaId,
        string $nome,
        ?string $descricao,
        float $preco,
        int $estoque,
        ?string $imagemUrl,
        bool $ativo
    ): bool {
        $sql = "
            UPDATE produto
            SET
                categoria_id = :categoria_id,
                nome = :nome,
                descricao = :descricao,
                preco = :preco,
                estoque = :estoque,
                imagem_url = :imagem_url,
                ativo = :ativo
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'categoria_id' => $categoriaId,
            'nome' => $nome,
            'descricao' => $descricao,
            'preco' => $preco,
            'estoque' => $estoque,
            'imagem_url' => $imagemUrl,
            'ativo' => $ativo
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "
            UPDATE produto
            SET ativo = 0
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id
        ]);
    }
}
