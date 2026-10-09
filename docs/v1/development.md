# Development

## Commands

| Command | Runs |
|---|---|
| `composer test` | Unit suite |
| `composer test-integration` | Integration suite |
| `composer test-coverage` | Full suite with Clover and text coverage |
| `composer mutation-test` | Infection |
| `composer test-all` | Unit, integration, then mutation testing |

Mago gates run through the pinned binary, one command each:

```bash
mago format --check
mago lint
mago analyze
mago guard
```

`mago fmt` rewrites files, and the tool can reorder class methods. Fix findings at the source
rather than adding expectations; this repository carries one deliberate inline
`@mago-expect lint:too-many-methods`, on `EventDispatcherFeature`, whose method count is the hook
set the interface fixes.

Mago's version has a single home: the `version` pin in
`vendor/webware/webware-tools/mago.toml`, which `composer.lock` selects. Do not add a version
anywhere else.

## Toolchain

The tooling container carries Composer, PHPUnit, Mago, Infection and PHPBench:

```bash
docker compose up -d
docker compose exec tooling composer install
docker compose exec tooling composer test
docker compose down
```

## Baselines

| File | Holds |
|---|---|
| `lint-baseline.toml` | Lint findings |
| `analysis-baseline.toml` | Analyzer findings |

Entries are keyed by file path, so moving a class drops its suppression. Both are generated with
`--generate-baseline`, never by hand. The one analyzer entry here covers php-db's
`AbstractFeature::$tableGateway`, which `FeatureSet::setTableGateway()` assigns after construction.
Guard findings are not baselineable.

## Tests

PHPUnit 13 runs in strict mode: `requireCoverageMetadata`, `failOnNotice`, `failOnWarning` and
`failOnDeprecation` are all enabled. Every test class therefore needs `#[CoversClass]`, and
`#[CoversMethod]` for the methods it exercises. A double that only returns values is created with
`createStub()`; `createMock()` is only for a double that carries `expects()`.

The integration suite needs MySQL, matching CI:

| Setting | Value |
|---|---|
| Host and port | `127.0.0.1:3306` |
| Database | `webware` |
| User | `root` |
| Password | empty |

`phpunit.xml.dist` sets the `TESTS_PHPDB_ADAPTER_*` environment variables, including the
PostgreSQL and SQLite toggles that `webware-ci.json` disables for this repository:

```php
TESTS_PHPDB_ADAPTER_PGSQL: '0',
TESTS_PHPDB_ADAPTER_SQLITE: '0',
```

## CI

`.github` carries no workflow. The org ruleset invokes
`webinertia/.github/.github/workflows/org-required-ci.yml` directly, and that workflow reads
`webware-ci.json` from this repository root for its parameters: PHP versions, integration toggle,
database image, coverage version, and the two MSI floors.

| Setting | Value |
|---|---|
| `min_msi` | `95` |
| `min_covered_msi` | `95` |
| `coverage_php_version` | `8.5` |
| `db_image` | `mysql:9.7` |
| `enable_codecov`, `enable_infection` | `true` |

Both MSI floors are the ecosystem standard. Lower them only as a deliberate decision.
