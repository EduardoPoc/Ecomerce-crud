<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Repositories\PedidoRepository;

/** Regras de negócio de pedidos: checkout, consulta com controle de dono e mudança de status. */
final class PedidoService
{
    private const MAX_ITENS = 50;
    private const MAX_QUANTIDADE = 100;

    /** Mudanças de status permitidas. ENTREGUE e CANCELADO são finais. */
    private const TRANSICOES = [
        'AGUARDANDO_PAGAMENTO' => ['PAGO', 'CANCELADO'],
        'PAGO' => ['ENVIADO', 'CANCELADO'],
        'ENVIADO' => ['ENTREGUE'],
        'ENTREGUE' => [],
        'CANCELADO' => [],
    ];

    private PedidoRepository $repository;

    public function __construct(?PedidoRepository $repository = null)
    {
        $this->repository = $repository ?? new PedidoRepository();
    }

    /** Cliente vê só os próprios pedidos; admin vê todos. */
    public function listar(array $usuario, int $pagina, int $limite): array
    {
        $filtro = $this->ehAdmin($usuario) ? null : (int) $usuario['id'];

        return [
            'dados' => $this->repository->listar($filtro, $limite, ($pagina - 1) * $limite),
            'pagina' => $pagina,
            'limite' => $limite,
            'total' => $this->repository->contar($filtro),
        ];
    }

    /** Pedido de outra pessoa aparece como "não encontrado" (não revela que ele existe). */
    public function buscar(int $id, array $usuario): array
    {
        $pedido = $id > 0 ? $this->repository->findById($id) : null;

        if ($pedido === null || (!$this->ehAdmin($usuario) && (int) $pedido['usuario_id'] !== (int) $usuario['id'])) {
            throw new HttpException(404, 'Pedido não encontrado.');
        }

        return $this->completo($pedido);
    }

    /**
     * Checkout. Tudo acontece numa transação: valida estoque, grava pedido e itens e baixa o estoque.
     * O preço e o total vêm SEMPRE do banco; qualquer preço enviado pelo cliente é ignorado.
     */
    public function criar(array $usuario, array $dados): array
    {
        [$enderecoId, $itens, $erros] = $this->validarCheckout($dados);
        if ($erros !== []) {
            throw new HttpException(422, 'Dados inválidos.', $erros);
        }

        if (!$this->repository->enderecoPertenceAoUsuario($enderecoId, (int) $usuario['id'])) {
            throw new HttpException(422, 'Dados inválidos.', ['endereco_id' => 'Endereço não encontrado.']);
        }

        $pedidoId = $this->repository->transacao(function () use ($usuario, $enderecoId, $itens): int {
            $linhas = [];
            $naoEncontrados = [];
            $semEstoque = [];
            $totalCentavos = 0;

            // Ordem fixa por id: evita deadlock entre dois checkouts com os mesmos produtos.
            ksort($itens);
            foreach ($itens as $produtoId => $quantidade) {
                $produto = $this->repository->bloquearProduto($produtoId);

                if ($produto === null || !$produto['ativo']) {
                    $naoEncontrados["produto_id {$produtoId}"] = 'Produto não encontrado.';
                    continue;
                }
                if ($produto['estoque'] < $quantidade) {
                    $semEstoque["produto_id {$produtoId}"] = "Estoque insuficiente (disponível: {$produto['estoque']}).";
                    continue;
                }

                $centavos = (int) round(((float) $produto['preco']) * 100);
                $totalCentavos += $centavos * $quantidade;
                $linhas[] = [$produtoId, $quantidade, number_format($centavos / 100, 2, '.', '')];
            }

            if ($naoEncontrados !== []) {
                throw new HttpException(422, 'Há produtos indisponíveis no pedido.', $naoEncontrados);
            }
            if ($semEstoque !== []) {
                throw new HttpException(409, 'Estoque insuficiente para concluir o pedido.', $semEstoque);
            }

            $id = $this->repository->criar(
                (int) $usuario['id'],
                $enderecoId,
                number_format($totalCentavos / 100, 2, '.', '')
            );
            foreach ($linhas as [$produtoId, $quantidade, $preco]) {
                $this->repository->criarItem($id, $produtoId, $quantidade, $preco);
                $this->repository->baixarEstoque($produtoId, $quantidade);
            }

            return $id;
        });

        return $this->buscar($pedidoId, $usuario);
    }

    /** Somente admin (a rota já exige). Cancelar devolve os itens ao estoque. */
    public function atualizarStatus(int $id, array $dados): array
    {
        $novo = is_string($dados['status'] ?? null) ? strtoupper(trim($dados['status'])) : '';
        if (!array_key_exists($novo, self::TRANSICOES)) {
            throw new HttpException(422, 'Dados inválidos.', [
                'status' => 'Status deve ser um de: ' . implode(', ', array_keys(self::TRANSICOES)) . '.',
            ]);
        }

        $this->repository->transacao(function () use ($id, $novo): void {
            $pedido = $id > 0 ? $this->repository->bloquearPorId($id) : null;
            if ($pedido === null) {
                throw new HttpException(404, 'Pedido não encontrado.');
            }

            if (!in_array($novo, self::TRANSICOES[$pedido['status']], true)) {
                throw new HttpException(409, "Não é possível mudar o pedido de {$pedido['status']} para {$novo}.");
            }

            if ($novo === 'CANCELADO') {
                $this->repository->devolverEstoque($id);
            }
            $this->repository->atualizarStatus($id, $novo);
        });

        return $this->completo($this->repository->findById($id));
    }

    /** @return array{0: int, 1: array<int,int>, 2: array} [endereco_id, [produto_id => quantidade], erros] */
    private function validarCheckout(array $dados): array
    {
        $erros = [];

        $enderecoId = $dados['endereco_id'] ?? null;
        if (!$this->ehInteiro($enderecoId) || (int) $enderecoId <= 0) {
            $erros['endereco_id'] = 'Informe o endereço de entrega.';
        }

        $itens = [];
        $lista = $dados['itens'] ?? null;
        if (!is_array($lista) || $lista === [] || !array_is_list($lista)) {
            $erros['itens'] = 'Envie ao menos um item, ex.: [{"produto_id": 1, "quantidade": 2}].';
        } elseif (count($lista) > self::MAX_ITENS) {
            $erros['itens'] = 'O pedido pode ter no máximo ' . self::MAX_ITENS . ' itens.';
        } else {
            foreach ($lista as $i => $item) {
                $produtoId = is_array($item) ? ($item['produto_id'] ?? null) : null;
                $quantidade = is_array($item) ? ($item['quantidade'] ?? null) : null;

                if (!$this->ehInteiro($produtoId) || (int) $produtoId <= 0) {
                    $erros["itens[{$i}].produto_id"] = 'Informe um produto válido.';
                    continue;
                }
                if (!$this->ehInteiro($quantidade) || (int) $quantidade < 1 || (int) $quantidade > self::MAX_QUANTIDADE) {
                    $erros["itens[{$i}].quantidade"] = 'A quantidade deve ser um inteiro de 1 a ' . self::MAX_QUANTIDADE . '.';
                    continue;
                }

                // Mesmo produto repetido na lista: soma as quantidades.
                $itens[(int) $produtoId] = ($itens[(int) $produtoId] ?? 0) + (int) $quantidade;
                if ($itens[(int) $produtoId] > self::MAX_QUANTIDADE) {
                    $erros["itens[{$i}].quantidade"] = 'A quantidade total deste produto passa de ' . self::MAX_QUANTIDADE . '.';
                }
            }
        }

        return [(int) ($enderecoId ?? 0), $itens, $erros];
    }

    private function completo(array $pedido): array
    {
        $pedido['endereco'] = $this->repository->endereco((int) $pedido['endereco_id']);
        $pedido['itens'] = $this->repository->itens((int) $pedido['id']);

        return $pedido;
    }

    private function ehAdmin(array $usuario): bool
    {
        return ($usuario['papel'] ?? '') === 'ADMIN';
    }

    /** Aceita 5, "5" e 5.0 (o JSON às vezes manda número como float ou texto). */
    private function ehInteiro(mixed $v): bool
    {
        return is_int($v)
            || (is_string($v) && preg_match('/^-?\d+$/', $v) === 1)
            || (is_float($v) && is_finite($v) && floor($v) === $v);
    }
}