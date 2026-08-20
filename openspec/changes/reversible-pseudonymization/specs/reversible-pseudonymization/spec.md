# reversible-pseudonymization Specification (delta)

---
status: proposed
---

## Purpose

Add a reversible anonymisation mode alongside the existing irreversible one:
entities are replaced with the stable, readable placeholders OpenRegister
already emits (`[PERSOON: 1]`, `[ADRES: 2]`, scope-local via
`getLastPlaceholderMap()` — verified at HEAD), and DocuDesk additionally stores
an encrypted `placeholder → original value` mapping keyed to the existing
`anonymizationLink`. An authorised, fail-closed, audit-logged restore operation
reconstructs the original from the anonymised copy plus the mapping. The
mapping is stored in a `writeOnly`/`_render:false` property and encrypted at
rest, and its lifecycle is tied to the `anonymizationLink` so a deleted
anonymisation never leaves an orphaned re-identification key. The default mode
stays irreversible and stores nothing. This extends OpenRegister's placeholder
emission and the existing `anonymizationLink`; it does not re-implement
entity replacement.

## ADDED Requirements

### Requirement: Encrypted, non-rendered pseudonym-mapping store (REQ-DDRPS-001)

The app MUST provide a `pseudonymMap` register object (in the `document`
register) that references its `anonymizationLink` and stores the
`placeholder → {originalValue, entityType}` mapping in a property that is both
`writeOnly` / `_render:false` (never returned in any ObjectService read
response) and encrypted at rest via Nextcloud's `ICrypto`. The object MUST also
carry a non-sensitive `entryCount`, the encrypting `algorithm` identifier and
the placeholder-numbering `scope`. The encrypted mapping MUST only be decrypted
on the authorised restore path and MUST NOT be exposed on any read route.
`anonymizationLink` MUST gain only a nullable additive `mappingRef` pointer and
no other field change.

#### Scenario: The mapping payload is never returned in a read

- GIVEN a stored pseudonymMap for a reversible anonymisation
- WHEN the object is read through the ObjectService API
- THEN the response contains entryCount and scope but not the mappings payload
- @e2e exclude render-boundary behaviour of a `_render:false` property — covered by PHPUnit (tests/unit/Service/PseudonymMapServiceTest.php::testMappingsNeverRendered)

#### Scenario: The mapping is stored encrypted

- GIVEN a reversible anonymisation producing three placeholder mappings
- WHEN the pseudonymMap is persisted
- THEN the stored mappings value is ICrypto ciphertext, not the plaintext original values
- @e2e exclude at-rest encryption — covered by PHPUnit (tests/unit/Service/PseudonymMapServiceTest.php)

### Requirement: Reversible anonymisation mode reusing OR placeholders (REQ-DDRPS-003)

Anonymisation MUST support a `reversible` mode alongside the existing
irreversible behaviour, with irreversible as the default. In reversible mode
the app MUST reuse OpenRegister's emitted placeholders
(`FileService::getLastPlaceholderMap()`) joined with the original entity values
from the request to build the mapping, and MUST persist it via the encrypted
store (REQ-DDRPS-001); it MUST NOT re-implement entity replacement or introduce
a new placeholder format. In the default (irreversible) mode the app MUST store
no mapping, preserving the irreversibility guarantee. The reversibility choice
MUST be independent of the output-format choice.

#### Scenario: Reversible mode stores a mapping, irreversible does not

- GIVEN a document anonymised once in reversible mode and once in irreversible mode
- WHEN each run completes
- THEN the reversible run has a pseudonymMap referenced by its anonymizationLink and the irreversible run has none
- @e2e tests/e2e/spec-coverage/reversible-pseudonymization.spec.ts

#### Scenario: Placeholders come from OpenRegister, not a new format

- GIVEN a reversible anonymisation of a document containing a person
- WHEN the anonymised output is produced
- THEN the person is replaced with OpenRegister's scope-local readable placeholder (e.g. "[PERSOON: 1]") and that same placeholder is the key in the stored mapping
- @e2e tests/e2e/spec-coverage/reversible-pseudonymization.spec.ts

### Requirement: Authorised restore reconstructs the original (REQ-DDRPS-004)

The app MUST provide a restore operation that, given an `anonymizationLink` with
a `pseudonymMap`, decrypts the mapping and reverses every placeholder back to
its original value, writing the result as a **distinct restored copy** without
mutating the anonymised file. Reversal MUST apply placeholders longest-first so
numbered placeholders cannot clobber one another. When the anonymised output is
a format whose text cannot be safely rewritten in place, the operation MUST
return a re-identification report (placeholder → original) rather than a
corrupted document — never a silent no-op.

#### Scenario: A permitted user restores the original text

- GIVEN a reversibly anonymised text document and a permitted operator
- WHEN they restore it
- THEN a new restored copy is produced in which "[PERSOON: 1]" is back to the original name and the anonymised file still exists unchanged
- @e2e tests/e2e/spec-coverage/reversible-pseudonymization.spec.ts

#### Scenario: Unsafe binary format yields a report, not corruption

- GIVEN a reversibly anonymised output whose text layer cannot be safely rewritten
- WHEN a permitted operator restores it
- THEN the operation returns a placeholder-to-original re-identification report and does not produce a corrupted document
- @e2e exclude binary-format reversal branch — covered by PHPUnit (tests/unit/Service/PseudonymRestoreServiceTest.php::testUnsafeFormatReturnsReport)

### Requirement: Restore is fail-closed gated and audit-logged (REQ-DDRPS-005)

Every restore route MUST be restricted to admins and members of the groups in
`docudesk.pseudonymisation.restore_allowed_groups` (default empty = admins
only), enforced server-side with an explicit auth attribute plus an in-method
gate; a non-member MUST receive HTTP 403 with a neutral body and a
configuration read failure MUST deny access. Every restore AND every denied
attempt MUST be written to OpenRegister's audit trail (actor, timestamp,
link/source reference, outcome). If the audit write fails, the restore MUST be
refused — an unlogged re-identification MUST NOT happen.

#### Scenario: A non-member is refused and the denial is logged

- GIVEN `restore_allowed_groups` is `["privacy-officers"]` and a user in neither that group nor admin
- WHEN they call the restore route
- THEN the response is HTTP 403, no restored copy is produced, and the denial is recorded in the audit trail
- @e2e tests/e2e/spec-coverage/reversible-pseudonymization.spec.ts

#### Scenario: A failed audit write blocks the restore

- GIVEN the audit-trail write fails
- WHEN a permitted operator attempts a restore
- THEN the restore is refused and no restored copy is produced
- @e2e exclude fault-injection on the audit write — covered by PHPUnit (tests/unit/Controller/PseudonymisationControllerTest.php::testFailedAuditRefusesRestore)

### Requirement: Mapping lifecycle is tied to the anonymizationLink (REQ-DDRPS-002)

The `pseudonymMap` MUST be created or overwritten together with its
`anonymizationLink` (a reversible re-anonymisation MUST overwrite the same
mapping, not append a second) and MUST be deleted when its `anonymizationLink`
is deleted, so a removed anonymisation leaves no orphaned re-identification key.

#### Scenario: Deleting the link deletes the key

- GIVEN a reversible anonymisation with a stored pseudonymMap
- WHEN its anonymizationLink is deleted
- THEN the pseudonymMap is also deleted and no re-identification material remains
- @e2e exclude cascade lifecycle — covered by PHPUnit (tests/unit/Service/PseudonymMapServiceTest.php::testMapDeletedWithLink)

#### Scenario: Re-anonymising overwrites the same mapping

- GIVEN a document already reversibly anonymised once
- WHEN it is reversibly anonymised again
- THEN its anonymizationLink still references exactly one pseudonymMap, updated to the new run
- @e2e exclude idempotent overwrite — covered by PHPUnit (tests/unit/Service/PseudonymMapServiceTest.php)

### Requirement: Reversible-mode and restore UI (REQ-DDRPS-006)

The app MUST let the operator choose reversible vs irreversible anonymisation
in the anonymise dialog (default irreversible) and MUST offer a gated "Restore
original" action on the document detail, both in the Manifest-V2 shell
(`src/manifest.json` + `registry.js`, never `src/router/index.js`). The restore
action MUST be shown only to permitted users, and its confirmation dialog —
living in its own file under `src/dialogs/` — MUST state that the restore is
audit-logged before it proceeds. Every `NcSelect` MUST carry an `inputLabel`
and colours/spacing MUST use Nextcloud CSS variables / NL Design tokens.

#### Scenario: Restore action states it is audited before proceeding

- GIVEN a permitted operator on a reversibly anonymised document
- WHEN they trigger "Restore original"
- THEN a confirmation dialog states the restore will be audit-logged before they confirm
- @e2e tests/e2e/spec-coverage/reversible-pseudonymization.spec.ts
