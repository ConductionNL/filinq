# AGENTS.md

Instructions for coding agents (and people) working in this repository. Every command below exists in `composer.json` or `package.json`.

## What this app is

Filinq is a Nextcloud app for document generation, anonymisation, signing, and GDPR/Woo publication consent. It stores its records as objects in [OpenRegister](https://github.com/ConductionNL/openregister), which must be installed first.

More context: [README.md](README.md), [ARCHITECTURE.md](ARCHITECTURE.md) and [docs/architecture.md](docs/architecture.md).

## Setup

Evaluation stack (Nextcloud with Filinq and OpenRegister, built from this repository):

```bash
docker compose -f docker/docker-compose.yml up -d --build
# open http://localhost:8080/apps/filinq/ and sign in as admin / admin
docker compose -f docker/docker-compose.yml down -v   # remove the stack and its data
```

Dependencies for development:

```bash
composer install   # also sets git core.hooksPath to .githooks
npm install
```

## Build

```bash
npm run build   # production webpack build
npm run dev     # development build
npm run watch   # rebuild on change
```

## Test

```bash
composer test:unit   # PHPUnit, tests/unit (phpunit.xml)
composer test:all    # full PHPUnit run
npm run test:unit    # Vitest
npm test             # Jest
npm run test:e2e     # Playwright (playwright.config.ts, tests/e2e)
```

## Lint and quality

```bash
composer check:strict   # lint, phpcs, phpmd, psalm, phpstan, test:all
composer check          # quicker: lint, phpcs, psalm, test:unit
composer cs:fix         # fix PHP code style
npm run lint            # ESLint
npm run stylelint       # CSS and Vue styles
npm run format          # Prettier check
npm run check:manifest  # validate src/manifest.json
npm run test:l10n       # translation keys
```

## OpenSpec flow

Work is specified before it is built, in [`openspec/`](openspec/):

1. Propose a change in `openspec/changes/<change-name>/` with `proposal.md`, `design.md`, `tasks.md` and spec deltas in `specs/<capability>/spec.md`.
2. Build it, ticking tasks in `tasks.md`.
3. Merge the spec deltas into `openspec/specs/<capability>/spec.md`.
4. Archive the change to `openspec/changes/archive/`.

Read [openspec/changes/README.md](openspec/changes/README.md) before archiving: a change must be synced into `openspec/specs/` first, or its requirements are lost. Project rules for proposals, specs and designs are in [openspec/config.yaml](openspec/config.yaml).

## Architecture decisions

App-level decision records are in [`openspec/architecture/`](openspec/architecture/). Design decisions for a single change are in that change's `design.md`.

## Repository conventions

- Code is grouped and named by what it does, not by technical layer ([ARCHITECTURE.md](ARCHITECTURE.md)).
- Data lives in OpenRegister, not in app database tables. Schemas are configuration in `lib/Settings/filinq_register.json` (OpenAPI 3.0.0), imported into OpenRegister.
- Pages and menus are manifest configuration: `src/manifest.json` plus fragments in `src/manifest.d/`. Check them with `npm run check:manifest`.
- Every user-visible string is translated; see `l10n/` and `npm run test:l10n`.

## Commits

Use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/), for example `docs: add AGENTS.md with build, test and lint commands`. See [CONTRIBUTING.md](CONTRIBUTING.md).
