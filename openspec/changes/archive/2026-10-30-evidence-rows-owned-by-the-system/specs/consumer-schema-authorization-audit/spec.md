# consumer-schema-authorization-audit (delta)

## ADDED Requirements

### Requirement: Existing evidence rows are owned by the system

Every row of a schema that only Filinq's server writes (documentVersion,
publicationLogEntry, prohibitionOverrideAudit, signerRecord, signingSession,
signingRequest, anonymizationLink, sanitizationRecord, erasureCertificate,
legalHoldCase, subjectErasureRequest, publicationRecord, printJob) MUST be
owned by OpenRegister's system identity, including rows written before these
schemas became server-only, so the owner bypass cannot reopen one.

#### Scenario: a certificate written before the change
- GIVEN an `erasureCertificate` row whose owner is the officer who ran the erasure
- WHEN the app is upgraded
- THEN the row's owner is the system identity
- AND the officer can no longer change or delete it through the OpenRegister API

#### Scenario: a schema users still write
- GIVEN a `template` row owned by the user who made it
- WHEN the app is upgraded
- THEN the row keeps its owner

#### Scenario: OpenRegister is not installed
- GIVEN OpenRegister's tables do not exist
- WHEN the repair step runs
- THEN it changes nothing and does not fail the upgrade
