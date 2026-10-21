<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb\FixtureLoader;

use WebwareTestIntegration\PhpDb\SqliteDsn;

use function is_file;
use function unlink;

final class SqliteFixtureLoader implements FixtureLoaderInterface
{
    public function createDatabase(): void
    {
        $path = SqliteDsn::path();

        if (is_file(filename: $path)) {
            unlink(filename: $path);
        }
    }

    public function dropDatabase(): void
    {
        $path = SqliteDsn::path();

        if (is_file(filename: $path)) {
            unlink(filename: $path);
        }
    }
}
