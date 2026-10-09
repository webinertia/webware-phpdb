<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ConfigProvider as PhpDbConfigProvider;
use PhpDb\Mysql;
use PhpDb\Pgsql;
use PhpDb\SchemaFactory;
use PhpDb\SchemaInterface;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sqlite;
use PhpDb\WebwareProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function getenv;

/**
 * Verifies that a {@see SchemaFactory}-produced {@see TableIdentifier} round-trips
 * through phpdb's DDL on every supported driver, so the configured prefix and
 * schema are rendered correctly per platform.
 */
#[CoversClass(SchemaFactory::class)]
#[CoversMethod(SchemaFactory::class, '__invoke')]
final class SchemaFactoryIntegrationTest extends TestCase
{
    #[Test]
    #[Group('integration')]
    #[Group('integration-mysql')]
    public function itCreatesPrefixedTableOnMysql(): void
    {
        if (! getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL')) {
            self::markTestSkipped('MySQL adapter is not configured.');
        }

        [$schemaFactory, $adapter] = $this->factoryAndAdapter(
            config            : [
                AdapterInterface::class => [
                    'driver'     => Mysql\Pdo\Driver::class,
                    'connection' => [
                        'hostname' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME'),
                        'port'     => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_PORT'),
                        'username' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
                        'password' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
                        'database' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
                    ],
                ],
                SchemaInterface::class  => [
                    'prefix'    => 'ww',
                    'separator' => '_',
                    'schema'    => 'webware',
                ],
            ],
            driverDependencies: new Mysql\ConfigProvider()->getDependencies(),
        );

        $this->assertTableRoundTrips(
            schemaFactory: $schemaFactory,
            adapter      : $adapter,
            schema       : 'webware',
            catalogSql   : "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'webware' AND TABLE_NAME = 'ww_session'",
        );
    }

    #[Test]
    #[Group('integration')]
    #[Group('integration-pgsql')]
    public function itCreatesPrefixedTableOnPgsql(): void
    {
        if (! getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL')) {
            self::markTestSkipped('PostgreSQL adapter is not configured.');
        }

        [$schemaFactory, $adapter] = $this->factoryAndAdapter(
            config            : [
                AdapterInterface::class => [
                    'driver'     => Pgsql\Pdo\Driver::class,
                    'connection' => [
                        'hostname' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_HOSTNAME'),
                        'port'     => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_PORT'),
                        'username' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_USERNAME'),
                        'password' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_PASSWORD'),
                        'database' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_PGSQL_DATABASE'),
                    ],
                ],
                SchemaInterface::class  => [
                    'prefix'    => 'ww',
                    'separator' => '_',
                    'schema'    => 'public',
                ],
            ],
            driverDependencies: new Pgsql\ConfigProvider()->getDependencies(),
        );

        $this->assertTableRoundTrips(
            schemaFactory: $schemaFactory,
            adapter      : $adapter,
            schema       : 'public',
            catalogSql   : "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'ww_session'",
        );
    }

    #[Test]
    #[Group('integration')]
    #[Group('integration-sqlite')]
    public function itCreatesPrefixedTableOnSqlite(): void
    {
        if (! getenv(name: 'TESTS_PHPDB_ADAPTER_SQLITE')) {
            self::markTestSkipped('SQLite adapter is not configured.');
        }

        [$schemaFactory, $adapter] = $this->factoryAndAdapter(
            config            : [
                AdapterInterface::class => [
                    'driver'     => Sqlite\Pdo\Driver::class,
                    'connection' => [
                        'dsn' => (string) getenv(name: 'TESTS_PHPDB_ADAPTER_SQLITE_DSN'),
                    ],
                ],
                SchemaInterface::class  => [
                    'prefix'    => 'ww',
                    'separator' => '_',
                ],
            ],
            driverDependencies: new Sqlite\ConfigProvider()->getDependencies(),
        );

        $this->assertTableRoundTrips(
            schemaFactory: $schemaFactory,
            adapter      : $adapter,
            schema       : null,
            catalogSql   : "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'ww_session'",
        );
    }

    private function assertTableRoundTrips(
        SchemaFactory $schemaFactory,
        AdapterInterface $adapter,
        ?string $schema,
        string $catalogSql,
    ): void {
        $identifier = $schemaFactory(IntegrationSchema::Session);

        self::assertSame('ww_session', $identifier->getTable());
        self::assertSame($schema, $identifier->getSchema());

        $createTable = new CreateTable(table: $identifier);
        $createTable->addColumn(new Column\Integer(
            name    : 'id',
            nullable: false,
        ));

        $adapter->executeQuery(
            sql: $createTable->getSqlString(adapterPlatform: $adapter->getPlatform()),
        );

        $rows = [];
        foreach ($adapter->executeQuery(sql: $catalogSql) as $row) {
            $rows[] = $row;
        }

        self::assertCount(1, $rows);

        $dropTable = new DropTable(table: $identifier);

        $adapter->executeQuery(
            sql: $dropTable->getSqlString(adapterPlatform: $adapter->getPlatform()),
        );
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $driverDependencies
     * @return array{0: SchemaFactory, 1: AdapterInterface}
     */
    private function factoryAndAdapter(array $config, array $driverDependencies): array
    {
        $container = new ServiceManager();
        $container->configure(new PhpDbConfigProvider()->getDependencies());
        $container->configure($driverDependencies);
        $container->configure(new WebwareProvider()->getDependencies());
        $container->setService('config', $config);

        return [
            $container->get(SchemaFactory::class),
            $container->get(AdapterInterface::class),
        ];
    }
}
