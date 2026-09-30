<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session\Console;

use PhpDb\Session\Console\InitDbCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use WebwareTest\PhpDb\Support\DdlAdapterTrait;

#[CoversClass(InitDbCommand::class)]
#[CoversMethod(InitDbCommand::class, 'configure')]
#[CoversMethod(InitDbCommand::class, 'execute')]
final class InitDbCommandTest extends TestCase
{
    use DdlAdapterTrait;

    #[Test]
    public function commandIsNamedSessionInitDb(): void
    {
        $executed = [];
        $command  = new InitDbCommand(
            adapter: $this->ddlAdapter($executed),
            table  : 'session',
        );

        self::assertSame('session:init-db', $command->getName());
        self::assertTrue($command->getDefinition()->hasOption('drop'));
    }

    #[Test]
    public function executeCreatesTheSessionTable(): void
    {
        $executed = [];
        $tester   = new CommandTester(new InitDbCommand(
            adapter: $this->ddlAdapter($executed),
            table  : 'session',
        ));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertCount(1, $executed);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS `session`', $executed[0]);
        self::assertStringContainsString('Creating table session...', $tester->getDisplay());
        self::assertStringContainsString('Session table initialized.', $tester->getDisplay());
        self::assertStringNotContainsString('Dropping table', $tester->getDisplay());
    }

    #[Test]
    public function executeDropsTheTableFirstWhenRequested(): void
    {
        $executed = [];
        $tester   = new CommandTester(new InitDbCommand(
            adapter: $this->ddlAdapter($executed),
            table  : 'session',
        ));
        $tester->execute(['--drop' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertCount(2, $executed);
        self::assertStringContainsString('DROP TABLE IF EXISTS `session`', $executed[0]);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS `session`', $executed[1]);
        self::assertStringContainsString('Dropping table session...', $tester->getDisplay());
    }

    #[Test]
    public function executeUsesTheConfiguredTableName(): void
    {
        $executed = [];

        new CommandTester(new InitDbCommand(
            adapter: $this->ddlAdapter($executed),
            table  : 'app_session',
        ))->execute([]);

        self::assertCount(1, $executed);
        self::assertStringContainsString('`app_session`', $executed[0]);
    }
}
