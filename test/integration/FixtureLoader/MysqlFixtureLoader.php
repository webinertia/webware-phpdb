<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb\FixtureLoader;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql;
use WebwareTestIntegration\PhpDb\Support\AdapterFactory;

use function getenv;
use function sprintf;

final class MysqlFixtureLoader implements FixtureLoaderInterface
{
    public function createDatabase(): void
    {
        $database = (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_DATABASE');
        $adapter  = $this->adapter();

        $adapter->executeQuery(sql: sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $database));
        $adapter->executeQuery(sql: sprintf('USE `%s`', $database));
    }

    public function dropDatabase(): void
    {
        $database = (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_DATABASE');
        $adapter  = $this->adapter();

        $adapter->executeQuery(sql: sprintf('DROP DATABASE IF EXISTS `%s`', $database));
    }

    private function adapter(): AdapterInterface
    {
        return AdapterFactory::create(
            adapterConfig     : [
                'driver'     => Mysql\Pdo\Driver::class,
                'connection' => [
                    'hostname' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME'),
                    'port'     => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_PORT'),
                    'username' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
                    'password' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
                ],
            ],
            driverDependencies: new Mysql\ConfigProvider()->getDependencies(),
        );
    }
}
