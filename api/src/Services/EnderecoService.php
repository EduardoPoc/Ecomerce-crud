<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Repositories\EnderecoRepository;

final class EnderecoService
{
    private const CAMPOS = ['destinatario', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'padrao'];
    private EnderecoRepository $repository;

    public function __construct(?EnderecoRepository $repository = null)
    {
        $this->repository = $repository ?? new EnderecoRepository();
    }

    public function listar(int $usuarioId): array
    {
        return $this->repository->findAllByUser($usuarioId);
    }

    public function criar(int $usuarioId, array $data): array
    {
        $validated = $this->validate($data, false);
        $id = $this->repository->create($usuarioId, $validated);
        return $this->buscar($id, $usuarioId);
    }

    public function atualizar(int $id, int $usuarioId, array $data): array
    {
        $this->buscar($id, $usuarioId);
        $validated = $this->validate($data, true);
        if ($validated === []) {
            throw new HttpException(422, 'Informe ao menos um campo para atualizar.');
        }
        $this->repository->update($id, $usuarioId, $validated);
        return $this->buscar($id, $usuarioId);
    }

    public function excluir(int $id, int $usuarioId): void
    {
        if (!$this->repository->deactivate($id, $usuarioId)) {
            throw new HttpException(404, 'Endereço não encontrado.');
        }
    }

    public function buscar(int $id, int $usuarioId): array
    {
        $address = $this->repository->findByIdForUser($id, $usuarioId);
        if ($address === null) {
            throw new HttpException(404, 'Endereço não encontrado.');
        }
        return $address;
    }

    private function validate(array $data, bool $partial): array
    {
        $out = [];
        foreach (self::CAMPOS as $field) {
            if ($partial && !array_key_exists($field, $data)) continue;
            $value = $data[$field] ?? null;
            if ($field === 'padrao') {
                $out[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
                continue;
            }
            if ($field === 'complemento') {
                $out[$field] = $value === null ? null : trim((string) $value);
                continue;
            }
            $value = trim((string) $value);
            if ($value === '') throw new HttpException(422, "Campo '{$field}' é obrigatório.");
            $out[$field] = $value;
        }
        if (isset($out['cep']) && !preg_match('/^\d{8}$/', preg_replace('/\D/', '', $out['cep']))) {
            throw new HttpException(422, 'CEP deve conter 8 dígitos.');
        }
        if (isset($out['uf']) && !preg_match('/^[A-Za-z]{2}$/', $out['uf'])) {
            throw new HttpException(422, 'UF deve conter 2 letras.');
        }
        if (isset($out['cep'])) $out['cep'] = preg_replace('/\D/', '', $out['cep']);
        if (isset($out['uf'])) $out['uf'] = strtoupper($out['uf']);
        return $out;
    }
}
