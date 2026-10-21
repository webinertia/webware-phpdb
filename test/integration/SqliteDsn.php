<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb;

use function getenv;
use function str_replace;
use function sys_get_temp_dir;

/**
 * Resolves the sqlite DSN from the environment.
 *
 * The DSN may contain the `:TMP:` token, which is replaced with the platform
 * temp directory. That keeps one definition working on Linux (where the
 * fixture lives under `/tmp`) and on Windows (where it does not exist).
 */
final class SqliteDsn
{
    public static function path(): string
    {
        return str_replace(
            search : 'sqlite:',
            replace: '',
            subject: self::resolve(),
        );
    }

    public static function resolve(): string
    {
        return str_replace(
            search : ':TMP:',
            replace: sys_get_temp_dir(),
            subject: (string) getenv(name: 'TESTS_PHPDB_ADAPTER_SQLITE_DSN'),
        );
    }
}
