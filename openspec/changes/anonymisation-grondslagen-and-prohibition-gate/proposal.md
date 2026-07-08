> **Superseded (prohibition-gate portion):** the prohibition-gate capability specced here — the per-request re-resolving gate on the anonymise endpoint, `acknowledgedOverrides`, the `PolicyOverrideAuditService`, and the `prohibitionOverrideAudit` schema — is **superseded by `anonymise-prohibition-consent-guard`**, which enforces prohibitions with a lighter compute-at-guard design (a guarded per-relation skip endpoint + a threshold-tiered `force`, plus standing-consent auto-skip) and no OpenRegister change. The grondslagen/bases portion of this change is unaffected.

## Why

Today the anonymisation pipeline runs end-to-end without surfacing per-entity legal bases (grondslagen) or guarding against missed prohibition-listed entities. Two concrete problems:

1. **No grondslag is recorded for any anonymised entity.** When DocuDesk anonymises "Jan Janssen" in a document, nothing on the resulting record says *why* — no Woo Art. 5 ground, no link to the dossier's `bases[]`. Compliance reporting and audit reconstruction need this.
2. **The `publicationProhibition` list (specced in `entity-publication-policies`) is not consulted by the anonymisation flow.** A protected entity could appear in a document, be detected, and slip past anonymisation if the operator failed to select it — silently. For categories like court-order witnesses, undercover officers, and minor-protection entries, "silently" is the wrong default.

This change closes both gaps in the publication-prep / per-document anonymisation flow that the frontend team is building. It also makes a tiny in-place wording fix to `entity-publication-policies` so the trigger boundary correctly distinguishes "workflow integration" from "read-only data access".

## What Changes

- **MODIFIED (post-explore-mode rework — 2026-05-12):** Bases are set via OpenRegister's new `PATCH /api/entity-relations/{id}` endpoint (or the equivalent DI mapper method `EntityRelationMapper::updateDecisionMetadata`), not via DocuDesk's anonymise payload. Per-relation, idempotent, audited on the OR side. DocuDesk's anonymise endpoint payload's `entities[]` array does NOT carry a `bases` field. Callers (frontend, batch tools, scripts) attach bases to detected entities by PATCHing OR directly between the extract step (which returns the relation IDs) and the anonymise call. DocuDesk does not act as a translator for per-entity bases — that responsibility moves to OR's own PATCH endpoint to keep each decision atomic.
- **MODIFIED:** The extract endpoint's response gains a `prohibitionMatch` field per detected entity: either `null` (no match) or an object containing `{ ruleId, ruleName, confidence }`. This lets the review UI render prohibition-matched entities as locked-on.
- **MODIFIED:** The entity-review endpoints (per-batch consolidated entities) include the same `prohibitionMatch` field plus an optional per-entity `suggestedBases` array (auto-picked from the dossier's `bases[]`) so the review UI can pre-fill the grondslag picker.
- **NEW:** A prohibition gate on the anonymise endpoint. Before forwarding the request to OpenRegister, the controller validates that every detected entity in the file matching an active `publicationProhibition` rule with confidence ≥ 0.85 is present in the to-be-anonymised set. Any high-confidence prohibition-matched entity that is missing from the set causes the request to fail with **HTTP 422** and a body listing the missing entities (using the OpenRegister Entity record's canonical name, not the literal detected text).
- **NEW:** Override mechanics for low-confidence prohibition matches. The anonymise request payload accepts an `acknowledgedOverrides` array. Each entry — `{ ruleId, entityId, reason }` — releases one low-confidence (< 0.85) prohibition match from the gate. Overrides for ≥ 0.85 matches are rejected with a 422 (override not allowed for high-confidence matches). The `acknowledgedOverrides` array is accepted on every request (no special "retry" flag needed). When an override is acknowledged, DocuDesk's controller MUST:
  1. Persist a DocuDesk-side audit entry capturing `{ruleId, entityRelationId, fileId, reason, acknowledgedBy: <user UID>, acknowledgedAt: <ISO-8601>}` — sufficient for Woo-compliance reconstruction of *why* a flagged entity was deliberately not redacted.
  2. PATCH OpenRegister's matching `EntityRelation` row(s) with `{ "skipAnonymization": true }` via `EntityRelationMapper::updateDecisionMetadata` (DI). OR's audit-trail captures the skip-flip; the DD-side entry captures the override's `reason` (which is operator commentary, not within OR's whitelist).
  3. Proceed with the anonymise call. OR's `markAsAnonymized` will already exclude the skipped rows (per the OR-side spec); no further DD work needed for execution.
- **MODIFIED (in-place edit, not a delta in this change):** The wording in `openspec/changes/entity-publication-policies/specs/entity-publication-policies/spec.md` and its consent-management delta — currently asserts that generic anonymisation flows "do not consult these policies". The wording is tightened to: generic anonymisation does not invoke the publication-clearance workflow and does not create `publicationConsent` records, but MAY read the `publicationProhibition` list as a data source for safety checks. Read access to a register is not workflow integration. Tracked as task 5.x in this change's tasks.md; not a capability delta.
- **NEW (DocuDesk-side):** A small `prohibitionOverrideAudit` schema (or equivalent persistent store — implementation choice) in DocuDesk recording acknowledged overrides. Minimum fields: `ruleId`, `entityRelationId`, `fileId`, `reason`, `acknowledgedBy`, `acknowledgedAt`. Implementation MAY add the schema to `docudesk_register.json` alongside existing schemas, or place it under a separate audit register. Distinct from OR's audit-trail — OR records *what* changed on the row (skipAnonymization flipped); DocuDesk records *why* the operator chose to acknowledge the override.
- **NO change to OR-side schemas from DocuDesk's side.** Bases and skipAnonymization live on `EntityRelation` per OpenRegister's paired (and now reworked) `entity-relation-grondslagen` change.

## Capabilities

### New Capabilities

- `anonymisation-prohibition-gate`: the safety check at anonymise time, the high-confidence-must-match contract, the `acknowledgedOverrides` mechanism, and the extract-time prohibition-flag response field. This capability owns the prohibition-driven behaviour layered on top of the existing anonymisation pipeline.

### Modified Capabilities

- `anonymization`: the anonymise endpoint contract is extended on the response side — the request payload no longer carries per-entity `bases` (those are set via OR's PATCH endpoint directly), and the extract-endpoint response includes per-entity `prohibitionMatch` for UI hinting.
- `anonymization-entity-review`: the consolidated-entities endpoint includes per-entity `prohibitionMatch` and `suggestedBases`, so the review UI can render prohibition state and pre-fill the grondslag picker.

## Cross-app Dependencies

- **Hard** — `openregister:entity-relation-grondslagen` (post-rework) — provides the `EntityRelation` columns (`bases`, `skipAnonymization`), the `PATCH /api/entity-relations/{id}` endpoint, and the parallel `EntityRelationMapper::updateDecisionMetadata` DI method. DocuDesk's override-acknowledge flow calls `updateDecisionMetadata` via DI; bases-set flows (anywhere they live — frontend, batch scripts) call OR's PATCH directly. This change cannot ship without OR's rework in place. **Status:** OR rework is committed locally on `feat/1435/entity-relation-grondslagen-impl` (commit `ef5464b94`); not yet merged.
- **Soft** — `docudesk:entity-publication-policies` — provides the `publicationProhibition` schema. The gate has nothing to enforce until prohibition records exist; either change can land first.

Each row MUST be tracked as a `Depends on` link from this change's GitHub issue once the target's tracking issue exists.

## Impact

- **Code (docudesk):**
  - `lib/Controller/AnonymizationController.php` — accept `acknowledgedOverrides` on anonymise (the bases field on `entities[]` is dropped); populate `prohibitionMatch` on extract response.
  - `lib/Controller/BatchAnonymizationController.php` — same on the batch endpoints.
  - `lib/Service/AnonymizationService.php` — perform prohibition gate before forwarding to OR; for each acknowledged override, write a DocuDesk-side audit entry AND PATCH OR's `EntityRelation` (`updateDecisionMetadata`) with `{skipAnonymization: true}` via DI. Bases are no longer threaded through this service — operators set them via OR's PATCH endpoint.
  - **NEW** `lib/Service/PolicyMatchService.php` (or extension to existing service) — the prohibition matcher. Specced earlier in `entity-publication-policies` but not yet implemented; this change is the first concrete consumer. **ADR-011:** before implementing, confirm no existing matcher in OpenRegister can be reused (preliminary check: none — prohibition data is DocuDesk-owned).
  - **NEW** `lib/Service/PolicyOverrideAuditService.php` (or fold into an existing audit-like service) — write override decisions to the persistent store. Reads + queries are out of scope for this change; just the write path.
  - **NEW** schema (or storage) for the override-audit entries. Smallest implementation: add a `prohibitionOverrideAudit` schema to `docudesk_register.json` with the fields listed in *What Changes*.
  - `lib/Service/ConsentService.php` — read access to `publicationProhibition` records (cache-loaded list of active rules with current time bounds). Existing `publicationConsent` workflow is untouched.
- **API contract:**
  - Anonymise endpoint: payload gains top-level `acknowledgedOverrides[]`. The per-entity `bases` field on `entities[]` is intentionally NOT added (bases-set is on OR's PATCH endpoint). Additive, non-breaking.
  - Extract endpoint and batch entities endpoint: response gains `prohibitionMatch` per entity. Additive.
  - 422 added as a possible response status with structured error body. Existing callers that don't have prohibitions in the consent register see no behaviour change.
- **Cross-app:**
  - **Hard** dependency on OpenRegister's reworked `entity-relation-grondslagen` change. DocuDesk's override-acknowledge path calls `EntityRelationMapper::updateDecisionMetadata` via DI; without it, override acknowledgement cannot persist a skip flag.
  - Depends on `entity-publication-policies` for the `publicationProhibition` schema. This change does not block on `entity-publication-policies` being implemented in code, but the gate has nothing to enforce until prohibition records exist.
- **Privacy/compliance:** Strengthens GDPR/AVG and Wet open overheid compliance by guaranteeing prohibition-listed entities cannot be missed from anonymisation. The 422 body uses OpenRegister Entity canonical names to identify missing entities — readable without exposing literal document text.
- **WOO compliance:** No change to the per-document publication-clearance workflow; `publicationConsent` records are not created by this flow (those are created by the publication-prep flow, which is separate).
- **Tests:** Unit tests for the prohibition matcher (high/low confidence, override valid/invalid, multiple matches). Integration tests for the four gate outcomes (no prohibitions; all included; missing high-confidence; missing low-confidence with override). Browser tests deferred to the frontend team's review-UI change.
- **Migration:** None. No schema changes.
