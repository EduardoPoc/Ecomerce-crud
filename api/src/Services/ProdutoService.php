<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Repositories\ProdutoRepository;
use PDOException;

/** Regras de negócio de produtos: validação, categoria existente e proteção na exclusão. */
class ProdutoService
{
    private const CAMPOS = ['categoria_id', 'nome', 'descricao', 'preco', 'estoque', 'imagem_url', 'ativo'];

    private ProdutoRepository $repository;

    public function __construct(?ProdutoRepository $repository = null)
    {
        $this->repository = $repository ?? new ProdutoRepository();
    }

    /** Catálogo público: só produtos ativos. */
    public function listarAtivos(): array
    {
        return $this->repository->findAll(apenasAtivos: true);
    }

    /** Painel admin: inclui os inativos. */
    public function listarTodos(): array
    {
        return $this->repository->findAll(apenasAtivos: false);
    }

    /** Detalhe público: produto inativo aparece como "não encontrado". */
    public function buscarAtivo(int $id): array
    {
        $produto = $this->buscar($id);
        if (!$produto['ativo']) {
            throw new HttpException(404, 'Produto não encontrado.');
        }

        return $produto;
    }

    public function buscar(int $id): array
    {
        if ($id <= 0) {
            throw new HttpException(400, 'ID do produto inválido.');
        }

        return $this->repository->findById($id)
            ?? throw new HttpException(404, 'Produto não encontrado.');
    }

    public function criar(array $dados): array
    {
        [$campos, $erros] = $this->validar($dados, parcial: false);
        $this->lancarSeHouverErros($erros);

        $campos += ['descricao' => null, 'estoque' => 0, 'imagem_url' => null, 'ativo' => true];
        $this->garantirCategoria($campos['categoria_id']);

        return $this->buscar($this->repository->create($campos));
    }

    /** Atualização parcial: só os campos enviados mudam (serve para PUT e PATCH). */
    public function atualizar(int $id, array $dados): array
    {
        $atual = $this->buscar($id);

        [$campos, $erros] = $this->validar($dados, parcial: true);
        $this->lancarSeHouverErros($erros);
        if ($campos === []) {
            throw new HttpException(422, 'Informe ao menos um campo para atualizar.');
        }

        if (isset($campos['categoria_id'])) {
            $this->garantirCategoria($campos['categoria_id']);
        }

        $novo = $campos + array_intersect_key($atual, array_flip(self::CAMPOS));
        $this->repository->update($id, $novo);

        return $this->buscar($id);
    }

    public function remover(int $id): void
    {
        $this->buscar($id);

        try {
            $this->repository->delete($id);
        } catch (PDOException $e) {
            // 1451 = existe linha em outra tabela apontando para este produto (pedido/carrinho).
            if (($e->errorInfo[1] ?? null) === 1451) {
                throw new HttpException(
                    409,
                    'Este produto já foi usado em pedidos ou carrinhos e não pode ser excluído. Desative-o enviando "ativo": false.'
                );
            }
            throw $e;
        }
    }

    /** @return array{0: array, 1: array} [campos válidos e normalizados, erros por campo] */
    private function validar(array $dados, bool $parcial): array
    {
        $campos = [];
        $erros = [];
        $enviado = static fn(string $k): bool => !$parcial || array_key_exists($k, $dados);

        if ($enviado('categoria_id')) {
            $v = $dados['categoria_id'] ?? null;
            if (!$this->ehInteiro($v) || (int) $v <= 0) {
                $erros['categoria_id'] = 'Informe uma categoria válida.';
            } else {
                $campos['categoria_id'] = (int) $v;
            }
        }

        if ($enviado('nome')) {
            $v = is_string($dados['nome'] ?? null) ? trim($dados['nome']) : '';
            if ($this->tamanho($v) < 1 || $this->tamanho($v) > 200) {
                $erros['nome'] = 'Informe um nome de 1 a 200 caracteres.';
            } else {
                $campos['nome'] = $v;
            }
        }

        if ($enviado('preco')) {
            $v = $dados['preco'] ?? null;
            $numerico = is_int($v) || is_float($v) || (is_string($v) && is_numeric($v));
            if (!$numerico || !is_finite((float) $v) || (float) $v < 0 || (float) $v > 99999999.99) {
                $erros['preco'] = 'O preço deve ser um número entre 0 e 99999999.99.';
            } else {
                $campos['preco'] = round((float) $v, 2);
            }
        }

        if (array_key_exists('estoque', $dados)) {
            $v = $dados['estoque'];
            if (!$this->ehInteiro($v) || (int) $v < 0) {
                $erros['estoque'] = 'O estoque deve ser um inteiro maior ou igual a zero.';
            } else {
                $campos['estoque'] = (int) $v;
            }
        }

        if (array_key_exists('descricao', $dados)) {
            $v = $dados['descricao'];
            if ($v !== null && !is_string($v)) {
                $erros['descricao'] = 'A descrição deve ser um texto.';
            } else {
                $campos['descricao'] = ($v === null || trim($v) === '') ? null : trim($v);
            }
        }

        if (array_key_exists('imagem_url', $dados)) {
            $v = $dados['imagem_url'];
            if ($v !== null && (!is_string($v) || $this->tamanho(trim($v)) > 500)) {
                $erros['imagem_url'] = 'A URL da imagem deve ser um texto de até 500 caracteres.';
            } else {
                $campos['imagem_url'] = ($v === null || trim($v) === '') ? null : trim($v);
            }
        }

        if (array_key_exists('ativo', $dados)) {
            if (!is_bool($dados['ativo'])) {
                $erros['ativo'] = 'O campo ativo deve ser true ou false.';
            } else {
                $campos['ativo'] = $dados['ativo'];
            }
        }

        return [$campos, $erros];
    }

    private function garantirCategoria(int $categoriaId): void
    {
        if (!$this->repository->categoryExists($categoriaId)) {
            throw new HttpException(422, 'Dados inválidos.', ['categoria_id' => 'Categoria não encontrada.']);
        }
    }

    private function lancarSeHouverErros(array $erros): void
    {
        if ($erros !== []) {
            throw new HttpException(422, 'Dados inválidos.', $erros);
        }
    }

    /** Aceita 5, "5" e 5.0 (o JSON às vezes manda número como float ou texto). */
    private function ehInteiro(mixed $v): bool
    {
        return is_int($v)
            || (is_string($v) && preg_match('/^-?\d+$/', $v) === 1)
            || (is_float($v) && is_finite($v) && floor($v) === $v);
    }

    /** Conta caracteres (não bytes), sem depender da extensão mbstring. */
    private function tamanho(string $texto): int
    {
        $n = preg_match_all('/./su', $texto);

        return $n === false ? strlen($texto) : $n;
    }
}