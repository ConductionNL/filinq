## Context

DocuDesk's anonymise pipeline today (per the canonical `anonymization` and `anonymization-entity-review` capabilities) runs upload → extract → review → anonymise. The review step exists in batch flows (with toggle endpoints), but two backend-side gaps remain:

1. **No grondslag is recorded for any anonymised entity.** The anonymise call accepts an `entities[]` array; each entry has `text`, `entityType`, `score`. There is no place for a per-entity legal basis. After the OpenRegister paired change `entity-relation-grondslagen` lands, OpenRegister's `EntityRelation` row will accept an optional `bases` JSON column and the anonymise endpoint will persist + strip it. DocuDesk's job is to forward operator-picked bases verbatim to OpenRegister.
2. **The `publicationProhibition` list is not consulted by the anonymise flow.** A user could deselect (or fail to detect) a court-order witness or undercover officer and still anonymise the document — silently. The design from `entity-publication-policies` explicitly stated generic anonymisation flows do not touch the policy layer. That bound is right *for workflow integration* (creating publicationConsent records, pre-empting WOO) but too tight *for read-only safety*. We refine it.

The frontend team is building the per-entity review UI on top of these endpoints. This change is the backend contract.

## Goals / Non-Goals

**Goals:**

- Forward per-entity `bases[]` from the operator's picker into OpenRegister's anonymise call. DocuDesk does not persist bases itself.
- Surface a per-entity `prohibitionMatch` on the extract-time response and on the per-batch consolidated entities response, so the frontend can render prohibition-locked entities without re-running the matcher client-side.
- Gate the anonymise call: no high-confidence prohibition-listed entity may be missing from the to-be-anonymised set. Fail with HTTP 422 + a structured body listing the missing entities (using OpenRegister Entity record canonical names, not literal document text).
- Allow operators to override low-confidence (< 0.85) prohibition matches via an `acknowledgedOverrides[]` field on the request. Overrides for ≥ 0.85 matches are rejected.
- Edit the wording of `entity-publication-policies` (in-flight change, not yet implemented) so its trigger-boundary correctly distinguishes "workflow integration" from "read-only data access".

**Non-Goals:**

- Build the publication-prep flow that creates `publicationConsent` records. Out of scope; tracked separately.
- Change the existing per-document anonymise UI. The frontend team owns review-screen design; this change provides API contract only.
- Persist bases anywhere DocuDesk-side. Storage lives on `EntityRelation` (paired OpenRegister change).
- Validate that base UUIDs resolve. Frontend picker output is trusted; downstream OpenRegister accepts any UUID.
- Build a separate prohibition admin page. That's part of `entity-publication-policies`.
- Touch the canonical `consent-management` capability. The publication-clearance workflow is unchanged; this gate is a different surface.

## Decisions

### D1. Gate runs in DocuDesk, not in OpenRegister

The prohibition matcher and the 422-on-missing logic live in DocuDesk's anonymise controller / service layer. OpenRegister stays a generic anonymise primitive (per the paired OR change's design).

**Rationale:**

- The `publicationProhibition` records live in DocuDesk's consent register. Putting the gate in OR would couple OR to a DocuDesk-specific schema or require a generic "registry-based safety filter" hook in OR — neither is justified by current scope.
- Other Conduction apps that might call OR's anonymise endpoint have their own prohibition models (or none). Each app's gate is each app's responsibility.

**Trade-off:** A non-DocuDesk caller of OR's anonymise endpoint bypasses the gate. Acceptable: OR's anonymise is currently DocuDesk-only in practice, and the gate is documented as a DocuDesk safety check, not an OR-level guarantee.

### D2. Prohibition matching reuses `PolicyMatchService` from `entity-publication-policies`

The matcher specced in `entity-publication-policies` (in-flight) is the same matcher we need here — load `active: true` `publicationProhibition` rules + active `scope: "entity"` `publicationConsent` rules into an in-memory cache, match by `(matchType, entityType, value)` tuples, return at most one match.

For this change, only the prohibition portion of that cache is consulted (standing-consent matching is publication-clearance-only). The matcher exposes a method like `matchProhibition(entityText, entityType, resolvedIdentifiers): ?MatchedRule`.

**Rationale:** ADR-011 — reuse before reimplementing. The matcher exists in spec (not yet code); this change is its first concrete consumer. If it doesn't exist as code by the time we apply, the apply phase wires it up here and the `entity-publication-policies` apply consumes it.

**Trade-off:** A small dependency-ordering concern. Either change can land first; the second one wires up the unused side. We document this in the implementation order (tasks).

### D3. Confidence threshold for hard gating: 0.85

Detected entities at confidence ≥ 0.85 that match a prohibition rule MUST be present in the to-be-anonymised set. Lower-confidence matches MAY be overridden via `acknowledgedOverrides`.

**Rationale:**

- 0.85 is the threshold the entity recognition pipeline today uses to distinguish "high-quality" from "borderline" detections (see configuration in `EntityDetectionService`).
- Below 0.85, false positives are common (e.g. partial name fragments). Forcing anonymisation on those would frustrate operators reviewing real-world documents; allowing override gives them a release valve.
- Above 0.85, the privacy-safe default is "trust the match" — false positives anonymise more than needed, false negatives unmask protected people. The asymmetry favours over-anonymising.

**Configurability:** the threshold is configurable via app config (`docudesk.prohibition.high_confidence_threshold`, default 0.85). The configurable seam is small and lets a tenant tune for their detector quality.

### D4. `acknowledgedOverrides` accepted on every request

The override array can be sent on the first request, not only after a 422. This:

- Lets the frontend pre-populate overrides based on the extract-time `prohibitionMatch` response (which already shows confidence scores).
- Removes the need for a "retry mode" flag.
- Makes the API uniform — same request shape always.

**Validation:** Each override entry MUST correspond to a rule + entity combination where the live confidence is < 0.85. An override for a ≥ 0.85 match is rejected with 422 ("override not allowed for high-confidence matches"). An override for a rule/entity combination that doesn't actually match (no such prohibition fires) is silently ignored — easier than gating "you can't override what isn't matched", and the result is correct.

### D5. 422 body uses OpenRegister Entity record's canonical name

When the gate fails, the response body lists missing entities by the OpenRegister `Entity` record's canonical name (e.g. `"Jan Janssen"`), NOT by the literal detected text in the document, NOT by the prohibition rule's `primaryName`.

**Rationale:**

- The operator already has access to all detected entities (they're choosing what to anonymise). Telling them "entity X is missing" using the canonical entity name is the most actionable identifier.
- The literal detected text varies per occurrence ("P. Jansen", "Pieter Jansen", "Pieter J.") — listing all variants is noisy.
- The prohibition rule's `primaryName` is sometimes a pseudonym ("Beschermde Getuige A") that doesn't help the operator find the entity in their UI list.

**Privacy note:** the canonical entity name is not exposed beyond the operator who already saw all detections. The 422 is a server→operator channel, not a public log. Logging policy (do we write the entity name into the application log, or only the entity ID + rule UUID?) is decided in tasks: log the rule UUID + entity ID; the canonical name appears only in the response payload.

### D6. Extract-time flag: `prohibitionMatch` per entity

The extract endpoint's response and the per-batch consolidated entities endpoint include a `prohibitionMatch` field on each detected entity:

```json
{
  "entityId": 42,
  "text": "Beschermde Getuige A",
  "entityType": "PERSON",
  "score": 0.97,
  "prohibitionMatch": {
    "ruleId": "uuid-of-rule",
    "ruleName": "Beschermde Getuige A — court order RB-AMS 2024-0312",
    "highConfidence": true
  }
}
```

If no prohibition rule matches, the field is `null`. The frontend uses `highConfidence` to render the toggle as locked-on (true) vs. flag-with-confirm (false).

**Rationale:** Running the matcher once at extract and embedding the result in the response saves the frontend from re-running it. The frontend's review UI is a thin renderer.

### D7. Suggested bases default to dossier inheritance

The consolidated-entities endpoint includes a `suggestedBases[]` array per entity, populated by inheritance from the dossier the file belongs to (`dossier.bases[]`). The frontend may pre-fill the picker with these and let the operator confirm or refine.

**Rationale:** Most entities in a dossier share the dossier's grondslagen. Pre-filling beats forcing the operator to pick from scratch for every entity.

**Edge cases:**

- File not in a dossier (orphan): `suggestedBases` is empty. Operator picks from full vocabulary.
- Dossier has empty `bases[]` (draft dossier): `suggestedBases` is empty. Same handling.
- Operator overrides at the entity level: supplied `bases` in the anonymise payload is taken verbatim. `suggestedBases` is a hint, not an enforced default.

### D8. In-place wording fix to `entity-publication-policies`

The in-flight `entity-publication-policies` change asserts that generic anonymisation flows "do not consult these policies". Strictly true for *workflow integration* (creating `publicationConsent` records, pre-empting WOO), but misleadingly broad — *read-only access* to the prohibition register for safety checks is a legitimate use that doesn't violate the trigger boundary.

The fix is small: tighten the wording in two places:

- `openspec/changes/entity-publication-policies/specs/entity-publication-policies/spec.md` — the "Out-of-scope behaviors" section's "Generic anonymisation" bullet.
- `openspec/changes/entity-publication-policies/specs/consent-management/spec.md` — the trigger-boundary preamble.

Old: *"Generic anonymisation flows … do not consult these policies."*
New: *"Generic anonymisation flows do not invoke the publication-clearance workflow and do not create `publicationConsent` records. They MAY read the `publicationProhibition` list as a data source for safety checks (e.g. the prohibition gate specced in `anonymisation-prohibition-gate`); read access to a register is not workflow integration."*

This edit is captured as a task in this change's `tasks.md`. It does NOT introduce a delta for `entity-publication-policies` here — the parent change's specs are still under its own change directory; this is an amendment, not an override.

### D9. No DocuDesk-side persistence of bases

DocuDesk's controller forwards `bases[]` to OpenRegister and does not persist them locally. There is no DocuDesk schema for "anonymisation log with bases" — all bases live on `EntityRelation` after the OR paired change.

**Rationale:** Single source of truth. Two storage locations means two writes that can disagree, and the data model gets confused. Reading bases for an EntityRelation goes through OR; DocuDesk can join when it needs to render summaries.

## Risks / Trade-offs

- **[Order-of-landing — DocuDesk's bases payload is a no-op until OR's column exists]** → Mitigation: the bases field is forwarded but silently lost on the OR side until the OR migration runs. The persist-then-strip semantics on OR's side are unchanged for callers that don't pass bases. Acceptable.
- **[PolicyMatchService doesn't exist as code yet]** → Mitigation: tasks include "verify PolicyMatchService is available; if not, scaffold from spec or block on `entity-publication-policies` apply". Either change is the natural implementer; whichever lands first builds the matcher.
- **[Prohibition rule deleted between extract and anonymise]** → Mitigation: cache invalidation on object-changed events ensures the gate sees current rules. If a rule is deleted between the extract response (which flagged the entity) and the anonymise call (which gates), the gate releases — operator may anonymise or not as they prefer. This is the right outcome: deletion of a prohibition is an explicit decision; it should propagate.
- **[Operator submits an override for a rule that's no longer active]** → Override is ignored (rule isn't matching). No harm.
- **[Operator submits an override for a high-confidence match that has dropped to low-confidence after a re-extract]** → Edge case. The gate evaluates against the live extraction, not the version the operator saw. If the operator sent an override at high-confidence-time and the re-extract drops it, the override now correctly applies. If the inverse happens (re-extract bumps confidence), the override is rejected and the operator gets a 422 — they retry without the override.
- **[Extract response inflation]** → Each detected entity gains `prohibitionMatch` and `suggestedBases`. For a doc with hundreds of entities, the response grows by a few KB. Acceptable.

## Migration Plan

1. Land `PolicyMatchService` (or confirm it exists from `entity-publication-policies` apply). Provides `matchProhibition()`.
2. Land controller + service changes for the gate, the override mechanism, and the bases pass-through.
3. Land the in-place wording fix to the in-flight `entity-publication-policies` change's spec files.
4. Land the extract-response and consolidated-entities-response field additions (`prohibitionMatch`, `suggestedBases`).
5. Release. The frontend team's review UI consumes the new fields when their change ships.

**Rollback:** Remove the gate (early-return before validation). Bases are silently forwarded but not enforced. Override rejection becomes a no-op. Acceptable for emergency rollback.

## Seed Data

Not applicable — this change introduces no new schemas or registers. The `publicationProhibition` records consumed by the gate are seeded via the in-flight `entity-publication-policies` change (4 realistic seed records: court order, minor protection, undercover officer, categorical exemption). The `base` vocabulary used for the bases pass-through is seeded via the in-flight `add-dossier-schema` change (six canonical Woo Art. 5 grondslagen). Both are upstream dependencies of this change, not its responsibility.

## Open Questions

- **Where does the high-confidence threshold live in app config?** Provisional: `docudesk.prohibition.high_confidence_threshold` (default 0.85). Alternative: piggyback on an existing detection-confidence config key. Resolve during apply by checking what `EntityDetectionService` already reads.
- **Should the consolidated-entities endpoint expose `prohibitionMatch.highConfidence` directly, or compute it client-side from the `score` and a known threshold?** Provisional: expose `highConfidence` server-side so the frontend doesn't need to know the threshold. This keeps the threshold a server-side concern.
- **What about prohibition matches found at extract time but where the operator never opens the review UI (auto-anonymise path)?** The frontend in scope here always goes through review. If a future caller bypasses review, the gate at anonymise still catches them. No additional work needed.
