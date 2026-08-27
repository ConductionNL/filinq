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
and publication capabilities. A dossier keeps a bound **home folder**
(`@self.folder`) as the physical location of the files it owns, but
membership is an explicit relation list (`documents[]`) so one document may
belong to several dossiers (the strict folder=dossier equivalence is
relaxed — see REQ-DDDMU-008); renaming a dossier keeps its bound home folder
in sync (REQ-DDDMU-009). The dossier lifecycle itself is declared on the
schema (see the `dossier-register` delta in this change).

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
an uploaded or picked file to the dossier: an uploaded/copied file lands in
the dossier's bound home folder and is recorded in `documents[]`; a file
picked from elsewhere is added by reference (its node id appended to
`documents[]`) without being moved (see REQ-DDDMU-008). The documents list
MUST update immediately without a page reload. Removing a document (GH #50)
MUST require confirmation; the confirmation MUST state whether the file will
be trashed or merely unlinked. When the document lives in this dossier's home
folder and is a member of no other dossier, removal MUST move the file to the
Nextcloud trashbin (recoverable) — never a silent hard delete; when the
document is a referenced member (lives elsewhere or is also a member of
another dossier), removal MUST drop only this dossier's membership reference
and MUST NOT delete the underlying file.

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
is uploaded in a single action on a Filinq upload surface: dossier
name (prefilled), optional description, and a "grondslagen allemaal
geselecteerd" toggle that, when on, preselects all six canonical `base`
grondslagen. Confirming MUST create the dossier bound to a new folder
containing the uploads; cancelling MUST upload the files without creating a
dossier. Uploading a single document MUST NOT show the modal.

#### Scenario: Multi-upload triggers the modal, single upload does not

- GIVEN a Filinq upload surface
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

### Requirement: Multi-dossier membership via an explicit relation list (REQ-DDDMU-008)

A document MAY belong to several dossiers. Membership MUST be tracked by an
explicit `documents[]` relation list on the `dossier` schema (NC file node
references — see the `dossier-register` delta) rather than by the bound home
folder alone; the strict folder=dossier equivalence is relaxed. A dossier's
membership set that the index count and detail list render MUST be the union
of the files physically present in its bound home folder and the files
referenced in `documents[]` (deduplicated). Files uploaded or created through
the dossier MUST land in the home folder AND be recorded in `documents[]`;
adding an existing document to a second dossier MUST append its node
reference to that dossier's `documents[]` and MUST NOT move or copy the file
(link semantics), so the same document appears in every dossier that
references it. A `documents[]` entry whose target file no longer exists (or
the caller cannot read) MUST be rendered as a visible "missing/inaccessible"
marker, never silently dropped, and MUST NOT break the detail view. All
membership writes are full-payload PUTs (the saveObject PUT-semantic rule)
and MUST NOT null the dossier's other fields.

#### Scenario: One document is a member of two dossiers

- GIVEN dossier A whose home folder holds a document, and dossier B
- WHEN the operator adds that document to dossier B via "+ Document toevoegen" (pick from elsewhere)
- THEN B's `documents[]` gains the file's node reference, the file is not moved out of A's home folder, and both A and B list the document in their detail
- @e2e tests/e2e/workflows/dossier-management.spec.ts

#### Scenario: Unlinking a referenced member keeps the file

- GIVEN a document that is a member of dossier A (its home folder) and dossier B (by reference)
- WHEN the operator removes the document from dossier B and confirms
- THEN only B's membership reference is dropped, the underlying file is untouched, and the document still lists in dossier A
- @e2e tests/e2e/spec-coverage/dossier-management.spec.ts

### Requirement: Renaming a dossier keeps the bound home folder in sync (REQ-DDDMU-009)

Renaming a dossier MUST attempt to rename the dossier's bound home folder
(`@self.folder`) to match — whether the rename comes from the inline title
rename (REQ-DDDMU-003) or the create-time name — keeping the object name and
its physical home folder in sync rather than diverging. The rename MUST run under the caller's
NC filesystem ACLs. If the folder cannot be renamed — the caller lacks write
permission, or a sibling node with the target name already exists — the
dossier object rename MUST still succeed and the UI MUST surface a readable
warning that the bound folder was not renamed (a name collision MUST NOT
silently overwrite or merge folders). Only the dossier's own bound home
folder is renamed; folders bound to other dossiers and referenced documents
that live elsewhere are never touched.

#### Scenario: Inline rename renames the bound folder

- GIVEN a dossier named "Handhaving 2024" bound to a writable home folder of the same name
- WHEN the operator renames the dossier inline to "Handhaving 2025"
- THEN the dossier object and its bound home folder are both named "Handhaving 2025"
- @e2e tests/e2e/workflows/dossier-management.spec.ts

#### Scenario: Folder rename collision keeps the object rename and warns

- GIVEN a dossier whose parent already contains a sibling folder with the target name
- WHEN the operator renames the dossier to that name
- THEN the dossier object is renamed, the bound folder is left unchanged, and the UI shows a readable warning that the folder could not be renamed
- @e2e exclude filesystem collision path — covered by PHPUnit tests (tests/unit/Service/DossierManagementServiceTest.php)
