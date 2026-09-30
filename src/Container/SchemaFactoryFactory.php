<?php

declare(strict_types=1);

namespace PhpDb\Container;

use PhpDb\SchemaFactory;
use PhpDb\SchemaInterface;
use PhpDb\WebwareProvider;
use Psl\Type;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @import-type SchemaConfig from WebwareProvider
 */
final class SchemaFactoryFactory
{
    /**
     * @throws Type\Exception\AssertException If the configured schema config does not match the expected shape.
     * @throws NotFoundExceptionInterface If the config service cannot be resolved.
     * @throws ContainerExceptionInterface If retrieving the config service fails.
     */
    public function __invoke(ContainerInterface $container): SchemaFactory
    {
        /** @var array<string, mixed> $config */
        $config = $container->has('config') ? $container->get('config') ?? [] : [];

        /** @var SchemaConfig $schemaConfig */
        $schemaConfig = $config[SchemaInterface::class] ?? [];

        return new SchemaFactory($schemaConfig);
    }
}
