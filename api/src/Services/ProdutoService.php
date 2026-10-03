<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Repositories\ProdutoRepository;
use Ecommerce\Api\Core\HttpException;

class ProdutoService
{
    private ProdutoRepository $repository;

    public function __construct()
    {
        $this->repository = new ProdutoRepository();
    }

    public function getAll(array $filters = [], bool $includeInactive = false): array
    {
        $page = (int) ($filters['pagina'] ?? 1);
        $limit = (int) ($filters['limite'] ?? 12);
        $search = trim((string) ($filters['busca'] ?? ''));
        $categoryId = $filters['categoria_id'] ?? null;
        $categoryId = $categoryId === null || $categoryId === '' ? null : (int) $categoryId;
        $sort = (string) ($filters['ordenar'] ?? ($search !== '' ? 'relevancia' : 'nome'));

        if ($page < 1) throw new HttpException(422, 'Página deve ser maior que zero.');
        if ($limit < 1 || $limit > 100) throw new HttpException(422, 'Limite deve estar entre 1 e 100.');
        if ($categoryId !== null && $categoryId < 1) throw new HttpException(422, 'Categoria inválida.');
        if (mb_strlen($search) > 100) throw new HttpException(422, 'Busca deve ter no máximo 100 caracteres.');
        if (!in_array($sort, ['nome', 'preco_asc', 'preco_desc', 'recentes', 'relevancia'], true)) {
            throw new HttpException(422, 'Ordenação inválida.');
        }

        $result = $this->repository->search($page, $limit, $search ?: null, $categoryId, $sort, $includeInactive);
        return [
            'itens' => $result['itens'],
            'pagina' => $page,
            'limite' => $limit,
            'total' => $result['total'],
            'paginas' => $result['total'] === 0 ? 0 : (int) ceil($result['total'] / $limit),
        ];
    }

    public function getById(int $id, bool $onlyActive = false): array
    {
        if ($id <= 0) {
            throw new HttpException(400, 'ID inválido.');
        }

        $produto = $this->repository->findById($id, $onlyActive);

        if ($produto === null) {
            throw new HttpException(404, 'Produto não encontrado.');
        }

        return $produto;
    }

    public function create(array $data): int
    {
        $categoriaId = (int) ($data['categoria_id'] ?? 0);
        $nome = trim($data['nome'] ?? '');
        $descricao = isset($data['descricao'])
            ? trim($data['descricao'])
            : null;

        $preco = $data['preco'] ?? null;
        $estoque = $data['estoque'] ?? null;
        $imagemUrl = isset($data['imagem_url'])
            ? trim($data['imagem_url'])
            : null;

        $ativo = $data['ativo'] ?? true;

        $this->validate(
            $categoriaId,
            $nome,
            $preco,
            $estoque
        );

        if (!$this->repository->categoryExists($categoriaId)) {
            throw new HttpException(422,
                'Categoria não encontrada.'
            );
        }

        return $this->repository->create(
            $categoriaId,
            $nome,
            $descricao,
            (float) $preco,
            (int) $estoque,
            $imagemUrl,
            (bool) $ativo
        );
    }

    public function update(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new HttpException(400, 'ID inválido.');
        }

        $produto = $this->repository->findById($id);

        if ($produto === null) {
            throw new HttpException(404, 'Produto não encontrado.');
        }

        $categoriaId = (int) ($data['categoria_id'] ?? 0);
        $nome = trim($data['nome'] ?? '');
        $descricao = isset($data['descricao'])
            ? trim($data['descricao'])
            : null;

        $preco = $data['preco'] ?? null;
        $estoque = $data['estoque'] ?? null;
        $imagemUrl = isset($data['imagem_url'])
            ? trim($data['imagem_url'])
            : null;
        $imagemAnterior = $produto['imagem_url'] ?? null;

        $ativo = $data['ativo'] ?? true;

        $this->validate(
            $categoriaId,
            $nome,
            $preco,
            $estoque
        );

        if (!$this->repository->categoryExists($categoriaId)) {
            throw new HttpException(422,
                'Categoria não encontrada.'
            );
        }

        $this->repository->update(
            $id,
            $categoriaId,
            $nome,
            $descricao,
            (float) $preco,
            (int) $estoque,
            $imagemUrl,
            (bool) $ativo
        );

        if ($imagemAnterior !== $imagemUrl) {
            $this->removeLocalImage($imagemAnterior);
        }
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new HttpException(400, 'ID inválido.');
        }

        $produto = $this->repository->findById($id);

        if ($produto === null) {
            throw new HttpException(404,
                'Produto não encontrado.'
            );
        }

        // A desativação é soft delete: a imagem precisa permanecer disponível
        // caso o livro seja ativado novamente no painel.
        $this->repository->delete($id);
    }

    public function hardDelete(int $id): void
    {
        if ($id <= 0) throw new HttpException(400, 'ID inválido.');
        $produto = $this->repository->findById($id);
        if ($produto === null) throw new HttpException(404, 'Produto não encontrado.');
        if ((bool) $produto['ativo']) throw new HttpException(409, 'Desative o livro antes de apagá-lo definitivamente.');

        try {
            if (!$this->repository->hardDelete($id)) throw new HttpException(404, 'Produto não encontrado.');
        } catch (\RuntimeException $error) {
            throw new HttpException(409, $error->getMessage());
        }
        $this->removeLocalImage($produto['imagem_url'] ?? null);
    }

    public function uploadImage(int $id, ?array $file): string
    {
        if ($id <= 0) throw new HttpException(400, 'ID inválido.');
        $product = $this->repository->findById($id);
        if ($product === null) throw new HttpException(404, 'Produto não encontrado.');
        if ($file === null) {
            throw new HttpException(422, 'Selecione uma imagem. Formatos aceitos: JPG, PNG ou WebP.');
        }

        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $message = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A imagem excede o limite máximo de 5 MB.',
                UPLOAD_ERR_PARTIAL => 'O upload da imagem foi interrompido. Tente novamente.',
                UPLOAD_ERR_NO_FILE => 'Selecione uma imagem. Formatos aceitos: JPG, PNG ou WebP.',
                default => 'Não foi possível receber a imagem. Tente novamente.',
            };
            throw new HttpException(422, $message);
        }

        $maxSize = 5 * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > $maxSize) {
            throw new HttpException(422, 'A imagem deve ter no máximo 5 MB.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($extensions[$mime]) || @getimagesize($tmpName) === false) {
            throw new HttpException(422, 'Formato de imagem inválido. Use JPG, PNG ou WebP.');
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/livros';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new HttpException(500, 'Não foi possível preparar o armazenamento da imagem.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $destination = $directory . '/' . $filename;
        if (!move_uploaded_file($tmpName, $destination)) {
            throw new HttpException(500, 'Não foi possível salvar a imagem.');
        }

        $imageUrl = '/uploads/livros/' . $filename;
        $this->repository->updateImage($id, $imageUrl);
        $this->removeLocalImage($product['imagem_url'] ?? null);
        return $imageUrl;
    }

    private function removeLocalImage(?string $imageUrl): void
    {
        if (!is_string($imageUrl) || !str_starts_with($imageUrl, '/uploads/livros/')) return;
        $path = dirname(__DIR__, 2) . '/public' . $imageUrl;
        if (is_file($path)) @unlink($path);
    }

    
    private function validate(
        int $categoriaId,
        string $nome,
        mixed $preco,
        mixed $estoque
    ): void {
        if ($categoriaId <= 0) {
            throw new HttpException(422,
                'Categoria é obrigatória.'
            );
        }

        if ($nome === '') {
            throw new HttpException(422,
                'Nome é obrigatório.'
            );
        }

        if ($preco === null || !is_numeric($preco)) {
            throw new HttpException(422,
                'Preço deve ser numérico.'
            );
        }

        if ((float) $preco < 0) {
            throw new HttpException(422,
                'Preço não pode ser negativo.'
            );
        }

        if ($estoque === null || !is_numeric($estoque)) {
            throw new HttpException(422,
                'Estoque deve ser numérico.'
            );
        }

        if ((int) $estoque < 0) {
            throw new HttpException(422,
                'Estoque não pode ser negativo.'
            );
        }
    }
}
