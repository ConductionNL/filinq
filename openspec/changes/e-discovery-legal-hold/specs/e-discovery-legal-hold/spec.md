# e-discovery-legal-hold Specification (delta)

---
status: proposed
---

## Purpose

Case-level legal holds for municipal matters (litigation, audit,
Woo-appeal): a `legalHoldCase` object carries matter type, reason, explicit
document/dossier scope and a custodian; activating it freezes destruction
through OpenRegister's per-object legal holds (which the
`archiefwet-retention-engine`'s disposal workflow already honours at the
platform layer), owners are notified on place and release, release is
overlap-safe across cases and fully audited, and the hold register is
searchable. Scope is deliberately hold + freeze + audit only — no
eDiscovery review platform (ZyLAB boundary, see design.md Non-Goals).

## ADDED Requirements

### Requirement: Legal hold case schema with explicit scope (REQ-DDEDL-001)

The document register MUST gain a `legalHoldCase` schema
(`hardValidation: true`): `name`, `holdType` (enum `litigation` | `audit` |
`woo-appeal` | `other`), mandatory `reason`, `caseReference`, `custodian`,
`scopeDocuments[]` and `scopeDossiers[]` (explicit object-UUID references
only — no query-based scopes), `status` with an `x-openregister-lifecycle`
of canonical `initial: active` and the single transition
`active → released` (released is terminal), `placedBy`, `placedAt`,
`releasedBy`, `releasedAt`, `releaseReason`, `notifiedOwners[]`. The schema
MUST carry `archive` configuration `defaultNominatie: bewaren` (the hold
register is itself a record) and MUST NOT carry `x-openregister-archival`.
The register version MUST be bumped for boot import.

#### Scenario: Register import creates the case schema and seed

- GIVEN a fresh install after `ConfigurationService::importFromApp()` runs
- WHEN the document register's schemas are listed
- THEN `legalHoldCase` exists with the documented properties, enums and lifecycle
- AND the seeded demo case validates against it
- @e2e exclude register import is a boot-time backend concern with no UI surface of its own — covered by PHPUnit register-import assertions (tests/unit/Settings/)

#### Scenario: Released is terminal

- GIVEN a case with status `released`
- WHEN any transition away from `released` is attempted
- THEN OpenRegister's lifecycle guard rejects it
- AND reopening a matter requires a new case object
- @e2e exclude lifecycle guard is enforced server-side by OpenRegister — covered by PHPUnit transition tests

### Requirement: Activation freezes destruction through OpenRegister holds (REQ-DDEDL-002)

The app MUST, on case activation, place an OpenRegister legal hold (reason
carrying the case reference) on every in-scope record object via OR's
legal-hold API, so that OR's `DestructionCheckJob` excludes them from
vernietigingslijsten at the platform layer — DocuDesk MUST NOT implement
its own freeze check in the disposal UI as the enforcement mechanism. The
case MUST expose per-object fan-out status and MUST NOT present itself as
fully protective until every in-scope object is verifiably held; fan-out
failures are retried and remain visible, never silent. An object already
held by another party is recorded in the case audit without overwriting
the existing hold.

#### Scenario: Held records disappear from new vernietigingslijsten

- GIVEN records whose archiefactiedatum has passed with nominatie `vernietigen`
- AND an active hold case whose scope contains them
- WHEN OpenRegister's destruction check next runs
- THEN the held records do not appear on the new vernietigingslijst
- AND the Archiefbeheer surface shows them as hold-excluded with the case name
- @e2e exclude the exclusion is OR's DestructionCheckJob behaviour — covered by PHPUnit fixture tests on the hold fan-out plus OR's own suite; the case-name resolution is covered in tests/e2e/workflows/e-discovery-legal-hold.spec.ts

#### Scenario: Partial fan-out failure is visible

- GIVEN a case activation where the hold placement fails for one of three in-scope objects
- WHEN the case detail is opened
- THEN the failed object is listed with a retry affordance
- AND the case is not presented as fully protective
- @e2e tests/e2e/workflows/e-discovery-legal-hold.spec.ts

### Requirement: Release is overlap-safe and audited (REQ-DDEDL-003)

The app MUST release a case only with a mandatory `releaseReason`, and MUST
lift the OpenRegister hold on an in-scope object only when no other active
case covers that object — when another active case covers it, the OR hold
remains and its reason is re-stamped to the surviving case's reference.
Coverage MUST be derived by querying active cases' scopes (no duplicated
coverage state). The OR-side `legalHold.history` (placedBy/placedDate/
releasedBy/releasedDate/releaseReason) and the case object's audit trail
MUST survive release intact, and released cases MUST remain queryable.

#### Scenario: Overlapping case keeps the record frozen

- GIVEN one record covered by two active hold cases
- WHEN the first case is released with a reason
- THEN the record's OpenRegister hold remains active, re-stamped to the surviving case
- AND only after the second case's release is the record's hold lifted
- @e2e exclude overlap ledger is backend orchestration — covered by PHPUnit overlap-matrix tests (tests/unit/Service/LegalHoldCaseServiceTest.php)

#### Scenario: Release without a reason is blocked

- GIVEN an active hold case
- WHEN the custodian attempts release without entering a reason
- THEN the UI blocks the submission until a reason is provided
- AND the recorded release carries releasedBy, releasedAt and the reason
- @e2e tests/e2e/workflows/e-discovery-legal-hold.spec.ts

#### Scenario: History survives release

- GIVEN a released hold case
- WHEN the previously held record's retention data is inspected
- THEN `legalHold.history` contains the full place/release entry
- AND the released case object remains queryable in the hold register
- @e2e exclude backend history preservation is OR-side — covered by PHPUnit assertions on the release path

### Requirement: Owners and custodian are notified on place and release (REQ-DDEDL-004)

The app MUST notify the owner of every in-scope document/dossier and the
case custodian when a hold case is activated and when it is released,
recording the notified users in `notifiedOwners[]`. The notification MUST
name the case and state that destruction and deletion of the record are
frozen (or unfrozen).

#### Scenario: Owner notified on activation

- GIVEN a document owned by user A inside a newly activated hold case
- WHEN the fan-out completes
- THEN user A receives a Nextcloud notification naming the case and the freeze
- AND user A appears in the case's `notifiedOwners[]`
- @e2e tests/e2e/workflows/e-discovery-legal-hold.spec.ts

#### Scenario: Owner notified on release

- GIVEN that same case being released
- WHEN the release completes
- THEN user A receives a release notification naming the case
- @e2e exclude duplicate of the notification path under a second trigger — covered by PHPUnit notification tests (tests/unit/Service/LegalHoldCaseServiceTest.php)

### Requirement: Searchable hold register with detail indicators (REQ-DDEDL-005)

The app MUST provide a hold-register surface listing cases with name,
type, status, custodian, scope counts and placement date, filterable by
status, type and custodian; a case detail with the scope list, per-object
fan-out status, audit block and the release action; and an active-hold
indicator on document/dossier detail naming the covering case(s) and
disabling destruction-adjacent actions there. Hold placement and release
MUST be restricted server-side to a designated authority group — UI hiding
MUST NOT be the access control.

#### Scenario: Register filters by matter type and custodian

- GIVEN hold cases of different types and custodians
- WHEN the user filters the hold register by `woo-appeal` and a custodian
- THEN only matching cases are listed
- @e2e tests/e2e/workflows/e-discovery-legal-hold.spec.ts

#### Scenario: Document detail shows the hold indicator

- GIVEN a document covered by an active hold case
- WHEN its detail page renders
- THEN an active-hold badge names the covering case
- AND destruction-adjacent actions are disabled with an explanatory state
- @e2e tests/e2e/workflows/e-discovery-legal-hold.spec.ts

#### Scenario: Unauthorized user cannot place or release holds

- GIVEN an authenticated user outside the designated authority group
- WHEN they call the case create or release endpoint directly
- THEN the request is rejected server-side with 403
- @e2e exclude server-side authorization check — covered by PHPUnit controller guard tests (tests/unit/Controller/LegalHoldCaseControllerTest.php)

### Requirement: Scope stays hold-and-freeze — no review platform (REQ-DDEDL-006)

This capability MUST NOT include document review, tagging, TAR/analytics,
production/export sets, custodian questionnaires or any matter workflow
beyond the `active → released` lifecycle (the ZyLAB category boundary), and
MUST NOT implement query-based or self-updating hold scopes in this wave —
scope changes on an active case happen only by explicitly adding
references, which re-runs the fan-out for the additions.

#### Scenario: Adding scope to an active case re-runs fan-out only for additions

- GIVEN an active hold case and a newly added in-scope document reference
- WHEN the addition is saved
- THEN an OpenRegister hold is placed on the added record
- AND existing holds are untouched
- @e2e exclude incremental fan-out is backend orchestration — covered by PHPUnit (tests/unit/Service/LegalHoldCaseServiceTest.php)

#### Scenario: No review-platform surface ships

- GIVEN the DocuDesk codebase and manifest at this change's completion
- WHEN the shipped pages, routes and services are inspected
- THEN no review, tagging, analytics or production-export surface exists in this capability
- @e2e exclude static scope property, enforced by review, not a browser flow
