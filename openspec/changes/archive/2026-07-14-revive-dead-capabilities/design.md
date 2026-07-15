# Design — revive-dead-capabilities (docudesk)

## Verdict table

Every method was VERIFY-THEN-classified against the live codebase. Callers were
grepped fleet-wide (`->method(`, dynamic dispatch, register.d handlers, routes,
listeners). Verdict is exactly one of: WIRE (dead+intended), DELETE (superseded),
SEAM (consumer/plugin), UNSURE (leave).

| Class::method | file:line | callers found | superseding path | verdict |
|---|---|---|---|---|
| `ConsentService::createEntityConsent` | lib/Service/ConsentService.php:285 (pre-edit) | none (only a prose comment ref at :342) | `PolicyCrudService::createStandingConsent` (lib/Service/PolicyCrudService.php:305), routed via `PolicyController::createStandingConsent` (lib/Controller/PolicyController.php:272) | **DELETE (superseded)** |
| `ConsentScopeValidator::validateWrite` | lib/Service/ConsentScopeValidator.php:121 (pre-edit) | only `createEntityConsent` (now deleted) | `assertValid()` remains; live update path uses `validateTransition()` | **DELETE (orphaned by the above)** |
| `DataResolverService::clearCache` | lib/Service/DataResolverService.php:316 (pre-edit) | none | `DataResolverService::resolve()` self-resets `$this->resolvedCache = []` at line 120 on every call | **DELETE (superseded by self-reset)** |

### Evidence detail

- `PolicyController::createStandingConsent` (`@NoAdminRequired`, POST route)
  calls `$this->crudService->createStandingConsent(data: $data)` where
  `crudService` is `PolicyCrudService`. That method sets `scope = 'entity'`,
  runs `consentService->validatePublicationConsentData()`, gates on
  `assertStandingConsentPermission('create')`, and saves. This is the live
  create path for the same records `createEntityConsent` was meant to create.
- `createEntityConsent` gated writes with `scopeValidator->validateWrite()` +
  `scopeValidator->requireStandingConsentAdminGroup()`. The former had no other
  caller (deleted with it); the latter is still used by
  `validateAndUpdateConsent` (the live revoke/expire RBAC gate), so the
  validator class stays alive.
- `DataResolverService::resolve()` line 120 is `$this->resolvedCache = [];` as
  its first statement. The cache exists solely to deduplicate reference lookups
  within one `resolve()` invocation (see `resolveReference` memoization at
  :197/:222). Across calls it is always empty at entry, so a public
  `clearCache()` can never clear anything a subsequent `resolve()` would not
  already clear. It is dead by construction, not merely un-called.

## Why DELETE and not WIRE

The triage explicitly warned against fabricating triggers for descoped code.
Wiring `createEntityConsent` would give docudesk two parallel, divergent
standing-consent create paths (one gated by `assertStandingConsentPermission`,
the other by `requireStandingConsentAdminGroup` + `validateWrite`), a classic
"share the contract, not the component" hazard. The correct fix for genuinely
superseded code is deletion. `clearCache` has no write path to wire to because
the cache is request-scoped and self-resetting.

## Seed Data

None. This change deletes dead code only — no schema, register config, seed
rows, or notification templates are added or modified.

## ADR-031 (notification dialect)

Not applicable. No object-notification dispatch, no `x-openregister-notifications`
config, and no `lib/Settings/*register*.json` changes are introduced. The
canonical dialect is untouched.

## Tests

Both methods are DELETED, so the test obligation is: re-confirm zero callers and
no superseding-path regression. No test references any of the three deleted
methods (`grep -rln` over `tests/` is empty for all three). The full unit suite
is run before/after to prove the deletion regresses nothing.
