# Architecture

Filinq is a Nextcloud app for documents. It generates, converts, edits and signs documents, anonymises them, and records publication consent under the GDPR and the Wet open overheid (Woo). It stores its records as objects in OpenRegister. Entity detection for anonymisation runs in a separate service, anonymiq, which OpenRegister drives.

This page is for new developers and for anyone who assesses the code, such as SIG. It names the functional components, says where each one starts and stops, and marks every top-level directory as production, test, generated or tooling. `docs/architecture.md` goes deeper into the anonymisation flow.

## System boundary

Filinq runs inside a Nextcloud server, in PHP on the server and Vue in the browser.

| Talks to | How |
|---|---|
| OpenRegister | In-process calls to `OCA\OpenRegister\...` services. Dossiers, policies and consent records live there. |
| Nextcloud server | The public `OCP` API (files, users, events, background jobs, workflow) |
| anonymiq | Indirectly. OpenRegister's anonymisation backend drives this Python service, which detects personal data. Filinq asks OpenRegister. |
| Office suites and converters | HTTP, configured by an admin (for example Collabora or OnlyOffice for editing) |

## Directory roles

| Directory | Role |
|---|---|
| `lib/` | Production PHP, PSR-4 under `OCA\Filinq`. |
| `lib/Fonts/` | DejaVu fonts for PDF output, shipped with their own licence. |
| `lib/Resources/` | Twig templates for generated documents. |
| `src/` | Production front end (Vue 3, TypeScript and JavaScript). Files ending in `.spec.js` or `.spec.ts` are tests. |
| `appinfo/`, `templates/` | App manifest, route table and the PHP pages that mount the front end. |
| `tests/` | Test code only: PHPUnit (`unit/`, `integration/`), Playwright (`e2e/`), Vitest (`vitest/`), stubs, fixtures and sample documents. |
| `l10n/` | Translation catalogues. `<locale>.js` is generated from `<locale>.json` by `npm run l10n:build`. |
| `scripts/` | Developer tooling: translation checks and quality scripts. |
| `openspec/` | Specifications and change proposals (Markdown). |
| `docs/` | Documentation site. |
| `docker/`, `deploy/`, `docker-compose*.yml`, `filinq-compose.yaml` | Local development, office-suite and demo environments. |

## Functional components

We group the code by what it does, not by technical layer. The class name or its directory decides the component; `sigrid.yaml` holds the exact patterns.

| Component | What it covers | PHP lines |
|---|---|---|
| Conversion and editing | Document conversion, office editing, OCR, PDF/A validation, comparison, charts, templates, text extraction | 14k |
| Documents and dossiers | Documents, dossiers, versions, correspondence, print jobs, batch generation | 9.5k |
| Anonymisation and erasure | Anonymisation, redaction, pseudonymisation, entity search, custom dictionaries, subject erasure | 8.6k |
| Publication and retention | Publication consent, legal bases, publication policies, legal holds, contracts, retention | 6.9k |
| Signing | Electronic signatures, signing envelopes, signer authentication, seals | 6.8k |
| Intake and classification | Document intake, email ingestion, classification, suggestions, validation, the setup wizard | 5.4k |
| Application wiring | Bootstrap, settings, repair steps, commands, shared exceptions and helpers | 7.1k |
| Web interface | The Vue front end in `src/` | |

### Boundaries and known coupling

Every component uses **Application wiring** for shared exceptions and helpers. That dependency is intended.

Measured on `lib/` in October 2026, about 72% of the PHP code is hidden inside its own component. The rest is called from elsewhere, and many pairs of components call each other both ways. Most incoming calls land in **Documents and dossiers** and **Conversion and editing**, which nearly every other component uses. Giving those two a small shared interface is the main architectural work ahead.

## How a request flows

1. The browser calls a route from `appinfo/routes.php`.
2. A controller in `lib/Controller/` checks access and hands over to a service in `lib/Service/`.
3. The service reads and writes files through Nextcloud and records through OpenRegister.
4. For anonymisation, the service asks OpenRegister's anonymisation backend, which runs anonymiq. Filinq stores the link to the redacted copy and checks the produced bytes.
5. Listeners in `lib/EventListener/` react to file and object events. Background jobs in `lib/BackgroundJob/` run slow work outside the request.

## Test code

All PHP test code lives in `tests/`. Front-end unit tests live in `tests/vitest/` or next to the code as `*.spec.js`. End-to-end tests are Playwright specs in `tests/e2e/`.

## What a default scan picks up

SIG's Sigrid excludes `vendor/`, `node_modules/`, `docs/`, migrations, mocks and stubs by default. Two things it does not exclude by default:

- `l10n/*.js`: generated translation catalogues that would count as JavaScript.
- `scripts/`: developer tooling that would count as production code.

`sigrid.yaml` handles both. It also pins the model version and maps the components above.

## Licence

Filinq is licensed under EUPL-1.2. The DejaVu fonts in `lib/Fonts/` keep their own licence.
