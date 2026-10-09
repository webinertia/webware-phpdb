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

namespace WebwareTestIntegration\PhpDb\Session;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\Container\PhpDbSessionPersistenceFactory;
use PhpDb\Session\PhpDbSessionPersistence;
use PhpDb\Session\SessionPayload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use WebwareTestIntegration\PhpDb\Support\MysqlSessionTestCase;

use function date;
use function serialize;
use function strtotime;
use function substr;
use function time;

#[CoversClass(PhpDbSessionPersistence::class)]
#[CoversClass(PhpDbSessionPersistenceFactory::class)]
#[CoversMethod(PhpDbSessionPersistenceFactory::class, '__invoke')]
#[CoversMethod(PhpDbSessionPersistence::class, '__construct')]
#[CoversMethod(PhpDbSessionPersistence::class, 'initializeSessionFromRequest')]
#[CoversMethod(PhpDbSessionPersistence::class, 'persistSession')]
#[RequiresPhpExtension('pdo_mysql')]
#[Group('integration')]
#[Group('integration-mysql')]
final class PhpDbSessionPersistenceTest extends MysqlSessionTestCase
{
    #[Test]
    public function aNewSessionThatWasNeverWrittenToIsIgnored(): void
    {
        $response = $this->persistence()->persistSession(new Session([]), new Response());

        self::assertSame(0, $this->countRows());
        self::assertFalse($response->hasHeader('Set-Cookie'));
    }

    #[Test]
    public function aNewSessionWithDataIsStoredAndItsCookieIsSet(): void
    {
        $persistence = $this->persistence();
        $session     = $persistence->initializeSessionFromRequest(new ServerRequest());
        $session->set('user', 'joey');

        $response = $persistence->persistSession($session, new Response());

        $cookie = $response->getHeaderLine('Set-Cookie');
        self::assertMatchesRegularExpression('/^PHPSESSID=([0-9a-f]{32});/', $cookie);

        $id = substr(
            string: $cookie,
            offset: 10,
            length: 32,
        );
        self::assertSame(['user' => 'joey'], SessionPayload::decode(payload: (string) $this->payloadOf($id)));
        self::assertSame(1, $this->countRows());
    }

    #[Test]
    public function anExpiredSessionIsRestoredEmptyUnderTheSameIdentifier(): void
    {
        $this->insertRow(
            id       : 'stale-id',
            payload  : serialize(value: ['user' => 'joey']),
            expiresAt: date(
                format   : 'Y-m-d H:i:s',
                timestamp: time() - 60,
            ),
        );

        $session = $this->persistence()->initializeSessionFromRequest($this->requestWithCookie(value: 'stale-id'));

        self::assertSame([], $session->toArray());
        self::assertSame('stale-id', $this->idOf($session));
    }

    #[Test]
    public function aPayloadThatIsNotAnArrayIsRestoredEmpty(): void
    {
        $this->insertRow(
            id       : 'odd-id',
            payload  : serialize(value: 'not an array'),
            expiresAt: date(
                format   : 'Y-m-d H:i:s',
                timestamp: time() + 600,
            ),
        );

        $session = $this->persistence()->initializeSessionFromRequest($this->requestWithCookie(value: 'odd-id'));

        self::assertSame([], $session->toArray());
    }

    #[Test]
    public function aRegeneratedSessionMovesToANewIdentifierAndDropsTheOldRow(): void
    {
        $persistence = $this->persistence();
        $this->insertRow(
            id       : 'old-id',
            payload  : serialize(value: ['user' => 'joey']),
            expiresAt: date(
                format   : 'Y-m-d H:i:s',
                timestamp: time() + 600,
            ),
        );

        $session  = $persistence->initializeSessionFromRequest($this->requestWithCookie(value: 'old-id'));
        $response = $persistence->persistSession($session->regenerate(), new Response());

        self::assertNull($this->payloadOf('old-id'));
        self::assertSame(1, $this->countRows());
        self::assertDoesNotMatchRegularExpression('/PHPSESSID=old-id/', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function aRequestWithoutACookieStartsAnEmptySession(): void
    {
        $session = $this->persistence()->initializeSessionFromRequest(new ServerRequest());

        self::assertSame([], $session->toArray());
        self::assertSame('', $this->idOf($session));
    }

    #[Test]
    public function aSessionThatAsksForALifetimeIsKeptForThatLong(): void
    {
        $persistence = $this->persistence(config: ['gc_maxlifetime' => 900]);
        $session     = $persistence->initializeSessionFromRequest(new ServerRequest());
        $session->set('user', 'joey');
        $session->persistSessionFor(300);

        $before = time();
        $persistence->persistSession($session, new Response());
        $after = time();

        $expiresAt = (int) strtotime((string) $this->onlyExpiry());

        self::assertGreaterThanOrEqual($before + 300, $expiresAt);
        self::assertLessThanOrEqual($after + 300, $expiresAt);
    }

    #[Test]
    public function aSessionThatDidNotChangeIsNotWritten(): void
    {
        $session = new Session(['user' => 'joey'], 'known-id');

        $response = $this->persistence()->persistSession($session, new Response());

        self::assertSame(0, $this->countRows());
        self::assertFalse($response->hasHeader('Set-Cookie'));
    }

    #[Test]
    public function aStoredSessionIsRestoredFromItsCookie(): void
    {
        $this->insertRow(
            id       : 'known-id',
            payload  : serialize(value: ['user' => 'joey']),
            expiresAt: date(
                format   : 'Y-m-d H:i:s',
                timestamp: time() + 600,
            ),
        );

        $session = $this->persistence()->initializeSessionFromRequest($this->requestWithCookie(value: 'known-id'));

        self::assertSame(['user' => 'joey'], $session->toArray());
        self::assertSame('known-id', $this->idOf($session));
    }

    #[Test]
    public function changingAnExistingSessionUpdatesItsRow(): void
    {
        $persistence = $this->persistence();
        $this->insertRow(
            id       : 'known-id',
            payload  : serialize(value: ['user' => 'joey']),
            expiresAt: date(
                format   : 'Y-m-d H:i:s',
                timestamp: time() + 600,
            ),
        );

        $session = $persistence->initializeSessionFromRequest($this->requestWithCookie(value: 'known-id'));
        $session->set('theme', 'dark');
        $persistence->persistSession($session, new Response());

        self::assertSame(1, $this->countRows());
        self::assertSame(
            ['user' => 'joey', 'theme' => 'dark'],
            SessionPayload::decode(payload: (string) $this->payloadOf('known-id')),
        );
    }

    #[Test]
    public function theConfiguredCookieNameIsUsedToReadAndWriteTheCookie(): void
    {
        $persistence = $this->persistence(config: ['name' => 'WEBWARE']);
        $session     = $persistence->initializeSessionFromRequest(new ServerRequest());
        $session->set('user', 'joey');

        $response = $persistence->persistSession($session, new Response());

        self::assertMatchesRegularExpression('/^WEBWARE=[0-9a-f]{32};/', $response->getHeaderLine('Set-Cookie'));

        $restored = $persistence->initializeSessionFromRequest(
            $this->requestWithCookie(
                value: substr(
                    string: $response->getHeaderLine('Set-Cookie'),
                    offset: 8,
                    length: 32,
                ),
                name : 'WEBWARE',
            ),
        );

        self::assertSame(['user' => 'joey'], $restored->toArray());
    }

    #[Test]
    public function theSessionIsKeptForTheConfiguredLifetime(): void
    {
        $persistence = $this->persistence(config: ['gc_maxlifetime' => 900]);
        $session     = $persistence->initializeSessionFromRequest(new ServerRequest());
        $session->set('user', 'joey');

        $before = time();
        $persistence->persistSession($session, new Response());
        $after = time();

        $expiresAt = (int) strtotime((string) $this->onlyExpiry());

        self::assertGreaterThanOrEqual($before + 900, $expiresAt);
        self::assertLessThanOrEqual($after + 900, $expiresAt);
    }

    private function idOf(SessionInterface $session): string
    {
        self::assertInstanceOf(Session::class, $session);

        return $session->getId();
    }

    private function onlyExpiry(): ?string
    {
        $id = $this->pdo->query(query: 'SELECT id FROM `session`')?->fetchColumn();

        return $this->expiresAtOf((string) $id);
    }

    /**
     * @param array{gc_maxlifetime?: int, name?: string} $config
     */
    private function persistence(array $config = []): PhpDbSessionPersistence
    {
        $container = new ServiceManager();
        $container->setService('config', ['session' => $config]);
        $container->setService(AdapterInterface::class, $this->adapter);

        return new PhpDbSessionPersistenceFactory()($container);
    }

    private function requestWithCookie(string $value, string $name = 'PHPSESSID'): ServerRequestInterface
    {
        return new ServerRequest()->withCookieParams([$name => $value]);
    }
}
