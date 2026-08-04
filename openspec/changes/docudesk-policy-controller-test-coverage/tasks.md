# Tasks: docudesk-policy-controller-test-coverage

All tasks are `[docudesk]`. Estimates: S = half-day, M = 1-2 days.

## [docudesk] Service-level RBAC tests

### A-1. PolicyCrudServiceTest.php (M)

- [ ] A-1.1 Create `tests/unit/Service/PolicyCrudServiceTest.php` following
  the mocking pattern in `tests/unit/Service/PolicyMatchServiceTest.php` /
  `ConsentControllerTest.php` (mock `IUserSession`, `IGroupManager`,
  `ObjectService`/container as needed).
- [ ] A-1.2 Test `requirePolicyPermission(PolicyCrudService::SURFACE_PROHIBITION,
  'read'|'create'|'update'|'delete')`: a user in `docudesk-policy-admins`
  succeeds (no exception) for all four actions; a user not in the group throws
  `RuntimeException`; a null current user (unauthenticated) throws.
- [ ] A-1.3 Test `requirePolicyPermission(PolicyCrudService::SURFACE_STANDING_CONSENT, ...)`
  with the same four-action matrix against `docudesk-standing-consent-admins`
  (`PolicyCrudService::STANDING_CONSENT_GROUP`), plus one case asserting an
  unknown `$surface` throws `InvalidArgumentException` rather than passing
  silently.
  - **Acceptance:** ≥3 test methods per ADR-008; temporarily commenting out
    the group-membership check in `assertProhibitionPermission()` or
    `assertStandingConsentPermission()` makes at least one new test fail
    (verify this manually once, then restore the check).

## [docudesk] Controller-level tests

### B-1. PolicyControllerTest.php — prohibitions (M)

- [ ] B-1.1 Create `tests/unit/Controller/PolicyControllerTest.php`,
  mocking `PolicyCrudService`, `IUserSession`, `IL10N`, `LoggerInterface`
  per the `ConsentControllerTest.php` pattern.
- [ ] B-1.2 `indexProhibitions()` — 200 with the service's list on success;
  401 when `userSession->getUser()` is null; the mapped-403 path when the
  service throws (mirrors `RuntimeException` from `requireProhibitionPermission`).
- [ ] B-1.3 `showProhibition()` — 200 for a found UUID; 404 when the service
  returns null; 401/403 same as above.
- [ ] B-1.4 `createProhibition()` — 201 on success; 400 on
  `InvalidArgumentException` from the service; 401/403 same as above.
- [ ] B-1.5 `updateProhibition()` / `deleteProhibition()` — same 200/401/403
  matrix as B-1.2.

### B-2. PolicyControllerTest.php — standing consents (S)

- [ ] B-2.1 Create `tests/unit/Controller/StandingConsentControllerTest.php`
  and mirror B-1.2–B-1.5 for `index` / `show` / `create` / `update` /
  `destroy`. These five moved off `PolicyController` onto
  `StandingConsentController` when the controller was split; the URLs are
  unchanged, only the route names and class.
  - **Acceptance:** all 10 routed policy/standing-consent controller methods
    have at least one happy-path and one auth-rejection test; `composer
    check:strict` passes with the new files included.

## [docudesk] Verification

### C-1. Confirm regression protection (S)

- [ ] C-1.1 Manually break `assertProhibitionPermission()` (e.g. stub it to
  always pass) and confirm at least one new controller test fails, then
  revert — proves the new suite actually exercises the guard rather than
  mocking it away.
- [ ] C-1.2 Run the full PHPUnit suite and confirm no regressions in
  existing controller/service tests.
