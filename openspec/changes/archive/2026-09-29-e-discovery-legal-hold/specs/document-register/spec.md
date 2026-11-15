# document-register Specification (delta)

---
status: proposed
---

## Purpose delta

The document register hosts the `legalHoldCase` schema — the case-level
layer over OpenRegister's per-object legal holds.

## ADDED Requirements

### Requirement: LegalHoldCase Schema in Document Register (REQ-DDEDL-020)

The document register MUST include the `legalHoldCase` schema with full
`required`, `properties` and `hardValidation: true` (OR Adoption
Decision 3): `name`, `holdType` (`litigation` | `audit` | `woo-appeal` |
`other`), `reason` (required), `caseReference`, `custodian`,
`scopeDocuments[]`, `scopeDossiers[]`, `status` (lifecycle
`active → released`, canonical `initial: active`), `placedBy`, `placedAt`,
`releasedBy`, `releasedAt`, `releaseReason`, `notifiedOwners[]`. The schema
MUST carry `archive` configuration with `defaultNominatie: bewaren` (hold
cases are permanent records) and MUST NOT declare `x-openregister-archival`
(a hold register must never be auto-deleted). The register version MUST be
bumped for boot import.

#### Scenario: Schema present after version bump

- GIVEN a fresh installation after `ConfigurationService::importFromApp()` runs
- WHEN the document register's schemas are listed via `objectService->getSchemas(register: 'document')`
- THEN `legalHoldCase` is included with `hardValidation: true` and the declared lifecycle
- @e2e exclude boot-time register import with no UI surface — covered by PHPUnit register-import assertions (tests/unit/Settings/)

#### Scenario: Hold cases are bewaren records

- GIVEN the shipped `legalHoldCase` schema definition
- WHEN its archive configuration and annotations are inspected after import
- THEN it declares `defaultNominatie: bewaren` and no `x-openregister-archival` annotation
- AND its objects are never eligible for OpenRegister's destruction scan
- @e2e exclude declarative register-content rule — covered by a PHPUnit register-lint test
