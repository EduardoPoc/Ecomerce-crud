<?php

declare(strict_types=1);

namespace Ecommerce\Api\Core;

use RuntimeException;

final class Env
{
    private static ?array $config = null;

    public static function get(string $key, string $default = ''): string
    {
        if (self::$config === null) {
            $path = dirname(__DIR__, 2) . '/.env';

            if (!is_file($path)) {
                throw new RuntimeException("Arquivo de configuração não encontrado: {$path}");
            }

            $config = parse_ini_file($path, false, INI_SCANNER_RAW);

            if ($config === false) {
                throw new RuntimeException("Não foi possível ler o arquivo: {$path}");
            }

            self::$config = $config;
        }

        return (string) (self::$config[$key] ?? $default);
    }
}