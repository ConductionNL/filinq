# Design: a final document is frozen

Kind: code. One lifecycle, one guard, one audited exception.

## Context

`DocumentVersionService` already chains versions.
`GeneratedDocumentLogger` records how a generated document was made.
`x-openregister-lifecycle` is already used on filinq schemas
(`signingSession`, `batchCorrespondenceJob`), so a declared lifecycle is
the house mechanism rather than a new one.

The register is `filinq`, one register since descriptor v8.0.0.

## D1. Final is a lifecycle state, not a boolean

A boolean is set by whoever can write the object. A declared lifecycle
has transitions, guards and an audit entry, and OpenRegister enforces it.
`draft` to `final` is one transition, guarded, and `final` is terminal.

Terminal means no transition out. The exception in D4 is not a
transition; it creates a new version and marks the old one as having been
unfrozen, so the record of what was final at what moment survives.

## D2. The guard is in the service, once

Every write path resolves through the document service. The guard sits
there, so the API, the two editors, the batch correspondence path, the
merge, the anonymisation output and the leaves all hit it without each
one remembering. A guard repeated in six controllers is a guard missing
from the seventh.

Refused writes name the version, its final state and who made it final,
because "cannot edit" with no reason is a support ticket.

## D3. A correction is a new version

Superseding is the whole mechanism. The new version references the one it
supersedes, the old one stays readable and stays final, and a reader of
either can see the chain. This is what makes a correction explicable in
2029 and a rewrite impossible.

## D4. The exception exists and is loud

An administrator may unfreeze. It writes an audit entry with the person,
the moment and the reason, and the document carries a permanent mark that
it was unfrozen. Without an exception, somebody edits the file on the
filesystem, and then there is no record at all. With a silent exception,
the guarantee is worthless. So: allowed, recorded, visible.

## D5. The rule can be declared by the consumer

A consuming app declares, per record type, which of its states make a
document final. filinq stores the declaration against the type reference
and applies it on the state change. That keeps the case-type vocabulary
in dossiq per ADR-022, and it means a besluit freezes because the
decision was taken, not because somebody remembered.

## Risks

- **A write path added later.** The guard is in the service every path
  resolves through, and the tests assert the refusal on each named path,
  so a new path that bypasses the service fails its own test rather than
  silently writing.
- **A file changed on disk.** Freezing the record does not freeze the
  filesystem. The version records the file's checksum at the moment it
  became final, and a mismatch is reported. That is detection, not
  prevention, and the spec says so rather than implying more.
- **An unfreeze used routinely.** The mark is permanent and the audit
  entry is queryable, so routine use is visible to an archivist rather
  than invisible to everybody.
