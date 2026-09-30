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

use PhpDb\WebwareProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebwareProvider::class)]
#[CoversMethod(WebwareProvider::class, '__invoke')]
final class WebwareProviderTest extends TestCase
{
    #[Test]
    public function providesAnEmptyDependencyFactoryMap(): void
    {
        $expected = [
            'dependencies' => [
                'factories' => [],
            ],
        ];

        self::assertSame($expected, new WebwareProvider()->__invoke());
    }
}
