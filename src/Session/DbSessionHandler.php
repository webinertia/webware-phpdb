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

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\Sql\Insert;
use PhpDb\Sql\Exception\InvalidArgumentException as SqlInvalidArgumentException;
use PhpDb\Sql\Sql;
use SessionHandlerInterface;

use function date;
use function ini_get;
use function is_array;
use function is_string;
use function time;

final class DbSessionHandler implements SessionHandlerInterface
{
    private const string TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    private readonly Sql $sql;

    public function __construct(AdapterInterface $adapter)
    {
        $this->sql = new Sql(
            adapter: $adapter,
            table  : SessionTable::Session->value,
        );
    }

    #[Override]
    public function close(): bool
    {
        return true;
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    public function destroy(string $id): bool
    {
        $delete = $this->sql->delete()->where(['id' => $id]);

        $this->sql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    public function gc(int $maxLifetime): int|false
    {
        $cutoff = date(format: self::TIMESTAMP_FORMAT);

        $delete = $this->sql->delete();
        $delete->where->lessThan('expires_at', $cutoff);

        $result = $this->sql->prepareStatementForSqlObject($delete)->execute();

        return null === $result ? false : $result->getAffectedRows();
    }

    #[Override]
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    public function read(string $id): string|false
    {
        $select = $this->sql->select()
            ->columns(['payload'])
            ->where(['id' => $id]);
        $select->where->greaterThan('expires_at', date(format: self::TIMESTAMP_FORMAT));

        $row     = $this->sql->prepareStatementForSqlObject($select)->execute()?->current();
        $payload = is_array($row) ? $row['payload'] ?? false : false;

        return is_string($payload) ? $payload : false;
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    public function write(string $id, string $data): bool
    {
        $maxLifetime = (int) ini_get(option: 'session.gc_maxlifetime');
        $expiresAt   = date(
            format   : self::TIMESTAMP_FORMAT,
            timestamp: time() + $maxLifetime,
        );
        $now = date(format: self::TIMESTAMP_FORMAT);

        $this->sql->prepareStatementForSqlObject(
            new Insert(table: SessionTable::Session->value)->values([
                'id'          => $id,
                'payload'     => $data,
                'expires_at'  => $expiresAt,
                'modified_at' => $now,
            ]),
        )
            ->execute();

        return true;
    }
}
