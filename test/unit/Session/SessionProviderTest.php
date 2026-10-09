<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session;

use Mezzio\Session\SessionPersistenceInterface;
use PhpDb\Session\Console\Container\InitDbCommandFactory;
use PhpDb\Session\Console\InitDbCommand;
use PhpDb\Session\Container\DbSessionHandlerFactory;
use PhpDb\Session\Container\PhpDbSessionPersistenceFactory;
use PhpDb\Session\DbSessionHandler;
use PhpDb\Session\PhpDbSessionPersistence;
use PhpDb\Session\SessionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Console\ConsoleInterface;

#[CoversClass(SessionProvider::class)]
#[CoversMethod(SessionProvider::class, '__invoke')]
#[CoversMethod(SessionProvider::class, 'getDependencies')]
final class SessionProviderTest extends TestCase
{
    #[Test]
    public function dependenciesAliasThePersistenceInterfaceToTheDatabasePersistence(): void
    {
        self::assertSame(
            [SessionPersistenceInterface::class => PhpDbSessionPersistence::class],
            new SessionProvider()->getDependencies()['aliases'],
        );
    }

    #[Test]
    public function dependenciesRegisterEveryFactory(): void
    {
        self::assertSame(
            [
                DbSessionHandler::class        => DbSessionHandlerFactory::class,
                PhpDbSessionPersistence::class => PhpDbSessionPersistenceFactory::class,
                InitDbCommand::class           => InitDbCommandFactory::class,
            ],
            new SessionProvider()->getDependencies()['factories'],
        );
    }

    #[Test]
    public function invokeCarriesTheDependencies(): void
    {
        $provider = new SessionProvider();

        self::assertSame($provider->getDependencies(), $provider()['dependencies']);
    }

    #[Test]
    public function invokeRegistersTheInitDbCommandForConsoleDiscovery(): void
    {
        $config = new SessionProvider()();

        self::assertSame(
            ['commands' => ['session:init-db' => InitDbCommand::class]],
            $config[ConsoleInterface::class],
        );
    }
}
