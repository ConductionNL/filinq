# Design: migrate-signing-audit-to-or-audit

## Context

The docudesk signing audit trail currently flows through `SigningAuditService`:

```
SigningController / SigningService
  → SigningAuditService::logEvent(signingRequestId, action, actorUserId, ...)
    → ObjectService::saveObject(signingAuditEntry_register, signingAuditEntry_schema, entry)
      → OR stores as regular object (not an audit-trail entry)
```

Immutability is enforced by `rejectUpdate()` / `rejectDelete()` throwing RuntimeException.
Discovery is done by `getAuditTrail()` scanning all `signingAuditEntry` objects and
filtering client-side — O(n) over all signing audit records, not just the one request.

After migration, the flow MUST be:

```
SigningController / SigningService
  → SigningAuditService::logEvent(signingRequestId, action, actorUserId, ...)  [same interface]
    → AuditTrailMapper::createAuditTrailEntry(ObjectEntity $object,
                                              string $action='docudesk.signing.{ACTION}',
                                              array $context=[...])
      → OR audit trail (hash-chained, natively immutable)
```

The `SigningController` and all calling code in `SigningService` and `lib/Service/Signing/`
are NOT modified — `SigningAuditService`'s public interface (`logEvent()`) remains stable.

## File-by-File Migration Plan

### lib/Service/SigningAuditService.php — REWRITE

**Constructor**: replace `SettingsService` + `IAppConfig` with
`OCA\OpenRegister\Db\AuditTrailMapper`. Retain `LoggerInterface`.

**VALID_ACTIONS constant**: keep the constant — it is the source of truth for valid action
names. The values remain `['CREATED', 'SIGNED', 'DECLINED', 'CANCELLED', 'EXPIRED',
'COMPLETED', 'VIEWED']`. They become the `{ACTION}` suffix of the namespaced type.

**logEvent() method**: rewrite body:
1. Validate `$action` against `VALID_ACTIONS` (same guard as current).
2. Build action type: `'docudesk.signing.' . $action`
3. Build `$context` array (persisted in the `changed` JSON column):
   ```php
   $context = [
       'signRequestId'   => $signingRequestId,
       'actorUserId'     => $actorUserId,
       'actorDisplayName'=> $actorDisplayName,
       'ipAddress'       => $ipAddress,
       'signatureLevel'  => $signatureLevel,
       'provider'        => $provider,
       'extra'           => $metadata,  // pass-through from caller
   ];
   ```
4. Call `AuditTrailMapper::createAuditTrailEntry($object, $actionType, $context)` where
   `$object` is the `ObjectEntity` for the signing request.
5. Return the created entry as an array (same return contract as before).

App-specific context is carried in the `$context` array argument to
`AuditTrailMapper::createAuditTrailEntry()`, which is persisted in the existing `changed`
JSON column on the `openregister_audit_trails` table. No OR schema change is required.

**getAuditTrail() method**: rewrite to query OR audit trail API:
```php
$entries = $this->auditTrailMapper->findAll(
    filters: ['objectUuid' => $signingRequestId]
);
// sort by created ASC (OR entries are already ordered; sort defensively)
usort($entries, fn($a, $b) => strcmp($a->getCreated(), $b->getCreated()));
return array_map(fn($e) => $e->jsonSerialize(), $entries);
```
If `AuditTrailMapper::findAll()` does not support `objectUuid` filter, use
`findAllByObject($signingRequestId)` — check which method the mapper exposes.

**rejectUpdate() and rejectDelete() methods**: REMOVE. OR enforces immutability natively.
If callers reference these methods, update callers to remove the calls (they should never
be calling these on the audit service — they were internal guards).

### lib/Controller/SigningController.php — NO CHANGE

The controller calls `SigningAuditService::logEvent(...)`. The interface signature does not
change, only the implementation. No modifications needed.

### lib/Service/SigningService.php and lib/Service/Signing/*.php — NO CHANGE

These services call `SigningAuditService::logEvent(...)`. Interface unchanged.

### signingAuditEntry schema configuration — DEPRECATE

The `signingAuditEntry_register` and `signingAuditEntry_schema` IAppConfig keys are no
longer used for writes after migration. Mark them deprecated in code with a comment:
```php
// DEPRECATED: signingAuditEntry_register and signingAuditEntry_schema were used
// before migrate-signing-audit-to-or-audit. Keys retained for read-only legacy access.
```
Do NOT remove the config keys — they may still be used by any legacy read path until sunset.

## Archiefwet Retention Configuration

OR audit trail supports configurable retention periods per register (per the
`audit-trail-immutable` spec, "Configure retention period" scenario). Docudesk signing
audit MUST be configured with at least 10-year retention (3650 days) per Archiefwet 1995.

This is a **deployment-time configuration**, not a code change:
- OR admin UI: set retention for the signing-related register to ≥ 3650 days.
- Or via OR's occ command / API if available.

Document this requirement in docudesk's deployment/administration guide. The code does not
enforce the retention value — OR's retention engine does.

## Event Type to VALID_ACTION Mapping

| VALID_ACTION | OR audit action type |
|---|---|
| CREATED | `docudesk.signing.CREATED` |
| SIGNED | `docudesk.signing.SIGNED` |
| DECLINED | `docudesk.signing.DECLINED` |
| CANCELLED | `docudesk.signing.CANCELLED` |
| EXPIRED | `docudesk.signing.EXPIRED` |
| COMPLETED | `docudesk.signing.COMPLETED` |
| VIEWED | `docudesk.signing.VIEWED` |

The uppercase convention (`SIGNED` not `signed`) is preserved from the existing
`VALID_ACTIONS` constant to minimise change scope. Future new action types follow the
same pattern.

## Backwards Compatibility

- Existing `signingAuditEntry` objects in OR remain queryable (read-only) until sunset.
- New signing events ONLY emit via OR audit trail after this spec ships.
- `SigningAuditService::logEvent()` public signature is unchanged — no caller modifications needed.
- `SigningAuditService::getAuditTrail()` public signature unchanged — callers receive the
  same array shape (now sourced from OR audit trail instead of private schema).

## Seed Data

No new schemas are added. No new register definitions are created. The `signingAuditEntry`
schema is deprecated in-place (the config keys are marked deprecated; no changes to
procest_register.json equivalent in docudesk). No seed data changes.

## Related ADRs

- **ADR-022** (primary) — mandate for this migration.
- **ADR-008** — testing contract; hash-chain integrity test required.
- **ADR-001** — data layer; no new entities or mappers introduced.
