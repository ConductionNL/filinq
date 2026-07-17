# woo-publicatie-pipeline Specification (delta)

---
status: proposed
---

## Purpose

Readiness-gated active-publication pipeline chaining DocuDesk's existing
anonymisation and publication-consent capabilities into a handoff to
OpenCatalogi as the publication endpoint (Woo active disclosure / Woo-index,
DiWoo). One `publicationRecord` per published unit carries the readiness
state (entities reviewed, consent clear, prohibitions clear), the assembled
DiWoo metadata, the endpoint reference, the destruction date and the
lifecycle; every pipeline action lands in an append-only publication log.
DocuDesk prepares, hands off and tracks state — it builds no portal, sitemap
or search surface (OpenCatalogi/OpenWoo own those).

## ADDED Requirements

### Requirement: Publication record and log schemas (REQ-DDWPP-001)

The app MUST declare two schemas in the `document` register:
`publicationRecord` (`subjectType` enum `document`|`dossier`,
`documentFileRef`, `dossierRef`, `redactedFileRef`, readiness booleans
`entitiesReviewed`/`consentClear`/`prohibitionsClear` +
`readinessEvaluatedAt`, DiWoo block `wooCategory`/`documentsoort`/
`publisher`/`officieleTitel`/`creatiedatum`/`publicatiedatum`, `status`,
`endpointPublicationRef`, `handoffAt`, `depublicationReason`,
`depublicationRequestedAt`, `destructionDate`, `destructionDateSource`) and
`publicationLogEntry` (`publicationRecordRef`, `action` enum, `actor`,
`timestamp`, `details`, `snapshot`). All data MUST be stored as OpenRegister
objects (ADR-001), with a register version bump for boot import.

#### Scenario: Register import creates the pipeline schemas

- GIVEN DocuDesk and OpenRegister installed
- WHEN `ConfigurationService::importFromApp()` runs on boot
- THEN `publicationRecord` and `publicationLogEntry` exist in the `document` register with the seeded demo objects queryable
- @e2e exclude boot-time register import with no UI surface of its own — covered by PHPUnit register-import assertions (tests/unit/Settings/)

### Requirement: Publication readiness evaluation (REQ-DDWPP-002)

`PublicationPipelineService::evaluateReadiness()` MUST compute and persist
three verdicts on the publication record, with `readinessEvaluatedAt`:
`entitiesReviewed` (every detected entity for the subject has a review
decision; for a dossier additionally `checkedOn` is set), `consentClear` (per
the consent-clearance signal, REQ-DDWPP-020) and `prohibitionsClear` (no
active `publicationProhibition` matches, via the existing
`PolicyMatchService`). The readiness snapshot MUST store only verdicts and
record UUIDs — never entity text or other personal data (data minimisation,
AVG Art. 5(1)(c)).

#### Scenario: Ready when all three gates are clear

- GIVEN a document whose entities are all reviewed, whose consents are all publication-permitting and which matches no active prohibition
- WHEN readiness is evaluated
- THEN all three booleans are true and `readinessEvaluatedAt` is set
- AND the record may transition to `ready`
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

#### Scenario: Open objection window blocks readiness

- GIVEN a document with a `publicationConsent` in status `pending` whose `objectionDeadline` lies in the future
- WHEN readiness is evaluated
- THEN `consentClear` is false and the record cannot reach `ready`
- AND the UI shows the blocking consent record as the reason
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

### Requirement: Readiness gate is enforced by the record lifecycle (REQ-DDWPP-003)

The `publicationRecord` schema MUST declare an `x-openregister-lifecycle`
annotation (canonical `initial: draft`) with transitions `draft → ready`,
`ready → draft` (readiness regression), `ready → handed_off`, `handed_off →
published`, `published → depublication_requested`,
`depublication_requested → depublished`. The `draft → ready` transition MUST
be guarded on all three readiness booleans being true, and handoff MUST only
be possible from `ready`, so the gate cannot be bypassed by a direct status
write. Readiness MUST be re-evaluated on every handoff attempt and the record
demoted to `draft` when it has regressed.

#### Scenario: Direct status write cannot skip the gate

- GIVEN a publication record in `draft` with `consentClear` false
- WHEN a save attempts `status = handed_off`
- THEN OpenRegister's lifecycle guard rejects the transition
- @e2e exclude server-side lifecycle guard — covered by PHPUnit transition tests (tests/unit/Service/PublicationPipelineServiceTest.php)

#### Scenario: Regressed readiness demotes the record at handoff

- GIVEN a record in `ready` for which a new objection has since been received
- WHEN handoff is attempted
- THEN readiness is re-evaluated, `consentClear` becomes false, the record returns to `draft` and no endpoint object is written
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

### Requirement: DiWoo metadata assembly (REQ-DDWPP-004)

The publication record MUST carry an operator-completed DiWoo metadata block
before handoff: `wooCategory` (one of the 17 TOOI Woo informatiecategorie
codes, selected from OpenCatalogi's bundled TOOI value list — never free
text), `documentsoort`, `publisher` (TOOI organisatie URI), `officieleTitel`,
`creatiedatum` and `publicatiedatum`. Handoff MUST be blocked while any
mandatory DiWoo field is missing. DocuDesk assembles and passes these values;
TOOI validation and `diwoo:Document` emission remain OpenCatalogi's
(WOO-TOOI-001/002).

#### Scenario: Missing Woo category blocks handoff

- GIVEN a `ready` record without a `wooCategory`
- WHEN the operator attempts handoff
- THEN handoff is refused with a message naming the missing DiWoo fields
- @e2e tests/e2e/spec-coverage/woo-publications.spec.ts

#### Scenario: Category is selected from the TOOI list

- GIVEN the DiWoo metadata form
- WHEN the operator opens the Woo-categorie field
- THEN it offers exactly the 17 TOOI informatiecategorieën (code + label) and no free-text entry
- @e2e tests/e2e/spec-coverage/woo-publications.spec.ts

### Requirement: Handoff to OpenCatalogi as the publication endpoint (REQ-DDWPP-005)

On handoff of a `ready` record, the app MUST create (or update, when
`endpointPublicationRef` already exists) an OpenRegister object addressed to
OpenCatalogi's register slug `publication`, schema slug `publication`,
mapping the record's title/summary/dates/DiWoo block, and attach the redacted
derivative (`redactedFileRef`) — NEVER the original file. It MUST store
`endpointPublicationRef` + `handoffAt`, transition to `handed_off` and append
a `handed_off` log entry. DocuDesk MUST NOT render any public
portal/sitemap/search surface. When OpenCatalogi is not installed, handoff
MUST be disabled with an explanatory state — never a silent no-op. An OR
authorization failure on the endpoint write MUST surface to the operator.

#### Scenario: Successful handoff creates the endpoint publication

- GIVEN a `ready` record with complete DiWoo metadata and a redacted derivative
- WHEN the operator confirms handoff
- THEN an OpenCatalogi `publication` object exists carrying the mapped metadata with the redacted file attached
- AND the record shows `handed_off` with `endpointPublicationRef` set and a `handed_off` log entry
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

#### Scenario: Endpoint absent

- GIVEN an instance without OpenCatalogi
- WHEN the operator views a `ready` record
- THEN the handoff action is disabled with an explanation that OpenCatalogi provides the publication endpoint
- @e2e tests/e2e/spec-coverage/woo-publications.spec.ts

### Requirement: De-publication with mandatory reason (REQ-DDWPP-006)

An operator MUST be able to withdraw a published record only with a
non-empty `depublicationReason`. Withdrawal MUST set `depublicatiedatum` on
the endpoint publication object (OpenCatalogi's published-predicate removes
it from all public surfaces), transition the record through
`depublication_requested` to `depublished`, and append log entries carrying
the reason. The endpoint object MUST NOT be deleted — the accountability
trace is retained.

#### Scenario: Withdraw a publication

- GIVEN a record in `published`
- WHEN the operator withdraws it with reason "Onterecht gepubliceerd: lopend bezwaar"
- THEN the endpoint publication's `depublicatiedatum` is set and the record reaches `depublished`
- AND the log shows `depublication_requested` and `depublished` entries with the reason
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

#### Scenario: Empty reason is refused

- GIVEN a record in `published`
- WHEN withdrawal is attempted without a reason
- THEN the action is refused and nothing changes on the endpoint
- @e2e tests/e2e/spec-coverage/woo-publications.spec.ts

### Requirement: Destruction-date propagation (REQ-DDWPP-007)

The app MUST propagate a destruction date recorded on a publication record
(`destructionDate` + `destructionDateSource` — e.g. supplied via the
zgw-document-bridge metadata or operator entry, per Archiefwet/selectielijst)
to the endpoint publication object as a
`retentionExpiresAt` override accompanied by a `retentionNote` naming the
source, as OpenCatalogi RET-003 requires. Actual disposal remains
OpenCatalogi's retention job; DocuDesk MUST NOT delete or archive endpoint
publications itself. Propagation MUST append a `destruction_date_propagated`
log entry.

#### Scenario: Destruction date reaches the endpoint

- GIVEN a published record with destruction date 2034-11-03 from "selectielijst 2020, procestype 6"
- WHEN the destruction date is propagated
- THEN the endpoint publication carries `retentionExpiresAt` 2034-11-03 and a `retentionNote` naming the source
- AND the record's log shows a `destruction_date_propagated` entry
- @e2e exclude endpoint-side retention fields are not rendered in DocuDesk UI — covered by PHPUnit handoff-mapping tests (tests/unit/Service/PublicationPipelineServiceTest.php); the log entry is covered under REQ-DDWPP-008

### Requirement: Append-only publication log (REQ-DDWPP-008)

The app MUST append a `publicationLogEntry` for every pipeline action
(create, readiness evaluation, metadata assembly, handoff, publication,
de-publication request/completion, destruction-date
propagation) with actor, timestamp,
details and a state snapshot. The app MUST expose no update or delete route
for log entries, and the record detail MUST render the log as a timeline for
accountability (Woo Art. 3.3 verantwoording; AVG Art. 5(2) accountability).

#### Scenario: Log timeline shows the full trail

- GIVEN the seeded published demo record
- WHEN its detail page renders
- THEN the log timeline shows `created`, `readiness_evaluated`, `handed_off`, `published` and `destruction_date_propagated` entries in order with actors and timestamps
- @e2e tests/e2e/spec-coverage/woo-publications.spec.ts

#### Scenario: Log entries cannot be mutated via the API

- GIVEN an existing log entry
- WHEN the route table is inspected for `publicationLogEntry` update/delete endpoints
- THEN none exist
- @e2e exclude route-surface property — covered by a PHPUnit route-table assertion, not a browser flow

### Requirement: Publish wizard chains the pipeline from document and dossier context (REQ-DDWPP-009)

MyDocuments document detail and dossier context MUST offer a "Publiceren"
action that creates (or opens) the publication record and presents the chain
anonymize → consent → publish as a stepped wizard: each step shows its gate
state and deep-links to the existing capability surface (anonymisation
review, consent records, this pipeline) — the wizard orchestrates and MUST
NOT reimplement those capabilities. Views use `@conduction/nextcloud-vue`
components (ADR-012) with NL Design tokens via Nextcloud CSS variables
(ADR-003).

#### Scenario: Wizard reflects gate state

- GIVEN a document with unreviewed entities
- WHEN the operator opens the publish wizard
- THEN the anonymisation step shows as blocking with a deep-link to the entity review, and the publish step is disabled
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts
