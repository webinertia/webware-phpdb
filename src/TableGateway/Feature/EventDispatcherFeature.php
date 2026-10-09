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

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PhpDb\TableGateway\Feature\EventDispatcher\TableGatewayEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * PSR-14 counterpart to php-db's laminas-based {@see EventFeature}.
 *
 * Implements the same {@see EventFeatureEventsInterface} contract, so a table gateway accepts it
 * exactly where php-db's own feature is accepted, and publishes each lifecycle hook as a
 * {@see TableGatewayEvent} through the composed PSR-14 dispatcher instead of a laminas event
 * manager.
 *
 * php-db's feature attaches the gateway as the event target once and reuses that event, so every
 * hook here carries the gateway as the target of the event it publishes.
 *
 * @api
 */
// @mago-expect lint:too-many-methods - accepted: the method count is the EventFeatureEventsInterface hook set, plus the constructor and the dispatcher accessor; the interface fixes it.
final class EventDispatcherFeature extends AbstractFeature implements EventFeatureEventsInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Retrieve the composed event dispatcher instance.
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Dispatch the "postDelete" event, mapping:
     * - $statement as "statement"
     * - $result as "result"
     */
    public function postDelete(StatementInterface $statement, ResultInterface $result): void
    {
        $this->dispatch(
            name: static::EVENT_POST_DELETE,
            params: [
                'statement' => $statement,
                'result'    => $result,
            ],
        );
    }

    /**
     * Dispatch the "postInitialize" event.
     */
    public function postInitialize(): void
    {
        $this->dispatch(name: static::EVENT_POST_INITIALIZE);
    }

    /**
     * Dispatch the "postInsert" event, mapping:
     * - $statement as "statement"
     * - $result as "result"
     */
    public function postInsert(StatementInterface $statement, ResultInterface $result): void
    {
        $this->dispatch(
            name: static::EVENT_POST_INSERT,
            params: [
                'statement' => $statement,
                'result'    => $result,
            ],
        );
    }

    /**
     * Dispatch the "postSelect" event, mapping:
     * - $statement as "statement"
     * - $result as "result"
     * - $resultSet as "result_set"
     */
    public function postSelect(
        StatementInterface $statement,
        ResultInterface $result,
        ResultSetInterface $resultSet,
    ): void {
        $this->dispatch(
            name: static::EVENT_POST_SELECT,
            params: [
                'statement'  => $statement,
                'result'     => $result,
                'result_set' => $resultSet,
            ],
        );
    }

    /**
     * Dispatch the "postUpdate" event, mapping:
     * - $statement as "statement"
     * - $result as "result"
     */
    public function postUpdate(StatementInterface $statement, ResultInterface $result): void
    {
        $this->dispatch(
            name: static::EVENT_POST_UPDATE,
            params: [
                'statement' => $statement,
                'result'    => $result,
            ],
        );
    }

    /**
     * Dispatch the "preDelete" event, mapping:
     * - $delete as "delete"
     */
    public function preDelete(Delete $delete): void
    {
        $this->dispatch(
            name: static::EVENT_PRE_DELETE,
            params: ['delete' => $delete],
        );
    }

    /**
     * Dispatch the "preInitialize" event.
     */
    public function preInitialize(): void
    {
        $this->dispatch(name: static::EVENT_PRE_INITIALIZE);
    }

    /**
     * Dispatch the "preInsert" event, mapping:
     * - $insert as "insert"
     */
    public function preInsert(Insert $insert): void
    {
        $this->dispatch(
            name: static::EVENT_PRE_INSERT,
            params: ['insert' => $insert],
        );
    }

    /**
     * Dispatch the "preSelect" event, mapping:
     * - $select as "select"
     */
    public function preSelect(Select $select): void
    {
        $this->dispatch(
            name: static::EVENT_PRE_SELECT,
            params: ['select' => $select],
        );
    }

    /**
     * Dispatch the "preUpdate" event, mapping:
     * - $update as "update"
     */
    public function preUpdate(Update $update): void
    {
        $this->dispatch(
            name: static::EVENT_PRE_UPDATE,
            params: ['update' => $update],
        );
    }

    /**
     * Publish one lifecycle hook as a {@see TableGatewayEvent}.
     *
     * @param array<array-key, mixed> $params
     */
    private function dispatch(string $name, array $params = []): void
    {
        $this->eventDispatcher->dispatch(
            new TableGatewayEvent(
                name  : $name,
                target: $this->tableGateway,
                params: $params,
            ),
        );
    }
}
