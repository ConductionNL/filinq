# policy-controller-test-coverage Specification (delta)

---
status: proposed
---

## Purpose

Close a zero-coverage gap on the RBAC-gated `PolicyController` /
`PolicyCrudService` pair — the security-relevant surface that lets
`docudesk-policy-admins` manage publication prohibitions and
`docudesk-standing-consent-admins` manage standing consents — so a future
regression in the group-membership guard is caught by CI rather than
shipping silently ("phantom green").

## ADDED Requirements

### Requirement: PolicyCrudService RBAC guards MUST have unit test coverage

`PolicyCrudService::requireProhibitionPermission()` and `requireStandingConsentPermission()` MUST each have unit test coverage.

The tests MUST verify: an authorized group member succeeds, an unauthorized
user is rejected, and an unauthenticated request is rejected.

#### Scenario: Authorized prohibition-admin succeeds

- GIVEN a current user who is a member of `docudesk-policy-admins`
- WHEN `requireProhibitionPermission('create')` is called
- THEN no exception SHALL be thrown

#### Scenario: Unauthorized user is rejected

- GIVEN a current user who is NOT a member of `docudesk-policy-admins`
- WHEN `requireProhibitionPermission('create')` is called
- THEN a `RuntimeException` SHALL be thrown

#### Scenario: Standing-consent guard checks its own group

- GIVEN a current user who is a member of `docudesk-policy-admins` but NOT
  `docudesk-standing-consent-admins`
- WHEN `requireStandingConsentPermission('create')` is called
- THEN a `RuntimeException` SHALL be thrown (prohibition-group membership
  does not grant standing-consent access)

### Requirement: PolicyController endpoints MUST have unit test coverage

Every one of `PolicyController`'s 10 routed methods MUST have a test
asserting the success response and a test asserting the authorization-
rejection response. The 10 methods are: `indexProhibitions`,
`showProhibition`, `createProhibition`, `updateProhibition`,
`deleteProhibition`, `indexStandingConsents`, `showStandingConsent`,
`createStandingConsent`, `updateStandingConsent`, `deleteStandingConsent`.

#### Scenario: Unauthenticated request is rejected before the service is called

- GIVEN no current user is set on `IUserSession`
- WHEN any `PolicyController` method is invoked
- THEN the response SHALL be 401 and `PolicyCrudService` SHALL NOT be
  called

#### Scenario: Service-level RuntimeException maps to a rejection response

- GIVEN `PolicyCrudService::requireProhibitionPermission()` throws
  `RuntimeException`
- WHEN `createProhibition()` is invoked
- THEN the controller SHALL return a non-2xx error response and MUST NOT
  swallow the exception into a success response

#### Scenario: Invalid input on create returns 400

- GIVEN `PolicyCrudService::createProhibition()` throws
  `InvalidArgumentException` for malformed input
- WHEN `PolicyController::createProhibition()` is invoked
- THEN the response SHALL be 400 with the exception message
