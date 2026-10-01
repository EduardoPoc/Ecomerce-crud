<?php

declare(strict_types=1);

namespace Ecommerce\Api\Core;

/**
 * Representa a requisição HTTP recebida. Os controllers recebem um objeto
 * Request em vez de mexer em $_SERVER, $_GET e php://input diretamente.
 */
final class Request
{
    /** Parâmetros de rota, ex.: /usuarios/{id} -> ['id' => '5'] */
    private array $params = [];

    /** Dados anexados durante o fluxo (ex.: o usuário autenticado pelo AuthMiddleware). */
    private array $attributes = [];

    private ?array $json = null;

    public function __construct(
        private string $method,
        private string $path,
        private array $query = [],
        private array $headers = [],
        private string $rawBody = '',
    ) {
        $this->method = strtoupper($method);
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');

        // Se a API estiver numa subpasta (ex.: Apache em /loja/api/public), remove o prefixo.
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = (string) $value;
            }
        }
        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $server => $name) {
            if (isset($_SERVER[$server])) {
                $headers[$name] = (string) $_SERVER[$server];
            }
        }

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $path,
            $_GET,
            $headers,
            (string) file_get_contents('php://input'),
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    /** Caminho normalizado: sempre começa com "/" e não termina com "/" (exceto a raiz). */
    public function path(): string
    {
        return '/' . trim($this->path, '/');
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');
        if ($auth !== null && preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) {
            return $m[1];
        }
        return null;
    }

    /** Corpo JSON como array. Corpo vazio devolve []; JSON inválido gera 400. */
    public function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }
        if (trim($this->rawBody) === '') {
            return $this->json = [];
        }

        $data = json_decode($this->rawBody, true);
        if (!is_array($data)) {
            throw new HttpException(400, 'Corpo da requisição não é um JSON válido.');
        }
        return $this->json = $data;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->json()[$key] ?? $default;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $name, mixed $default = null): mixed
    {
        return $this->params[$name] ?? $default;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}