## Why

Today every detected PERSON or ORGANIZATION entity in a publication-bound document goes through the per-document Wet Open Overheid (WOO) workflow tracked by `publicationConsent`: notification → 28-day objection window → decision. Two real-world cases this workflow does not handle:

1. **Entities that must always be anonymised regardless of consent** — protected witnesses under court order, undercover officers, minors, individuals with active threat assessments, names categorically excluded by AVG/WOO. Anonymisation is mandatory and not negotiable.
2. **Entities that have given blanket prior consent** — public officials acting in their official capacity, persons who have signed a standing publication consent form, organisations that have opted-in. Running the per-document WOO workflow for them is wasteful at best.

This change introduces the **`publicationProhibition` schema** + detection-time matching service + retroactive handling + admin surfaces — the always-anonymise enforcement layer. The sibling change `publication-consent-policy-fields` extends the existing `publicationConsent` schema with the `scope` discriminator, entity-scope fields, and the polymorphic `policyMatch` reference so the workflow can pre-empt on standing consents.

## What Changes

- **NEW capability:** `entity-publication-policies` — the `publicationProhibition` schema, the detection-time matching contract that pre-empts the WOO workflow, conflict resolution (prohibition wins), retroactive-application semantics, UI toggle behaviour for policy-matched entities, three admin surfaces.
- **NEW schema:** `publicationProhibition` in `lib/Settings/docudesk_register.json` under the `consent` register. Properties: `primaryName`, `entityType` (PERSON/ORGANIZATION/OTHER), `matchRules` (array of `{type, value}` with `type` enum: `exact`, `normalized`, `bsn`, `kvk`), `reason`, `legalAuthority`, `caseReference`, `severity`, `jurisdiction`, `addedBy`, `validFrom`, `validUntil`, `active`, `notes`. Required: `primaryName`, `entityType`, `matchRules`, `reason`, `active`.
- **NEW service:** `PolicyMatchService` that loads active prohibition records (and active standing-consent records, when `publication-consent-policy-fields` ships) into an in-memory cache; exposes `match(entityText, entityType, resolvedIdentifiers): ?MatchedRule`. Match types: `exact`, `normalized`, `bsn`, `kvk`. Conflict resolution: prohibition wins.
- **NEW retroactive handler:** on `publicationProhibition` INSERT (or activate), find all in-flight `scope: "document"` `publicationConsent` records (status not in {`anonymized`, terminal published}) for matching entities and force-resolve to `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, `policyMatch` populated.
- **NEW admin surfaces:** "Publication Prohibitions" page (CRUD for `publicationProhibition`), plus indicator on the existing "Consent Workflow" page rows where `policyMatch` is non-null.
- **NEW seed data:** four realistic `publicationProhibition` records (court order, minor protection, undercover officer, categorical privacy-board exemption).

### Out of scope

- Standing-consent surface — covered by `publication-consent-policy-fields`.
- Match rule types `regex` and `reference` — v2.
- Approval workflow on adding entries — separate change.
- Retroactive sweep of already-published documents — audit-only.
- OpenRegister code changes — none required.

## Capabilities

### New Capabilities

- `entity-publication-policies`

## Cross-app Dependencies

- **Hard** — `docudesk:publication-consent-policy-fields` — provides the `policyMatch` field on `publicationConsent` that this change populates; the retroactive handler writes to that field.

## Impact

- **Code (docudesk):** `lib/Settings/docudesk_register.json` (new schema), `lib/Service/PolicyMatchService.php` (new), `lib/Service/ConsentService.php` (retroactive handler), frontend Vue 2 admin pages.
- **API contract:** new CRUD on `publicationProhibition`; existing `publicationConsent` listing UI gains a "policy pre-empted" row indicator (additive).
- **Privacy / compliance:** strengthens GDPR/AVG compliance for protected categories.
- **Performance:** one batched lookup at detection time; small in-memory cache (tens to low hundreds per organisation).
- **Migration:** no data migration. New schema comes up empty; populated via admin.
