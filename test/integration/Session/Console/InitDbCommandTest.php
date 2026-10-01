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

namespace WebwareTestIntegration\PhpDb\Session\Console;

use PDO;
use PhpDb\Session\Console\InitDbCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use WebwareTestIntegration\PhpDb\Support\MysqlSessionTestCase;

/**
 * The expected columns are the ones of the `session` table in the IMS schema this table was taken
 * from (data/schema/015_session.sql), less its database-side timestamp defaults.
 */
#[CoversClass(InitDbCommand::class)]
#[CoversMethod(InitDbCommand::class, 'execute')]
#[RequiresPhpExtension('pdo_mysql')]
#[Group('integration')]
#[Group('integration-mysql')]
final class InitDbCommandTest extends MysqlSessionTestCase
{
    #[Test]
    public function createsTheSessionTableWithTheImsColumns(): void
    {
        $statement = $this->pdo->query(
            query: 'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS'
                . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session' ORDER BY ORDINAL_POSITION",
        );

        self::assertNotFalse($statement);
        self::assertSame(
            [
                ['COLUMN_NAME' => 'id', 'COLUMN_TYPE' => 'varchar(64)', 'IS_NULLABLE' => 'NO', 'COLUMN_KEY' => 'PRI'],
                ['COLUMN_NAME' => 'payload', 'COLUMN_TYPE' => 'mediumtext', 'IS_NULLABLE' => 'NO', 'COLUMN_KEY' => ''],
                [
                    'COLUMN_NAME' => 'modified_at',
                    'COLUMN_TYPE' => 'datetime',
                    'IS_NULLABLE' => 'NO',
                    'COLUMN_KEY'  => '',
                ],
                [
                    'COLUMN_NAME' => 'expires_at',
                    'COLUMN_TYPE' => 'datetime',
                    'IS_NULLABLE' => 'NO',
                    'COLUMN_KEY'  => 'MUL',
                ],
            ],
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    #[Test]
    public function indexesTheExpiryColumn(): void
    {
        $statement = $this->pdo->query(
            query: 'SELECT INDEX_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.STATISTICS'
                . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session' AND INDEX_NAME <> 'PRIMARY'",
        );

        self::assertNotFalse($statement);
        self::assertSame(
            [['INDEX_NAME' => 'idx_expires_at', 'COLUMN_NAME' => 'expires_at']],
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    #[Test]
    public function runningWithDropEmptiesTheTable(): void
    {
        $this->insertRow(
            id       : 'gone',
            payload  : 'data',
            expiresAt: '2999-01-01 00:00:00',
        );

        new CommandTester(new InitDbCommand(
            adapter: $this->adapter,
            table  : 'session',
        ))->execute(['--drop' => true]);

        self::assertSame(0, $this->countRows());
    }

    #[Test]
    public function runningWithoutDropKeepsExistingRows(): void
    {
        $this->insertRow(
            id       : 'kept',
            payload  : 'data',
            expiresAt: '2999-01-01 00:00:00',
        );

        $tester = new CommandTester(new InitDbCommand(
            adapter: $this->adapter,
            table  : 'session',
        ));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(1, $this->countRows());
    }
}
