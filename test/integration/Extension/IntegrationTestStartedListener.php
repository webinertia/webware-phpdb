<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb\Extension;

use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use WebwareTestIntegration\PhpDb\FixtureLoader\FixtureLoaderInterface;

final readonly class IntegrationTestStartedListener implements StartedSubscriber
{
    /**
     * @param FixtureLoaderInterface[] $fixtureLoaders
     */
    public function __construct(
        private array $fixtureLoaders,
    ) {}

    public function notify(Started $event): void
    {
        if ('integration test' !== $event->testSuite()->name() || [] === $this->fixtureLoaders) {
            return;
        }

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->createDatabase();
        }
    }
}
