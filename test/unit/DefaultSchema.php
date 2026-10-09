<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb;

use PhpDb\SchemaInterface;

enum DefaultSchema: string implements SchemaInterface
{
    case Session = 'session';
}
