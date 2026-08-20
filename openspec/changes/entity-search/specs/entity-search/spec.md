# entity-search Specification (delta)

---
status: proposed
---

## Purpose

Gated cross-document entity discovery: search OpenRegister's detected-entity
catalogue ("find every document containing this BSN / person / IBAN"), view
one entity's occurrences across documents with dossier membership,
anonymisation state and risk level, and hand matched documents to the
Woo-verzoek collection step. Because entity search is itself PII processing,
the surface is fail-closed permission-gated and every use is recorded in an
append-only Art. 30 processing log that never stores the raw searched value.
Detected entities live in OR's entity catalogue
(`oc_openregister_entities` + `oc_openregister_entity_relations`, verified at
HEAD) — DocuDesk adds no detection or indexing engine.

## ADDED Requirements

### Requirement: Entity search over OR's detected-entity catalogue (REQ-DDESR-001)

The app MUST provide `GET /api/entity-search` returning detected entities
from OpenRegister's entity catalogue matching a case-insensitive substring
`query` on the entity value, with optional exact `type` and `category`
filters and mandatory pagination. Each result MUST include the entity uuid,
type, value, category and occurrence (relation) count. Results MUST be
organisation-scoped with the same fail-closed rule as OR's own entity API
(non-admins see only entities of their accessible organisations; a non-admin
with none sees an empty set). The surface MUST NOT introduce any DocuDesk
copy of entity data and MUST NOT perform detection — documents never
extracted are absent, and the UI empty-state MUST say so. When OpenRegister
is not available the endpoint MUST return an explanatory unavailable state,
never a crash.

#### Scenario: Search by value returns matching entities with counts

- GIVEN extracted fixture documents that produced a PERSON entity "Jan de Vries" occurring in three documents
- WHEN a permitted operator searches for "de vries"
- THEN the result lists the PERSON entity "Jan de Vries" with an occurrence count of 3
- @e2e tests/e2e/spec-coverage/entity-search.spec.ts

#### Scenario: Organisation scoping is fail-closed

- GIVEN a non-admin user with no accessible organisation
- WHEN they call `GET /api/entity-search?query=vries`
- THEN the response is an empty result set, not an error and not cross-tenant data
- @e2e exclude tenant-scoping edge is backend authorization logic — covered by PHPUnit (tests/unit/Service/EntitySearchServiceTest.php) mirroring OR's #1825 rule

### Requirement: Entity detail lists occurrences across documents with context (REQ-DDESR-002)

The app MUST provide `GET /api/entity-search/{entityUuid}` returning, for one
catalogue entity, its occurrences grouped per document: file name and path,
dossier membership (resolved via the `dossier` register's `@self.folder`
binding), the document's anonymisation state resolved via `anonymizationLink`
(source-side or derivative-side), OR's per-file risk level, and per-occurrence
confidence and anonymized flag. Occurrences in files the caller cannot read
MUST be reported only as an opaque no-access count — never with name, path or
content. Relations that reference objects or emails instead of files MUST be
listed under a separate "other occurrences" group with their kind.

#### Scenario: Detail shows documents, dossiers and anonymisation state

- GIVEN the entity "Jan de Vries" occurring in two documents, one inside a dossier-bound folder and already anonymised
- WHEN the operator opens the entity detail
- THEN both documents are listed with file name, the first shows its dossier name and an anonymised state with a link to the derivative, and each occurrence shows its confidence
- @e2e tests/e2e/spec-coverage/entity-search.spec.ts

#### Scenario: Unreadable files never leak names

- GIVEN an occurrence in a file the caller has no read access to
- WHEN the entity detail is rendered
- THEN that occurrence appears only in an aggregate "documents without access" count with no file name or path
- @e2e exclude negative file-ACL projection — covered by PHPUnit (tests/unit/Service/EntitySearchServiceTest.php::testUnreadableFilesAreOpaque)

### Requirement: Fail-closed permission gate on every entity-search route (REQ-DDESR-003)

Access to every `api/entity-search/*` route MUST be restricted to admins and
members of the Nextcloud groups configured in
`docudesk.entity_search.allowed_groups` (default empty = admins only). The
gate MUST be enforced server-side in the controller on every route and MUST
fail closed: a non-member receives HTTP 403 with a neutral body; a
configuration read failure denies access. Hiding the navigation entry for
non-members is cosmetic only and MUST NOT be the enforcement mechanism.

#### Scenario: Non-member is refused

- GIVEN `allowed_groups` is `["privacy-officers"]` and a logged-in user who is in neither that group nor admin
- WHEN they call `GET /api/entity-search?query=vries`
- THEN the response is HTTP 403 and no processing-log entry with results is produced
- @e2e tests/e2e/spec-coverage/entity-search.spec.ts

#### Scenario: Empty configuration means admins only

- GIVEN `allowed_groups` is unset
- WHEN a non-admin user calls any entity-search route
- THEN the response is HTTP 403, and an admin's call succeeds
- @e2e exclude configuration-default authz matrix — covered exhaustively by PHPUnit (tests/unit/Controller/EntitySearchControllerTest.php)

### Requirement: Every use writes an append-only Art. 30 processing-log entry (REQ-DDESR-004)

Every search and every entity-detail view MUST write one `entitySearchLog`
object (`document` register) before the response is returned: `action`
(`search`|`detail`), `performedBy`, `performedAt`; for searches a
`queryDigest` (sha256 of the lower-cased trimmed query), the type/category
filters and the result count; for detail views the OR entity uuid
(`entityRef`) and occurrence count. The raw searched value MUST NOT be stored
anywhere by DocuDesk (AVG data minimisation — a logged BSN would be a new PII
store). The log MUST be append-only in app code (no update or delete
endpoint). If the log write fails, the search or detail response MUST be
refused — an unlogged PII lookup MUST NOT happen. The `entitySearchLog`
schema MUST carry a `docudesk-entity-search` `x-openregister-processing`
annotation (rechtsgrond `public-task`, `logReads: true`) so the activity
appears in the platform AVG Art. 30 verwerkingsregister via the existing
processing-activity-export mechanism — DocuDesk MUST NOT add any aggregation
or export engine of its own.

#### Scenario: Search produces a digest-only log entry

- GIVEN a permitted operator
- WHEN they search for "123456782" with type filter BSN and get 2 results
- THEN one `entitySearchLog` object exists with `action: search`, the sha256 digest of "123456782", `typeFilter: BSN`, `resultCount: 2`, the actor and timestamp
- AND no stored field anywhere in the log contains the literal string "123456782"
- @e2e tests/e2e/spec-coverage/entity-search.spec.ts

#### Scenario: Failed log write blocks the lookup

- GIVEN the OR object write for the log entry fails
- WHEN a search is attempted
- THEN the response is an error and no entity data is returned
- @e2e exclude fault-injection on the OR write path — covered by PHPUnit (tests/unit/Service/EntitySearchServiceTest.php::testLogWriteFailureBlocksLookup)

#### Scenario: Activity is declared in the platform register

- GIVEN DocuDesk's register configuration is imported into OpenRegister
- WHEN the platform verwerkingsregister is inspected
- THEN a `docudesk-entity-search` activity exists, declared via the `x-openregister-processing` annotation on `entitySearchLog`
- @e2e exclude boot-time register import contract — covered by PHPUnit register-import assertions (tests/unit/Settings/)

### Requirement: Entity-search UI (REQ-DDESR-005)

The app MUST provide a gated Entity search manifest page (`CnIndexPage` +
`CnDataTable`: value, type, category, occurrence count; search input and
type/category filters bound to OR's catalogue types) and an entity detail
view with the occurrence table (document, dossier, anonymisation state chip,
risk level chip, confidence), per ADR-012 (`@conduction/nextcloud-vue`
components) and ADR-003 (NL Design tokens via Nextcloud CSS variables, no
hardcoded colors). Every `NcSelect` MUST carry an `inputLabel`; modals and
dialogs MUST live in their own files under `src/modals/`/`src/dialogs/`. The
empty state MUST explain that only extracted documents are searchable. The
navigation entry MUST be hidden for users the gate refuses.

#### Scenario: Index renders results with filters

- GIVEN seeded extractions producing entities of types PERSON and IBAN
- WHEN the operator filters on type IBAN
- THEN only IBAN entities are listed with their occurrence counts
- @e2e tests/e2e/spec-coverage/entity-search.spec.ts

### Requirement: Matched documents can be collected into a Woo-verzoek (REQ-DDESR-006)

From the entity detail, the app MUST offer "Collect into Woo-verzoek" when
the woo-request-workflow capability is installed AND at least one
`wooRequest` is in status `collecting`. The action MUST hand the selected,
readable file hits to the existing woo-request-workflow collection step —
which owns copying, hashing and dedupe — and MUST NOT reimplement any
collection logic. Without the workflow (or with no collecting request) the
action MUST be hidden, not broken. The handoff MUST be recorded on the
corresponding processing-log entry (`collectedInto` = the request reference).

#### Scenario: Entity hits collected into a collecting request

- GIVEN a `wooRequest` in `collecting` and an entity with three readable document hits
- WHEN the operator selects two hits and collects them into the request
- THEN two `requestDocument` rows are created through the existing collection step and the log entry records the request reference
- @e2e tests/e2e/spec-coverage/entity-search.spec.ts

#### Scenario: Action hidden without the workflow

- GIVEN an instance without the woo-request-workflow capability
- WHEN the entity detail renders
- THEN no Woo-collection action is offered
- @e2e exclude absent-capability rendering permutation — covered by component tests and PHPUnit presence-gate tests (tests/unit/Service/EntitySearchServiceTest.php)
