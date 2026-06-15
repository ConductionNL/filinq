# signing-audit-via-or Specification

## Purpose
TBD - created by archiving change migrate-signing-audit-to-or-audit. Update Purpose after archive.
## Requirements
### Requirement: Signing Action Emits OR Audit Event

SHALL emit an OR audit trail entry for every signing action — CREATED, SIGNED, DECLINED,
CANCELLED, EXPIRED, COMPLETED, VIEWED — with action type `docudesk.signing.{ACTION}` (e.g.
`docudesk.signing.SIGNED`, `docudesk.signing.DECLINED`). No signing audit event shall be
written only to the private `signingAuditEntry` schema after this spec ships.

#### Scenario: SIGNED action creates OR audit entry

- GIVEN a signing request with UUID `sign-001` stored in OR
- WHEN `SigningAuditService::logEvent('sign-001', 'SIGNED', ...)` is called
- THEN an OR audit entry SHALL be created with `objectUuid = sign-001`
- AND the entry's `action` field SHALL equal `docudesk.signing.SIGNED`
- AND the entry SHALL be retrievable via `GET /api/audit-trails?objectUuid=sign-001`

#### Scenario: DECLINED action creates OR audit entry

- GIVEN a signing request with UUID `sign-002` stored in OR
- WHEN `SigningAuditService::logEvent('sign-002', 'DECLINED', ...)` is called
- THEN an OR audit entry SHALL be created with action `docudesk.signing.DECLINED`
- AND the hash chain SHALL remain intact across all entries for `sign-002`

#### Scenario: All VALID_ACTIONS produce OR audit entries

- GIVEN the seven action types: CREATED, SIGNED, DECLINED, CANCELLED, EXPIRED, COMPLETED, VIEWED
- WHEN each is passed to `SigningAuditService::logEvent()`
- THEN each MUST produce an OR audit entry with the corresponding `docudesk.signing.*` action type

---

### Requirement: Audit Event Carries Signing Context

The OR audit event `$context` payload (stored in the `changed` JSON column) MUST include
the following fields for every signing action: `signRequestId`, `actorUserId`,
`actorDisplayName`, `ipAddress` (if available), `signatureLevel`, `provider` (signing
method: NativeSigningProvider or external provider name).

#### Scenario: Changed column carries signer identity

- GIVEN an OR audit entry for `docudesk.signing.SIGNED` on `sign-001`
- WHEN the entry is retrieved via the audit trail API
- THEN the `changed` field MUST contain `signRequestId` equal to `sign-001`
- AND the `changed` field MUST contain `actorUserId` and `actorDisplayName`
- AND the `changed` field MUST contain `provider` identifying the signing method

#### Scenario: IP address carried when available

- GIVEN a signing action triggered from IP address `1.2.3.4`
- WHEN the OR audit entry is created
- THEN the `changed` field's `ipAddress` key MUST equal `1.2.3.4`

---

### Requirement: Retention Aligned With Archiefwet

The docudesk signing audit retention configuration SHALL set OR retention to at least 10
years (3650 days) for the register containing signing requests. This configuration is a
deploy-time setting in OR, not enforced in application code.

#### Scenario: Retention configuration documented

- GIVEN a docudesk installation with OR as the backend
- WHEN an administrator consults the docudesk deployment guide
- THEN the guide SHALL specify setting OR retention for the signing register to ≥ 3650 days
- AND the guide SHALL reference Archiefwet 1995 as the regulatory basis

#### Scenario: OR retains signing audit entries for at least 10 years

- GIVEN OR retention for the signing register is configured to 3650 days
- WHEN a signing audit entry is 9 years old
- THEN OR SHALL NOT purge the entry
- AND OR SHALL NOT allow manual deletion of the entry via the API (HTTP 405)

---

### Requirement: No Parallel Audit Storage

Application code MUST NOT write to any audit storage other than OR's audit-trail-immutable
after this spec ships. The `SigningAuditService::logEvent()` method MUST route all events
through OR's audit trail.

#### Scenario: No new signingAuditEntry objects created after migration

- GIVEN the migration is applied
- WHEN `SigningAuditService::logEvent()` is called for any action
- THEN the count of `signingAuditEntry` objects SHALL NOT increase
- AND a new OR audit trail entry SHALL exist for the action

#### Scenario: Existing signingAuditEntry objects remain readable

- GIVEN `signingAuditEntry` objects exist from before the migration
- WHEN the OR API is queried for those objects
- THEN the existing records SHALL remain readable (schema deprecated, not deleted)

---

### Requirement: Audit Discoverable Via OR

Given a signing request or document UUID, querying OR's audit-trail-immutable API SHALL
return all signing events in chronological order with hash-chain integrity verified.

#### Scenario: Full signing history discoverable via OR

- GIVEN a signing request `sign-003` that has gone through CREATED → SIGNED → COMPLETED
- WHEN `GET /api/audit-trails?objectUuid=sign-003` is called
- THEN the response SHALL include three entries in chronological order
- AND each entry SHALL have an `action` field matching `docudesk.signing.*`
- AND `GET /api/audit-trails/verify` SHALL return a passing integrity check for the chain

#### Scenario: SigningAuditService.getAuditTrail reads from OR

- GIVEN the migration is applied
- WHEN `SigningAuditService::getAuditTrail($signingRequestId)` is called
- THEN the method SHALL query OR's audit trail for `objectUuid = $signingRequestId`
- AND MUST NOT scan the deprecated `signingAuditEntry` schema objects for new entries

---

### Requirement: Existing Audit Records Remain Readable

MUST remain queryable in read-only mode until the sunset date documented in `proposal.md`.
Existing `signingAuditEntry` storage SHALL NOT be deleted or made inaccessible by the
migration.

#### Scenario: Historical signing audit records readable after migration

- GIVEN `signingAuditEntry` objects exist from before the migration was applied
- WHEN those records are queried via the OR object API
- THEN they SHALL return the historical records without error

#### Scenario: No write path to signingAuditEntry after migration

- GIVEN the migration is applied
- WHEN any code path triggers a signing action
- THEN no new objects SHALL be written to `signingAuditEntry_schema`
- AND the OR audit trail SHALL receive the new entry instead

