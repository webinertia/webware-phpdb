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

namespace PhpDb\Session\Console\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\Console\InitDbCommand;
use PhpDb\Session\SessionTable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Exception\LogicException;

final readonly class InitDbCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws LogicException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): InitDbCommand
    {
        return new InitDbCommand(
            adapter: $container->get(AdapterInterface::class),
            table  : SessionTable::Session->value,
        );
    }
}
