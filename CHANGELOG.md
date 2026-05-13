# Changelog

## Unreleased
### Added
- `publicationProhibition` schema for entity-level deny rules in the publication-clearance flow (court orders, minor protection, undercover officers, AVG categorical exemptions). Includes seed data covering four representative scenarios.
- `scope` discriminator on `publicationConsent` plus the entity-scope field set (`matchRules`, `validFrom`, `validUntil`, `active`, `consentMethod`, `consentDocument`, `consentScope`) — enables "standing publication consent" records that pre-empt the per-document workflow.
- `policyMatch` field on `publicationConsent` linking a `scope: "document"` record back to the prohibition or standing consent that drove its outcome.
- New **Standing Publication Consents** admin page (`scope: "entity"` records — list, edit, expire, revoke).
- New **Publication Prohibitions** admin page (CRUD for `publicationProhibition` records).
- Detection-time `PolicyMatchService` matches detected entities against active rules with O(1) lookup; conflict resolution is deterministic ("prohibition wins", lexicographic UUID tie-break).
- Retroactive force-resolve: creating or widening a prohibition force-resolves matching in-flight `scope: "document"` records to `anonymized`. Standing-consent creation is **not** retroactive (future detections only).
- Service-level RBAC gate: writes to standing consents require membership in `docudesk-standing-consent-admins`. The prohibition schema defaults write to `docudesk-policy-admins`.

### Changed
- The Consent Management admin page is now **Consent Workflow** and filters to `scope: "document"` records only. Rows whose `policyMatch` is non-null show a "policy" badge.
- The publication-prep anonymisation toggle is now keyed off the **referent type** of `policyMatch`, not `consentStatus`: prohibition → ON+locked, standing consent → OFF+overridable, no match → existing UX.

### Behavior changes
- Inside publication-clearance flows, the consent service consults the policy layer **before** defaulting to the WOO workflow. Existing `consentStatus` enum is unchanged; the policy-pre-empted distinction lives in `policyMatch` + `notificationStatus: "skipped"`.
- Generic anonymisation flows (file sanitisation prior to email/storage) are unaffected — they do not call `ConsentService::createConsentRequest` and therefore do not consult the policy layer.

## 0.1.5 – 2024-09-07
### Added
- First version for the Nextcloud store

### Changed
- Changes in existing functionality for this release:

### Fixed
- Bug fixes for this release:

### Added
- Initial release

