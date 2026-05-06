## Why

Today, every detected PERSON or ORGANIZATION entity in a publication-bound document goes through the per-document Wet Open Overheid (WOO) workflow tracked by `publicationConsent`: notification → 28-day objection window → decision. There are two real-world cases this workflow does not handle:

1. **Entities that must always be anonymized regardless of consent** — protected witnesses under court order, undercover officers, minors, individuals with active threat assessments, names categorically excluded by AVG/WOO. The WOO workflow should not run for them at all; anonymization is mandatory and not negotiable.
2. **Entities that have given blanket prior consent** — public officials acting in their official capacity, persons who have signed a standing publication consent form, organizations that have opted-in. Running the per-document WOO workflow (notification + 28-day wait) for them is wasteful at best and operationally untenable for high-volume publication pipelines.

Both cases are entity-level **policy** that should pre-empt the per-document workflow. They are not currently representable. Without them, organizations must either skip WOO compliance manually (legal risk) or run the workflow regardless (operational cost, and no force-anonymize guarantee for protected entities).

## What Changes

- **NEW:** `mandatoryAnonymization` schema in the consent register — per-real-world-entity policy records that ALWAYS force anonymization on detection, with no consent-process override possible.
- **NEW:** `publicationAllowance` schema in the consent register — per-real-world-entity policy records that grant blanket consent to publication, skipping the WOO notification/objection workflow.
- **MODIFIED:** `publicationConsent` schema gains:
  - Two new `consentStatus` enum values: `mandatory_anonymized` (deny-list match) and `blanket_consent_given` (allow-list match).
  - New polymorphic reference field `policyMatch` constrained via `oneOf` + `$ref` to point ONLY to a `mandatoryAnonymization` or `publicationAllowance` record. Constraint is enforced by OpenRegister's existing `ValidateObject` pipeline (the `items.oneOf` polymorphic-reference path).
- **NEW:** detection-time matching logic in DocuDesk's consent service — for each detected entity, check the deny list first (deny wins on conflict), then the allow list, then fall through to the existing WOO workflow.
- **NEW:** retroactive handling — when a deny-list rule is added, in-flight `publicationConsent` records for matching entities are force-resolved to `mandatory_anonymized`. When an allow-list rule is added, in-flight records are left alone (an existing objection isn't overridden after the fact). Already-published documents are never touched.
- **NEW:** UI semantics for the anonymization toggle — locked **on** for `mandatory_anonymized` (no override), defaults **off** for `blanket_consent_given` but the publishing user MAY flip it to anonymize anyway (override-up).
- **NEW:** seed data for both schemas demonstrating realistic deny/allow records (court-order, threat-assessment, signed consent, public-official scenarios).

### Match rule types (v1)

Both new schemas accept a `matchRules` array. v1 ships:

- `exact` — literal string match
- `normalized` — case + accent-stripped string match
- `bsn` — Dutch citizen ID (PERSON)
- `kvk` — Chamber of commerce number (ORGANIZATION)

Deferred to v2 (out of scope here): `regex` (false-positive risk) and `reference` (cross-register link).

### Conflict resolution

If an entity matches both a deny-list and an allow-list rule, **deny wins, full stop.** Audit log records both matches. No configuration knob.

### Permissions

RBAC only at v1. Privileged users may write to either list directly. Two-eyes / formal approval workflow on writes is out of scope and tracked as a follow-up.

### Out of scope

- Match rule types `regex` and `reference` — v2.
- Approval workflow on adding entries to either list — separate change.
- Retroactive sweep of already-published documents — audit-only.
- OpenRegister code changes — none required (the `oneOf` + `$ref` polymorphic-reference pattern is already supported via `ValidateObject::extractObjectConfigurationHandling`).

## Capabilities

### New Capabilities

- `entity-publication-policies` — the deny-list and allow-list policy schemas, the detection-time matching contract that pre-empts the WOO workflow, the conflict-resolution rule (deny wins), the retroactive-application semantics, and the UI toggle behavior.

### Modified Capabilities

- `consent-management` — gains two new `consentStatus` enum values (`mandatory_anonymized`, `blanket_consent_given`) and the new `policyMatch` polymorphic reference field. Existing requirements continue to apply for the WOO-workflow path; new requirements describe the policy-pre-emption path.

## Impact

- **Code (docudesk):**
  - `lib/Settings/docudesk_register.json` — two new schema definitions; `publicationConsent` schema gets two new `consentStatus` enum values and a new `policyMatch` property with `oneOf` + `$ref` to the two policy schemas.
  - `lib/Service/ConsentService.php` (or equivalent detection-time service) — pre-emption logic: deny-check → allow-check → fall through to existing WOO workflow.
  - New service or extension to the existing consent service for retroactive handling on rule additions.
  - Frontend (Vue 2) consent UI — toggle behavior for the two new statuses (locked for deny, defaults-off-but-overridable for allow).
- **API contract:** Public consumers of the `publicationConsent` shape see a wider `consentStatus` enum and a new optional `policyMatch` field. Additive change for existing values; consumers reading the new statuses MUST handle them.
- **Cross-app:** No openregister code changes. opencatalogi and other consent consumers are not affected (they do not interact with the policy schemas directly; they only ever see `publicationConsent` records).
- **Performance:** Detection-time adds two batched lookups (deny then allow). Both are cached at runtime per ADR-008-compatible service patterns; the deny+allow lists are expected to be small (tens to low hundreds per organization), so the cache fits in memory.
- **Privacy/compliance:** Strengthens GDPR/AVG compliance for protected categories (deny-list). Allow-list shifts WOO compliance burden from per-document notification to per-entity prior-consent record-keeping; legal review required before allow-list goes live in production.
- **Tests:** New unit tests for the matching service. Integration tests for the four detection-time outcomes (no match, deny match, allow match, both match → deny wins). UI tests for toggle behavior.
- **Migration:** No data migration. Existing `publicationConsent` records remain valid (the schema extension is additive). New schemas come up empty; org admins populate them as needed.
