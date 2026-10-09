<?php

declare(strict_types=1);

namespace WebwareTestIntegration\PhpDb;

use PhpDb\SchemaInterface;

enum IntegrationSchema: string implements SchemaInterface
{
    case Session = 'session';
}
