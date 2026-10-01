<?php

declare(strict_types=1);

namespace Ecommerce\Api\Core;

/**
 * Resposta HTTP. Controllers e middlewares devolvem um Response; só o
 * public/index.php chama send(), o que mantém tudo testável.
 */
final class Response
{
    public function __construct(
        private mixed $body = null,
        private int $status = 200,
        private array $headers = [],
    ) {
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self($data, $status);
    }

    public static function created(mixed $data): self
    {
        return new self($data, 201);
    }

    public static function noContent(): self
    {
        return new self(null, 204);
    }

    /** Formato padrão de erro: {"erro": "mensagem", "detalhes": {...}} */
    public static function error(string $message, int $status, array $details = []): self
    {
        $body = ['erro' => $message];
        if ($details !== []) {
            $body['detalhes'] = $details;
        }
        return new self($body, $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): mixed
    {
        return $this->body;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if ($this->body !== null && $this->status !== 204) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                $this->body,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        }
    }
}