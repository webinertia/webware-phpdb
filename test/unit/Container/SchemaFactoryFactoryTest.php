<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Container;

use PhpDb\Container\SchemaFactoryFactory;
use PhpDb\SchemaInterface;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(SchemaFactoryFactory::class)]
#[CoversMethod(SchemaFactoryFactory::class, '__invoke')]
final class SchemaFactoryFactoryTest extends TestCase
{
    #[Test]
    public function itBuildsFactoryFromConfig(): void
    {
        $factory = new SchemaFactoryFactory()($this->container([
            SchemaInterface::class => [
                'prefix'        => 'ww',
                'separator'     => '__',
                'schema'        => 'public',
                'prefixes'      => ['session' => 'sess'],
                'schemas'       => ['session' => 'tenant'],
                'backup_prefix' => 'bck',
                'backup_schema' => 'backup',
            ],
        ]));

        self::assertSame('ww', $factory->getPrefix());
        self::assertSame('__', $factory->getSeparator());
        self::assertSame('public', $factory->getSchema());
        self::assertSame('bck', $factory->getBackupPrefix());
        self::assertSame('backup', $factory->getBackupSchema());
    }

    #[Test]
    public function itUsesDefaultsWhenConfigServiceAbsent(): void
    {
        $factory = new SchemaFactoryFactory()($this->container(null));

        self::assertNull($factory->getPrefix());
        self::assertNull($factory->getSchema());
        self::assertNull($factory->getBackupPrefix());
        self::assertNull($factory->getBackupSchema());
        self::assertSame(TableIdentifier::SEPARATOR, $factory->getSeparator());
    }

    #[Test]
    public function itUsesDefaultsWhenSchemaConfigAbsent(): void
    {
        $factory = new SchemaFactoryFactory()($this->container([]));

        self::assertNull($factory->getPrefix());
        self::assertNull($factory->getSchema());
        self::assertNull($factory->getBackupPrefix());
        self::assertNull($factory->getBackupSchema());
        self::assertSame(TableIdentifier::SEPARATOR, $factory->getSeparator());
    }

    /**
     * @param array<string, mixed>|null $config
     */
    private function container(?array $config): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(null !== $config);
        $container->method('get')->willReturn($config ?? []);

        return $container;
    }
}
