<?php

declare(strict_types=1);

/**
 * This file is part of the Webware PhpDb package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebwareTestIntegration\PhpDb\Support;

use Override;
use PDO;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql;
use PhpDb\Session\Console\InitDbCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function getenv;
use function sprintf;

/**
 * A MySQL connection with a freshly created, empty session table, built by the command under test.
 */
class MysqlSessionTestCase extends TestCase
{
    protected AdapterInterface $adapter;

    protected PDO $pdo;

    protected function countRows(): int
    {
        return (int) $this->pdo->query(query: 'SELECT COUNT(*) FROM `session`')?->fetchColumn();
    }

    protected function expiresAtOf(string $id): ?string
    {
        $statement = $this->pdo->prepare(query: 'SELECT expires_at FROM `session` WHERE id = ?');
        $statement->execute([$id]);

        $expiresAt = $statement->fetchColumn();

        return false === $expiresAt ? null : (string) $expiresAt;
    }

    protected function insertRow(string $id, string $payload, string $expiresAt): void
    {
        $this->pdo->prepare(
            query: 'INSERT INTO `session` (id, payload, modified_at, expires_at) VALUES (?, ?, NOW(), ?)',
        )
            ->execute([$id, $payload, $expiresAt]);
    }

    protected function payloadOf(string $id): ?string
    {
        $statement = $this->pdo->prepare(query: 'SELECT payload FROM `session` WHERE id = ?');
        $statement->execute([$id]);

        $payload = $statement->fetchColumn();

        return false === $payload ? null : (string) $payload;
    }

    #[Override]
    protected function setUp(): void
    {
        if (! getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL')) {
            self::markTestSkipped('MySQL adapter is not configured.');
        }

        $hostname = (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME');
        $port     = (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_PORT');
        $username = (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_USERNAME');
        $password = (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD');
        $database = (string) getenv(name: 'TESTS_PHPDB_ADAPTER_MYSQL_DATABASE');

        $this->adapter = AdapterFactory::create(
            adapterConfig     : [
                'driver'     => Mysql\Pdo\Driver::class,
                'connection' => [
                    'hostname' => $hostname,
                    'port'     => $port,
                    'username' => $username,
                    'password' => $password,
                    'database' => $database,
                ],
            ],
            driverDependencies: new Mysql\ConfigProvider()->getDependencies(),
        );

        $this->pdo = new PDO(
            dsn     : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $hostname, $port, $database),
            username: $username,
            password: $password,
            options : [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        new CommandTester(new InitDbCommand(
            adapter: $this->adapter,
            table  : 'session',
        ))->execute(['--drop' => true]);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->pdo->exec(statement: 'DROP TABLE IF EXISTS `session`');
    }
}
