<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb;

use PhpDb\SchemaInterface;

enum AclSchema: string implements SchemaInterface
{
    case Role = 'acl_role';
}
