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

    public function search(
        int $page,
        int $limit,
        ?string $search = null,
        ?int $categoryId = null,
        string $sort = 'nome',
        bool $includeInactive = false
    ): array
    {
        $where = $includeInactive ? [] : ['p.ativo = 1'];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = '(p.nome LIKE :search_title OR p.descricao LIKE :search_description)';
            $params['search_title'] = "%{$search}%";
            $params['search_description'] = "%{$search}%";
        }
        if ($categoryId !== null) {
            $where[] = 'p.categoria_id = :categoria_id';
            $params['categoria_id'] = $categoryId;
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $orderSql = match ($sort) {
            'preco_asc' => 'p.preco ASC, p.id ASC',
            'preco_desc' => 'p.preco DESC, p.id ASC',
            'recentes' => 'p.criado_em DESC, p.id DESC',
            'relevancia' => $search !== null && $search !== ''
                ? 'CASE WHEN p.nome LIKE :exact_title THEN 0 WHEN p.nome LIKE :contains_title THEN 1 ELSE 2 END, CASE WHEN p.nome LIKE :prefix_title THEN 0 ELSE 1 END, p.nome ASC, p.id ASC'
                : 'p.nome ASC, p.id ASC',
            default => 'p.nome ASC, p.id ASC',
        };

        $countParams = $params;
        if ($sort === 'relevancia' && $search !== null && $search !== '') {
            $params['exact_title'] = $search;
            $params['contains_title'] = "%{$search}%";
            $params['prefix_title'] = "{$search}%";
        }

        $count = $this->db->prepare("SELECT COUNT(*) FROM produto p {$whereSql}");
        $count->execute($countParams);
        $total = (int) $count->fetchColumn();

        $offset = ($page - 1) * $limit;
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
            {$whereSql}
            ORDER BY {$orderSql}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['itens' => $stmt->fetchAll(), 'total' => $total];
    }

    public function findById(int $id, bool $onlyActive = false): ?array
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
            " . ($onlyActive ? 'AND p.ativo = 1' : '') . "
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

    public function updateImage(int $id, string $imageUrl): bool
    {
        $stmt = $this->db->prepare('UPDATE produto SET imagem_url = :imagem_url WHERE id = :id');
        return $stmt->execute(['id' => $id, 'imagem_url' => $imageUrl]);
    }
}
