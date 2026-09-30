<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb\FixtureLoader;

use function getenv;
use function is_file;
use function str_replace;
use function unlink;

final class SqliteFixtureLoader implements FixtureLoaderInterface
{
    public function createDatabase(): void
    {
        $path = $this->databasePath();

        if (is_file(filename: $path)) {
            unlink(filename: $path);
        }
    }

    public function dropDatabase(): void
    {
        $path = $this->databasePath();

        if (is_file(filename: $path)) {
            unlink(filename: $path);
        }
    }

    private function databasePath(): string
    {
        return str_replace(
            search : 'sqlite:',
            replace: '',
            subject: (string) getenv(name: 'TESTS_PHPDB_ADAPTER_SQLITE_DSN'),
        );
    }
}
