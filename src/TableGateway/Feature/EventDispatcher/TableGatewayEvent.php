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

namespace PhpDb\TableGateway\Feature\EventDispatcher;

use Webware\Event\Event;

/**
 * The event php-db's table gateway lifecycle hooks are published as.
 *
 * The name is the hook being run (`preSelect`, `postInsert`, …), taken from
 * {@see \PhpDb\TableGateway\Feature\EventFeatureEventsInterface}; the target is the table gateway
 * the hook belongs to; the params are the hook's own arguments, keyed as php-db's
 * {@see \PhpDb\TableGateway\Feature\EventFeature} keys them.
 *
 * @api
 */
final class TableGatewayEvent extends Event
{
    /**
     * @param array<array-key, mixed> $params
     */
    public function __construct(
        ?string $name = null,
        ?object $target = null,
        array $params = [],
    ) {
        parent::__construct(
            name  : $name,
            target: $target,
            params: $params,
        );
    }
}
