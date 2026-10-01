<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session\Sql;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Session\Sql\Insert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function preg_replace;

#[CoversClass(Insert::class)]
final class InsertTest extends TestCase
{
    #[Test]
    public function rendersAnUpsertOverTheThreeMutableColumns(): void
    {
        $driver     = $this->createStub(DriverInterface::class);
        $connection = $this->createStub(ConnectionInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $insert = new Insert(table: 'session')->values([
            'id'          => 'abc',
            'payload'     => 'data',
            'expires_at'  => '2026-01-01 00:00:00',
            'modified_at' => '2026-01-01 00:00:00',
        ]);

        $sql = (string) preg_replace(
            pattern    : '/\s+/',
            replacement: ' ',
            subject    : $insert->getSqlString(new AdapterPlatform($driver)),
        );

        self::assertStringStartsWith('INSERT INTO `session` (', $sql);
        self::assertStringEndsWith(
            ' ON DUPLICATE KEY UPDATE payload = VALUES(payload), expires_at = VALUES(expires_at), modified_at = VALUES(modified_at)',
            $sql,
        );
    }
}
