<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb;

use PhpDb\SchemaInterface;

enum IntSchema: int implements SchemaInterface
{
    case Role = 1;
}
