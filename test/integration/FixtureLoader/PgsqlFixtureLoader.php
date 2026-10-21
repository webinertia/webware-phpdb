<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb\FixtureLoader;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Pgsql;
use WebwareTestIntegration\PhpDb\Support\AdapterFactory;

use function getenv;

/**
 * Resets the pgsql schema between integration tests.
 *
 * Each test creates and drops its own table, so the database only has to
 * exist and be reachable. Dropping and recreating the whole database is not
 * needed, and an ordinary role cannot do it: DROP DATABASE cannot run while
 * connected to the target, WITH (FORCE) needs pg_signal_backend to terminate
 * other backends, and CREATE DATABASE needs CREATEDB. Requiring any of that
 * would force the suite to run as a superuser, which is exactly what the
 * connection settings exist to avoid.
 *
 * Recreating the public schema is equivalent here and needs only ownership.
 */
final class PgsqlFixtureLoader implements FixtureLoaderInterface
{
    public function createDatabase(): void
    {
        $this->resetSchema();
    }

    public function dropDatabase(): void
    {
        $this->resetSchema();
    }

    private function adapter(): AdapterInterface
    {
        return AdapterFactory::create(
            adapterConfig     : [
                'driver'     => Pgsql\Pdo\Driver::class,
                'connection' => [
                    'hostname' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_HOSTNAME'),
                    'port'     => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_PORT'),
                    'dbname'   => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_DATABASE'),
                    'username' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_USERNAME'),
                    'password' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_PASSWORD'),
                ],
            ],
            driverDependencies: new Pgsql\ConfigProvider()->getDependencies(),
        );
    }

    private function resetSchema(): void
    {
        $adapter = $this->adapter();

        $adapter->executeQuery(sql: 'DROP SCHEMA IF EXISTS public CASCADE');
        $adapter->executeQuery(sql: 'CREATE SCHEMA public');
    }
}
