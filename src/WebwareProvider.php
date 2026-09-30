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

namespace PhpDb;

/**
 * Wiring entry point for the package.
 *
 * Declared under `extra.laminas.config-provider` in composer.json, so a consumer's
 * config aggregator merges this without any further registration.
 *
 * Named `WebwareProvider` rather than `ConfigProvider` because this package shadows the `PhpDb`
 * root namespace that `php-db/phpdb` owns: a second `PhpDb\ConfigProvider` would be an ambiguous
 * class resolution for Composer's optimized autoloader. Under this name both providers load, and a
 * consumer merging this one after PhpDb's layers the bridge wiring on top.
 */
final class WebwareProvider
{
    /** @return array<string, mixed> */
    private function getDependencies(): array
    {
        return [
            'factories' => [],
        ];
    }

    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }
}
