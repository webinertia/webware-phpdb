<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb\FixtureLoader;

interface FixtureLoaderInterface
{
    public function createDatabase(): void;

    public function dropDatabase(): void;
}
