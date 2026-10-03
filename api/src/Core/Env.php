<?php

declare(strict_types=1);

namespace Ecommerce\Api\Core;

use RuntimeException;

/** Leitura simples de variáveis de ambiente e do arquivo api/.env. */
final class Env
{
    /** @var array<string, mixed>|null */
    private static ?array $values = null;

    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (isset($_ENV[$key])) {
            return (string) $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return (string) $_SERVER[$key];
        }

        $values = self::load();
        if (!array_key_exists($key, $values) || $values[$key] === null) {
            return $default;
        }

        return (string) $values[$key];
    }

    /** @return array<string, mixed> */
    private static function load(): array
    {
        if (self::$values !== null) {
            return self::$values;
        }

        $path = dirname(__DIR__, 2) . '/.env';
        if (!is_file($path)) {
            return self::$values = [];
        }

        $values = parse_ini_file($path, false, INI_SCANNER_RAW);
        if ($values === false) {
            throw new RuntimeException('Não foi possível ler o arquivo api/.env.');
        }

        return self::$values = $values;
    }
}
