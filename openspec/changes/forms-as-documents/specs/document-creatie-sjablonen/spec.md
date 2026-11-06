# document-creatie-sjablonen Specification (delta)

## Purpose

A form field can hold a document reference, and a submitted form becomes a
document on the record. Moved from `documents-from-a-template`.

## ADDED Requirements

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
- @e2e exclude not built: blocked on the decision which surface owns forms (tasks 0.1)

#### Scenario: The form in the dossier

- GIVEN a submitted aanvraagformulier
- WHEN it is submitted
- THEN a document is generated from the values as submitted and filed on the case
- @e2e exclude not built: blocked on the decision which surface owns forms (tasks 0.1)

#### Scenario: A later value change does not rewrite the form

- GIVEN a filed aanvraagformulier document
- WHEN a field on the case is changed afterwards
- THEN the filed document still shows what was submitted
- @e2e exclude not built: blocked on the decision which surface owns forms (tasks 0.1)
