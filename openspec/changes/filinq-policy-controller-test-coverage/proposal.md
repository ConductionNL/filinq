# Proposal: filinq-policy-controller-test-coverage

kind: code

## Why

`lib/Controller/PolicyController.php` (376 lines) exposes 10 routed CRUD
endpoints for two security-relevant policy record types:

- `indexProhibitions` / `showProhibition` / `createProhibition` /
  `updateProhibition` / `deleteProhibition` (`lib/Controller/PolicyController.php:78-215`)
- `indexStandingConsents` / `showStandingConsent` / `createStandingConsent` /
  `updateStandingConsent` / `deleteStandingConsent` (`:217-374`)

Every write path is gated by group-based RBAC in
`lib/Service/PolicyCrudService.php`: `requireProhibitionPermission()`
(`:399-404`) and `requireStandingConsentPermission()` (`:420-424`) are public
aliases the controller calls before every operation, delegating to
`assertProhibitionPermission()` (`:484-509`) and
`assertStandingConsentPermission()` (`:445-470`), which check membership in
the `docudesk-policy-admins` / `docudesk-standing-consent-admins` NC groups
(`PolicyCrudService.php:75` defines `STANDING_CONSENT_GROUP`). A
publication-prohibition record forces an entity to always be anonymised
regardless of consent, and a standing-consent record lets specific
operators pre-authorise publication for an entity — both are
consequential, RBAC-gated writes to the anonymisation/publication-clearance
policy surface Filinq enforces fleet-wide.

**Confirmed via repo-wide search: zero test files exist for either class.**
`tests/unit/Controller/` has test files for 12 of Filinq's 20 other
controllers (e.g. `ConsentControllerTest.php`, `DossierControllerTest.php`,
`SettingsControllerTest.php`), but no `PolicyControllerTest.php`.
`tests/unit/Service/` similarly has no `PolicyCrudServiceTest.php` (it does
have `PolicyMatchServiceTest.php` and `PolicyRetroactiveServiceTest.php` for
the two sibling services in the same `Service/Policy*` family — the CRUD +
RBAC service is the one gap). `grep -rl "PolicyCrudService\|PolicyController"
tests/` returns no results at all.

This is precisely the fleet's "phantom green" failure mode called out for
this sweep: ADR-008 requires "every new PHP service/controller → PHPUnit
tests (≥3 methods)", and CI reports fully green for this app while the
RBAC guard that gates all 10 policy-mutation endpoints has no regression
protection whatsoever. If a future change accidentally weakens or removes
`assertProhibitionPermission()` / `assertStandingConsentPermission()` (e.g.
during a refactor of the shared group-check helper), or a future
`PolicyController` change forgets to call `requireXPermission()` before a
new operation, nothing in the test suite would catch it — CI would stay
green while any authenticated user could create/update/delete publication
prohibitions or standing consents.

## What Changes

- Add `tests/unit/Service/PolicyCrudServiceTest.php` covering
  `assertProhibitionPermission()` / `assertStandingConsentPermission()` (via
  the public `requireProhibitionPermission()` / `requireStandingConsentPermission()`
  aliases): member of the required group succeeds for
  read/create/update/delete; non-member is rejected (asserts the thrown
  `RuntimeException`); unauthenticated (no current user) is rejected.
- Add `tests/unit/Controller/PolicyControllerTest.php` covering all 10
  routed methods: happy-path 200/201 response for an authorized caller,
  403 (mapped from the service's `RuntimeException`) for an unauthorized
  caller, 404 for `showProhibition`/`showStandingConsent` on an unknown
  UUID, and 400 for `createProhibition`/`createStandingConsent` given
  invalid input (`InvalidArgumentException` path already in the controller).
- No BREAKING change — test-only addition, no production code path is
  altered.

## Out of Scope

- `PolicyMatchServiceTest.php` / `PolicyRetroactiveServiceTest.php` — these
  already exist and are out of scope for this change.
- Any change to the RBAC logic itself, or to the two NC group names.
- Extending coverage to other under-tested controllers found in the same
  sweep (`ComparisonController`, `MetricsController`, `ValidationController`)
  — flagged separately as lower severity (no RBAC/write-gate logic) and not
  bundled here to keep this change reviewable.

## Success Criteria

- `tests/unit/Controller/PolicyControllerTest.php` and
  `tests/unit/Service/PolicyCrudServiceTest.php` exist, each with ≥3 test
  methods per ADR-008, and pass under `composer check:strict`.
- Removing or weakening either `assertProhibitionPermission()` or
  `assertStandingConsentPermission()` locally causes at least one new test
  to fail (manually verified as part of implementation, not just asserted).
