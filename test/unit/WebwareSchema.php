<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb;

use PhpDb\SchemaInterface;

enum WebwareSchema: string implements SchemaInterface
{
    case Session = 'session';

    public const string NAME = 'webware';
}
