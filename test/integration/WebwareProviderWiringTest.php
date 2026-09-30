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

namespace WebwareTestIntegration\PhpDb;

use JsonException;
use PhpDb\Session\SessionProvider;
use PhpDb\WebwareProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Guards the wiring contract between composer.json and this package's namespace.
 *
 * Renaming the namespace but missing `extra.laminas.config-provider` breaks every
 * consumer silently, so the two are asserted to agree.
 */
#[CoversClass(WebwareProvider::class)]
final class WebwareProviderWiringTest extends TestCase
{
    /**
     * @throws JsonException
     */
    #[Test]
    public function composerDeclaresThisPackageAsItsConfigProvider(): void
    {
        $json = (string) file_get_contents(__DIR__ . '/../../composer.json');

        /** @var array{extra: array{laminas: array{'config-provider': list<string>}}} $composer */
        $composer = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(
            [WebwareProvider::class, SessionProvider::class],
            $composer['extra']['laminas']['config-provider'],
        );
    }
}
