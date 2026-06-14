# Proposal: migrate-signing-audit-to-or-audit

## Why

ADR-022 (Apps Consume OpenRegister Abstractions) prohibits home-grown audit trails for
OR-owned objects. Docudesk currently violates this rule:

- `lib/Service/SigningAuditService.php` maintains a parallel audit storage pipeline. Its
  `logEvent()` method saves signing events to a `signingAuditEntry` register/schema
  configured via `IAppConfig` (`signingAuditEntry_register`, `signingAuditEntry_schema`),
  bypassing OR's built-in audit trail entirely.
- `rejectUpdate()` and `rejectDelete()` manually re-implement immutability guards (throwing
  RuntimeException for Archiefwet compliance) — these are already provided by OR's
  hash-chained audit trail (HTTP 405 natively).
- `getAuditTrail()` scans all objects in the private schema and filters client-side — an
  inefficient O(n) approach that OR's indexed audit trail API does not need.

The umbrella spec `consume-or-audit-trail-fleet-wide` (hydra) mandates per-app migration
specs for each violating app within 90 days of umbrella acceptance.

## What

Rewrite `SigningAuditService` to emit through OR's audit trail:

1. Convert `logEvent()` to call `AuditTrailMapper::createAuditTrailEntry()` with
   action type `docudesk.signing.{ACTION}` (e.g. `docudesk.signing.SIGNED`) and signing
   context in the `$context` payload (persisted in the `changed` JSON column).
2. Remove `rejectUpdate()` and `rejectDelete()` (OR audit trail enforces immutability natively).
3. Rewrite `getAuditTrail()` to query `GET /api/audit-trails?objectUuid={signingRequestId}`
   instead of scanning the private schema.
4. Mark the `signingAuditEntry` register/schema configuration deprecated; document sunset.
5. Document the 10-year Archiefwet retention requirement as an OR deployment configuration
   (set OR retention to ≥ 3650 days for signing-related audit events).

## Capabilities

### New Capabilities

- `signing-audit-via-or`: Signing events are discoverable via
  `GET /api/audit-trails?objectUuid={signRequestId}` with action types matching
  `docudesk.signing.*`. OR handles immutability, hash chaining, and Archiefwet retention.

### Modified Capabilities

- `signing-audit-service`: `SigningAuditService` is rewritten as a thin adapter around OR's
  audit trail. Callers retain the same `logEvent()` interface but the underlying storage
  path changes. `rejectUpdate()` / `rejectDelete()` are removed.

## Affected Projects

- [x] Project: `docudesk` — all implementation work is in this repo
- Reference: `hydra/openspec/changes/consume-or-audit-trail-fleet-wide/` (umbrella policy)
- Reference: `openregister/openspec/specs/audit-trail-immutable/spec.md` (OR contract)

## Scope

### In Scope

- Rewriting `SigningAuditService.logEvent()` to emit via OR audit trail
- Rewriting `SigningAuditService.getAuditTrail()` to query OR audit trail API
- Removing `rejectUpdate()` and `rejectDelete()` from `SigningAuditService`
- Documenting 10-year Archiefwet retention configuration (deploy-time OR setting)
- Deprecating `signingAuditEntry` register/schema configuration
- Tests verifying discoverability via OR audit trail API for all VALID_ACTIONS

### Out of Scope

- Changing the signing flow, `SigningController`, `SigningService`, or
  `lib/Service/Signing/*.php` provider implementations (signing logic unchanged)
- Modifying OR's audit-trail API (consumed, not changed)
- Changing the Archiefwet 10-year retention period (requirement stays, mechanism moves
  to OR configuration)
- Historical data backfill (out of scope — per ADR-022 + Archiefwet retention, historical rows
  remain in the deprecated `signingAuditEntry` store read-only; backfilling into OR's hash
  chain would risk integrity since chronological ordering of legacy rows is not guaranteed)

## Sunset Date

The `signingAuditEntry` schema deprecation sunset date is one major docudesk release after
this spec is accepted. Existing rows remain queryable (read-only) until that date.

## Success Criteria

- `openspec validate --strict migrate-signing-audit-to-or-audit` exits 0.
- `SigningAuditService.logEvent()` emits via OR audit trail (no `signingAuditEntry` writes).
- `GET /api/audit-trails?objectUuid={signRequestId}` returns signing events for all
  VALID_ACTIONS.
- `composer check:strict` passes.
