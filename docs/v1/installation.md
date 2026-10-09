# Installation

## Requirements

| Requirement | Constraint |
|---|---|
| PHP | `~8.4.1 \|\| ~8.5.0` |
| `php-db/phpdb` | `0.6.x-dev` (hard requirement, and what this package shadows) |
| `psr/container` | `^2.0` |

A `php-db` driver package for your database is required in practice, for example
`php-db/phpdb-mysql`, `php-db/phpdb-pgsql` or `php-db/phpdb-sqlite`, though this package does not
declare one.

## Consumer applications

Install from within the consuming application:

```bash
composer require webware/webware-phpdb
```

Add the providers to the config aggregator:

```php
use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator([
    PhpDb\ConfigProvider::class,
    PhpDb\WebwareProvider::class,
    // ...
    Mezzio\Session\ConfigProvider::class,
    // Merge this after every provider that declares SessionPersistenceInterface:
    // it re-points that alias at the database persistence.
    PhpDb\Session\SessionProvider::class,
]);
```

`WebwareProvider` and php-db's `ConfigProvider` share no keys. php-db's owns
`dependencies.abstract_factories`, the `AdapterInterface` alias, the `Adapter` and
`TableIdentifierFactory` factories, and the `adapters` key; this package's owns the `SchemaFactory`
factory and the `SchemaInterface::class` config key. Their relative order does not change the
merged result.

The package declares both of its providers under `extra.laminas.config-provider`:

```json
"extra": {
    "laminas": { "config-provider": [
        "PhpDb\\WebwareProvider",
        "PhpDb\\Session\\SessionProvider"
    ] }
}
```

An installer or config aggregator that reads that key merges them automatically, and the explicit
list above is then unnecessary. The ordering rule for `SessionProvider` still applies.

Load `SessionProvider` only when `mezzio/mezzio-session` and `webware/webware-console` are
installed: it aliases `SessionPersistenceInterface` and registers a console command. Both packages
are listed under `suggest` in this package.

## Optional dependencies

| Package | Needed for |
|---|---|
| `mezzio/mezzio-session` | Database session persistence |
| `webware/webware-console` | The console that runs `session:init-db` |
| `webware/webware-event` | The PSR-14 table gateway event dispatcher feature |

## Working on the package

```bash
composer install
composer test
composer test-integration
```

The integration suite needs a MySQL database. See [development.md](development.md).
