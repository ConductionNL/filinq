# batch-anonymization Specification (delta)

---
status: proposed
---

## Purpose

Ensure every routed `BatchAnonymizationController` endpoint declares an
explicit Nextcloud auth attribute, per ADR-005 / ADR-016, so authorization
posture is grep-able and mechanically verifiable rather than relying on the
framework's implicit admin-only default.

## MODIFIED Requirements

### Requirement: WOO Profile Update Endpoint Declares An Explicit Auth Attribute

`BatchAnonymizationController::updateProfiles()` MUST carry an explicit auth
attribute matching its intended audience, routed as
`PUT api/anonymization/profiles` (`@NoAdminRequired` + `@NoCSRFRequired`, or
`#[AuthorizedAdminSetting(...)]`). It MUST NOT rely on Nextcloud's implicit
"no annotation = admin-only" default.

#### Scenario: The method declares an explicit attribute

- GIVEN `lib/Controller/BatchAnonymizationController.php`
- WHEN the docblock/attributes immediately preceding `updateProfiles()` are
  inspected
- THEN at least one of `@NoAdminRequired`, `@PublicPage`, `@NoCSRFRequired`,
  or `#[AuthorizedAdminSetting]` SHALL be present

#### Scenario: Access level is consistent with the sibling read endpoint

- GIVEN `getProfiles()` is `@NoAdminRequired` (operator-level read access)
- WHEN `updateProfiles()`'s access level is decided
- THEN the write endpoint's access level SHALL be an explicit, documented
  decision (either matching operator-level read access, or explicitly
  narrowed to admin-only) — not an accidental default
