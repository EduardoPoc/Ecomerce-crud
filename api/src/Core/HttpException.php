<?php

declare(strict_types=1);

namespace Ecommerce\Api\Core;

use RuntimeException;

/**
 * Lance esta exceção em qualquer camada (service, repository, middleware)
 * para interromper o fluxo e devolver um erro HTTP padronizado.
 *
 *   throw new HttpException(404, 'Usuário não encontrado');
 *   throw new HttpException(422, 'Dados inválidos', ['email' => 'obrigatório']);
 */
final class HttpException extends RuntimeException
{
    public function __construct(
        private int $status,
        string $message = '',
        private array $errors = [],
    ) {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}