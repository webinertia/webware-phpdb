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

namespace WebwareTestIntegration\PhpDb\Session;

use PhpDb\Session\DbSessionHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use WebwareTestIntegration\PhpDb\Support\MysqlSessionTestCase;

use function date;
use function ini_get;
use function strtotime;
use function time;

#[CoversClass(DbSessionHandler::class)]
#[CoversMethod(DbSessionHandler::class, 'open')]
#[CoversMethod(DbSessionHandler::class, 'close')]
#[CoversMethod(DbSessionHandler::class, 'read')]
#[CoversMethod(DbSessionHandler::class, 'write')]
#[CoversMethod(DbSessionHandler::class, 'destroy')]
#[CoversMethod(DbSessionHandler::class, 'gc')]
#[RequiresPhpExtension('pdo_mysql')]
#[Group('integration')]
#[Group('integration-mysql')]
final class DbSessionHandlerTest extends MysqlSessionTestCase
{
    #[Test]
    public function destroyRemovesTheSession(): void
    {
        $handler = new DbSessionHandler($this->adapter);
        $handler->write('abc', 'data');
        $handler->write('keep', 'data');

        self::assertTrue($handler->destroy('abc'));
        self::assertFalse($handler->read('abc'));
        self::assertSame('data', $handler->read('keep'));
    }

    #[Test]
    public function gcRemovesOnlyExpiredSessionsAndReportsHowMany(): void
    {
        $past = date(
            format   : 'Y-m-d H:i:s',
            timestamp: time() - 60,
        );
        $this->insertRow(
            id       : 'stale-1',
            payload  : 'old',
            expiresAt: $past,
        );
        $this->insertRow(
            id       : 'stale-2',
            payload  : 'old',
            expiresAt: $past,
        );

        $handler = new DbSessionHandler($this->adapter);
        $handler->write('live', 'data');

        self::assertSame(2, $handler->gc(1440));
        self::assertSame(1, $this->countRows());
        self::assertSame('data', $handler->read('live'));
    }

    #[Test]
    public function openAndCloseAlwaysSucceed(): void
    {
        $handler = new DbSessionHandler($this->adapter);

        self::assertTrue($handler->open('', 'PHPSESSID'));
        self::assertTrue($handler->close());
    }

    #[Test]
    public function readReturnsFalseForAnExpiredSession(): void
    {
        $this->insertRow(
            id       : 'stale',
            payload  : 'old',
            expiresAt: date(
                format   : 'Y-m-d H:i:s',
                timestamp: time() - 60,
            ),
        );

        self::assertFalse(new DbSessionHandler($this->adapter)->read('stale'));
    }

    #[Test]
    public function readReturnsFalseForAnUnknownIdentifier(): void
    {
        self::assertFalse(new DbSessionHandler($this->adapter)->read('missing'));
    }

    #[Test]
    public function readReturnsWhatWasWritten(): void
    {
        $handler = new DbSessionHandler($this->adapter);

        self::assertTrue($handler->write('abc', 'user|s:4:"joey";'));
        self::assertSame('user|s:4:"joey";', $handler->read('abc'));
    }

    #[Test]
    public function writeExpiresTheSessionAfterTheConfiguredLifetime(): void
    {
        $lifetime = (int) ini_get(option: 'session.gc_maxlifetime');

        $before = time();
        new DbSessionHandler($this->adapter)->write('abc', 'data');
        $after = time();

        $expiresAt = (int) strtotime((string) $this->expiresAtOf('abc'));

        self::assertGreaterThan(0, $lifetime);
        self::assertGreaterThanOrEqual($before + $lifetime, $expiresAt);
        self::assertLessThanOrEqual($after + $lifetime, $expiresAt);
    }

    #[Test]
    public function writingTheSameIdentifierTwiceReplacesThePayload(): void
    {
        $handler = new DbSessionHandler($this->adapter);

        $handler->write('abc', 'first');
        $handler->write('abc', 'second');

        self::assertSame('second', $handler->read('abc'));
        self::assertSame(1, $this->countRows());
    }
}
