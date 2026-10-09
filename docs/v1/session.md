# Database session persistence

Two classes read and write the session table, and either can be used on its own.

| Class | Contract | Use it when |
|---|---|---|
| `PhpDb\Session\PhpDbSessionPersistence` | `Mezzio\Session\SessionPersistenceInterface`, `InitializePersistenceIdInterface` | You want Mezzio session middleware with no ext-session state |
| `PhpDb\Session\DbSessionHandler` | `SessionHandlerInterface` | You want a native PHP session save handler |

Both write the same four columns to the same table, so switching between them does not migrate
data. `PhpDb\Session\SessionTable` names that table once (`session`), for the handler, the
persistence and the DDL.

## Persistence

`PhpDbSessionPersistence` holds no process-global state. Session identity is read from the session
object rather than from ext-session, which makes it safe to use under concurrent async runtimes.
Payloads are stored with `serialize()`, so objects kept in a session must implement
`__serialize()` and `__unserialize()` to round-trip.

```php
use PhpDb\Session\PhpDbSessionPersistence;

$persistence = new PhpDbSessionPersistence(
    adapter      : $adapter,
    sessionConfig: ['cookie_samesite' => 'Lax', 'gc_maxlifetime' => 3600],
);
```

Config passed as `sessionConfig` overrides the php.ini defaults key by key.

| Key | Type | php.ini source | Fallback when unset |
|---|---|---|---|
| `gc_maxlifetime` | `int` | `session.gc_maxlifetime` | `1440` |
| `cache_limiter` | `string` | `session.cache_limiter` | `'nocache'` |
| `cache_expire` | `int` | `session.cache_expire` | `0` |
| `name` | `string` | `session.name` | `'PHPSESSID'` |
| `cookie_lifetime` | `int` | `session.cookie_lifetime` | `0` |
| `cookie_path` | `string` | `session.cookie_path` | `'/'` |
| `cookie_domain` | `string` | `session.cookie_domain` | `''` |
| `cookie_secure` | `bool` | `session.cookie_secure` | `false` |
| `cookie_httponly` | `bool` | `session.cookie_httponly` | `false` |
| `cookie_samesite` | `string` | `session.cookie_samesite` | `''` |

Integer keys treat a php.ini value of `0` as unset and fall back to the default in the table.

What a persist does:

1. Regenerates the identifier when the session asks for it, deleting the old row, or when a new
   session has data but no identifier yet.
2. Returns without touching the database when the session has no identifier, or has not changed.
3. Generates identifiers with `bin2hex(random_bytes(16))`, 32 hexadecimal characters.
4. Upserts the row with `expires_at` set to now plus the session lifetime, or `gc_maxlifetime` when
   the session carries none, and adds the session cookie and cache headers to the response.

A read selects the payload for the identifier where `expires_at` is still in the future. A missing
or expired row yields an empty session that keeps the same identifier, so a browser holding a stale
cookie writes a fresh row rather than a second one.

## Session handler

`DbSessionHandler` implements the native `SessionHandlerInterface`. It reads and deletes by the
same rules, and takes the write lifetime from `session.gc_maxlifetime` in php.ini at write time; it
does not read the `session` config section.

## Wiring

`PhpDb\Session\SessionProvider` registers everything:

```php
'dependencies' => [
    'aliases' => [
        SessionPersistenceInterface::class => PhpDbSessionPersistence::class,
    ],
    'factories' => [
        DbSessionHandler::class        => DbSessionHandlerFactory::class,
        PhpDbSessionPersistence::class => PhpDbSessionPersistenceFactory::class,
        InitDbCommand::class           => InitDbCommandFactory::class,
    ],
],
```

It also publishes the console command, so a console that reads
`Webware\Console\ConsoleInterface::class` discovers it:

```php
ConsoleInterface::class => [
    'commands' => ['session:init-db' => InitDbCommand::class],
],
```

`PhpDbSessionPersistenceFactory` reads `config['session']` (the table above) and fetches
`AdapterInterface` from the container. `DbSessionHandlerFactory` and `InitDbCommandFactory` fetch
`AdapterInterface` only.

```php
// config/autoload/session.global.php
return [
    // Idle lifetime in seconds; overrides php.ini session.gc_maxlifetime (1440).
    'session' => ['gc_maxlifetime' => 28800],
];
```

## Creating the table

```bash
vendor/bin/webware session:init-db
vendor/bin/webware session:init-db --drop
```

`session:init-db` builds the table with php-db's DDL classes and runs it through the adapter.
`--drop` issues `DROP TABLE IF EXISTS` first, then recreates. `CREATE TABLE IF NOT EXISTS` means a
plain run against an existing table changes nothing.

| Column | Type | Nullable |
|---|---|---|
| `id` | `VARCHAR(64)` | no |
| `payload` | `MEDIUMTEXT` | no |
| `modified_at` | `DATETIME` | no |
| `expires_at` | `DATETIME` | no |

The primary key is `id` and there is an index named `idx_expires_at` on `expires_at`. Table options
are `ENGINE=InnoDB`, `DEFAULT CHARSET=utf8mb4`, `COLLATE=utf8mb4_0900_ai_ci`. Timestamps are
formatted `Y-m-d H:i:s`.

`MediumText` exists because php-db ships `TEXT` but no `MEDIUMTEXT`, and a serialized session can
exceed the 64 KB that `TEXT` holds.

## MySQL assumptions

The shipped DDL and the upsert are both MySQL. `SessionSchema` emits MySQL table options, and
`PhpDb\Session\Sql\Insert` appends `ON DUPLICATE KEY UPDATE` directly rather than going through a
platform builder, which its own `@todo` records as the portability gap to close. The integration
suite runs against MySQL.
