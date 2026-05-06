## Why

Today, every detected PERSON or ORGANIZATION entity in a publication-bound document goes through the per-document Wet Open Overheid (WOO) workflow tracked by `publicationConsent`: notification → 28-day objection window → decision. There are two real-world cases this workflow does not handle:

1. **Entities that must always be anonymized regardless of consent** — protected witnesses under court order, undercover officers, minors, individuals with active threat assessments, names categorically excluded by AVG/WOO. The WOO workflow should not run for them at all; anonymization is mandatory and not negotiable.
2. **Entities that have given blanket prior consent** — public officials acting in their official capacity, persons who have signed a standing publication consent form, organizations that have opted-in. Running the per-document WOO workflow (notification + 28-day wait) for them is wasteful at best and operationally untenable for high-volume publication pipelines.

Both cases are entity-level **policy** that should pre-empt the per-document workflow. They are not currently representable. Without them, organizations must either skip WOO compliance manually (legal risk) or run the workflow regardless (operational cost, and no force-anonymize guarantee for protected entities).

## What Changes

- **NEW:** `publicationProhibition` schema in the consent register — per-real-world-entity records that ALWAYS force anonymization on detection in publication-bound flows, with no consent-process override possible. Functionally a deny-list; semantically a prohibition (the absence of permission asserted positively).
- **MODIFIED:** `publicationConsent` schema gains a `scope` discriminator and entity-scope fields. The schema now models two flavors:
  - `scope: "document"` — existing per-(document, entity) workflow record. Today's behavior is unchanged.
  - `scope: "entity"` — NEW. A standing publication consent for one real-world entity, time-bounded by `validFrom` / `validUntil` / `active`, with `matchRules` for detection-time matching and `consentMethod` / `consentDocument` / `consentScope` capturing how the consent was obtained.
- **NEW:** `publicationConsent.policyMatch` — optional polymorphic reference field, valid only on `scope: "document"` records. Constrained via `oneOf` + `$ref` to point ONLY to a `publicationProhibition` record OR a `scope: "entity"` `publicationConsent` record. Constraint is enforced by OpenRegister's existing `ValidateObject` pipeline (the `items.oneOf` polymorphic-reference path).
- **NO new `consentStatus` enum values.** The discriminator for "this record was pre-empted by a policy" is the combination of `policyMatch` (non-null + which schema it references) and `notificationStatus: "skipped"`. A prohibition match resolves to the existing `consentStatus: "anonymized"` plus `policyMatch → publicationProhibition`. A standing-consent match resolves to `consentStatus: "consent_given"` plus `policyMatch → publicationConsent` (scope=entity). Existing enum semantics are preserved.
- **NEW:** detection-time matching logic in DocuDesk's consent service — for each detected entity, check the prohibition list first (deny wins on conflict), then check active standing consent records (`scope: "entity"`, `active: true`, time bounds met), then fall through to the existing WOO workflow.
- **NEW:** retroactive handling — when a `publicationProhibition` rule is added, in-flight `scope: "document"` `publicationConsent` records for matching entities are force-resolved to `consentStatus: "anonymized"` with `policyMatch` and `notificationStatus: "skipped"` populated. When a `scope: "entity"` `publicationConsent` is added, in-flight per-document records are left alone (an existing objection is not overridden after the fact). Already-published documents are never touched.
- **NEW:** UI semantics for the per-entity anonymization toggle — keyed off `policyMatch` referent type. Locked **on** when `policyMatch → publicationProhibition` (no override). Defaults **off** when `policyMatch → publicationConsent` (scope=entity), but the publishing user MAY flip it to anonymize anyway (override-up).
- **NEW:** three separate admin surfaces:
  - "Consent Workflow" — the existing per-document consent records list (`scope: "document"`).
  - "Standing Publication Consents" — NEW. Filtered view of `publicationConsent` where `scope: "entity"`. Lists, edits, expires, revokes standing consents.
  - "Publication Prohibitions" — NEW. CRUD for `publicationProhibition` records.
- **NEW:** seed data demonstrating realistic prohibition records and `scope: "entity"` `publicationConsent` records (court-order, threat-assessment, signed standing consent, public-official scenarios).

### Match rule types (v1)

Both `publicationProhibition` and the `scope: "entity"` flavor of `publicationConsent` accept a `matchRules` array. v1 ships:

- `exact` — literal string match
- `normalized` — case + accent-stripped string match
- `bsn` — Dutch citizen ID (PERSON)
- `kvk` — Chamber of commerce number (ORGANIZATION)

Deferred to v2 (out of scope here): `regex` (false-positive risk) and `reference` (cross-register link).

### Conflict resolution

If an entity matches both a `publicationProhibition` rule and an active standing `publicationConsent`, **prohibition wins, full stop.** Audit log records both matches. No configuration knob.

### Permissions

RBAC only at v1. Privileged users may write to `publicationProhibition` and to `scope: "entity"` `publicationConsent` records directly. Two-eyes / formal approval workflow on writes is out of scope and tracked as a follow-up.

### Out of scope

- **Generic anonymisation flows** (sanitisation of files not destined for publication) — these do not invoke the publication-clearance workflow, do not create `publicationConsent` records, and therefore do not consult `publicationProhibition` or standing consent records. The trigger boundary for this change is the publication-clearance entry point (`ConsentService::createConsentRequest()` and its caller); the policy layer activates there and nowhere else.
- **The automation that calls `createConsentRequest()` from a publication-prep flow.** Per the canonical `consent-management` spec (CONS-048 / CONS-050), no API endpoint or automated trigger creates consents today — invocation is programmatic only. Building the publication-prep flow that drives this is a separate change. This change is scoped to: when the publication-prep flow exists and calls `createConsentRequest()`, the policy layer is consulted first.
- Match rule types `regex` and `reference` — v2.
- Approval workflow on adding entries to either policy surface — separate change.
- Retroactive sweep of already-published documents — audit-only.
- OpenRegister code changes — none required (the `oneOf` + `$ref` polymorphic-reference pattern is already supported via `ValidateObject::extractObjectConfigurationHandling`).

## Capabilities

### New Capabilities

- `entity-publication-policies` — the `publicationProhibition` schema, the standing-consent variant of `publicationConsent` (`scope: "entity"`), the detection-time matching contract that pre-empts the WOO workflow, the conflict-resolution rule (prohibition wins), the retroactive-application semantics, and the UI toggle behavior.

### Modified Capabilities

- `consent-management` — `publicationConsent` schema gains a `scope` discriminator, the entity-scope field set (`matchRules`, `validFrom`, `validUntil`, `active`, `consentMethod`, `consentDocument`, `consentScope`), and the polymorphic `policyMatch` reference. Existing WOO workflow requirements continue to apply for `scope: "document"` records that match no policy. New requirements describe `scope: "entity"` records and the policy-pre-emption path.

## Impact

- **Code (docudesk):**
  - `lib/Settings/docudesk_register.json` — extended `publicationConsent` schema (new fields + `policyMatch`); new `publicationProhibition` schema definition.
  - `lib/Service/ConsentService.php` (or equivalent detection-time service) — pre-emption logic: prohibition-check → standing-consent-check → fall through to existing WOO workflow.
  - New service or extension to the existing consent service for retroactive handling on rule additions (force-resolve in-flight per-document records when a prohibition is added).
  - Frontend (Vue 2) consent UI — three admin surfaces (Consent Workflow / Standing Publication Consents / Publication Prohibitions) and the per-entity toggle behavior keyed off `policyMatch` referent type.
- **API contract:** Public consumers of the `publicationConsent` shape see new optional fields (`scope`, `matchRules`, `validFrom`, `validUntil`, `active`, `consentMethod`, `consentDocument`, `consentScope`, `policyMatch`). No new enum values; existing `consentStatus` and `notificationStatus` semantics are preserved. Consumers that don't recognize the new fields can safely ignore them.
- **Cross-app:** No openregister code changes. opencatalogi and other consent consumers are not affected (they continue to read `publicationConsent` records; the additions are optional and scope-discriminated).
- **Performance:** Detection-time adds two batched lookups (prohibition then standing consent). Both are cached at runtime per ADR-008-compatible service patterns; the prohibition list and the active standing consents are expected to be small (tens to low hundreds per organization), so the cache fits in memory.
- **Privacy/compliance:** Strengthens GDPR/AVG compliance for protected categories (prohibition list). Standing consent shifts WOO compliance burden from per-document notification to per-entity prior-consent record-keeping; legal review required before standing consents go live in production.
- **Tests:** New unit tests for the matching service. Integration tests for the four detection-time outcomes (no match, prohibition match, standing-consent match, both → prohibition wins). UI tests for the three admin pages and the toggle behavior.
- **Migration:** No data migration. Existing `publicationConsent` records are valid as `scope: "document"` (the field defaults to `document`). New `publicationProhibition` schema comes up empty; new `scope: "entity"` `publicationConsent` records are created on demand.
