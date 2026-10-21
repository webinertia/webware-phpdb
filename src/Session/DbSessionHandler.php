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
use Random\RandomException;
use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

use function bin2hex;
use function date;
use function ini_get;
use function is_array;
use function is_string;
use function random_bytes;
use function time;

/**
 * Session handler backed by the session table.
 *
 * Both auxiliary session interfaces are implemented because PHP 8.6 warns
 * when a SessionHandlerInterface implementation omits create_sid() and
 * validateId(), which SessionHandlerInterface itself declares from PHP 9.0.
 */
final class DbSessionHandler implements
    SessionHandlerInterface,
    SessionIdInterface,
    SessionUpdateTimestampHandlerInterface
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
     * @throws RandomException
     */
    // @mago-expect lint:method-name -- create_sid is mandated by SessionIdInterface.
    #[Override]
    public function create_sid(): string
    {
        return bin2hex(random_bytes(16));
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
    // @mago-expect lint:variable-name -- max_lifetime is mandated by SessionHandlerInterface::gc().
    public function gc(int $max_lifetime): int|false
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
        $select = $this->sql
            ->select()
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
    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->write(
            id  : $id,
            data: $data,
        );
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    public function validateId(string $id): bool
    {
        return false !== $this->read($id);
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

        $this->sql
            ->prepareStatementForSqlObject(
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
