<?php

declare(strict_types=1);

namespace PhpDb;

/**
 * Marker contract for schema enums.
 *
 * A string-backed enum whose case values are unprefixed table names. Override
 * the {@see self::NAME} constant to declare the schema identifier shared by
 * every table in the enum, or leave it as an empty string for the connection
 * default schema.
 *
 * @api
 */
interface SchemaInterface
{
    /**
     * The schema identifier shared by every table in the enum.
     *
     * An empty string means "no explicit schema" (use the connection default).
     */
    public const string NAME = '';
}
