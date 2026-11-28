# dossier-management-ui Specification

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

## Requirements

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

The app MUST provide a dossier detail view aggregating the dossier's
documents, its grondslagen, the folder-batch runs for its folder and, when
the publication pipeline is installed, its publication state. Selecting a
document MUST switch the inline viewer without a page reload (GH #50). Each
document row MUST offer a mini-menu with "mark as checked" (hidden without
the review workbench) and "remove". Listings MUST honour the caller's
access, as the scenarios below state.

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

#### Scenario: Each section shows what its source holds

- GIVEN a dossier detail
- WHEN the sections render
- THEN each document in the bound folder shows its name, its anonymisation state from `anonymizationLink` and, when the review workbench is installed, its checked state from the `documentReview` gate
- AND the batch-runs section lists the folder-batch runs for the dossier's folder, deep-linking to the redaction-at-scale progress view when installed
- AND the publication section shows the `publicationRecord` state when the pipeline is installed
- @e2e exclude moved out of the requirement text unchanged; no dedicated test yet

#### Scenario: The detail reads only what the caller may read

- GIVEN a caller and a dossier id
- WHEN the detail endpoint is called
- THEN it verifies the caller can read the dossier object before aggregating anything (no IDOR via the dossier id)
- AND every document listing runs through the caller's Nextcloud filesystem ACLs, so a file the caller cannot see is not listed
- @e2e exclude moved out of the requirement text unchanged; no dedicated test yet

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

The dossier detail MUST offer an "Add document" action (GH #48) that takes
the operator to Folder Analysis, which owns upload and folder selection; the
dossier page carries no second uploader or picker (decision 130). The
membership API MUST add documents as the scenarios below state, and the list
MUST show a new member without a page reload. Removing a document (GH #50)
MUST require a confirmation that states whether the file will be trashed or
only unlinked.

#### Scenario: Added document appears immediately

- GIVEN an open dossier detail
- WHEN the operator uses "Add document"
- THEN Folder Analysis opens, where the upload happens
- AND a file added to the dossier through the membership API is listed on the detail without a page reload
- @e2e tests/e2e/workflows/dossier-management.spec.ts

#### Scenario: Removal is confirmed and recoverable

- GIVEN a dossier with a document
- WHEN the operator chooses remove and confirms
- THEN the file is moved to the trashbin and disappears from the list
- AND restoring it from the trashbin returns it to the dossier folder
- @e2e tests/e2e/spec-coverage/dossier-management.spec.ts

#### Scenario: The membership API adds by upload or by reference

- GIVEN a dossier with a bound home folder
- WHEN a file is uploaded or copied into the dossier through the membership API
- THEN it is placed in the home folder and recorded in `documents[]`
- AND a file picked from elsewhere is added by reference only: its node id is appended to `documents[]` and the file is not moved (see REQ-DDDMU-008)
- @e2e exclude covered by PHPUnit (tests/unit/Service/DossierManagementServiceTest.php::testLinkingAppendsAReferenceWithoutMoving)

#### Scenario: Removal trashes an owned file and unlinks a referenced one

- GIVEN a document in this dossier's home folder that no other dossier holds, and a document that lives elsewhere or that another dossier also holds
- WHEN the operator removes each and confirms
- THEN the first is moved to the Nextcloud trashbin (recoverable), never silently hard-deleted
- AND for the second only this dossier's membership reference is dropped and the underlying file is not deleted
- @e2e exclude covered by PHPUnit (tests/unit/Service/DossierManagementServiceTest.php::testAFileOnlyThisDossierHoldsIsTrashed, ::testAFileAnotherDossierAlsoHoldsIsUnlinkedEvenFromItsOwnFolder, ::testAFileOutsideTheDossierFolderIsUnlinked)

### Requirement: Auto-dossier on multi-upload (REQ-DDDMU-005)

The upload dialog of a Filinq upload surface MUST offer to create a dossier
(GH #47) when more than one document is uploaded in one action, asking for a
dossier name beside the grondslagen choice; one dialog serves both cases
(decision 130). Confirming with a name MUST create the dossier bound to a
new folder holding the uploads; an empty name or cancel MUST NOT create one.
A single-document upload MUST NOT ask for a dossier name.

#### Scenario: Multi-upload triggers the modal, single upload does not

- GIVEN a Filinq upload surface
- WHEN the operator uploads three documents in one action, fills a name and confirms
- THEN a dossier exists with the three documents in its folder
- AND a subsequent single-document upload asks for no dossier name
- @e2e tests/e2e/workflows/dossier-management.spec.ts

### Requirement: Dossier-level actions wire existing capabilities (REQ-DDDMU-006)

The dossier detail header MUST offer **batch anonymize**, **generate
grondslagen PDF** and **publish**, each wired to the existing capability as
the scenarios below state. No action may re-implement batch, report or
publication logic. Every presence-gated action MUST be hidden, not broken,
when its capability is absent. The change MUST declare its dependency on
`fix-dossier-grondslagen-route-mismatch` and MUST NOT duplicate that fix.

#### Scenario: Grondslagen PDF from the detail

- GIVEN a dossier with bases set and the route fix applied
- WHEN the operator clicks "Generate grondslagen PDF"
- THEN the existing endpoint is called and the produced PDF is offered
- @e2e tests/e2e/workflows/dossier-management.spec.ts

#### Scenario: Each action reuses its existing flow

- GIVEN the dossier detail header
- WHEN the operator uses an action
- THEN **batch anonymize** starts or links the existing folder-batch flow for the bound folder, deep-linking to the redaction-at-scale progress view when installed
- AND **generate grondslagen PDF** calls `POST api/anonymization/dossier/{dossierId}/grondslagen-pdf`
- AND **publish** opens the woo-publicatie-pipeline publish entry with the dossier preselected, and is hidden when the pipeline is not installed
- @e2e exclude moved out of the requirement text unchanged; no dedicated test yet

#### Scenario: Publish action is presence-gated

- GIVEN an instance without the woo-publicatie-pipeline
- WHEN the dossier detail renders
- THEN no publish action is offered and the publication section explains the capability is not installed
- @e2e exclude absent-capability rendering permutation — covered by component tests and PHPUnit presence-gate tests (tests/unit/Service/DossierManagementServiceTest.php)

### Requirement: Lifecycle rendering and transitions (REQ-DDDMU-007)

The index and detail MUST render the dossier `status` as a chip (absent =
`open`) and the detail MUST offer only the transitions the declarative
lifecycle allows from the current status (declared in the `dossier-register`
delta). A transition writes `status` through a full-payload save;
OpenRegister's lifecycle guard rejects an out-of-order write. The UI MUST
surface a rejection as a readable error, never silently.

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

#### Scenario: The offered transitions are the declared ones

- GIVEN the declarative lifecycle
- WHEN the detail lists the targets for each status
- THEN they are exactly `open → in-review`, `in-review → processed`, `in-review → open`, `processed → published`, `processed → closed` and `published → closed`
- @e2e exclude covered by PHPUnit (tests/unit/Service/DossierManagementServiceTest.php::testOnlyDeclaredTransitionsAreOffered)

### Requirement: Multi-dossier membership via an explicit relation list (REQ-DDDMU-008)

A document MAY belong to several dossiers. Membership MUST be tracked by an
explicit `documents[]` relation list on the `dossier` schema (NC file node
references), not by the bound home folder alone. The membership the index
count and the detail list render MUST be the deduplicated union of the files
in the home folder and those referenced in `documents[]`. All membership
writes are full-payload saves and MUST NOT null the dossier's other fields.

#### Scenario: One document is a member of two dossiers

- GIVEN dossier A whose home folder holds a document, and dossier B
- WHEN that document is added to dossier B by reference (`POST api/dossiers/{id}/documents`, pick from elsewhere)
- THEN B's `documents[]` gains the file's node reference, the file is not moved out of A's home folder, and both A and B list the document in their detail
- @e2e tests/e2e/workflows/dossier-management.spec.ts

#### Scenario: Unlinking a referenced member keeps the file

- GIVEN a document that is a member of dossier A (its home folder) and dossier B (by reference)
- WHEN the operator removes the document from dossier B and confirms
- THEN only B's membership reference is dropped, the underlying file is untouched, and the document still lists in dossier A
- @e2e tests/e2e/spec-coverage/dossier-management.spec.ts

#### Scenario: Files land in the home folder; existing files are linked

- GIVEN a dossier
- WHEN a file is uploaded or created through the dossier
- THEN it lands in the home folder AND is recorded in `documents[]`
- AND adding an existing document to a second dossier appends its node reference to that dossier's `documents[]` without moving or copying it, so it appears in every dossier that references it
- @e2e exclude covered by PHPUnit (tests/unit/Service/DossierManagementServiceTest.php::testLinkingAppendsAReferenceWithoutMoving, ::testLinkingIsIdempotent)

#### Scenario: A reference to a missing or unreadable file stays visible

- GIVEN a `documents[]` entry whose file no longer exists or the caller cannot read
- WHEN the index and the detail render
- THEN the entry shows as a visible "missing/inaccessible" marker, never silently dropped
- AND the detail view still renders
- @e2e exclude covered by PHPUnit (tests/unit/Service/DossierManagementServiceTest.php::testUnresolvableMembershipReferenceIsSurfaced, ::testIndexReportsMissingSeparately)

### Requirement: Renaming a dossier keeps the bound home folder in sync (REQ-DDDMU-009)

Renaming a dossier, inline (REQ-DDDMU-003) or at creation, MUST attempt to
rename its bound home folder (`@self.folder`) to match, under the caller's NC
filesystem ACLs. When the folder cannot be renamed (no write permission, or
a sibling already has the name), the object rename MUST still succeed and
the UI MUST show a readable warning; a collision MUST NOT overwrite or merge
folders. Only the dossier's own home folder is ever renamed.

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
