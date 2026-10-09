<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb;

use PhpDb\SchemaInterface;

enum EmptySchema: string implements SchemaInterface
{
    case Session = '';
}
