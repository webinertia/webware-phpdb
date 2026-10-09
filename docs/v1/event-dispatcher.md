# Table gateway event dispatcher

`PhpDb\TableGateway\Feature\EventDispatcherFeature` publishes php-db's table gateway lifecycle
hooks to a PSR-14 dispatcher. It is the PSR-14 counterpart to php-db's own laminas-based
`EventFeature`: same hook contract, different transport.

It implements `PhpDb\TableGateway\Feature\EventFeatureEventsInterface`, so a table gateway accepts
it exactly where php-db's feature is accepted, and it extends `AbstractFeature`, so it can be
combined with other features in a `FeatureSet`.

## Requirements

The event class extends `Webware\Event\Event`, so `webware/webware-event` must be installed to use
this feature. The bridge lists it under `suggest` and installs it for development:

```bash
composer require webware/webware-event
```

## Usage

Pass the feature to the gateway constructor:

```php
use PhpDb\TableGateway\Feature\EventDispatcherFeature;
use PhpDb\TableGateway\TableGateway;
use Psr\EventDispatcher\EventDispatcherInterface;

$feature = new EventDispatcherFeature(eventDispatcher: $dispatcher);

$gateway = new TableGateway(
    table   : 'session',
    adapter : $adapter,
    features: $feature,
);
```

Add it to an existing feature set when other features are in play:

```php
$featureSet = $gateway->getFeatureSet();
$featureSet->addFeature($feature);
```

Every hook publishes a new `PhpDb\TableGateway\Feature\EventDispatcher\TableGatewayEvent`:

```php
use PhpDb\TableGateway\Feature\EventDispatcher\TableGatewayEvent;

final class SessionWriteLogger
{
    public function __invoke(TableGatewayEvent $event): void
    {
        if ('postInsert' !== $event->getName()) {
            return;
        }

        $insert = $event->getParam('insert');   // PhpDb\Sql\Insert
        $result = $event->getParam('result');   // PhpDb\Adapter\Driver\ResultInterface
        $gateway = $event->getTarget();         // PhpDb\TableGateway\AbstractTableGateway
    }
}
```

The dispatcher is whatever PSR-14 implementation the consumer uses, for example the one
`webware/webware-event` provides.

## Hooks

The event name is the hook being run, taken from `EventFeatureEventsInterface`. The target is the
table gateway the hook belongs to.

| Hook | Event name | Params |
|---|---|---|
| `preInitialize()` | `preInitialize` | none |
| `postInitialize()` | `postInitialize` | none |
| `preSelect(Select $select)` | `preSelect` | `select` |
| `postSelect(StatementInterface $statement, ResultInterface $result, ResultSetInterface $resultSet)` | `postSelect` | `statement`, `result`, `result_set` |
| `preInsert(Insert $insert)` | `preInsert` | `insert` |
| `postInsert(StatementInterface $statement, ResultInterface $result)` | `postInsert` | `statement`, `result` |
| `preUpdate(Update $update)` | `preUpdate` | `update` |
| `postUpdate(StatementInterface $statement, ResultInterface $result)` | `postUpdate` | `statement`, `result` |
| `preDelete(Delete $delete)` | `preDelete` | `delete` |
| `postDelete(StatementInterface $statement, ResultInterface $result)` | `postDelete` | `statement`, `result` |

The param keys match the ones php-db's `EventFeature` uses, so a listener written for one feature
reads the same keys from the other. Read them with `getParam($name)` or `getParams()`.

## Notes

`EventDispatcherFeature` publishes a fresh event per hook rather than mutating one shared instance,
so a listener may keep the event it receives. `TableGatewayEvent` itself is mutable, inheriting
`setName()`, `setParams()` and `setTarget()` from `Webware\Event\Event`; a dispatch of
`new TableGatewayEvent()` with no name reports the class name, because that is how
`Webware\Event\Event::getName()` treats a null name.

The gateway is available as the event target because `AbstractFeature::$tableGateway` is assigned
by the feature set when the feature is attached. Building the feature and reading a target before
attaching it is a programming error, not a supported state.
