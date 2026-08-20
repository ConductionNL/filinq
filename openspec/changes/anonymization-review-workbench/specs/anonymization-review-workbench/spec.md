# anonymization-review-workbench Specification (delta)

---
status: proposed
---

## Purpose

The interactive human-review workbench for anonymisation: a per-document
split view (original preview | anonymized preview) with the existing
`EntityReviewTable` as the decision panel, selection-to-manual-entity,
pre-application of the org-level "always anonymise" (prohibition) and
"never anonymise" (standing consent) rule lists, grondslag auto-proposal
surfacing, and a per-document human-checked gate that anonymize-commit and
export respect. Detection, extraction and anonymisation engines remain in
OpenRegister; the workbench orchestrates and renders (GDPR Art. 5(1)(d)
accuracy — human verification of machine detections; Woo Art. 5 grondslag
per redaction).

## ADDED Requirements

### Requirement: Side-by-side review workbench per document (REQ-DDARW-001)

The system MUST provide a review workbench view for a single document that
renders the original document preview (reusing the existing
`PdfViewer`/`WordViewer`/`TextViewer` components by MIME type) side-by-side
with the anonymized result preview, and hosts the existing
`EntityReviewTable` as the decision panel. When no anonymized result exists
yet (no `anonymizationLink` object for the file's `sourceFileId`), the
anonymized pane MUST show a pending placeholder stating that anonymisation
has not been committed. The anonymized pane MUST resolve its file via the
`anonymizationLink` OR object (`sourceFileId` → `anonymizedFileId`). For
file types no viewer supports, the pane MUST degrade to the existing
"cannot be previewed" message while the entity table remains fully
functional.

#### Scenario: Reviewer opens the workbench for an extracted document

- GIVEN a PDF that has been extracted and has detected entities
- AND no anonymized result exists yet for the file
- WHEN the reviewer opens the review workbench for the file
- THEN the left pane shows the original PDF preview
- AND the right pane shows the pending placeholder
- AND the entity review table lists the detected entities
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Anonymized result appears after a commit

- GIVEN a document with a committed anonymisation run (an `anonymizationLink` object exists)
- WHEN the reviewer opens the review workbench
- THEN the right pane previews the file referenced by `anonymizedFileId`
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

### Requirement: One shared entity decision model (REQ-DDARW-002)

The workbench MUST use the existing `EntityReviewTable` entity state model
(`included`, `_decisionSkip`, `_decisionBases`) as the single source of
truth for per-entity decisions. Accept/reject/toggle actions performed from
the preview panes (clicking a highlighted occurrence) MUST mutate the same
model the table mutates, and MUST NOT introduce a second entity state store.
The table's existing realtime search, entity-type filter, selected/total
counters and bulk select/deselect of visible rows MUST remain available
inside the workbench.

#### Scenario: Toggling from the preview updates the table

- GIVEN the workbench shows a document with the entity "Jan Jansen" (PERSON) included
- WHEN the reviewer clicks the highlighted "Jan Jansen" occurrence in the original pane and chooses reject
- THEN the entity's `included` flag becomes false in the review table row
- AND the selected/total counter decreases by one
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Search and type filter operate in the workbench

- GIVEN a document with 40 detected entities of mixed types
- WHEN the reviewer types "Utrecht" in the search box and selects type ORGANIZATION
- THEN only ORGANIZATION entities whose value contains "Utrecht" remain visible
- AND the counter shows the filtered count out of the total
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

### Requirement: Select text in the preview to create a manual entity (REQ-DDARW-003)

The system MUST let the reviewer select a text range in the original
preview and create a manual entity from it. The selection MUST open the
existing `AddManualEntityModal` pre-filled with the selected text, an
entity-type picker, and a grondslag (bases) picker pre-filled from the
grondslag auto-proposal mapping for the chosen type (see REQ-DDARW-006).
Submission MUST go to OpenRegister's chunk-aware matcher
(`POST /api/files/{fileId}/manual-entities`) — the client MUST NOT compute
or transmit character offsets. Created entity occurrences MUST appear in
the review table without a full page reload, and a zero-match outcome MUST
be reported to the reviewer as a non-error notice (existing modal
behaviour).

#### Scenario: Reviewer creates a manual entity from a selection

- GIVEN the original pane shows extracted text containing "Pieter de Vries"
- WHEN the reviewer selects "Pieter de Vries" and chooses "Add as entity", picks type PERSON, and submits
- THEN the modal was pre-filled with the selected text
- AND one review-table row appears per occurrence matched by OpenRegister
- AND the row carries the proposed grondslag for PERSON as its pre-filled bases
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Selection matches nothing in the extracted chunks

- GIVEN a selection whose normalised text does not occur in the file's extracted chunks
- WHEN the reviewer submits the manual entity
- THEN the workbench shows the zero-match notice
- AND no review-table row is added
- @e2e exclude zero-match path depends on OR matcher internals; covered by vitest store tests (tests/vitest) and the existing PoC widget contract — no stable UI fixture can force a zero-match deterministically

### Requirement: Org rule lists pre-apply during review (REQ-DDARW-004)

The system MUST pre-apply the org-level rule lists to every entity in the
review workbench: an entity matching an active `publicationProhibition`
rule ("always anonymise") MUST be pre-set `included: true` and carry a
warning badge naming the rule; an entity matching an active standing-consent
`publicationConsent` rule (scope `entity`, "never anonymise") MUST be
pre-set `included: false` and carry an info badge naming the rule. Both
rule kinds MUST be evaluated by the existing `PolicyMatchService::match()`
in a single pass, with prohibitions winning on conflict (existing
behaviour). The pre-application is a default: the reviewer MAY override
either direction, except that overriding a prohibition remains subject to
the existing prohibition commit gate. Both badges MUST link to the matched
rule's detail view.

#### Scenario: Prohibited entity arrives pre-included with a badge

- GIVEN an active prohibition rule matching "Beschermde Getuige A" (PERSON)
- AND a document in which that entity was detected
- WHEN the reviewer opens the workbench
- THEN the entity row is pre-set included with a prohibition badge naming the rule
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Standing-consent entity arrives pre-excluded with a badge

- GIVEN an active standing consent for "Gemeente Voorbeeldstad" (ORGANIZATION)
- AND a document in which that entity was detected
- WHEN the reviewer opens the workbench
- THEN the entity row is pre-set excluded with a standing-consent badge naming the rule
- AND the reviewer can still re-include it manually
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Entity matching both kinds follows the prohibition

- GIVEN one active prohibition and one active standing consent that both match the same entity text
- WHEN the entity is pre-applied in the workbench
- THEN the prohibition wins and the entity is pre-set included with the prohibition badge
- @e2e exclude conflict precedence is matcher logic already unit-covered in PolicyMatchService; asserted by PHPUnit (tests/unit/Service/PolicyMatchServiceTest.php), not by UI

### Requirement: Grondslag per org rule (REQ-DDARW-005)

The `publicationProhibition` and `publicationConsent` schemas MUST gain an
optional `bases` array of grondslag slugs referencing `base` objects (the
same dialect as `dossier.bases`). The policy CRUD endpoints
(`/api/policy/prohibitions`, `/api/policy/standing-consents`) MUST accept,
persist and return `bases`; rules without `bases` MUST keep matching
exactly as before (additive, non-breaking). When a rule with `bases`
pre-applies to an entity whose decision bases are empty, the rule's `bases`
MUST be pre-filled as that entity's grondslag (fill-only-when-empty,
consistent with the existing proposal semantics). The rule category remains
the existing `entityType` property; the enum is NOT widened by this change.

#### Scenario: Admin adds a grondslag to a prohibition rule

- GIVEN an existing prohibition rule without bases
- WHEN an admin edits the rule in the prohibition form and picks "Woo art. 5.1.2.e" as grondslag
- THEN `PUT /api/policy/prohibitions/{id}` persists `bases: ["woo-art-5-1-2-e"]`
- AND the rule detail shows the grondslag
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Rule bases pre-fill the matched entity's grondslag

- GIVEN a prohibition rule with `bases: ["woo-art-5-1-2-e"]` matching a detected entity whose decision bases are empty
- WHEN the workbench pre-applies the rule
- THEN the entity row's grondslag picker is pre-filled with "woo-art-5-1-2-e"
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Pre-change rule without bases keeps matching

- GIVEN a prohibition rule created before this change (no `bases` property)
- WHEN `PolicyMatchService` evaluates entities
- THEN the rule matches exactly as before
- AND the API returns the rule without error
- @e2e exclude backwards-compatibility of an optional schema property; covered by PHPUnit (tests/unit/Service/PolicyCrudServiceTest.php, PolicyMatchServiceTest.php)

### Requirement: Grondslag auto-proposal surfaced and overridable (REQ-DDARW-006)

The workbench MUST show the per-entity-type grondslag proposals that
`GrondslagProposalService` already writes at extraction time (config key
`docudesk.grondslagen.entity_type_bases`, CB #122) as the pre-filled value
of each entity row's grondslag picker, visually marked as proposed until
the reviewer confirms or changes it. The proposal MUST also pre-fill the
grondslag picker of the manual-entity modal based on the chosen entity
type. Reviewer overrides MUST always win over proposals and rule `bases`.

#### Scenario: Proposal appears pre-filled for a detected BSN

- GIVEN the admin mapping assigns grondslag "woo-art-5-1-2-e" to entity type BSN
- AND a document in which a BSN was detected
- WHEN the reviewer opens the workbench
- THEN the BSN row's grondslag picker is pre-filled with "woo-art-5-1-2-e" and marked as proposed
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Reviewer override wins

- GIVEN an entity row with a proposed grondslag
- WHEN the reviewer picks a different grondslag
- THEN the reviewer's choice is what is sent on the anonymise request
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

### Requirement: Per-document checked gate (REQ-DDARW-007)

The system MUST provide a per-document "reviewed" mark persisted as a
`documentReview` OR object (`fileId` as idempotency key, `checkedOn`,
`checkedBy`, `entityCountAtCheck`, `manualEntityCount`, optional `note`)
via `POST /api/review/{fileId}/check` and `DELETE /api/review/{fileId}/check`
(uncheck), readable via `GET /api/review/{fileId}`. Marking a document
checked MUST require that extraction has completed for the file. Any
mutation of the file's entity decisions after `checkedOn` (toggle, manual
entity added, bases changed) MUST invalidate the check: the gate MUST treat
the document as unchecked until re-checked.

#### Scenario: Reviewer marks a document as checked

- GIVEN an extracted document whose entities the reviewer has reviewed
- WHEN the reviewer clicks "Mark as reviewed" in the workbench
- THEN a `documentReview` object is created with `checkedBy` = the reviewer and `checkedOn` = now
- AND the workbench shows the document as reviewed
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Editing entities invalidates the check

- GIVEN a document marked checked
- WHEN the reviewer adds a manual entity to the document
- THEN the document reverts to unchecked
- AND the workbench prompts for re-review
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

### Requirement: Checked gate blocks anonymize-commit and export (REQ-DDARW-008)

The system MUST enforce the checked gate at commit time while the admin
setting `docudesk.review.checked_gate` is `enforced` (default):
`POST /api/anonymization/anonymize/{fileId}` MUST return HTTP 409 with a
machine-readable reason when the file has no valid `documentReview`, and
`POST /api/anonymization/batch/{batchId}/anonymize` MUST return HTTP 409
listing every unchecked file in the batch, committing nothing. Export/download surfaces for anonymized results produced by this
flow MUST be blocked by the same predicate. When the setting is
`advisory`, the operations MUST proceed but the response MUST include the
gate verdict (`checkedGate: { passed: false, uncheckedFiles: [...] }`).
Only admins MAY change the setting.

#### Scenario: Single-file commit blocked while unchecked

- GIVEN gate mode `enforced` and an extracted, unchecked document
- WHEN `POST /api/anonymization/anonymize/{fileId}` is called
- THEN the system returns HTTP 409 with reason `document_not_reviewed`
- AND no anonymized file is produced
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: Batch commit blocked listing unchecked files

- GIVEN gate mode `enforced` and a batch of 3 files of which 1 is unchecked
- WHEN `POST /api/anonymization/batch/{batchId}/anonymize` is called
- THEN the system returns HTTP 409 listing the unchecked file
- AND no file in the batch is anonymized
- @e2e exclude batch API contract without stable UI surface for partial-checked batches; covered by PHPUnit + Newman (tests/newman /api/anonymization/batch contracts)

#### Scenario: Advisory mode proceeds with verdict

- GIVEN gate mode `advisory` and an unchecked document
- WHEN the anonymise commit is called
- THEN the operation succeeds
- AND the response contains `checkedGate.passed: false`
- @e2e exclude admin-config branch of an API contract; covered by PHPUnit (tests/unit/Controller/AnonymizationControllerTest.php)

### Requirement: Rule lists reachable and manageable from the workbench (REQ-DDARW-009)

The workbench MUST link to the org rule lists (the existing
`ProhibitionIndex` and `StandingConsentIndex` views), and those views MUST
be reachable from the app navigation (today they are deep-link only,
verified at HEAD). The prohibition and standing-consent form modals MUST
include the grondslag (`bases`) picker and continue to show the rule
category (`entityType`). Creating or updating a rule MUST invalidate the
policy matcher cache so the next review pass uses the new rule (existing
`PolicyMatchService::invalidateCache()` seam).

#### Scenario: Reviewer navigates from a badge to the rule list

- GIVEN a workbench entity carrying a prohibition badge
- WHEN the reviewer clicks the badge
- THEN the prohibition detail/list view opens showing the matched rule
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts

#### Scenario: New rule applies to the next review

- GIVEN a reviewer-created standing consent for "Stichting Voorbeeld"
- WHEN a new document containing "Stichting Voorbeeld" is extracted and opened in the workbench
- THEN the entity is pre-excluded with the standing-consent badge
- @e2e tests/e2e/spec-coverage/review-workbench.spec.ts
