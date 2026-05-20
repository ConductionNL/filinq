## Why

DocuDesk stores document analysis results — file metadata, NER entity detection, risk scores, processing status — in OpenRegister. The data model file `lib/Settings/document_register.json` was created with three schemas (`report`, `template`, `entity`) and three pre-seeded sample objects demonstrating the anonymization pipeline. However, the register is **never loaded during application boot**: `SettingsService::initialize()` only imports `docudesk_register.json`; `document_register.json` is never passed to `ConfigurationService::importFromApp()`. This means the register, its schemas, and all sample objects are never created in OpenRegister — the analysis pipeline has nowhere to persist its output.

A second bug: two of the three sample objects declare `schema: "anonymization"`, a slug that does not appear in the register's schema list (`report`, `template`, `entity` are defined; `anonymization` is not). The inconsistency was never caught because the register was never loaded.

Without this fix: `AnonymizationService` attempts to save report objects into a non-existent register, sample objects can never be inspected, and new developers or QA testers see an empty system.

## What Changes

- **BUG FIX** — `lib/Service/SettingsService.php` (or `RegistersLoader` repair step): add a `ConfigurationService::importFromApp()` call for `document_register.json` alongside the existing `docudesk_register.json` import.
- **BUG FIX** — `lib/Settings/document_register.json`: declare the missing `anonymization` schema (same `hardValidation: false`, `properties: []` pattern as the other schemas) so sample objects 2 and 3 reference a defined schema.
- **FORMALISE** — document the existing ad-hoc report fields in this spec (not schema-enforced — `hardValidation: false` is intentional for pipeline flexibility) so implementers know the expected payload shape.

Not in scope:
- Adding schema properties / validation to `template`, `entity`, or `anonymization` schemas — intentionally deferred, ad-hoc usage is the v1 contract.
- Implementing planned fields (WCAG compliance, language level, retention, GDPR data controller) — tracked as separate follow-up changes.
- Cross-document entity linking UI — the `entity` schema is a placeholder; its properties are TBD.
- Upgrading the file hash from MD5 to SHA-256 — tracked separately (standards note: MD5 / RFC 1321 is cryptographically weak; SHA-256 is the recommended upgrade path).

## Capabilities

### New Capabilities

- `document-register`: Defines the DocuDesk document register (`slug: "document"`, version `0.0.1`), its four schemas (`report`, `template`, `entity`, `anonymization`), and the pre-seeded pipeline sample objects. Covers the boot-time import that makes the register available on every install.

### Modified Capabilities

None. The document register is additive alongside the existing `docudesk_register.json` registers.

## Impact

**Affected code (DocuDesk):**
- `lib/Settings/document_register.json` — add `anonymization` schema declaration.
- `lib/Service/SettingsService.php` (or equivalent `RegistersLoader` repair step) — add `importFromApp()` call for `document_register.json`.

**Affected code (OpenRegister):** None.

**APIs / dependencies:**
- HTTP API: register and schema endpoints surface automatically via OpenRegister's generic `/api/registers` and `/api/objects/{schema}` routes — no new OCS controllers required.
- `ConfigurationService::importFromApp(appId, data, version, force)` — existing method; no changes to it.

**Data / migrations:**
- Running the repair step (or `occ maintenance:repair`) creates the `document` register, four schemas, and three seed objects idempotently. No database schema changes — everything in OpenRegister's `object` table.
- Re-running is safe: `version_compare`-based skip logic in `ImportHandler` prevents duplicates.
