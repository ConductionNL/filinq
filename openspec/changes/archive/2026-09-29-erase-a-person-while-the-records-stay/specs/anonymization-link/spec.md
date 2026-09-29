# anonymization-link Specification (delta)

---
status: proposed
---

## Purpose

A person is removed from every document they appear in, while the
documents stay. Round 4 discovery candidate C-access-and-privacy-22,
`must`, one driven passer and one documented, the Archiefwet against the
AVG in one feature. The document half of cluster 38; openregister keeps
the record fields, the delete and the data subject's export.

## ADDED Requirements

### Requirement: An erasure is a request about a person, with a ground (REQ-EPR-01)

Filinq MUST carry a `subjectErasureRequest` naming the subject by the
identifiers the instance holds, the legal ground, the requester and a
lifecycle with progress. The erasure MUST be declared as its own
`x-openregister-processing` activity with its purpose, legal ground, data
categories and retention.

#### Scenario: A request is a record, not a button

- GIVEN an operator acting on a data subject's erasure request
- WHEN they open the request
- THEN the subject, the ground and the requester are recorded before anything is previewed

#### Scenario: The activity is declared

- GIVEN the register descriptor after this change
- WHEN the processing activity export is produced
- THEN subject erasure appears with its purpose, its ground and its retention
- @e2e exclude the export is a descriptor read; covered by the processing-activity PHPUnit test

### Requirement: Nothing is touched before a preview is read (REQ-EPR-02)

Filinq MUST produce a preview before any content is written, listing
every document the subject appears in, the occurrence count per document,
which documents carry a final current version, which are under an
obligation, and which cannot be processed and why. The preview MUST be
capped, and MUST state both the cap and the full count. Every occurrence
MUST be reviewable, and MUST be excludable with a reason before the job
runs.

#### Scenario: The operator sees the blast radius first

- GIVEN a subject appearing in eighty documents
- WHEN the preview is produced
- THEN each document is listed with its occurrence count, its version state and any obligation on it

#### Scenario: A common surname is not erased wholesale

- GIVEN occurrences matched on a name that also belongs to somebody else
- WHEN the operator reviews the preview
- THEN they exclude those occurrences with a reason, and the job does not touch them

#### Scenario: The cap is stated, not hidden

- GIVEN a subject appearing in more documents than the preview cap
- WHEN the preview is produced
- THEN it states the cap and the full count

### Requirement: The records stay while the person goes (REQ-EPR-03)

The job MUST replace the subject's occurrences through the existing
anonymisation path, document by document, with progress on the request,
and MUST be resumable. Where a document's current version is final, the
job MUST write a new version referencing the one it supersedes, then
erase the superseded version's content while keeping its version record.
No document record MUST be deleted by the erasure.

#### Scenario: The zaak survives the erasure

- GIVEN a case whose documents name the subject
- WHEN the erasure runs
- THEN every document record still exists, and the subject's name is in none of their content

#### Scenario: A final besluit is superseded, not edited

- GIVEN a besluit whose current version is final
- WHEN the erasure reaches it
- THEN a new version carries the anonymised content, references the superseded one, and the superseded version record remains

#### Scenario: A run that stops says where

- GIVEN a job interrupted halfway
- WHEN it is resumed
- THEN it continues from where it stopped and the request's progress reflects what completed

### Requirement: An obligation refuses, and the refusal is a decision (REQ-EPR-04)

A document under a retention obligation, a publication prohibition or a
legal hold MUST NOT be erased. It MUST be listed with the obligation
named and the person who must decide. Any reversible-pseudonymisation
mapping entries for the subject MUST be destroyed, and the destruction
MUST be recorded.

#### Scenario: A legal hold wins

- GIVEN a document under a legal hold
- WHEN the erasure runs
- THEN the document is untouched and is listed with the hold named

#### Scenario: The way back is closed

- GIVEN a subject whose earlier pseudonymisation left an encrypted mapping
- WHEN the erasure runs
- THEN the mapping entries for that subject are destroyed and the destruction is recorded
- @e2e exclude mapping destruction; covered by PHPUnit against the mapping store

### Requirement: The act is certified (REQ-EPR-05)

Filinq MUST write an `erasureCertificate` recording which documents were
erased, how many occurrences each, when, by whom, on which ground, what
was refused and why, and which already-published copies need
republishing. The certificate MUST carry its own retention and MUST
outlive the erasure.

#### Scenario: The data subject can be told what happened

- GIVEN a completed erasure
- WHEN the certificate is read
- THEN it names the documents, the counts, the moment, the actor and the ground

#### Scenario: The archivist can see the records stayed

- GIVEN the same certificate
- WHEN an archivist reads it
- THEN it shows that no document record was deleted, and which documents were refused and why

#### Scenario: Published copies are named for republication

- GIVEN a subject appearing in two already-published documents
- WHEN the erasure completes
- THEN the certificate lists both as needing republication
