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

namespace PhpDb\Session\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\PhpDbSessionPersistence;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @phpstan-import-type SessionSettings from PhpDbSessionPersistence
 */
final class PhpDbSessionPersistenceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): PhpDbSessionPersistence
    {
        /** @var array{session?: SessionSettings} $config */
        $config = $container->has('config') ? $container->get('config') : [];

        return new PhpDbSessionPersistence(
            adapter      : $container->get(AdapterInterface::class),
            sessionConfig: $config['session'] ?? [],
        );
    }
}
