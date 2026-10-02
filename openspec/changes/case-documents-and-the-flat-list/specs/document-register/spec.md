# document-register Specification (delta)

---
status: proposed
---

## Purpose

A case is read as document records and as a flat list of files, the
folder behind it keeps its permissions in step with the domain, and an
upload obeys an administered policy. Round 4 discovery cluster 21, eleven
candidates. Consumed by dossiq on its Files tab.

## ADDED Requirements

### Requirement: Every file on a case is readable as one flat list (REQ-CDF-01)

Filinq MUST offer a list of every file on every document record of a
given object, carrying the record each file belongs to as a column. The
list MUST page and filter. It MUST be offered as a leaf per ADR-066 so a
consuming app places it beside, not instead of, the record view.

#### Scenario: The jurist finds the attachment

- GIVEN a case with twelve document records holding thirty files
- WHEN the handler opens the flat file list
- THEN all thirty files are listed, each naming the record it belongs to

#### Scenario: Both views stay available

- GIVEN a consuming app placing the record list and the flat list
- WHEN a user switches between them
- THEN both read the same document records and neither replaces the other

### Requirement: A domain folder is created and kept in step (REQ-CDF-02)

Filinq MUST create the file folder for a unit or case domain when the
domain is created, and MUST reconcile the folder's permissions whenever
the domain's membership changes. A nightly job MUST reconcile and MUST
report both the drift it corrected and the drift it could not. Every
correction MUST be written to the audit trail. A folder MUST be able to
be pinned out of reconciliation with a recorded reason.

#### Scenario: A new domain gets its folder

- GIVEN a new case domain with two groups
- WHEN it is created
- THEN its folder exists with those two groups' permissions

#### Scenario: A member leaves and the folder follows

- GIVEN a domain whose group loses a member
- WHEN the membership changes
- THEN the folder's permissions are reconciled and the correction is in the audit trail

#### Scenario: Drift that cannot be corrected is reported

- GIVEN a folder on a mount that refuses a permission change
- WHEN the nightly job runs
- THEN it reports the folder, the permission and the reason, and does not claim success
- @e2e exclude the nightly job's report; covered by PHPUnit with a mount stub that refuses

### Requirement: An upload obeys an administered policy (REQ-CDF-03)

Filinq MUST carry an `uploadPolicy` declaring allowed extensions, allowed
media types, a maximum size and whether an unknown type is refused. The
policy MUST be enforced server-side on every write path, including the
leaf, the API and the intake channels. The media type MUST be read from
the file's bytes and not from its name.

#### Scenario: An executable is refused

- GIVEN a policy allowing PDF, ODT and DOCX
- WHEN a user uploads an executable renamed to `bijlage.pdf`
- THEN the upload is refused, naming the detected type

#### Scenario: The same refusal on every path

- GIVEN the same policy
- WHEN the file arrives through the API and through an intake channel
- THEN both are refused with the same message
- @e2e exclude multi-path enforcement; covered by PHPUnit across the write paths

#### Scenario: Too large is too large

- GIVEN a policy with a maximum of 200 MB
- WHEN a 400 MB file is uploaded
- THEN it is refused before the bytes are stored

### Requirement: One document record serves several domains (REQ-CDF-04)

A document record MUST carry `domains[]` rather than one owning domain.
Access MUST be the union of what those domains allow, evaluated by
OpenRegister. Unlinking the last domain MUST keep the record, owned by
its creator.

#### Scenario: One advies, three zaaktypen

- GIVEN an advies linked to three case domains
- WHEN a handler in each domain opens it
- THEN all three read one record with one version history

#### Scenario: An unlinked document is not deleted

- GIVEN a document record linked to one domain
- WHEN that link is removed
- THEN the record still exists, owned by its creator, and appears in their documents list

### Requirement: A person sees their own documents, and nothing rots (REQ-CDF-05)

Filinq MUST offer a list of the document records a person created. A
nightly job MUST remove upload fragments older than a declared age and
MUST log the count and the bytes removed.

#### Scenario: My documents

- GIVEN a user who created nine document records
- WHEN they open their documents list
- THEN those nine are listed as records, not as files in a folder

#### Scenario: The reaper reports

- GIVEN eleven upload fragments older than the declared age
- WHEN the nightly job runs
- THEN they are removed and the count and the bytes are logged
- @e2e exclude nightly job; covered by PHPUnit with seeded fragments

### Requirement: An external store is validated before it is used (REQ-CDF-06)

When the case folder lives on a storage Nextcloud mounts, filinq MUST
validate on setup that the mount supports what the upload policy and the
folder reconciliation need, and MUST name the parts it cannot enforce.
Desktop editing and resumable upload MUST be verified against the case
folder and MUST NOT be reimplemented in filinq.

#### Scenario: A mount that cannot do permissions

- GIVEN an external mount that does not support per-group permissions
- WHEN it is configured as the store for a domain
- THEN setup names the reconciliation requirement it cannot meet, before any document is stored
- @e2e exclude the validation is reached from the setup path, which needs a probe over a real mount; covered by PHPUnit on ExternalMountValidator with a probe that refuses and a probe that cannot answer

#### Scenario: Word on the desktop, through the sync client

- GIVEN a case document in the case folder
- WHEN a handler edits it in the desktop application and saves
- THEN the change syncs back to the case folder and the document record's version history records it
