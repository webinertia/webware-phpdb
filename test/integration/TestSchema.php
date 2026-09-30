<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb;

use PhpDb\SchemaInterface;

enum TestSchema: string implements SchemaInterface
{
    case Roles = 'core_role';
}
