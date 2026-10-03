<?php
declare(strict_types=1);
namespace Ecommerce\Api\Services;
use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Repositories\PedidoRepository;

final class PedidoService
{
    private const STATUS = ['AGUARDANDO_PAGAMENTO', 'PAGO', 'ENVIADO', 'ENTREGUE', 'CANCELADO'];
    private PedidoRepository $repository;
    public function __construct(?PedidoRepository $repository = null) { $this->repository = $repository ?? new PedidoRepository(); }
    public function listar(?int $userId = null): array { return $this->repository->findAll($userId); }
    public function buscar(int $id, ?int $userId = null): array { $order = $this->repository->findById($id, $userId); if (!$order) throw new HttpException(404, 'Pedido não encontrado.'); return $order; }
    public function criar(int $userId, array $data): array { $addressId = (int) ($data['endereco_id'] ?? 0); if ($addressId <= 0) throw new HttpException(422, 'endereco_id é obrigatório.'); try { $id = $this->repository->createFromCart($userId, $addressId); } catch (\RuntimeException $e) { throw new HttpException(422, $e->getMessage()); } return $this->buscar($id, $userId); }
    public function pagar(int $id, int $userId): array { try { $this->repository->pay($id, $userId); } catch (\RuntimeException $e) { throw new HttpException(422, $e->getMessage()); } return $this->buscar($id, $userId); }
    public function status(int $id, string $status): array { $status = strtoupper(trim($status)); if (!in_array($status, self::STATUS, true)) throw new HttpException(422, 'Status inválido.'); if (!$this->repository->updateStatus($id, $status)) throw new HttpException(404, 'Pedido não encontrado.'); return $this->buscar($id); }
    public function excluirPendente(int $id, int $userId): void { if (!$this->repository->deletePending($id, $userId)) throw new HttpException(404, 'Pedido pendente não encontrado.'); }
}
