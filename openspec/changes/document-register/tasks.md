# Tasks: document-register

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Schemas + register wiring

- [x] 1.1 Add the `document` schema to `lib/Settings/filinq_register.json` under `components.schemas` (REQ-DREG-D01)
  - Field contracts exactly per design.md D1; `hardValidation: true`; `x-openregister-lifecycle` on `status` with the five-state machine; `x-openregister-archival` default retention; `configuration.objectNameField: "title"`; `relatedCases` uses `$ref: "case"` + `x-external-register: "procest"`.
  - Drift-pin: verify every lifecycle/archival key against OpenRegister HEAD, not against this spec snapshot.

- [x] 1.2 Add the `documentType` schema to `filinq_register.json` under `components.schemas` (REQ-DREG-D02)
  - Field contracts exactly per design.md D2; `hardValidation: true`; `identifier` unique.

- [x] 1.3 Add both slugs (`document`, `documentType`) to the `document` register's `schemas` array and bump `info.version` (REQ-DREG-D05)
  - The version bump drives idempotent re-import via `SettingsInitializer::initialize()` → `ConfigurationService::importFromApp()`.

## 2. Seed data (ADR-016)

- [x] 2.1 Seed the canonical `documentType` starter set under `components.objects` (REQ-DREG-D03)
  - `brief`, `besluit`, `rapport`, `factuur`, `contract`, `notulen`, `beleidsstuk` (minimum); each with realistic `retentionPeriod` + placeholder `selectielijstCategory`.

- [x] 2.2 Seed 3–5 realistic `document` objects referencing the seeded types (REQ-DREG-D03)
  - Distinct `status`, `confidentiality`, and `documentType` values; at least one with a `relatedCases`/`relatedObjects` reference.

- [ ] 2.3 APPLY-BLOCKER — replace every `TODO-*` `selectielijstCategory` placeholder with a real VNG selectielijst category before apply/done (REQ-DREG-D04)
  - Add a PHPUnit seed-lint test that FAILS on any `TODO-` category so the gate enforces it (production-enablement gate).

## 3. Verification

- [x] 3.1 Import-roundtrip unit test: `document` + `documentType` import via `ConfigurationService::importFromApp()` and the imported schemas expose `hardValidation`, the `x-openregister-lifecycle` block, the `x-openregister-archival` block, and `configuration.objectNameField` intact (REQ-DREG-D01, REQ-DREG-D05)
  - If any key is dropped on import, file an OpenRegister issue; do not add a Filinq-side workaround.
  - Verified live (2026-11-25) on a real import rather than a unit double, because OpenRegister's
    `ConfigurationService` needs its database: Nextcloud 34.0.6 + MariaDB, OpenRegister main 2fc34e45,
    filinq development, `app:enable` then `occ maintenance:repair`. Stored `document` (1.0.0):
    `hard_validation` 1; `configuration` holds `objectNameField: title`, `objectDescriptionField`,
    `x-openregister-lifecycle` (field `status`, initial `concept`, final `gearchiveerd`, five
    transitions) and `x-openregister-archival` (retention default P10Y). Stored `documentType`
    (1.0.0): `hard_validation` 1, `objectNameField: name`. No key dropped, so no OpenRegister issue.

- [x] 3.2 Register-validity test: the full `filinq_register.json` still validates and imports cleanly with the two new schemas + seeds present (REQ-DREG-D05)
  - Run in the `nextcloud:34` container (host PHP too old): `docker run --rm -v $PWD:/app -w /app <nc-image> php vendor/bin/phpunit`.
  - Built (c5): `tests/unit/Settings/DocumentRegisterValidityTest.php` reads the whole descriptor as the import does: every schema a register lists is declared, every seed names a declared register and schema with a slug unique per schema, `document` and `documentType` keep `hardValidation`, a name field that exists and required properties that exist, the `document` lifecycle moves only between its own status values and it keeps its archival block, and every `document` and `documentType` seed fits its schema field by field (unknown field, missing required, type, enum, length, pattern, date), with a self-test that a broken seed is caught. Runs on host PHP 8.3 with the unit suite. The round trip through OpenRegister's `importFromApp()` is task 3.1, still open.

- [x] 3.3 Lifecycle/relation smoke: create a `document` via the generic `/api/objects/document` route, transition `status` through the lifecycle, and resolve a `documentType` + `relatedObjects` reference (REQ-DREG-D01, REQ-DREG-D06)
  - Verified live (2026-11-25, same instance), through OpenRegister's generic
    `/api/objects/filinq/document` route only: POST created a `concept` document with
    `documentType` = the seeded `besluit` and `relatedObjects` = a seeded document; PATCH
    `concept -> definitief` answered 422 ("No transition allows moving status from concept to
    definitief"); `in_bewerking`, `ter_vaststelling`, `definitief`, `gearchiveerd` each answered 200;
    GET with `_extend=documentType,relatedObjects` resolved "Besluit" (`besluit`) and the related
    document by title; `@self.retention.annotation` reads `effectiveRetention: P10Y` with its
    `expiresAt`, from the archival annotation, with no filinq service in the path.

Acceptance criteria:
- A governed `document` object exists as an OpenRegister object, queryable via generic object routes, with no new Filinq PHP controller or service.
- `documentType` classification vocabulary is seeded and referenceable.
- `status` is lifecycle-governed and retention is archival-annotation-driven — no ad-hoc service writes.
- No OpenRegister-core change; no database migration.
