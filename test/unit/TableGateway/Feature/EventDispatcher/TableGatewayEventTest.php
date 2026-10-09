<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\TableGateway\Feature\EventDispatcher;

use PhpDb\TableGateway\Feature\EventDispatcher\TableGatewayEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Webware\Event\Event;

#[CoversClass(TableGatewayEvent::class)]
#[CoversMethod(TableGatewayEvent::class, '__construct')]
final class TableGatewayEventTest extends TestCase
{
    #[Test]
    public function itCarriesTheNameTargetAndParamsItWasConstructedWith(): void
    {
        $target = new stdClass();

        $event = new TableGatewayEvent(
            name  : 'preSelect',
            target: $target,
            params: ['select' => 'a select'],
        );

        self::assertSame('preSelect', $event->getName());
        self::assertSame($target, $event->getTarget());
        self::assertSame(['select' => 'a select'], $event->getParams());
    }

    #[Test]
    public function itFallsBackToTheClassNameWhenNoNameIsGiven(): void
    {
        $event = new TableGatewayEvent();

        self::assertSame(TableGatewayEvent::class, $event->getName());
        self::assertNull($event->getTarget());
        self::assertSame([], $event->getParams());
    }

    #[Test]
    public function itIsAWebwareEvent(): void
    {
        self::assertInstanceOf(Event::class, new TableGatewayEvent());
    }
}
