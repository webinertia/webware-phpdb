<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session;

use ArrayObject;
use PhpDb\Session\SessionPayload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function serialize;

#[CoversClass(SessionPayload::class)]
#[CoversMethod(SessionPayload::class, 'encode')]
#[CoversMethod(SessionPayload::class, 'decode')]
final class SessionPayloadTest extends TestCase
{
    #[Test]
    public function decodeRestoresStoredObjects(): void
    {
        $decoded = SessionPayload::decode(payload: SessionPayload::encode(data: ['bag' => new ArrayObject([1, 2])]));

        self::assertInstanceOf(ArrayObject::class, $decoded['bag']);
        self::assertSame([1, 2], $decoded['bag']->getArrayCopy());
    }

    #[Test]
    public function decodeReturnsAnEmptySessionWhenThePayloadIsNotAnArray(): void
    {
        self::assertSame([], SessionPayload::decode(payload: serialize(value: 'not an array')));
    }

    #[Test]
    public function decodeReturnsWhatEncodeStored(): void
    {
        $data = ['user' => 'joey', 'roles' => ['admin', 'member']];

        self::assertSame($data, SessionPayload::decode(payload: SessionPayload::encode(data: $data)));
    }
}
