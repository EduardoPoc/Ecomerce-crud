<?php

declare(strict_types=1);

namespace Ecommerce\Api\Repositories;

use Ecommerce\Api\Config\Database;
use PDO;
use Throwable;

/** Todas as consultas SQL de pedido e item_pedido (e as leituras de endereço/produto que o checkout precisa). */
final class PedidoRepository
{
    private const COLUNAS = 'p.id, p.usuario_id, u.nome AS usuario_nome, p.endereco_id, p.status, p.total, p.criado_em, p.pago_em';
    private const ORIGEM = ' FROM pedido p INNER JOIN usuario u ON u.id = p.usuario_id';

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * Executa $acao dentro de uma transação: se qualquer coisa lançar exceção, nada é gravado.
     * Usado no checkout para que pedido, itens e baixa de estoque aconteçam juntos ou não aconteçam.
     */
    public function transacao(callable $acao): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $resultado = $acao();
            $this->pdo->commit();

            return $resultado;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // ---------- Leituras ----------

    public function enderecoPertenceAoUsuario(int $enderecoId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM endereco WHERE id = :id AND usuario_id = :usuario_id AND ativo = 1');
        $stmt->execute(['id' => $enderecoId, 'usuario_id' => $usuarioId]);

        return $stmt->fetchColumn() !== false;
    }

    public function endereco(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT destinatario, cep, logradouro, numero, complemento, bairro, cidade, uf FROM endereco WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** Trava a linha do produto até o fim da transação: dois checkouts simultâneos não vendem o mesmo estoque. */
    public function bloquearProduto(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, preco, estoque, ativo FROM produto WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUNAS . self::ORIGEM . ' WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        $pedido = $stmt->fetch();

        return $pedido ? $this->normalizar($pedido) : null;
    }

    /** Igual ao findById, mas trava o pedido: usado ao mudar o status. */
    public function bloquearPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, status FROM pedido WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** @return array<int, array> */
    public function itens(int $pedidoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.produto_id, pr.nome AS produto_nome, pr.imagem_url, i.quantidade, i.preco_unitario
             FROM item_pedido i
             INNER JOIN produto pr ON pr.id = i.produto_id
             WHERE i.pedido_id = :id
             ORDER BY i.id'
        );
        $stmt->execute(['id' => $pedidoId]);

        return array_map(static function (array $item): array {
            $item['preco_unitario'] = (float) $item['preco_unitario'];
            $item['subtotal'] = round($item['preco_unitario'] * $item['quantidade'], 2);

            return $item;
        }, $stmt->fetchAll());
    }

    /** @param int|null $usuarioId null = todos os pedidos (admin) */
    public function listar(?int $usuarioId, int $limite, int $offset): array
    {
        $sql = 'SELECT ' . self::COLUNAS . self::ORIGEM
            . ($usuarioId !== null ? ' WHERE p.usuario_id = :usuario_id' : '')
            . ' ORDER BY p.id DESC LIMIT :limite OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        if ($usuarioId !== null) {
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'normalizar'], $stmt->fetchAll());
    }

    public function contar(?int $usuarioId): int
    {
        if ($usuarioId === null) {
            return (int) $this->pdo->query('SELECT COUNT(*) FROM pedido')->fetchColumn();
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM pedido WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn();
    }

    // ---------- Escritas (chamar dentro de transacao()) ----------

    /** @param string $total valor já calculado, ex.: "154.70" */
    public function criar(int $usuarioId, int $enderecoId, string $total): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pedido (usuario_id, endereco_id, total) VALUES (:usuario_id, :endereco_id, :total)'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'endereco_id' => $enderecoId, 'total' => $total]);

        return (int) $this->pdo->lastInsertId();
    }

    public function criarItem(int $pedidoId, int $produtoId, int $quantidade, string $precoUnitario): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO item_pedido (pedido_id, produto_id, quantidade, preco_unitario)
             VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario)'
        );
        $stmt->execute([
            'pedido_id' => $pedidoId,
            'produto_id' => $produtoId,
            'quantidade' => $quantidade,
            'preco_unitario' => $precoUnitario,
        ]);
    }

    public function baixarEstoque(int $produtoId, int $quantidade): void
    {
        $stmt = $this->pdo->prepare('UPDATE produto SET estoque = estoque - :quantidade WHERE id = :id');
        $stmt->execute(['quantidade' => $quantidade, 'id' => $produtoId]);
    }

    /** Devolve ao estoque tudo o que o pedido havia reservado (usado ao cancelar). */
    public function devolverEstoque(int $pedidoId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE produto pr
             INNER JOIN item_pedido i ON i.produto_id = pr.id
             SET pr.estoque = pr.estoque + i.quantidade
             WHERE i.pedido_id = :pedido_id'
        );
        $stmt->execute(['pedido_id' => $pedidoId]);
    }

    public function atualizarStatus(int $id, string $status): void
    {
        $pagoEm = $status === 'PAGO' ? ', pago_em = CURRENT_TIMESTAMP' : '';
        $stmt = $this->pdo->prepare("UPDATE pedido SET status = :status{$pagoEm} WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /** Devolve total como número no JSON. */
    private function normalizar(array $pedido): array
    {
        $pedido['total'] = (float) $pedido['total'];

        return $pedido;
    }
}