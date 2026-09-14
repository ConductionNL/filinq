# document-creatie-sjablonen Specification (delta)

---
status: proposed
---

## Purpose

A generated document leaves the building on the right paper, a dossier
leaves as one bundle with a manifest, and a besluitenlijst renders itself
from the view that defines it. Round 4 discovery cluster 30, eight
candidates. Consumed by dossiq per case type.

## ADDED Requirements

### Requirement: The page layout is administered and versioned (REQ-DFT-01)

Filinq MUST carry a `pageLayout` object declaring paper size, margins,
header, footer, logo and whether the first page differs, and MUST version
it. A template MUST name a layout version, and rendering MUST use that
version. The generated document MUST record both the template version and
the layout version it used. Editing a layout MUST create a new version
and MUST NOT change documents already generated or templates naming an
earlier version.

#### Scenario: A besluit on the right briefpapier

- GIVEN a template naming the "Gemeente, besluit" layout
- WHEN a handler generates a besluit
- THEN the output carries that layout's header, footer and logo, with the first page as the layout declares

#### Scenario: A layout change does not rewrite history

- GIVEN a besluit generated in March against layout version 2
- WHEN the layout is edited to version 3
- THEN the March besluit still records version 2 and its file is unchanged

### Requirement: A dossier is downloaded as one bundle with a manifest (REQ-DFT-02)

Filinq MUST offer a download of every file on an object as one archive,
honouring an administered size ceiling. The archive MUST contain a
manifest listing every file included, and every file left out with the
reason. When a selection already exceeds the ceiling the user MUST be
told before the job starts.

#### Scenario: One bundle for the bezwaarcommissie

- GIVEN a case with twenty files the handler may read
- WHEN they download all case files
- THEN one archive is produced containing those twenty files and a manifest naming them

#### Scenario: Nothing is dropped silently

- GIVEN a case whose files exceed the ceiling
- WHEN the archive is produced
- THEN the manifest names every file left out and the reason, and the user was warned before the job started

#### Scenario: A file the user may not read

- GIVEN a case carrying a file outside the user's access
- WHEN they download all case files
- THEN the file is absent and the manifest records that it was left out on permission

### Requirement: A periodic document renders from a saved view (REQ-DFT-03)

Filinq MUST render a template over the records a named saved view
returns, on a declared cadence or on demand. Each run MUST write a new
generated document recording the view, the number of records read and the
moment it ran, and MUST NOT edit a document from an earlier run. A run
against a view that no longer exists MUST fail visibly, naming the view,
and MUST NOT produce an empty document.

#### Scenario: The besluitenlijst makes itself

- GIVEN a saved view of the decisions taken this month and a besluitenlijst template
- WHEN the weekly run fires
- THEN a new document is generated listing those decisions, recording the view and the count

#### Scenario: Last week's list is untouched

- GIVEN a besluitenlijst generated last week
- WHEN this week's run fires
- THEN a second document is generated and the first is unchanged

#### Scenario: A deleted view fails loudly

- GIVEN a schedule naming a view somebody has deleted
- WHEN the run fires
- THEN it fails naming the view, and no document is produced

### Requirement: A released document is reviewed again on a date (REQ-DFT-04)

A released document MUST accept a review interval, from which filinq
computes a review date. On that date the document MUST be listed as due
for review and its owner MUST be notified through the notification
dialect. The document MUST stay listed as due until somebody reviews it.

#### Scenario: A beleidsregel comes back

- GIVEN a released beleidsregel with a review interval of twelve months
- WHEN the review date arrives
- THEN the document is listed as due and its owner is notified

#### Scenario: An unread notification changes nothing

- GIVEN a due document whose owner has not read the notification
- WHEN the list of due documents is opened
- THEN the document is still listed as due

### Requirement: A file is a value a field can hold, and a form becomes a document (REQ-DFT-05)

A form field MUST be able to hold a reference to a document record. The
field MUST validate that the reference resolves and that the caller may
read it, and MUST NOT copy the file into the field. A submitted form MUST
be rendered to a document at submission, from the values as submitted,
and filed on the record.

#### Scenario: Evidence as a field value

- GIVEN a permit form with a field "Overzicht DigiD-aansluitingen" holding a document reference
- WHEN the applicant selects a document they may read
- THEN the field holds the reference and the file is stored once

#### Scenario: The form in the dossier

- GIVEN a submitted aanvraagformulier
- WHEN it is submitted
- THEN a document is generated from the values as submitted and filed on the case

#### Scenario: A later value change does not rewrite the form

- GIVEN a filed aanvraagformulier document
- WHEN a field on the case is changed afterwards
- THEN the filed document still shows what was submitted

### Requirement: A verified signature says what was verified (REQ-DFT-06)

When filinq verifies a signature on a document it MUST state which
signature was checked, against which key or certificate, at what time,
and what the result proves. A verification that could not be completed
MUST say so rather than rendering as unverified.

#### Scenario: The trust is legible

- GIVEN a signed besluit whose signature verifies
- WHEN a handler opens the document
- THEN the result names the signer, the key, the time of checking and what it proves

#### Scenario: An unavailable trust anchor is not a failed signature

- GIVEN a signature whose certificate chain cannot be reached
- WHEN verification runs
- THEN the result says verification could not be completed, and does not report the signature as invalid
