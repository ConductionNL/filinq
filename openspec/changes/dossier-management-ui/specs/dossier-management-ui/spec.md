# dossier-management-ui Specification (delta)

---
status: proposed
---

## Purpose

First-class dossier surface: a browsable dossier index and a dossier detail
that aggregates documents, lifecycle status, grondslagen, batch runs and
publication state; membership operations (create, inline rename, add/remove
documents, auto-dossier on multi-upload per GH #47/#48/#50/#51); and
dossier-level actions wired to the existing folder-batch, grondslagen-PDF
and publication capabilities. A dossier's documents are the files in its
bound folder (`@self.folder`) — no second membership store. The dossier
lifecycle itself is declared on the schema (see the `dossier-register`
delta in this change).

## ADDED Requirements

### Requirement: Dossier index page (REQ-DDDMU-001)

The app MUST provide a Dossiers index manifest page (`CnIndexPage` +
`CnDataTable`) listing dossiers with name, status chip (absent `status`
renders as `open`), document count (live folder listing), grondslagen chips
resolved via the existing `BasesResolverService` (unknown slugs render as a
visible warning, never silently dropped), last reviewed (`checkedOn`) and —
when the woo-publicatie-pipeline is installed — the publication state chip.
Manifest schema references MUST use slugs.

#### Scenario: Index lists dossiers with live context

- GIVEN a seeded dossier with two documents in its folder and `bases: ["persoonsgegevens"]`
- WHEN the Dossiers index renders
- THEN the row shows the dossier name, status chip `open`, document count 2 and the resolved grondslag chip
- @e2e tests/e2e/spec-coverage/dossier-management.spec.ts

### Requirement: Dossier detail aggregates documents, grondslagen, batch runs and publication state (REQ-DDDMU-002)

The app MUST provide a dossier detail view aggregating: the documents in the
bound folder (name, per-document anonymisation state via `anonymizationLink`,
checked state via the review-workbench `documentReview` gate when installed),
the grondslagen section, the folder-batch runs for the dossier's folder
(deep-linking to the redaction-at-scale progress view when installed), and
the publication section (`publicationRecord` state, when the pipeline is
installed). Selecting a document MUST switch the inline viewer without a page
reload (GH #50). Each document row MUST offer a mini-menu with "mark as
checked" (hidden without the review workbench) and "remove". All document
listings MUST honour the caller's Nextcloud filesystem ACLs, and the detail
endpoint MUST verify the caller can read the dossier object before
aggregating (no IDOR via dossier id).

#### Scenario: Detail shows the aggregated dossier

- GIVEN a dossier whose folder holds three documents, one anonymised, with one completed folder batch
- WHEN the operator opens the dossier detail
- THEN the three documents are listed with the anonymised one carrying its state chip, the grondslagen section shows the resolved bases, and the batch-runs section lists the completed batch
- @e2e tests/e2e/workflows/dossier-management.spec.ts

#### Scenario: Document switch has no page reload

- GIVEN the dossier detail with two documents
- WHEN the operator clicks the second document
- THEN the viewer swaps to it without a route navigation or page reload
- @e2e tests/e2e/spec-coverage/dossier-management.spec.ts

### Requirement: Create dossier and inline title rename (REQ-DDDMU-003)

The app MUST provide a create-dossier dialog (name required, description,
bases multi-select bound to the `base` register, folder selection or
creation) and inline renaming of the dossier title on the detail header
(GH #51): clicking the title makes it editable and the change is saved as a
full-payload update. Because OR saves are PUT-semantic, every dossier update
MUST carry all schema fields forward — a rename MUST NOT null `bases`,
`checkedOn`, `description` or `status`.

#### Scenario: Inline rename preserves the other fields

- GIVEN a dossier with `bases: ["persoonsgegevens"]` and a set `checkedOn`
- WHEN the operator renames it inline to "Handhaving centrum 2025"
- THEN the new name is shown in the index and detail
- AND `bases` and `checkedOn` still hold their previous values
- @e2e tests/e2e/spec-coverage/dossier-management.spec.ts

### Requirement: Add and remove documents in a dossier (REQ-DDDMU-004)

The dossier detail MUST offer a "+ Document toevoegen" CTA (GH #48) that adds
an uploaded or picked file into the dossier's bound folder, with the
documents list updating immediately without a page reload. Removing a
document (GH #50) MUST require confirmation and MUST move the file to the
Nextcloud trashbin (recoverable) — never a silent hard delete. Membership is
the folder content: the app MUST NOT maintain a second membership store.

#### Scenario: Added document appears immediately

- GIVEN an open dossier detail
- WHEN the operator uses "+ Document toevoegen" and uploads a PDF
- THEN the file lands in the dossier folder and the documents list shows it without a page reload
- @e2e tests/e2e/workflows/dossier-management.spec.ts

#### Scenario: Removal is confirmed and recoverable

- GIVEN a dossier with a document
- WHEN the operator chooses remove and confirms
- THEN the file is moved to the trashbin and disappears from the list
- AND restoring it from the trashbin returns it to the dossier folder
- @e2e tests/e2e/spec-coverage/dossier-management.spec.ts

### Requirement: Auto-dossier on multi-upload (REQ-DDDMU-005)

The app MUST show the auto-dossier modal (GH #47) when more than one document
is uploaded in a single action on a DocuDesk upload surface: dossier
name (prefilled), optional description, and a "grondslagen allemaal
geselecteerd" toggle that, when on, preselects all six canonical `base`
grondslagen. Confirming MUST create the dossier bound to a new folder
containing the uploads; cancelling MUST upload the files without creating a
dossier. Uploading a single document MUST NOT show the modal.

#### Scenario: Multi-upload triggers the modal, single upload does not

- GIVEN a DocuDesk upload surface
- WHEN the operator uploads three documents in one action, fills a name and confirms with the toggle on
- THEN a dossier exists with the three documents in its folder and all six canonical grondslagen selected
- AND a subsequent single-document upload shows no modal
- @e2e tests/e2e/workflows/dossier-management.spec.ts

### Requirement: Dossier-level actions wire existing capabilities (REQ-DDDMU-006)

The dossier detail header MUST offer: **batch anonymize** — starting/linking
the existing folder-batch flow for the bound folder (deep-linking to the
redaction-at-scale progress view when installed); **generate grondslagen
PDF** — calling the existing
`POST api/anonymization/dossier/{dossierId}/grondslagen-pdf` endpoint, which
requires the sibling `fix-dossier-grondslagen-route-mismatch` change (this
change MUST declare that dependency and MUST NOT duplicate the fix); and
**publish** — the woo-publicatie-pipeline publish entry with the dossier
preselected, hidden when the pipeline is not installed. No action may
re-implement batch, report or publication logic. Every presence-gated action
MUST be hidden, not broken, when its capability is absent.

#### Scenario: Grondslagen PDF from the detail

- GIVEN a dossier with bases set and the route fix applied
- WHEN the operator clicks "Generate grondslagen PDF"
- THEN the existing endpoint is called and the produced PDF is offered
- @e2e tests/e2e/workflows/dossier-management.spec.ts

#### Scenario: Publish action is presence-gated

- GIVEN an instance without the woo-publicatie-pipeline
- WHEN the dossier detail renders
- THEN no publish action is offered and the publication section explains the capability is not installed
- @e2e exclude absent-capability rendering permutation — covered by component tests and PHPUnit presence-gate tests (tests/unit/Service/DossierManagementServiceTest.php)

### Requirement: Lifecycle rendering and transitions (REQ-DDDMU-007)

The index and detail MUST render the dossier `status` as a chip (absent =
`open`) and the detail MUST offer only the transitions the declarative
lifecycle allows from the current status (`open → in-review`,
`in-review → processed`, `in-review → open`, `processed → published`,
`processed → closed`, `published → closed`). A transition writes `status`
via a full-payload save; an out-of-order direct write is rejected by
OpenRegister's lifecycle guard (declared in the `dossier-register` delta).
The UI MUST surface the rejection as a readable error, never silently.

#### Scenario: Only legal transitions are offered

- GIVEN a dossier in `open`
- WHEN the operator opens the status control
- THEN only "in-review" is offered, and after transitioning, `processed` and reopen become the offered targets
- @e2e tests/e2e/spec-coverage/dossier-management.spec.ts

#### Scenario: Out-of-order write is rejected server-side

- GIVEN a dossier in `open`
- WHEN a save attempts `status = published`
- THEN OpenRegister rejects the transition and the dossier stays `open`
- @e2e exclude server-side lifecycle guard — covered by PHPUnit transition tests (tests/unit/Service/DossierManagementServiceTest.php)
