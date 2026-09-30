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

namespace PhpDb\Session;

use Closure;

use function filter_var;
use function ini_get;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;

/**
 * The php.ini session settings the persistence falls back to when configuration does not name one.
 *
 * @internal
 */
final readonly class IniDefaults
{
    /**
     * @param Closure(string): (string|false) $reader returns a php.ini value by name, or false when unset
     */
    public function __construct(
        private Closure $reader,
    ) {}

    public static function fromPhpIni(): self
    {
        return new self(reader: ini_get(...));
    }

    /**
     * @return array{
     *     gc_maxlifetime: int,
     *     cache_limiter: string,
     *     cache_expire: int,
     *     name: string,
     *     cookie_lifetime: int,
     *     cookie_path: string,
     *     cookie_domain: string,
     *     cookie_secure: bool,
     *     cookie_httponly: bool,
     *     cookie_samesite: string,
     * }
     */
    public function settings(): array
    {
        return [
            'gc_maxlifetime'  => $this->int(
                name   : 'session.gc_maxlifetime',
                default: 1440,
            ),
            'cache_limiter'   => $this->string(
                name   : 'session.cache_limiter',
                default: 'nocache',
            ),
            'cache_expire'    => $this->int(
                name   : 'session.cache_expire',
                default: 0,
            ),
            'name'            => $this->string(
                name   : 'session.name',
                default: 'PHPSESSID',
            ),
            'cookie_lifetime' => $this->int(
                name   : 'session.cookie_lifetime',
                default: 0,
            ),
            'cookie_path'     => $this->string(
                name   : 'session.cookie_path',
                default: '/',
            ),
            'cookie_domain'   => $this->string(
                name   : 'session.cookie_domain',
                default: '',
            ),
            'cookie_secure'   => $this->bool(name: 'session.cookie_secure'),
            'cookie_httponly' => $this->bool(name: 'session.cookie_httponly'),
            'cookie_samesite' => $this->string(
                name   : 'session.cookie_samesite',
                default: '',
            ),
        ];
    }

    private function bool(string $name): bool
    {
        return (bool) filter_var(
            value  : ($this->reader)($name),
            filter : FILTER_VALIDATE_BOOLEAN,
            options: FILTER_NULL_ON_FAILURE,
        );
    }

    private function int(string $name, int $default): int
    {
        $value = (int) ($this->reader)($name);

        return 0 === $value ? $default : $value;
    }

    private function string(string $name, string $default): string
    {
        $value = ($this->reader)($name);

        return false === $value || '' === $value ? $default : $value;
    }
}
