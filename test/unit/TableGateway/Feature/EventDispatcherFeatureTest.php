<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\TableGateway\Feature;

use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Feature\EventDispatcher\TableGatewayEvent;
use PhpDb\TableGateway\Feature\EventDispatcherFeature;
use PhpDb\TableGateway\Feature\EventFeatureEventsInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

use function array_map;
use function array_unique;
use function spl_object_id;

#[CoversClass(EventDispatcherFeature::class)]
#[CoversMethod(EventDispatcherFeature::class, '__construct')]
#[CoversMethod(EventDispatcherFeature::class, 'getEventDispatcher')]
#[CoversMethod(EventDispatcherFeature::class, 'preInitialize')]
#[CoversMethod(EventDispatcherFeature::class, 'postInitialize')]
#[CoversMethod(EventDispatcherFeature::class, 'preSelect')]
#[CoversMethod(EventDispatcherFeature::class, 'postSelect')]
#[CoversMethod(EventDispatcherFeature::class, 'preInsert')]
#[CoversMethod(EventDispatcherFeature::class, 'postInsert')]
#[CoversMethod(EventDispatcherFeature::class, 'preUpdate')]
#[CoversMethod(EventDispatcherFeature::class, 'postUpdate')]
#[CoversMethod(EventDispatcherFeature::class, 'preDelete')]
#[CoversMethod(EventDispatcherFeature::class, 'postDelete')]
final class EventDispatcherFeatureTest extends TestCase
{
    /** @var list<TableGatewayEvent> */
    private array $dispatched = [];

    #[Test]
    public function itCarriesTheHooksArgumentsAsEventParams(): void
    {
        $select    = new Select();
        $insert    = new Insert();
        $update    = new Update();
        $delete    = new Delete();
        $statement = $this->createStub(StatementInterface::class);
        $result    = $this->createStub(ResultInterface::class);
        $resultSet = $this->createStub(ResultSetInterface::class);

        $feature = $this->feature($this->createStub(AbstractTableGateway::class));

        $feature->preInitialize();
        $feature->postInitialize();
        $feature->preSelect($select);
        $feature->postSelect($statement, $result, $resultSet);
        $feature->preInsert($insert);
        $feature->postInsert($statement, $result);
        $feature->preUpdate($update);
        $feature->postUpdate($statement, $result);
        $feature->preDelete($delete);
        $feature->postDelete($statement, $result);

        self::assertSame(
            [
                [],
                [],
                ['select' => $select],
                ['statement' => $statement, 'result' => $result, 'result_set' => $resultSet],
                ['insert' => $insert],
                ['statement' => $statement, 'result' => $result],
                ['update' => $update],
                ['statement' => $statement, 'result' => $result],
                ['delete' => $delete],
                ['statement' => $statement, 'result' => $result],
            ],
            array_map(static fn(TableGatewayEvent $event): array => $event->getParams(), $this->dispatched),
        );
    }

    #[Test]
    public function itExposesTheComposedDispatcher(): void
    {
        $dispatcher = $this->createStub(EventDispatcherInterface::class);

        self::assertSame(
            $dispatcher,
            new EventDispatcherFeature(eventDispatcher: $dispatcher)->getEventDispatcher(),
        );
    }

    #[Test]
    public function itPublishesAFreshEventInstancePerHook(): void
    {
        $this->runEveryHook();

        self::assertCount(10, $this->dispatched);
        self::assertCount(
            10,
            array_unique(array_map(spl_object_id(...), $this->dispatched)),
        );
    }

    #[Test]
    public function itPublishesOneEventPerLifecycleHookUnderThatHooksName(): void
    {
        $this->runEveryHook();

        self::assertSame(
            [
                EventFeatureEventsInterface::EVENT_PRE_INITIALIZE,
                EventFeatureEventsInterface::EVENT_POST_INITIALIZE,
                EventFeatureEventsInterface::EVENT_PRE_SELECT,
                EventFeatureEventsInterface::EVENT_POST_SELECT,
                EventFeatureEventsInterface::EVENT_PRE_INSERT,
                EventFeatureEventsInterface::EVENT_POST_INSERT,
                EventFeatureEventsInterface::EVENT_PRE_UPDATE,
                EventFeatureEventsInterface::EVENT_POST_UPDATE,
                EventFeatureEventsInterface::EVENT_PRE_DELETE,
                EventFeatureEventsInterface::EVENT_POST_DELETE,
            ],
            array_map(static fn(TableGatewayEvent $event): string => $event->getName(), $this->dispatched),
        );
    }

    #[Test]
    public function itTargetsEveryPublishedEventAtTheTableGateway(): void
    {
        $gateway = $this->runEveryHook();

        self::assertCount(10, $this->dispatched);
        foreach ($this->dispatched as $event) {
            self::assertSame($gateway, $event->getTarget());
        }
    }

    /**
     * Builds a feature whose dispatcher records every event it is handed.
     */
    private function feature(AbstractTableGateway $gateway): EventDispatcherFeature
    {
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')
            ->willReturnCallback(
                function (object $event): object {
                    if ($event instanceof TableGatewayEvent) {
                        $this->dispatched[] = $event;
                    }

                    return $event;
                },
            );

        $feature = new EventDispatcherFeature(eventDispatcher: $dispatcher);
        $feature->setTableGateway($gateway);

        return $feature;
    }

    /**
     * Drives every lifecycle hook, and returns the gateway they were driven against.
     */
    private function runEveryHook(): AbstractTableGateway
    {
        $gateway = $this->createStub(AbstractTableGateway::class);
        $feature = $this->feature($gateway);

        $feature->preInitialize();
        $feature->postInitialize();
        $feature->preSelect(new Select());
        $feature->postSelect(
            $this->createStub(StatementInterface::class),
            $this->createStub(ResultInterface::class),
            $this->createStub(ResultSetInterface::class),
        );
        $feature->preInsert(new Insert());
        $feature->postInsert(
            $this->createStub(StatementInterface::class),
            $this->createStub(ResultInterface::class),
        );
        $feature->preUpdate(new Update());
        $feature->postUpdate(
            $this->createStub(StatementInterface::class),
            $this->createStub(ResultInterface::class),
        );
        $feature->preDelete(new Delete());
        $feature->postDelete(
            $this->createStub(StatementInterface::class),
            $this->createStub(ResultInterface::class),
        );

        return $gateway;
    }
}
