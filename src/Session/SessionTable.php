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

use PhpDb\SchemaInterface;

/**
 * The session table, named once so the handler, the persistence and the schema cannot drift apart.
 */
enum SessionTable: string implements SchemaInterface
{
    case Session = 'session';
}
