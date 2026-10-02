<?php

declare(strict_types=1);

namespace Ecommerce\Api\Repositories;

use Ecommerce\Api\Config\Database;
use PDO;

/** Todas as consultas SQL da tabela produto. */
class ProdutoRepository
{
    private const SELECT = '
        SELECT p.id, p.categoria_id, p.nome, p.descricao, p.preco, p.estoque,
               p.imagem_url, p.ativo, p.criado_em, c.nome AS categoria_nome
        FROM produto p
        INNER JOIN categoria c ON c.id = p.categoria_id
    ';

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /** @return array<int, array> */
    public function findAll(bool $apenasAtivos = true): array
    {
        $sql = self::SELECT . ($apenasAtivos ? ' WHERE p.ativo = 1' : '') . ' ORDER BY p.id';

        return array_map([$this, 'normalizar'], $this->db->query($sql)->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        $produto = $stmt->fetch();

        return $produto ? $this->normalizar($produto) : null;
    }

    public function categoryExists(int $categoriaId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM categoria WHERE id = :id');
        $stmt->execute(['id' => $categoriaId]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array{categoria_id:int, nome:string, descricao:?string, preco:float,
     *              estoque:int, imagem_url:?string, ativo:bool} $c
     * @return int id criado
     */
    public function create(array $c): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO produto (categoria_id, nome, descricao, preco, estoque, imagem_url, ativo)
             VALUES (:categoria_id, :nome, :descricao, :preco, :estoque, :imagem_url, :ativo)'
        );
        $stmt->execute($this->parametros($c));

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $c): void
    {
        $stmt = $this->db->prepare(
            'UPDATE produto
             SET categoria_id = :categoria_id, nome = :nome, descricao = :descricao, preco = :preco,
                 estoque = :estoque, imagem_url = :imagem_url, ativo = :ativo
             WHERE id = :id'
        );
        $stmt->execute($this->parametros($c) + ['id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM produto WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Com execute([...]) tudo vai como string: um bool false viraria '' e o MySQL
     * recusaria na coluna BOOLEAN. Por isso ativo vai como 0/1 e preco com 2 casas.
     */
    private function parametros(array $c): array
    {
        return [
            'categoria_id' => $c['categoria_id'],
            'nome' => $c['nome'],
            'descricao' => $c['descricao'],
            'preco' => number_format((float) $c['preco'], 2, '.', ''),
            'estoque' => $c['estoque'],
            'imagem_url' => $c['imagem_url'],
            'ativo' => $c['ativo'] ? 1 : 0,
        ];
    }

    /** Devolve tipos corretos no JSON: ativo true/false e preco numérico. */
    private function normalizar(array $produto): array
    {
        $produto['ativo'] = (bool) $produto['ativo'];
        $produto['preco'] = (float) $produto['preco'];

        return $produto;
    }
}
