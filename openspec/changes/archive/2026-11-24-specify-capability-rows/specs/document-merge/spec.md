# document-merge Specification (delta)

## Purpose

Copying a merged PDF, or any single document, to the files of another record.

## ADDED Requirements

### Requirement: A document is copied to another record the user may write (REQ-SCR-018)

From the documents list on a record's page, and from a finished merge job, a user MUST be able to copy a document to another record. The copy MUST be written to the target record's folder as a new file with the same name and content, and MUST be linked to the target record the way an uploaded document is. The source document MUST stay where it was. filinq MUST check read on the source and write on the target on the server before it writes anything, and MUST refuse the copy without writing when either check fails. The copy MUST be in the audit trail of both records, naming the other one.

Rows: dossiq `4.14` (provider row: merge to PDF, copy to another case)

#### Scenario: A merged PDF goes to a related case

- GIVEN a merge job that is `done` on case A, and a related case B the handler may write
- WHEN the handler copies the merged PDF to case B
- THEN case B's documents list shows a file with the same name and content
- AND the PDF is still on case A, and both audit trails name the copy

#### Scenario: No write on the target, no copy

- GIVEN a handler who may read case A but not write case B
- WHEN they copy a document from A to B
- THEN the copy is refused with HTTP 403 and no file is written in B's folder
- @e2e exclude server-side refusal; the change that builds it adds a PHPUnit test
