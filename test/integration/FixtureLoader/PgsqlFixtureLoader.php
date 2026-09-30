<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb\FixtureLoader;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Pgsql;
use WebwareTestIntegration\PhpDb\Support\AdapterFactory;

use function getenv;
use function sprintf;

final class PgsqlFixtureLoader implements FixtureLoaderInterface
{
    public function createDatabase(): void
    {
        $database = (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_DATABASE');
        $adapter  = $this->adapter();

        $adapter->executeQuery(sql: sprintf('DROP DATABASE IF EXISTS "%s" WITH (FORCE)', $database));
        $adapter->executeQuery(sql: sprintf('CREATE DATABASE "%s"', $database));
    }

    public function dropDatabase(): void
    {
        $database = (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_DATABASE');
        $adapter  = $this->adapter();

        $adapter->executeQuery(sql: sprintf('DROP DATABASE IF EXISTS "%s" WITH (FORCE)', $database));
    }

    private function adapter(): AdapterInterface
    {
        return AdapterFactory::create(
            adapterConfig     : [
                'driver'     => Pgsql\Pdo\Driver::class,
                'connection' => [
                    'hostname' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_HOSTNAME'),
                    'port'     => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_PORT'),
                    'dbname'   => 'postgres',
                    'username' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_USERNAME'),
                    'password' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_PASSWORD'),
                ],
            ],
            driverDependencies: new Pgsql\ConfigProvider()->getDependencies(),
        );
    }
}
