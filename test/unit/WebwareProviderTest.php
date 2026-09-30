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

namespace WebwareTest\PhpDb;

use PhpDb\Container\SchemaFactoryFactory;
use PhpDb\SchemaFactory;
use PhpDb\SchemaInterface;
use PhpDb\Sql\TableIdentifier;
use PhpDb\WebwareProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebwareProvider::class)]
#[CoversMethod(WebwareProvider::class, '__invoke')]
#[CoversMethod(WebwareProvider::class, 'getDependencies')]
#[CoversMethod(WebwareProvider::class, 'getSchemaConfig')]
final class WebwareProviderTest extends TestCase
{
    #[Test]
    public function registersTheSchemaFactoryAndItsConfiguration(): void
    {
        $expected = [
            'dependencies'         => [
                'factories' => [
                    SchemaFactory::class => SchemaFactoryFactory::class,
                ],
            ],
            SchemaInterface::class => [
                WebwareProvider::SEPARATOR_KEY => TableIdentifier::SEPARATOR,
            ],
        ];

        self::assertSame($expected, new WebwareProvider()->__invoke());
    }
}
