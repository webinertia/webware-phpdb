<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb\Support;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ConfigProvider as PhpDbConfigProvider;

/**
 * Builds a phpdb adapter from connection config plus a single driver's
 * dependencies. Each driver gets its own isolated ServiceManager, since phpdb
 * supports only one driver package per container.
 */
final class AdapterFactory
{
    /**
     * @param array<string, mixed> $adapterConfig
     * @param array<string, mixed> $driverDependencies
     */
    public static function create(array $adapterConfig, array $driverDependencies): AdapterInterface
    {
        $container = new ServiceManager();
        $container->configure(new PhpDbConfigProvider()->getDependencies());
        $container->configure($driverDependencies);
        $container->setService('config', [AdapterInterface::class => $adapterConfig]);

        return $container->get(AdapterInterface::class);
    }
}
