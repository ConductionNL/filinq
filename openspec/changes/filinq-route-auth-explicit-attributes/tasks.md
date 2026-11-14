# Tasks: filinq-route-auth-explicit-attributes

All tasks are `[filinq]`. Estimates: S = half-day.

## [filinq] Auth attribute correction

### A-1. Decide the intended audience (S)

- [x] A-1.1 Confirm with product/domain intent whether WOO anonymisation
  profile **updates** (as opposed to reads) should be operator-level
  (`@NoAdminRequired`, matching `getProfiles()`) or Nextcloud-admin-only. The
  "WOO entity profile routes" comment in `appinfo/routes.php:71` groups both
  under the same feature without distinguishing read/write access levels.

### A-2. Apply the explicit attribute (S)

- [ ] A-2.1 If operator-level: add `@NoAdminRequired` and `@NoCSRFRequired`
  docblock tags to `BatchAnonymizationController::updateProfiles()`
  (`lib/Controller/BatchAnonymizationController.php:574`), matching the
  pattern already on `getProfiles()` two methods above it.
- [x] A-2.2 If admin-only: add
  `#[AuthorizedAdminSetting(\OCA\Filinq\AppInfo\Application::class)]` (or
  the app's equivalent settings-section class) instead, per ADR-005's
  preferred-explicit-attribute guidance, and import the attribute class.
  - **Acceptance:** exactly one of A-2.1 / A-2.2 is applied;
    `updateProfiles()` carries a recognizable auth attribute
    (`hydra-gate-route-auth` gate-5 passes).

### A-3. Regression test (S)

- [x] A-3.1 Add/extend a unit test in
  `tests/unit/Controller/BatchAnonizationControllerTest.php` (or the correct
  existing test file) asserting the chosen access level: a non-admin,
  non-authenticated request is rejected consistently with the documented
  attribute, and (if `@NoAdminRequired`) a non-admin authenticated request
  succeeds.
- [x] A-3.2 Re-run the full `hydra-gate-route-auth` scan (or manual
  attribute-presence check across `lib/Controller/*.php` against
  `appinfo/routes.php`) and confirm no other routed method is missing an
  explicit auth attribute (verified clean for all other ~70 routed methods as
  part of this review — re-verify after the fix to catch drift).

## Status

Already on development: `updateProfiles()` carries
`#[AuthorizedAdminSetting(FilinqAdmin::class)]` with a docblock giving the
reason (the profile is instance-wide, so the write stays admin-only while the
read stays user-level). A-2.1 is deliberately not applied: exactly one of
A-2.1 / A-2.2 is. The regression tests are
`tests/unit/Controller/BatchAnonymizationControllerProfileAuthTest.php`
(admin-only write, user-accessible read) and
`BatchAnonymizationControllerUpdateProfilesTest.php`.

A-3.2 re-check (244 routes in `appinfo/routes.php` against
`lib/Controller/*.php`): every routed method carries an explicit attribute
except `ScanIntakeController::declareProfiles()`, which is admin-only by
the implicit default on purpose and documents that with an `@auth admin-only`
tag. It is outside this change's scope and left as it is.
