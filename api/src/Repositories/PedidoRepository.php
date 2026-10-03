<?php
declare(strict_types=1);
namespace Ecommerce\Api\Repositories;
use Ecommerce\Api\Config\Database;
use PDO;

final class PedidoRepository
{
    private const FRETE_FIXO = 30.0;
    private PDO $pdo;
    public function __construct(?PDO $pdo = null) { $this->pdo = $pdo ?? Database::connection(); }
    public function findAll(?int $usuarioId = null): array
    {
        $sql = 'SELECT p.*, e.destinatario, e.cep, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.uf FROM pedido p JOIN endereco e ON e.id = p.endereco_id';
        if ($usuarioId !== null) $sql .= ' WHERE p.usuario_id = :usuario_id';
        $sql .= ' ORDER BY p.criado_em DESC, p.id DESC';
        $stmt = $this->pdo->prepare($sql); $stmt->execute($usuarioId === null ? [] : ['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }
    public function findById(int $id, ?int $usuarioId = null): ?array
    {
        $sql = 'SELECT p.*, e.destinatario, e.cep, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.uf FROM pedido p JOIN endereco e ON e.id = p.endereco_id WHERE p.id = :id';
        if ($usuarioId !== null) $sql .= ' AND p.usuario_id = :usuario_id';
        $stmt = $this->pdo->prepare($sql); $stmt->execute($usuarioId === null ? ['id' => $id] : ['id' => $id, 'usuario_id' => $usuarioId]);
        $order = $stmt->fetch(); if (!$order) return null;
        $items = $this->pdo->prepare('SELECT i.*, p.nome, p.imagem_url FROM item_pedido i JOIN produto p ON p.id = i.produto_id WHERE i.pedido_id = :pedido_id ORDER BY i.id');
        $items->execute(['pedido_id' => $id]); $order['itens'] = $items->fetchAll(); return $order;
    }
    public function createFromCart(int $userId, int $addressId): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM endereco WHERE id = :id AND usuario_id = :usuario_id AND ativo = 1');
            $stmt->execute(['id' => $addressId, 'usuario_id' => $userId]); if (!$stmt->fetch()) throw new \RuntimeException('Endereço não encontrado.');
            $stmt = $this->pdo->prepare('SELECT id FROM carrinho WHERE usuario_id = :usuario_id LIMIT 1'); $stmt->execute(['usuario_id' => $userId]); $cartId = $stmt->fetchColumn();
            if ($cartId === false) throw new \RuntimeException('Carrinho vazio.');
            $stmt = $this->pdo->prepare('SELECT i.produto_id, i.quantidade, p.preco, p.ativo, p.estoque FROM item_carrinho i JOIN produto p ON p.id = i.produto_id WHERE i.carrinho_id = :carrinho_id FOR UPDATE');
            $stmt->execute(['carrinho_id' => $cartId]); $items = $stmt->fetchAll(); if ($items === []) throw new \RuntimeException('Carrinho vazio.');
            $total = self::FRETE_FIXO; foreach ($items as $item) { if (!(bool) $item['ativo'] || (int) $item['quantidade'] > (int) $item['estoque']) throw new \RuntimeException('Um dos produtos não possui estoque suficiente.'); $total += (float) $item['preco'] * (int) $item['quantidade']; }
            $stmt = $this->pdo->prepare("INSERT INTO pedido (usuario_id, endereco_id, status, total) VALUES (:usuario_id, :endereco_id, 'AGUARDANDO_PAGAMENTO', :total)"); $stmt->execute(['usuario_id' => $userId, 'endereco_id' => $addressId, 'total' => $total]); $orderId = (int) $this->pdo->lastInsertId();
            $insert = $this->pdo->prepare('INSERT INTO item_pedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario)');
            foreach ($items as $item) $insert->execute(['pedido_id' => $orderId, 'produto_id' => $item['produto_id'], 'quantidade' => $item['quantidade'], 'preco_unitario' => $item['preco']]);
            $this->pdo->commit(); return $orderId;
        } catch (\Throwable $e) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $e; }
    }
    public function pay(int $id, int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT status FROM pedido WHERE id = :id AND usuario_id = :usuario_id FOR UPDATE'); $stmt->execute(['id' => $id, 'usuario_id' => $userId]); $order = $stmt->fetch();
            if (!$order) throw new \RuntimeException('Pedido não encontrado.'); if ($order['status'] !== 'AGUARDANDO_PAGAMENTO') throw new \RuntimeException('Este pedido não está aguardando pagamento.');
            $stmt = $this->pdo->prepare('SELECT i.produto_id, i.quantidade, p.estoque, p.ativo FROM item_pedido i JOIN produto p ON p.id = i.produto_id WHERE i.pedido_id = :pedido_id FOR UPDATE'); $stmt->execute(['pedido_id' => $id]); $items = $stmt->fetchAll();
            foreach ($items as $item) if (!(bool) $item['ativo'] || (int) $item['quantidade'] > (int) $item['estoque']) throw new \RuntimeException('Estoque insuficiente para concluir o pagamento.');
            $decrease = $this->pdo->prepare('UPDATE produto SET estoque = estoque - :quantidade WHERE id = :produto_id'); foreach ($items as $item) $decrease->execute(['quantidade' => $item['quantidade'], 'produto_id' => $item['produto_id']]);
            $stmt = $this->pdo->prepare("UPDATE pedido SET status = 'PAGO', pago_em = CURRENT_TIMESTAMP WHERE id = :id"); $stmt->execute(['id' => $id]);
            $stmt = $this->pdo->prepare('SELECT id FROM carrinho WHERE usuario_id = :usuario_id LIMIT 1'); $stmt->execute(['usuario_id' => $userId]); $cartId = $stmt->fetchColumn(); if ($cartId !== false) { $stmt = $this->pdo->prepare('DELETE FROM item_carrinho WHERE carrinho_id = :id'); $stmt->execute(['id' => $cartId]); }
            $this->pdo->commit();
        } catch (\Throwable $e) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $e; }
    }
    public function updateStatus(int $id, string $status): bool { $stmt = $this->pdo->prepare('UPDATE pedido SET status = :status WHERE id = :id'); $stmt->execute(['status' => $status, 'id' => $id]); return $stmt->rowCount() > 0; }
    public function deletePending(int $id, int $userId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM pedido WHERE id = :id AND usuario_id = :usuario_id AND status = 'AGUARDANDO_PAGAMENTO' FOR UPDATE");
            $stmt->execute(['id' => $id, 'usuario_id' => $userId]);
            if (!$stmt->fetch()) { $this->pdo->rollBack(); return false; }
            $stmt = $this->pdo->prepare('DELETE FROM item_pedido WHERE pedido_id = :pedido_id');
            $stmt->execute(['pedido_id' => $id]);
            $stmt = $this->pdo->prepare('DELETE FROM pedido WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
