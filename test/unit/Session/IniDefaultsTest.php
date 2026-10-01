<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session;

use Closure;
use PhpDb\Session\IniDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IniDefaults::class)]
#[CoversMethod(IniDefaults::class, 'settings')]
#[CoversMethod(IniDefaults::class, 'fromPhpIni')]
final class IniDefaultsTest extends TestCase
{
    #[Test]
    public function fromPhpIniReadsTheRunningConfiguration(): void
    {
        $settings = IniDefaults::fromPhpIni()->settings();

        self::assertGreaterThan(0, $settings['gc_maxlifetime']);
        self::assertNotSame('', $settings['name']);
    }

    #[Test]
    public function settingsFallBackWhenIniValuesAreEmpty(): void
    {
        $defaults = new IniDefaults(reader: $this->reader(values: [
            'session.gc_maxlifetime'  => '0',
            'session.cache_limiter'   => '',
            'session.cache_expire'    => '0',
            'session.name'            => '',
            'session.cookie_lifetime' => '0',
            'session.cookie_path'     => '',
            'session.cookie_domain'   => '',
            'session.cookie_secure'   => '0',
            'session.cookie_httponly' => '0',
            'session.cookie_samesite' => '',
        ]));

        self::assertSame(
            [
                'gc_maxlifetime'  => 1440,
                'cache_limiter'   => 'nocache',
                'cache_expire'    => 0,
                'name'            => 'PHPSESSID',
                'cookie_lifetime' => 0,
                'cookie_path'     => '/',
                'cookie_domain'   => '',
                'cookie_secure'   => false,
                'cookie_httponly' => false,
                'cookie_samesite' => '',
            ],
            $defaults->settings(),
        );
    }

    #[Test]
    public function settingsFallBackWhenIniValuesAreUnset(): void
    {
        $settings = new IniDefaults(reader: $this->reader(values: []))->settings();

        self::assertSame(1440, $settings['gc_maxlifetime']);
        self::assertSame('PHPSESSID', $settings['name']);
        self::assertFalse($settings['cookie_secure']);
    }

    #[Test]
    public function settingsReadEachIniValue(): void
    {
        $defaults = new IniDefaults(reader: $this->reader(values: [
            'session.gc_maxlifetime'  => '600',
            'session.cache_limiter'   => 'private',
            'session.cache_expire'    => '30',
            'session.name'            => 'WEBWARE',
            'session.cookie_lifetime' => '120',
            'session.cookie_path'     => '/app',
            'session.cookie_domain'   => 'example.test',
            'session.cookie_secure'   => '1',
            'session.cookie_httponly' => '1',
            'session.cookie_samesite' => 'Strict',
        ]));

        self::assertSame(
            [
                'gc_maxlifetime'  => 600,
                'cache_limiter'   => 'private',
                'cache_expire'    => 30,
                'name'            => 'WEBWARE',
                'cookie_lifetime' => 120,
                'cookie_path'     => '/app',
                'cookie_domain'   => 'example.test',
                'cookie_secure'   => true,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict',
            ],
            $defaults->settings(),
        );
    }

    /**
     * @param array<string, string> $values
     */
    private function reader(array $values): Closure
    {
        return static fn(string $name): string|false => $values[$name] ?? false;
    }
}
