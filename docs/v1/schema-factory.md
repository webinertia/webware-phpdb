# Schema factory

`PhpDb\SchemaFactory` is a callable that turns a `SchemaInterface` enum case into a
`PhpDb\Sql\TableIdentifier`, applying a configured table prefix and schema. Raw string table names
never need to appear in application code.

## Declaring a schema

A `SchemaInterface` is a string-backed enum whose case values are unprefixed table names. The
`NAME` constant declares the schema shared by every table in the enum; it defaults to an empty
string, which means no explicit schema.

```php
use PhpDb\SchemaInterface;

enum AclSchema: string implements SchemaInterface
{
    public const string NAME = 'acl';

    case Role = 'acl_role';
}
```

An enum that leaves `NAME` alone produces identifiers with a `null` schema, leaving the choice to
the connection:

```php
enum TestSchema: string implements SchemaInterface
{
    case Roles = 'core_role';
}
```

## Producing an identifier

```php
use PhpDb\SchemaFactory;

$factory = new SchemaFactory([
    'prefix' => 'ww',
    'schema' => 'public',
]);

$identifier = $factory(AclSchema::Role);

$identifier->getTable();           // 'ww_acl_role'
$identifier->getSchema();          // 'public'
$identifier->getPrefix();          // 'ww'
$identifier->getSeparator();       // '_'
$identifier->getUnprefixedTable(); // 'acl_role'
$identifier->getTableAndSchema();  // ['ww_acl_role', 'public']
```

`TableIdentifier::getTable()` applies the prefix and separator; `getUnprefixedTable()` returns the
enum value exactly as declared. The class has no `__toString()`.

## Precedence

Each part of the identifier resolves independently, highest priority first:

| Part | Order |
|---|---|
| table | The enum case value, always |
| schema | Call-time `$schemaName`, then `schemas[$table]`, then `schema`, then the enum `NAME` |
| prefix | Call-time `$prefix`, then `prefixes[$table]`, then `prefix` |
| separator | Call-time `$separator`, then `separator`, then `TableIdentifier::SEPARATOR` (`'_'`) |

What the values look like:

```php
$factory = new SchemaFactory([
    'prefix'   => 'ww',
    'prefixes' => ['acl_role' => 'acl'],
    'schemas'  => ['acl_role' => 'authz'],
]);

$factory(AclSchema::Role)->getTable();  // 'acl_acl_role'  (per-table prefix wins)
$factory(AclSchema::Role)->getSchema(); // 'authz'         (per-table schema wins)
$factory(AclSchema::Role, prefix: 'x')->getTable();       // 'x_acl_role'    (call time wins)
```

## Backup identifiers

`backup()` builds the identifier for a backup copy of the same table, for example before mutating
the live table. It resolves each part in this order:

| Part | Order |
|---|---|
| schema | Call-time `$schemaName`, then `schemas[$table]`, then `backup_schema`, then `schema`, then the enum `NAME` |
| prefix | Call-time `$prefix`, then `backup_prefix` |
| separator | Call-time `$separator`, then `separator`, then `TableIdentifier::SEPARATOR` |

```php
$factory = new SchemaFactory([
    'prefix'        => 'ww',
    'schema'        => 'public',
    'backup_prefix' => 'bck',
    'backup_schema' => 'backup',
]);

$factory->backup(AclSchema::Role)->getTable();  // 'bck_acl_role'
$factory->backup(AclSchema::Role)->getSchema(); // 'backup'
```

Two consequences of that resolution order:

- `backup_prefix` replaces the live prefix rather than extending it, so backing up a table
  prefixed `ww_` with `backup_prefix: 'bck'` yields `bck_acl_role`, not `bck_ww_acl_role`.
- When `backup_prefix` is not configured, the backup identifier carries no prefix at all. It does
  not fall back to `prefix` or `prefixes[$table]`. The `backup_schema` does fall back, to whatever
  the live lookup resolves.

## Configuration

`SchemaFactory` accepts config read from the top-level `SchemaInterface::class` key, validated
against a `Psl\Type` shape. Every key is optional and every value must be a non-empty string.

| Key | Type | Purpose |
|---|---|---|
| `prefix` | `non-empty-string` | App-wide table prefix |
| `separator` | `non-empty-string` | Prefix and table separator, defaults to `'_'` |
| `schema` | `non-empty-string` | App-wide schema |
| `prefixes` | `array<non-empty-string, non-empty-string>` | Per-table prefixes, keyed by unprefixed table name |
| `schemas` | `array<non-empty-string, non-empty-string>` | Per-table schemas, keyed by unprefixed table name |
| `backup_prefix` | `non-empty-string` | Prefix for `backup()` targets |
| `backup_schema` | `non-empty-string` | Schema for `backup()` targets |

`PhpDb\WebwareProvider` publishes this config and defaults the separator:

```php
// PhpDb\WebwareProvider::getSchemaConfig()
return ['separator' => TableIdentifier::SEPARATOR];
```

Consumer overrides merge on top of it. The provider also registers the factory:

```php
'dependencies' => [
    'factories' => [
        SchemaFactory::class => SchemaFactoryFactory::class,
    ],
],
```

`SchemaFactoryFactory` reads the `config` service when the container has one, takes
`$config[SchemaInterface::class]` when present, and constructs the factory. An absent config
service, or an absent key, produces a factory with defaults.

## Failures

| Situation | Exception |
|---|---|
| Config does not match the shape, for example a non-string or empty value | `Psl\Type\Exception\AssertException` |
| Config carries a key that is not one of the seven above | `Psl\Type\Exception\AssertException` |
| Enum case value is an empty string, for example `case Role = '';` | `Psl\Type\Exception\AssertException` |
| A call-time override is an empty string | `PhpDb\Sql\Exception\InvalidArgumentException` |

An empty enum value is rejected on purpose. Use the `NAME` constant, not an empty case, to express
that a table belongs to the connection's default schema.

An empty value in configuration never reaches `TableIdentifier`: the shape assertion rejects it
while `SchemaFactory` is constructed, so only a call-time override can produce
`InvalidArgumentException` from the identifier itself. The shape is closed, so an unrecognized key
such as `prefx` fails at construction rather than being ignored.
