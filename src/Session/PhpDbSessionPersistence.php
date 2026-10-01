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

use Mezzio\Session\InitializePersistenceIdInterface;
use Mezzio\Session\Persistence\CacheHeadersGeneratorTrait;
use Mezzio\Session\Persistence\SessionCookieAwareTrait;
use Mezzio\Session\Session;
use Mezzio\Session\SessionCookiePersistenceInterface;
use Mezzio\Session\SessionIdentifierAwareInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionPersistenceInterface;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Session\Sql\Insert;
use PhpDb\Sql\Exception\InvalidArgumentException as SqlInvalidArgumentException;
use PhpDb\Sql\Sql;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Random\RandomException;

use function bin2hex;
use function date;
use function is_array;
use function random_bytes;
use function time;

/**
 * Async-safe PhpDb-backed session persistence.
 *
 * Does not use ext-session or any process-global state. Session identity is
 * retrieved via SessionIdentifierAwareInterface::getId() (1.x; moves to
 * SessionInterface in 2.0), making this implementation safe
 * for use in concurrent async environments
 * (TrueAsync, Swoole, ReactPHP).
 *
 * Payload is serialized using PHP's serialize()/unserialize(). Objects stored
 * in session data must implement __serialize()/__unserialize() for correct
 * round-tripping.
 *
 * @phpstan-type SessionSettings array{
 *     gc_maxlifetime?: int,
 *     cache_limiter?: string,
 *     cache_expire?: int,
 *     name?: string,
 *     cookie_lifetime?: int,
 *     cookie_path?: string,
 *     cookie_domain?: string,
 *     cookie_secure?: bool,
 *     cookie_httponly?: bool,
 *     cookie_samesite?: string,
 * }
 */
final class PhpDbSessionPersistence implements InitializePersistenceIdInterface, SessionPersistenceInterface
{
    use CacheHeadersGeneratorTrait;
    use SessionCookieAwareTrait;

    private const string TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    private readonly Sql $sql;

    private int $gcMaxLifetime;

    /**
     * @param SessionSettings $sessionConfig overrides the php.ini session defaults, key by key
     */
    public function __construct(AdapterInterface $adapter, array $sessionConfig = [])
    {
        $settings = $sessionConfig + IniDefaults::fromPhpIni()->settings();

        $this->sql = new Sql(
            adapter: $adapter,
            table  : SessionTable::Session->value,
        );
        $this->gcMaxLifetime  = $settings['gc_maxlifetime'];
        $this->cacheLimiter   = $settings['cache_limiter'];
        $this->cacheExpire    = $settings['cache_expire'];
        $this->cookieName     = $settings['name'];
        $this->cookieLifetime = $settings['cookie_lifetime'];
        $this->cookiePath     = $settings['cookie_path'];
        $this->cookieDomain   = $settings['cookie_domain'];
        $this->cookieSecure   = $settings['cookie_secure'];
        $this->cookieHttpOnly = $settings['cookie_httponly'];
        $this->cookieSameSite = $settings['cookie_samesite'];
    }

    /**
     * @throws RandomException
     */
    #[Override]
    public function initializeId(SessionInterface $session): SessionInterface
    {
        $id = $this->idOf($session);

        if ('' !== $id && ! $session->isRegenerated()) {
            return $session;
        }

        /** @var array<string, mixed> $data */
        $data = $session->toArray();

        return new Session($data, $this->generateId());
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    public function initializeSessionFromRequest(ServerRequestInterface $request): SessionInterface
    {
        $id = $this->getSessionCookieValueFromRequest($request);

        if ('' === $id) {
            return new Session([], '');
        }

        $select = $this->sql->select()
            ->columns(['payload'])
            ->where(['id' => $id]);
        $select->where->greaterThan('expires_at', date(format: self::TIMESTAMP_FORMAT));

        $row = $this->sql->prepareStatementForSqlObject($select)->execute()?->current();

        if (! is_array($row)) {
            // Session not found or expired — return empty session keeping same ID.
            // Browser already holds the cookie; on write it will upsert.
            return new Session([], $id);
        }

        return new Session(SessionPayload::decode(payload: (string) ($row['payload'] ?? '')), $id);
    }

    /**
     * @throws RandomException
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    public function persistSession(SessionInterface $session, ResponseInterface $response): ResponseInterface
    {
        // Retrieve the session ID — uses SessionIdentifierAwareInterface (1.x);
        // getId() moves to SessionInterface in 2.0.
        $id = $this->idOf($session);

        // Regenerate: either explicitly requested, or new session with data.
        if ($session->isRegenerated() || ('' === $id && $session->hasChanged())) {
            if ('' !== $id && $session->isRegenerated()) {
                $this->destroy($id);
            }

            $id = $this->generateId();
        }

        // No ID means a new session was created but never written to.
        if ('' === $id) {
            return $response;
        }

        // Unchanged sessions do not need a new write or cookie.
        if (! $session->hasChanged()) {
            return $response;
        }

        $ttl =
            $session instanceof SessionCookiePersistenceInterface && 0 < $session->getSessionLifetime()
                ? $session->getSessionLifetime()
                : $this->gcMaxLifetime;

        $expiresAt = date(
            format   : self::TIMESTAMP_FORMAT,
            timestamp: time() + $ttl,
        );
        $now     = date(format: self::TIMESTAMP_FORMAT);
        $payload = SessionPayload::encode(data: $session->toArray());

        $this->sql->prepareStatementForSqlObject(
            new Insert(table: SessionTable::Session->value)->values([
                'id'          => $id,
                'payload'     => $payload,
                'expires_at'  => $expiresAt,
                'modified_at' => $now,
            ]),
        )
            ->execute();

        $response = $this->addSessionCookieHeaderToResponse($response, $id, $session);

        return $this->addCacheHeadersToResponse($response);
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    private function destroy(string $id): void
    {
        $delete = $this->sql->delete()->where(['id' => $id]);
        $this->sql->prepareStatementForSqlObject($delete)->execute();
    }

    /**
     * @throws RandomException
     */
    private function generateId(): string
    {
        return bin2hex(random_bytes(length: 16));
    }

    // getId() is on SessionIdentifierAwareInterface in 1.x and moves to SessionInterface in 2.0.
    private function idOf(SessionInterface $session): string
    {
        return $session instanceof SessionIdentifierAwareInterface
            ? $session->getId()
            : '';
    }
}
