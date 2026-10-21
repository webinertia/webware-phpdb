<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb\FixtureLoader;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Pgsql;
use Throwable;
use WebwareTestIntegration\PhpDb\Support\AdapterFactory;

use function getenv;
use function sprintf;
use function str_contains;
use function str_replace;

/**
 * Ensures the pgsql test database exists, following the same shape as the
 * mysql loader.
 *
 * CI provisions the database through the container environment (POSTGRES_DB),
 * so it is already present when the suite starts, and this is a no-op. Locally
 * it creates the database when missing. Either way the create path never drops
 * anything.
 *
 * Postgres has no CREATE DATABASE IF NOT EXISTS, so existence is checked
 * against pg_database first.
 *
 * Nothing here drops a database with sessions attached. Postgres refuses that
 * where mysql allows it, so a drop-then-create cycle would need FORCE and
 * therefore pg_signal_backend, a privilege a test role should not hold.
 */
final class PgsqlFixtureLoader implements FixtureLoaderInterface
{
    private const string MAINTENANCE_DATABASE = 'postgres';

    public function createDatabase(): void
    {
        if ($this->exists()) {
            return;
        }

        $this->adapter()->executeQuery(
            sql: sprintf(
                'CREATE DATABASE "%s" OWNER "%s"',
                $this->database(),
                $this->username(),
            ),
        );
    }

    public function dropDatabase(): void
    {
        try {
            $this->adapter()->executeQuery(
                sql: sprintf('DROP DATABASE IF EXISTS "%s"', $this->database()),
            );
        } catch (Throwable $e) {
            // Postgres refuses to drop a database that has a session attached,
            // and at teardown the suite's own connection is still open. A
            // non-superuser role cannot terminate its own backend, so this is
            // unreachable without pg_signal_backend.
            //
            // Tolerating exactly this case is safe because the outcome the drop
            // exists to achieve is already guaranteed: CI uses a disposable
            // container per leg, and each test creates and drops its own table,
            // so no state carries between runs.
            if (! str_contains($e->getMessage(), '55006')) {
                throw $e;
            }
        }
    }

    private function adapter(): AdapterInterface
    {
        return AdapterFactory::create(
            adapterConfig     : [
                'driver'     => Pgsql\Pdo\Driver::class,
                'connection' => [
                    'hostname' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_HOSTNAME'),
                    'port'     => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_PORT'),
                    'dbname'   => self::MAINTENANCE_DATABASE,
                    'username' => $this->username(),
                    'password' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_PASSWORD'),
                ],
            ],
            driverDependencies: new Pgsql\ConfigProvider()->getDependencies(),
        );
    }

    private function database(): string
    {
        return (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_DATABASE');
    }

    private function exists(): bool
    {
        $rows = $this
            ->adapter()
            ->executeQuery(
                sql: sprintf(
                    'SELECT 1 FROM pg_database WHERE datname = %s',
                    $this->quotedDatabase(),
                ),
            )
            ->getQueryResult()
            ->toArray();

        return [] !== $rows;
    }

    private function quotedDatabase(): string
    {
        return sprintf(
            "'%s'",
            str_replace(
                search : "'",
                replace: "''",
                subject: $this->database(),
            ),
        );
    }

    private function username(): string
    {
        return (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_USERNAME');
    }
}
