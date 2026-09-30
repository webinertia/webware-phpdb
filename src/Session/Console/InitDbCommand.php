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

namespace PhpDb\Session\Console;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\Console\Schema\SessionSchema;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Exception\InvalidArgumentException as SqlInvalidArgumentException;
use PhpDb\Sql\Sql;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException as ConsoleLogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name       : 'session:init-db',
    description: 'Create the table the database session persistence writes to',
)]
final class InitDbCommand extends Command
{
    private readonly SessionSchema $schema;

    /**
     * @throws ConsoleLogicException
     */
    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly string $table,
    ) {
        $this->schema = new SessionSchema();

        parent::__construct();
    }

    /**
     * @throws ConsoleInvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            name       : 'drop',
            mode       : InputOption::VALUE_NONE,
            description: 'Drop the session table before recreating it',
        );
    }

    /**
     * @throws ConsoleInvalidArgumentException
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = new Sql($this->adapter);

        if (true === $input->getOption('drop')) {
            $output->writeln("Dropping table {$this->table}...");
            $this->executeDdl($sql, $this->schema->dropTable($this->table));
        }

        $output->writeln("Creating table {$this->table}...");
        $this->executeDdl($sql, $this->schema->sessionTable($this->table));

        $output->writeln('Session table initialized.');

        return Command::SUCCESS;
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    private function executeDdl(Sql $sql, CreateTable|DropTable $ddl): void
    {
        $this->adapter->query(
            $sql->buildSqlString($ddl),
            AdapterInterface::QUERY_MODE_EXECUTE,
        );
    }
}
