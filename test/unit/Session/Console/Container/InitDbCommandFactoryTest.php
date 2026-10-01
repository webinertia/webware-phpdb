<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session\Console\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\Console\Container\InitDbCommandFactory;
use PhpDb\Session\Console\InitDbCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use WebwareTest\PhpDb\Support\DdlAdapterTrait;

#[CoversClass(InitDbCommandFactory::class)]
#[CoversMethod(InitDbCommandFactory::class, '__invoke')]
final class InitDbCommandFactoryTest extends TestCase
{
    use DdlAdapterTrait;

    #[Test]
    public function invokeBuildsACommandForTheSessionTable(): void
    {
        $executed  = [];
        $adapter   = $this->ddlAdapter($executed);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): AdapterInterface => $adapter,
            );

        $command = (new InitDbCommandFactory())($container);

        self::assertInstanceOf(InitDbCommand::class, $command);

        new CommandTester($command)->execute([]);

        self::assertStringContainsString('`session`', $executed[0]);
    }
}
