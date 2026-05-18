## Why

The existing `publicationConsent` schema models per-(document, entity) workflow records: notification → 28-day objection window → decision. To handle entities that have given blanket prior consent (mayor in official capacity, signed opt-in, recorded verbal consent), the schema needs a `scope` discriminator and an entity-scope field set. To express which policy pre-empted the workflow (a prohibition rule from the sibling `publication-prohibition-schema`, or a standing consent record at this same schema), a polymorphic `policyMatch` reference is introduced.

This change extends the existing `consent-management` capability. The new always-anonymise schema + detection service + retroactive handler live in the sibling change `publication-prohibition-schema`.

## What Changes

- **MODIFIED:** `consent-management` capability — `publicationConsent` schema gains:
  - `scope` discriminator (enum `document` / `entity`, default `document`; existing records load as `document` automatically).
  - Entity-scope field set: `matchRules` (array of `{type, value}` with `type` enum exact/normalized/bsn/kvk), `validFrom`, `validUntil`, `active`, `consentMethod` (enum: paper / digital_signature / verbal_recorded / opt_in_form), `consentDocument` (file ref), `consentScope`. None go in the schema's top-level `required` array — service-level enforcement gates them by `scope`.
  - `policyMatch` — optional polymorphic `oneOf` + `$ref` reference (valid only on `scope: "document"` records) constrained to point at a `publicationProhibition` record OR a `scope: "entity"` `publicationConsent` record. Enforced via OpenRegister's existing `ValidateObject` pipeline.
- **NO new `consentStatus` enum values.** A prohibition pre-emption resolves to existing `consentStatus: "anonymized"` + `policyMatch → publicationProhibition` + `notificationStatus: "skipped"`. A standing-consent match resolves to `consentStatus: "consent_given"` + `policyMatch → publicationConsent` (scope=entity) + `notificationStatus: "skipped"`.
- **NEW service-level write validation** on `publicationConsent`: reject `scope: "document"` writes missing `documentId`; reject `scope: "entity"` writes that include `documentId`; reject `scope: "entity"` writes missing `matchRules` or `consentMethod`; reject `policyMatch` on `scope: "entity"` records; reject `publicationConsent` referents in `policyMatch` whose own `scope` is not `"entity"`.
- **NEW admin surface:** "Standing Publication Consents" page — filtered view of `publicationConsent` where `scope: "entity"`. Lists, edits, expires, revokes standing consents. The existing "Consent Workflow" page applies a hard filter `scope: "document"`.
- **NEW UI behaviour:** for `policyMatch → publicationConsent` (scope=entity): toggle OFF by default, enabled; user may flip ON to anonymise anyway (override-up) — emits audit event and updates `publicationDecision: "anonymize"` while keeping `consentStatus: "consent_given"` and preserving `policyMatch`.
- **NEW seed data:** four realistic standing-consent records (mayor blanket consent, organisation signed opt-in, council member, recorded verbal consent).

## Capabilities

### Modified Capabilities

- `consent-management`

## Cross-app Dependencies

- **Hard** — `docudesk:publication-prohibition-schema` — provides the `publicationProhibition` schema that this change's `policyMatch` polymorphic reference targets.

## Impact

- **Code (docudesk):** `lib/Settings/docudesk_register.json` (extended `publicationConsent` schema), `lib/Service/ConsentService.php` (scope-validation + standing-consent matching + override-up flow), frontend "Standing Publication Consents" admin page + Consent Workflow filter.
- **API contract:** new optional fields on `publicationConsent` (`scope`, `matchRules`, `validFrom`, `validUntil`, `active`, `consentMethod`, `consentDocument`, `consentScope`, `policyMatch`); consumers that don't recognise them can safely ignore.
- **Privacy / compliance:** standing consent shifts WOO compliance burden from per-document notification to per-entity prior-consent record-keeping; legal review required before standing consents go live in production.
- **Migration:** no data migration. Existing `publicationConsent` records are valid as `scope: "document"` by default.
