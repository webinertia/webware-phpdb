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

namespace PhpDb\Session;

use Mezzio\Session\SessionPersistenceInterface;
use PhpDb\Session\Console\Container\InitDbCommandFactory;
use PhpDb\Session\Console\InitDbCommand;
use PhpDb\Session\Container\DbSessionHandlerFactory;
use PhpDb\Session\Container\PhpDbSessionPersistenceFactory;
use Webware\Console\ConsoleInterface;

/**
 * Wiring for the database-backed session persistence.
 *
 * Named `SessionProvider` rather than `ConfigProvider` so it never collides with the
 * `PhpDb\Session\ConfigProvider` of php-db/phpdb-mezzio-session, whose namespace this package
 * presents.
 *
 * @phpstan-type SessionDependencies array{
 *     aliases: array<interface-string, class-string>,
 *     factories: array<class-string, class-string>,
 * }
 */
final class SessionProvider
{
    /** @return SessionDependencies */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                // PhpDbSessionPersistence is the default: async-safe, no ext-session.
                SessionPersistenceInterface::class => PhpDbSessionPersistence::class,
            ],
            'factories' => [
                DbSessionHandler::class        => DbSessionHandlerFactory::class,
                PhpDbSessionPersistence::class => PhpDbSessionPersistenceFactory::class,
                InitDbCommand::class           => InitDbCommandFactory::class,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'dependencies'          => $this->getDependencies(),
            ConsoleInterface::class => [
                'commands' => [
                    'session:init-db' => InitDbCommand::class,
                ],
            ],
        ];
    }
}
