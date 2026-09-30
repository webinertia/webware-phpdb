# webware/webware-phpdb

PhpDb bridge for the Webware stack: this package registers the `PhpDb` root namespace against its own
`src/`, so Webware packages can replace or extend individual `php-db/phpdb` classes without forking
the library.

[![PHP Version](https://img.shields.io/packagist/php-v/webware/webware-phpdb)](https://packagist.org/packages/webware/webware-phpdb)
[![Latest Version](https://img.shields.io/packagist/v/webware/webware-phpdb)](https://packagist.org/packages/webware/webware-phpdb)
[![License](https://img.shields.io/github/license/webinertia/webware-phpdb)](LICENSE)
[![Required CI](https://github.com/webinertia/webware-phpdb/actions/workflows/required/webinertia/.github/.github/workflows/org-required-ci.yml/badge.svg)](https://github.com/webinertia/webware-phpdb/actions/workflows/required/webinertia/.github/.github/workflows/org-required-ci.yml)
[![codecov](https://codecov.io/gh/webinertia/webware-phpdb/graph/badge.svg)](https://codecov.io/gh/webinertia/webware-phpdb)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fwebinertia%2Fwebware-phpdb%2F1.0.x)](https://dashboard.stryker-mutator.io/reports/github.com/webinertia/webware-phpdb/1.0.x)

## The shadow

`php-db/phpdb` maps the `PhpDb` root namespace to its `src/`. This package declares the same root
against its own `src/`, and Composer registers each package's PSR-4 directories in dependency order —
a package before the packages it requires — so this package's `src/` is consulted **first** for every
`PhpDb` class:

- a class defined here **replaces** the `php-db/phpdb` class of the same name;
- every class this package does not define still resolves to `php-db/phpdb`, unchanged.

`php-db/phpdb` is therefore a hard `require` of this package — without it there is nothing to shadow.

**One name must not be repeated.** A second `PhpDb\ConfigProvider` would be an ambiguous class
resolution for Composer's optimized autoloader, so this package's provider is `PhpDb\WebwareProvider`.
`php-db/phpdb`'s own provider keeps its name and both load; a consumer merging this one after it
layers the bridge's wiring on top of PhpDb's.

## Installation

```bash
composer require webware/webware-phpdb
```

Register `PhpDb\WebwareProvider` after `PhpDb\ConfigProvider` in the consumer's config aggregator,
so these rules win where the two overlays meet.

## What ships here

Everything in this repository is either a **package of record** consumed from
`webware/webware-tools`, or the **thin per-repo wiring** that cannot live in a shared
config:

| Path | Role |
|---|---|
| `mago.toml` | Extends the centre (`vendor/webware/webware-tools/mago.toml`) and overrides `php-version` only. Never re-add general rules locally. |
| `webware-ci.json` | The required CI workflow's parameter contract — read from the repository root by `webinertia/.github`. |
| `phpunit.xml.dist` | PHPUnit 13 strict mode: `requireCoverageMetadata`, `failOnNotice`, `failOnWarning`, `failOnDeprecation`. |
| `compose.yml` / `Dockerfile` / `.devcontainer/` | The containerized toolchain (Composer, PHPUnit, Mago, Infection, PHPBench, roave BC-check). |
| `src/WebwareProvider.php` | The package wiring entry point, declared under `extra.laminas.config-provider`. Named for the collision reason in "The shadow" above. |

`mago.toml`, `phpunit.xml.dist`, `.gitattributes`, `codecov.yml`, `Dockerfile`,
`.dockerignore`, `infection.json5.dist`, `phpbench.json.dist` and devcontainer config are
byte-identical to the canonical artifacts in
`webware-tools/presets/webware-alignment/artifacts/` — copy updates from there rather than
editing them here.

## Quality gates

Both MSI gates are set to **95** — the ecosystem standard, not a starting point. Lower them
only with a deliberate decision, and never silently:

```json
"min_msi": "95",
"min_covered_msi": "95"
```

Four Mago gates run in CI and must be clean: `format --check`, `lint`, `analyze`, `guard`.
Run `mago fmt` first when making changes, and fix findings at source rather than adding
`@mago-expect` — a suppression needs to be a decision, not a reflex.

## Development

The toolchain runs in a container, so the host needs no PHP install. With VS Code, reopen in
the container; without it:

```shell
docker compose up -d
docker compose exec tooling composer install
docker compose exec tooling composer test
docker compose exec tooling composer test-integration
docker compose exec tooling mago lint
docker compose down
```

Packages whose tests need MySQL uncomment the `mysql` service in `compose.yml`, mirroring the
`db_image` / `db_env_json` / `db_port` values they declare in `webware-ci.json`.
