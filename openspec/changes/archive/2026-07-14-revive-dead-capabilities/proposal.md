kind: code

## Why

Hydra gate-52 (`orphaned-write-capability`) re-run against `lib/Service/**` at
HEAD `32d0bddf` (issue #176) flagged two public side-effecting service methods
with zero production callers anywhere in the repo (verified fleet-wide with
`->method(`, dynamic-dispatch, and register.d checks):

- `ConsentService::createEntityConsent()` — a scope=entity (standing) consent
  create path.
- `DataResolverService::clearCache()` — a resolver cache-reset helper.

The triage lesson (2026-07-14 orphan sweep) is that **class-injected ≠
method-called**: both classes are dependency-injected and central, yet these
specific methods are never invoked. Verification for this change confirmed both
are **superseded** — a live, wired path already does the work — so wiring them
would duplicate an existing code path, not restore a lost feature.

## What Changes

- **DELETE** `ConsentService::createEntityConsent()`. The live, routed create
  path for scope=entity standing consents is
  `PolicyCrudService::createStandingConsent()` (reached over HTTP via
  `PolicyController::createStandingConsent`). The deleted method was a
  never-called duplicate.
- **DELETE** the now-orphaned `ConsentScopeValidator::validateWrite()` — a
  two-line wrapper over `assertValid()` whose only caller was
  `createEntityConsent()`. `assertValid()` and `validateTransition()` remain
  (still used by the live `validateAndUpdateConsent` update path).
- **DELETE** `DataResolverService::clearCache()`. `DataResolverService::resolve()`
  already resets `$this->resolvedCache = []` at its first statement on every
  call, so the cache is a within-single-`resolve()`-call memoization that can
  never persist or go stale across calls. An external reset is dead by
  construction.

No behaviour changes for any live path. No wiring is added because both
capabilities are already delivered elsewhere.

## Impact

- Affected specs: `entity-publication-policies` (clarify single canonical
  standing-consent create path).
- Affected code: `lib/Service/ConsentService.php`,
  `lib/Service/ConsentScopeValidator.php`, `lib/Service/DataResolverService.php`.
- No migration, no schema change, no route change, no i18n change.
