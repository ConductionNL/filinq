# woo-publicatie-pipeline Specification

## Purpose
Readiness-gated active-publication pipeline chaining Filinq's existing
anonymisation and publication-consent capabilities into a handoff to
OpenCatalogi as the publication endpoint (Woo active disclosure / Woo-index,
DiWoo). One `publicationRecord` per published unit carries the readiness
state (entities reviewed, consent clear, prohibitions clear), the assembled
DiWoo metadata, the endpoint reference, the destruction date and the
lifecycle; every pipeline action lands in an append-only publication log.
Filinq prepares, hands off and tracks state — it builds no portal, sitemap
or search surface (OpenCatalogi/OpenWoo own those).

## Requirements

### Requirement: Publication record and log schemas (REQ-DDWPP-001)

The app MUST declare two schemas in the `filinq` register:
`publicationRecord` (`subjectType` enum `document`|`dossier`,
`documentFileRef`, `dossierRef`, `redactedFileRef`, readiness booleans
`entitiesReviewed`/`consentClear`/`prohibitionsClear` +
`readinessEvaluatedAt` + `readinessReasons`, DiWoo block `wooCategory`/`documentsoort`/
`publisher`/`officieleTitel`/`creatiedatum`/`publicatiedatum`, `status`,
`endpointPublicationRef`, `handoffAt`, `depublicationReason`,
`depublicationRequestedAt`, `destructionDate`, `destructionDateSource`) and
`publicationLogEntry` (`publicationRecordRef`, `action` enum, `actor`,
`timestamp`, `details`, `snapshot`). All data MUST be stored as OpenRegister
objects (ADR-001), with a register version bump for boot import.

#### Scenario: Register import creates the pipeline schemas

- GIVEN Filinq and OpenRegister installed
- WHEN `ConfigurationService::importFromApp()` runs on boot
- THEN `publicationRecord` and `publicationLogEntry` exist in the `document` register with the seeded demo objects queryable
- @e2e exclude boot-time register import with no UI surface of its own, covered by PHPUnit (tests/unit/Service/Publication/PublicationPipelineServiceTest.php::testEveryPayloadValidatesAndEveryMoveIsDeclared validates every write against both schemas)

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
published`, `handed_off → depublication_requested`, `published →
depublication_requested`, `depublication_requested → depublished`, so
OpenRegister refuses any other status move on save. The `draft ↔ ready` move
MUST only be made by the readiness evaluation, and a handoff MUST re-evaluate
readiness first and demote the record to `draft` when it has regressed, so a
status written directly never reaches the publication platform.

#### Scenario: Direct status write cannot skip the gate

- GIVEN a publication record in `draft` with `consentClear` false
- WHEN a save attempts `status = handed_off`
- THEN OpenRegister's lifecycle guard rejects the transition
- @e2e exclude server-side lifecycle, covered by PHPUnit (tests/unit/Service/Publication/PublicationPipelineServiceTest.php::testEveryPayloadValidatesAndEveryMoveIsDeclared, ::testAHandoffChecksAgainAndDemotes)

#### Scenario: Regressed readiness demotes the record at handoff

- GIVEN a record in `ready` for which a new objection has since been received
- WHEN handoff is attempted
- THEN readiness is re-evaluated, `consentClear` becomes false, the record returns to `draft` and no endpoint object is written
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

### Requirement: DiWoo metadata assembly (REQ-DDWPP-004)

The publication record MUST carry an operator-completed DiWoo metadata block
before handoff: `wooCategory` (one of the 17 TOOI Woo informatiecategorie
codes, selected from OpenCatalogi's bundled TOOI value list, read through
its `TooiVocabularyService` and checked on the server — never free text), `documentsoort`, `publisher` (TOOI organisatie URI), `officieleTitel`,
`creatiedatum` and `publicatiedatum`. Handoff MUST be blocked while any
mandatory DiWoo field is missing. Filinq assembles and passes these values;
TOOI validation and `diwoo:Document` emission remain OpenCatalogi's
(WOO-TOOI-001/002). OpenCatalogi's `publication` schema has no fields for the
category, document type or publisher yet, so those stay on the Filinq record
until it does.

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
mapping `officieleTitel` to `title`, `documentsoort` to `documentsoort` and
`publicatiedatum` to `publicationDate` (the fields OpenCatalogi's
`publication` schema declares; the field map is pinned against a copy of it
by a unit test), and attach the redacted derivative (`redactedFileRef`),
NEVER the original file. The app MUST NOT write a document type into
`summary`. Before the write, the app MUST check that OpenCatalogi's
`publication` schema declares `documentsoort`; when it does not, the handoff
MUST be refused with a message naming the OpenCatalogi update it needs, and
the record MUST stay `ready`. It MUST store `endpointPublicationRef` and
`handoffAt`, transition to `handed_off` and append a `handed_off` log entry
only after the write succeeded. Filinq MUST NOT render any public
portal, sitemap or search surface. When OpenCatalogi is not installed, handoff
MUST be disabled with an explanatory state, never a silent no-op. An OR
authorization failure, or OpenCatalogi refusing the document type, on the
endpoint write MUST surface to the operator and leave the record `ready`.

#### Scenario: Successful handoff creates the endpoint publication

- GIVEN a `ready` record with complete DiWoo metadata, `documentsoort` `besluit` and a redacted derivative
- WHEN the operator confirms handoff
- THEN an OpenCatalogi `publication` object exists with `documentsoort` holding the besluit member and an empty `summary`, with the redacted file attached
- AND the record shows `handed_off` with `endpointPublicationRef` set and a `handed_off` log entry
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

#### Scenario: The document type never reaches the summary

- GIVEN any record with a `documentsoort`
- WHEN `OpenCatalogiPublicationMap::toPublication()` maps it
- THEN the result has `documentsoort` and has no `summary` key
- @e2e exclude a field map; covered by PHPUnit `OpenCatalogiPublicationMapTest::testTheDocumentTypeGoesToItsOwnField`

#### Scenario: An opencatalogi without the field refuses the handoff

- GIVEN an installed opencatalogi whose `publication` schema does not declare `documentsoort`
- WHEN the operator confirms handoff
- THEN the handoff is refused naming the opencatalogi update it needs, no publication is written, and the record stays `ready`
- @e2e exclude needs an older opencatalogi; covered by PHPUnit `PublicationPipelineServiceTest::testAnOpenCatalogiWithoutTheFieldRefusesTheHandoff`

#### Scenario: A document type outside the national list is refused

- GIVEN a record with `documentsoort` `memo van de wethouder`
- WHEN the handoff writes and OpenCatalogi refuses the value
- THEN the operator sees the refusal naming `documentsoort`, and the record stays `ready`
- @e2e exclude a cross-app refusal; covered by PHPUnit `PublicationPipelineServiceTest::testARefusedDocumentTypeLeavesTheRecordReady`

#### Scenario: Endpoint absent

- GIVEN an instance without OpenCatalogi
- WHEN the operator views a `ready` record
- THEN the handoff action is disabled with an explanation that OpenCatalogi provides the publication endpoint
- @e2e tests/e2e/spec-coverage/woo-publications.spec.ts

### Requirement: De-publication with mandatory reason (REQ-DDWPP-006)

An operator MUST be able to withdraw a published record only with a
non-empty `depublicationReason`. Withdrawal MUST set `depublicationDate` on
the endpoint publication object (OpenCatalogi's published-predicate removes
it from all public surfaces), transition the record through
`depublication_requested` to `depublished`, and append log entries carrying
the reason. The endpoint object MUST NOT be deleted — the accountability
trace is retained.

#### Scenario: Withdraw a publication

- GIVEN a record in `published`
- WHEN the operator withdraws it with reason "Onterecht gepubliceerd: lopend bezwaar"
- THEN the endpoint publication's `depublicationDate` is set and the record reaches `depublished`
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
OpenCatalogi's retention job; Filinq MUST NOT delete or archive endpoint
publications itself. Propagation MUST append a `destruction_date_propagated`
log entry.

#### Scenario: Destruction date reaches the endpoint

- GIVEN a published record with destruction date 2034-11-03 from "selectielijst 2020, procestype 6"
- WHEN the destruction date is propagated
- THEN the endpoint publication carries `retentionExpiresAt` 2034-11-03 and a `retentionNote` naming the source
- AND the record's log shows a `destruction_date_propagated` entry
- @e2e exclude endpoint-side retention fields are not rendered in Filinq, covered by PHPUnit (tests/unit/Service/Publication/PublicationPipelineServiceTest.php::testADestructionDateReachesThePlatform)

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
- @e2e exclude route-surface property, covered by PHPUnit (tests/unit/Controller/PublicationControllerTest.php::testNoRouteChangesTheLog)

### Requirement: Publish wizard chains the pipeline from document and dossier context (REQ-DDWPP-009)

The document viewer (MyDocuments) MUST offer a "Publish" action that creates
the publication record and opens it, and the record MUST present the chain
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

### Requirement: The hand-off files the publication under its Woo category (REQ-DDWPP-010)

On hand-off, filinq MUST write the record's Woo category on the OpenCatalogi
`publication` object as `wooCategory`, in OpenCatalogi's own code
(`infocat001` to `infocat017`). A record that holds the basename of the TOOI
URI MUST be translated to that code. A value that is neither MUST block the
hand-off with the reason "Unknown Woo category". When the category of a
handed-off record changes, filinq MUST write the new code on the publication.
filinq MUST NOT build a sitemap of its own: the listing stays OpenCatalogi's.

Rows: `woo-index` (filinq matrix)

#### Scenario: A handed-off decision appears in its category sitemap

- GIVEN a ready publication record with category "Besluiten op Woo-verzoeken" and complete metadata
- WHEN the operator hands it off from `/publications`
- THEN the OpenCatalogi publication carries `wooCategory` set to that category's code
- AND the OpenCatalogi sitemap of that category lists the publication
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

#### Scenario: A record from before the change still hands off

- GIVEN a ready record whose `wooCategory` holds the TOOI URI basename `c_8c840238`
- WHEN the operator hands it off
- THEN the publication carries the OpenCatalogi code whose TOOI URI ends in `c_8c840238`
- @e2e exclude a record from before the change cannot be made through the page; PHPUnit covers it

#### Scenario: An unknown category blocks the hand-off

- GIVEN a ready record whose `wooCategory` is `notacategory`
- WHEN the operator hands it off
- THEN the hand-off is refused with the reason "Unknown Woo category"
- AND no OpenCatalogi publication is written
- @e2e exclude the picker cannot store an unknown value; PHPUnit covers it

#### Scenario: Changing the category moves the publication

- GIVEN a handed-off record filed under one category
- WHEN the operator saves a different category on it
- THEN the OpenCatalogi publication carries the new code
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts
