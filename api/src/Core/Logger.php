<?php

declare(strict_types=1);

namespace Ecommerce\Api\Core;

use Throwable;

/** Emite logs JSON por linha para o stdout/stderr do processo PHP. */
final class Logger
{
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function exception(Throwable $error, string $message, array $context = []): void
    {
        self::error($message, [
            ...$context,
            'exception' => $error::class,
            'exception_message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ]);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $record = [
            'timestamp' => gmdate('c'),
            'level' => $level,
            'service' => 'api',
            'message' => $message,
            ...$context,
        ];

        $encoded = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        error_log($encoded === false ? '{"level":"error","service":"api","message":"log_encoding_failed"}' : $encoded);
    }
}
