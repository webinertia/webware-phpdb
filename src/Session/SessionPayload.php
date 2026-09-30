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

use function is_array;
use function serialize;
use function unserialize;

/**
 * Encodes session data for the payload column and decodes it back.
 *
 * Objects stored in a session must implement __serialize()/__unserialize() to round-trip.
 *
 * @internal
 */
final class SessionPayload
{
    /**
     * A payload that does not decode to an array yields an empty session.
     *
     * @return array<string, mixed>
     */
    public static function decode(string $payload): array
    {
        $data = unserialize(data: $payload);

        /** @var array<string, mixed> */
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function encode(array $data): string
    {
        return serialize(value: $data);
    }
}
