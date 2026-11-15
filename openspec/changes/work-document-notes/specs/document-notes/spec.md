# document-notes Specification (delta)

## Purpose

Colleagues leave notes on a document for each other, where they open it in
filinq. A note is a Nextcloud comment on the file, so it shows in Files
too and follows the file's access. Matrix row `wk-notes` (filinq).

## ADDED Requirements

### Requirement: A note is a Nextcloud comment on the file (REQ-WDN-001)

Filinq MUST store a note as a Nextcloud comment with object type `files`
on the document's file id, through Nextcloud's comments endpoint. Filinq
MUST NOT keep notes in a schema, a table or a copy of its own. A note
added in filinq MUST show in the Files sidebar's comments, and a comment
added in Files MUST show in filinq.

Rows: `wk-notes` (filinq matrix)

#### Scenario: A note written in filinq shows in Files

- GIVEN a clerk with a contract open in the viewer on `/my-documents`
- WHEN the clerk adds the note "Annex 2 is missing, asked the supplier"
- THEN a colleague who opens the same file in Files sees that note in the comments tab
- @e2e tests/e2e/document-notes.spec.ts

### Requirement: Notes sit beside the document on filinq's screens (REQ-WDN-002)

The viewer on My documents, the document view of a dossier, and the
intake worklist MUST show the notes of the document and MUST let the user
add a note and delete their own. On the intake worklist the notes MUST
open from a "Notes" row action.

Rows: `wk-notes` (filinq matrix)

#### Scenario: A registrar leaves a line on the intake worklist

- GIVEN a registrar on `/intake` with a waiting letter
- WHEN she chooses "Notes" on its row and adds "Check with finance before assigning"
- THEN a colleague who opens "Notes" on the same row reads that line with her name and the time
- @e2e tests/e2e/document-notes.spec.ts

#### Scenario: Each document of a dossier has its own notes

- GIVEN a dossier with two documents, one with a note
- WHEN an employee switches between them in the dossier's document view
- THEN the note shows only under the document it was written on
- @e2e tests/e2e/document-notes.spec.ts

### Requirement: A note follows the file's access and stays on its file (REQ-WDN-003)

Only a user who can open the file MUST be able to read or add its notes.
An anonymised copy, a merged PDF, a share link, a bundle or a generated
document MUST NOT carry the notes of the file it came from.

Rows: `wk-notes` (filinq matrix)

#### Scenario: The anonymised copy starts clean

- GIVEN a document with a note that names a person
- WHEN a Woo coordinator anonymises it and opens the anonymised copy
- THEN the copy shows no notes
- @e2e tests/e2e/document-notes.spec.ts

#### Scenario: No access, no notes

- GIVEN an employee who cannot open a colleague's private document
- WHEN that employee asks the comments endpoint for the document's file id
- THEN Nextcloud refuses and no note is shown
- @e2e exclude access is enforced by Nextcloud's comments endpoint; covered by Newman in task 3.1

### Requirement: My documents shows which documents have notes (REQ-WDN-004)

The My documents list MUST show on each row how many notes the document
has, taken from the same listing request that loads the rows.

Rows: `wk-notes` (filinq matrix)

#### Scenario: A clerk spots the documents with notes

- GIVEN a folder in which one of five documents has two notes
- WHEN a clerk opens it on `/my-documents`
- THEN that row shows a note icon with 2 and the other rows show none
- @e2e tests/e2e/document-notes.spec.ts
