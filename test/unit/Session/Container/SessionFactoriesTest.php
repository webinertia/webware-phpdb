<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\Container\DbSessionHandlerFactory;
use PhpDb\Session\Container\PhpDbSessionPersistenceFactory;
use PhpDb\Session\DbSessionHandler;
use PhpDb\Session\PhpDbSessionPersistence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_key_exists;

#[CoversClass(DbSessionHandlerFactory::class)]
#[CoversClass(PhpDbSessionPersistenceFactory::class)]
#[CoversMethod(DbSessionHandlerFactory::class, '__invoke')]
#[CoversMethod(PhpDbSessionPersistenceFactory::class, '__invoke')]
final class SessionFactoriesTest extends TestCase
{
    #[Test]
    public function handlerFactoryBuildsTheHandler(): void
    {
        self::assertInstanceOf(
            DbSessionHandler::class,
            new DbSessionHandlerFactory()($this->container(services: [])),
        );
    }

    #[Test]
    public function persistenceFactoryBuildsThePersistenceFromTheConfigService(): void
    {
        self::assertInstanceOf(
            PhpDbSessionPersistence::class,
            new PhpDbSessionPersistenceFactory()($this->container(services: [
                'config' => ['session' => ['name' => 'WEBWARE']],
            ])),
        );
    }

    #[Test]
    public function persistenceFactoryBuildsThePersistenceWithoutAConfigService(): void
    {
        self::assertInstanceOf(
            PhpDbSessionPersistence::class,
            new PhpDbSessionPersistenceFactory()($this->container(services: [])),
        );
    }

    /**
     * @param array<string, mixed> $services
     */
    private function container(array $services): ContainerInterface
    {
        $services[AdapterInterface::class] = $this->createStub(AdapterInterface::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturnCallback(
                static fn(string $id): bool => array_key_exists($id, $services),
            );
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => $services[$id],
            );

        return $container;
    }
}
