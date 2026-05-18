## Why

The DocuDesk anonymise pipeline today does not consult the `publicationProhibition` register specced in `entity-publication-policies`. A protected entity (court-order witness, undercover officer, minor-protection entry) could be detected in a document and then silently slip past anonymisation if the operator deselected it. For these categories, "silently" is the wrong default — the operator deserves an explicit gate.

This change adds a backend safety check on the anonymise endpoint that consults the prohibition cache and refuses (HTTP 422) to forward requests where high-confidence prohibition-listed entities are missing from the to-be-anonymised set. Low-confidence matches may be released by an `acknowledgedOverrides[]` array on the payload.

This is split from the larger `anonymisation-grondslagen-and-prohibition-gate` umbrella into three sibling per-capability changes:

1. `anonymisation-prohibition-gate` (this change) — the new gate capability.
2. `anonymisation-bases-passthrough` — the `anonymization` delta forwarding per-entity `bases[]` to OpenRegister + adding `prohibitionMatch` to extract.
3. `anonymisation-entity-review-prohibition-hints` — the `anonymization-entity-review` delta adding `prohibitionMatch` + `suggestedBases` to the consolidated-entities endpoint.

## What Changes

- **NEW capability:** `anonymisation-prohibition-gate`. The safety check at anonymise time, the high-confidence-must-match contract, and the `acknowledgedOverrides` release mechanism.
- **NEW logic in `lib/Controller/AnonymizationController.php` + `lib/Controller/BatchAnonymizationController.php`:** accept `acknowledgedOverrides[]` and emit 422 with structured body when the gate fires.
- **NEW logic in `lib/Service/AnonymizationService.php`:** run the gate before forwarding to OpenRegister.
- **NEW (or extended) service `lib/Service/PolicyMatchService.php`:** prohibition matcher reused from `entity-publication-policies` (ADR-011 — reuse before reimplementing). If not yet present, this change's apply phase scaffolds it from the parent spec.
- **MODIFIED in-place** (not a delta): the wording in `openspec/changes/entity-publication-policies/specs/entity-publication-policies/spec.md` and its `consent-management` delta — tighten "do not consult these policies" to distinguish workflow integration from read-only data access. Tracked as a task here.

## Capabilities

### New Capabilities

- `anonymisation-prohibition-gate`

## Cross-app Dependencies

- **Soft** — `docudesk:entity-publication-policies` — provides the `publicationProhibition` schema and `PolicyMatchService`. The gate has nothing to enforce until prohibition records exist; either change can land first.

## Impact

- **Code (docudesk):** `lib/Controller/AnonymizationController.php`, `lib/Controller/BatchAnonymizationController.php`, `lib/Service/AnonymizationService.php`, `lib/Service/BatchAnonymizeService.php`, `lib/Service/PolicyMatchService.php` (or extension), `lib/Service/ConsentService.php` (cache loading).
- **API contract:** the anonymise endpoint may now respond with HTTP 422 when prohibition-listed entities are missing. Existing callers with no prohibition records configured see no behaviour change. The 422 body lists missing entities by OpenRegister `Entity` canonical name (not literal document text, not the rule's `primaryName`).
- **Privacy/compliance:** Strengthens GDPR/AVG + Wet open overheid compliance by guaranteeing prohibition-listed entities cannot be missed.
- **Migration:** None. No schema changes.
