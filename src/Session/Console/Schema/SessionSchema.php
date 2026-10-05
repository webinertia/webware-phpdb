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

namespace PhpDb\Session\Console\Schema;

use PhpDb\Session\Console\Ddl\Column\MediumText;
use PhpDb\Sql\Ddl\Column\Datetime;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Ddl\Index\Index;
use PhpDb\Sql\Literal;

/**
 * Builds the table {@see \PhpDb\Session\DbSessionHandler} and
 * {@see \PhpDb\Session\PhpDbSessionPersistence} read and write.
 *
 * The columns are the four both classes use. modified_at carries no database default because
 * every write supplies it.
 *
 * @internal
 */
final class SessionSchema
{
    public function dropTable(string $table): DropTable
    {
        return new DropTable(table: $table)->ifExists();
    }

    public function sessionTable(string $table): CreateTable
    {
        $createTable = new CreateTable(table: $table)->ifNotExists();

        $createTable->addColumn(new Varchar(
            name    : 'id',
            length  : 64,
            nullable: false,
        ));
        $createTable->addColumn(new MediumText(
            name    : 'payload',
            nullable: false,
        ));
        $createTable->addColumn(new Datetime(
            name    : 'modified_at',
            nullable: false,
        ));
        $createTable->addColumn(new Datetime(
            name    : 'expires_at',
            nullable: false,
        ));

        $createTable->addConstraint(new PrimaryKey(columns: 'id'));
        $createTable->addConstraint(new Index(
            columns: 'expires_at',
            name   : 'idx_expires_at',
        ));

        $createTable->setOptions(options: [
            'engine'          => new Literal(literal: 'InnoDB'),
            'default charset' => new Literal(literal: 'utf8mb4'),
            'collate'         => new Literal(literal: 'utf8mb4_0900_ai_ci'),
        ]);

        return $createTable;
    }
}
