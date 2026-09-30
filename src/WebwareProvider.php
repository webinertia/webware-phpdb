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

use PhpDb\Container\SchemaFactoryFactory;
use PhpDb\Sql\TableIdentifier;

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
 *
 * Also carries the schema abstraction's own configuration: the layering {@see SchemaFactory}
 * applies is read from the config under {@see SchemaInterface::class}, and this package owns the
 * key names for it.
 *
 * @type SchemaConfig = array{
 *      prefix?: non-empty-string,
 *      separator?: non-empty-string,
 *      schema?: non-empty-string,
 *      prefixes?: array<string, non-empty-string>,
 *      schemas?: array<string, non-empty-string>,
 *      backup_prefix?: non-empty-string,
 *      backup_schema?: non-empty-string,
 * }
 */
final class WebwareProvider
{
    public const string PREFIX_KEY        = 'prefix';
    public const string SEPARATOR_KEY     = 'separator';
    public const string SCHEMA_KEY        = 'schema';
    public const string PREFIXES_KEY      = 'prefixes';
    public const string SCHEMAS_KEY       = 'schemas';
    public const string BACKUP_PREFIX_KEY = 'backup_prefix';
    public const string BACKUP_SCHEMA_KEY = 'backup_schema';

    /** @return array<string, mixed> */
    private function getDependencies(): array
    {
        return [
            'factories' => [
                SchemaFactory::class => SchemaFactoryFactory::class,
            ],
        ];
    }

    /** @return SchemaConfig */
    private function getSchemaConfig(): array
    {
        return [
            self::SEPARATOR_KEY => TableIdentifier::SEPARATOR,
        ];
    }

    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'dependencies'         => $this->getDependencies(),
            SchemaInterface::class => $this->getSchemaConfig(),
        ];
    }
}
