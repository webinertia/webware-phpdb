<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\DbSessionHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

#[CoversClass(DbSessionHandler::class)]
#[CoversMethod(DbSessionHandler::class, 'create_sid')]
#[CoversMethod(DbSessionHandler::class, 'validateId')]
final class DbSessionHandlerTest extends TestCase
{
    #[Test]
    public function createSidReturnsANativeShapedIdentifier(): void
    {
        $id = $this->handler()->create_sid();

        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $id);
        self::assertNotSame($id, $this->handler()->create_sid());
    }

    /**
     * PHP 8.6 warns when a SessionHandlerInterface implementation leaves out
     * create_sid() and validateId(); the warnings are fatal under phpunit's
     * failOnWarning. Naming both interfaces keeps the class honest.
     */
    #[Test]
    public function implementsTheSessionIdAndTimestampInterfaces(): void
    {
        $handler = $this->handler();

        self::assertInstanceOf(SessionHandlerInterface::class, $handler);
        self::assertInstanceOf(SessionIdInterface::class, $handler);
        self::assertInstanceOf(SessionUpdateTimestampHandlerInterface::class, $handler);
    }

    #[Test]
    public function validateIdIsDeclaredWithTheInterfaceReturnType(): void
    {
        $method = new ReflectionMethod(DbSessionHandler::class, 'validateId');

        self::assertSame('bool', (string) $method->getReturnType());
        self::assertSame(1, $method->getNumberOfParameters());
    }

    private function handler(): DbSessionHandler
    {
        return new DbSessionHandler($this->createStub(AdapterInterface::class));
    }
}
