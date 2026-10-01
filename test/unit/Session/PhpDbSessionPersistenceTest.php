<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session;

use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\PhpDbSessionPersistence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpDbSessionPersistence::class)]
#[CoversMethod(PhpDbSessionPersistence::class, 'initializeId')]
final class PhpDbSessionPersistenceTest extends TestCase
{
    #[Test]
    public function initializeIdIssuesAnIdentifierToAnAnonymousSession(): void
    {
        $session = $this->persistence()->initializeId(new Session(['user' => 'joey']));

        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $this->idOf($session));
        self::assertSame(['user' => 'joey'], $session->toArray());
    }

    #[Test]
    public function initializeIdKeepsAnExistingIdentifier(): void
    {
        $session = new Session(['user' => 'joey'], 'existing-id');

        self::assertSame($session, $this->persistence()->initializeId($session));
    }

    #[Test]
    public function initializeIdReplacesTheIdentifierOfARegeneratedSession(): void
    {
        $original = new Session(['user' => 'joey'], 'old-id');
        $session  = $this->persistence()->initializeId($original->regenerate());

        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $this->idOf($session));
        self::assertNotSame('old-id', $this->idOf($session));
        self::assertSame(['user' => 'joey'], $session->toArray());
    }

    private function idOf(SessionInterface $session): string
    {
        self::assertInstanceOf(Session::class, $session);

        return $session->getId();
    }

    private function persistence(): PhpDbSessionPersistence
    {
        return new PhpDbSessionPersistence(adapter: $this->createStub(AdapterInterface::class));
    }
}
