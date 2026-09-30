<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session\Console\Ddl\Column;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Sql\Platform;
use PhpDb\Session\Console\Ddl\Column\MediumText;
use PhpDb\Sql\Ddl\CreateTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MediumText::class)]
final class MediumTextTest extends TestCase
{
    #[Test]
    public function rendersAsMediumtext(): void
    {
        $table = new CreateTable(table: 'probe');
        $table->addColumn(new MediumText(
            name    : 'payload',
            nullable: false,
        ));

        $driver     = $this->createStub(DriverInterface::class);
        $connection = $this->createStub(ConnectionInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $platform = new Platform();
        $platform->setSubject($table);

        self::assertStringContainsString(
            '`payload` MEDIUMTEXT NOT NULL',
            $platform->getSqlString(new AdapterPlatform($driver)),
        );
    }
}
