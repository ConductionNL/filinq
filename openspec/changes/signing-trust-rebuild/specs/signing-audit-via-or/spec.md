# signing-audit-via-or Specification (delta)

---
status: proposed
---

## Purpose

Finish GH #289 (verified open at HEAD): signing audit entries currently anchor
to a uuid-only `ObjectEntity` stub, retrieval scans every `filinq.signing.*`
entry fleet-wide and filters in PHP, and no test proves OR's API actually
rejects mutation of these entries. These ADDED requirements bind entries to the
real OR object, bound the query, and turn the immutability assumption into a
verified control.

## ADDED Requirements

### Requirement: Audit entries bind to the real signing-request object (REQ-DDSTR-006)

`SigningAuditService::logEvent()` MUST resolve the actual signing-request
object (register `signing`, schema `signingRequest`) via ObjectService and
create the OR audit entry against that entity, so the entry carries real
register/schema/object linkage and the tamper-evident hash chain anchors to a
real row — not a uuid-only stub. When resolution fails (request deleted
mid-flight), the service MUST still write the entry with the uuid fallback and
log a warning: an unlinked audit entry is acceptable, a dropped one is not.
Audit rows MUST never be mutated after their hash is sealed.

#### Scenario: Entry carries real object linkage

- GIVEN a persisted signing request in the `signing` register
- WHEN `logEvent(<its uuid>, 'SIGNED', ...)` is called
- THEN the created OR audit entry references the resolved signing-request entity (objectUuid AND its register/schema linkage)
- AND the entry participates in the object's hash chain
- @e2e exclude OR persistence internals with no UI surface — covered by PHPUnit against the live OR container (tests/unit/Service/SigningAuditServiceTest.php)

#### Scenario: Vanished request still yields an audit entry

- GIVEN a signing-request uuid that no longer resolves to an object
- WHEN `logEvent()` is called for a CANCELLED action on that uuid
- THEN an audit entry is still created carrying the uuid
- AND a warning is logged about the unresolved linkage
- @e2e exclude fail-soft fault injection — covered by PHPUnit (tests/unit/Service/SigningAuditServiceTest.php)

### Requirement: Audit retrieval is object-scoped and bounded (REQ-DDSTR-007)

`SigningAuditService::getAuditTrail()` MUST query OR's audit trail scoped to
the signing request's object identity (objectUuid filter pushed into the
mapper/query layer) instead of fetching all `filinq.signing.*` entries
fleet-wide and filtering in PHP. The result MUST remain chronologically
ordered. If the OR mapper exposes no object-scoped filter, the filter MUST be
added on the OR side rather than retaining the unbounded scan.

#### Scenario: Trail query does not scan unrelated requests

- GIVEN 3 audit entries for signing request A and 500 entries for other signing requests
- WHEN `getAuditTrail(A)` is called
- THEN exactly A's 3 entries are returned in chronological order
- AND the underlying query is scoped to A's object identity (verified via query assertions, not post-hoc PHP filtering)
- @e2e exclude query-shape assertion — covered by PHPUnit (tests/unit/Service/SigningAuditServiceTest.php)

### Requirement: Immutability is a verified control, not an assumption (REQ-DDSTR-008)

The test suite MUST prove, against a live OpenRegister instance, that signing
audit entries cannot be updated or deleted through OR's API: an authenticated
attempt to modify or remove a `filinq.signing.*` audit entry MUST be
rejected (4xx, e.g. 403/405), and the entry's content and hash-chain integrity
MUST be unchanged afterwards. The deployment guide MUST state that signing
audit immutability and the ≥ 3650-day retention (Archiefwet 1995) are enforced
by OR configuration, and the deploy check asserting that retention MUST cover
the signing register. This closes the #289 finding that the in-app
"reject" guards were dead code creating a false sense of enforcement.

#### Scenario: API mutation of an audit entry is rejected

- GIVEN an existing `filinq.signing.SIGNED` audit entry in OR
- WHEN an authenticated PUT and DELETE are attempted on that entry via OR's audit-trail API
- THEN both are rejected with a 4xx response
- AND `GET /api/audit-trails?objectUuid=...` afterwards returns the entry unchanged with a passing chain-integrity check
- @e2e exclude API-contract negative test — covered by Newman (signing audit collection) against the Postgres dev instance, no UI surface

#### Scenario: Retention deploy check covers the signing register

- GIVEN the documented deploy check for audit retention
- WHEN it runs against an instance whose signing-register retention is below 3650 days
- THEN the check fails naming the signing register and Archiefwet 1995
- @e2e exclude deploy-time configuration assertion — covered by the retention check script + PHPUnit, not a browser flow
