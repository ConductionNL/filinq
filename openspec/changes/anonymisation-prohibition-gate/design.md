## Context

DocuDesk's anonymise pipeline (per the canonical `anonymization` and `anonymization-entity-review` capabilities) runs upload → extract → review → anonymise. The `publicationProhibition` list specced in `entity-publication-policies` is not consulted by this flow. A user could deselect (or fail to detect) a court-order witness or undercover officer and still anonymise the document — silently. The design from `entity-publication-policies` explicitly stated generic anonymisation flows do not touch the policy layer. That bound is right *for workflow integration* (creating `publicationConsent` records, pre-empting WOO) but too tight *for read-only safety*. We refine it.

This change is the gate. Two sibling changes (`anonymisation-bases-passthrough`, `anonymisation-entity-review-prohibition-hints`) carry the payload + response extensions to the canonical anonymisation capabilities.

## Goals / Non-Goals

**Goals:**

- Gate the anonymise call: no high-confidence (≥ 0.85) prohibition-listed entity may be missing from the to-be-anonymised set. Fail with HTTP 422 + structured body listing missing entities by OpenRegister Entity canonical name.
- Allow operators to override low-confidence (< 0.85) prohibition matches via `acknowledgedOverrides[]` on the request. Overrides for ≥ 0.85 matches are rejected.
- Reuse `PolicyMatchService` from `entity-publication-policies` (or scaffold it from spec here if that change hasn't landed yet).
- Tighten the trigger-boundary wording in `entity-publication-policies` so read-only access to the prohibition register isn't lumped in with workflow integration.

**Non-Goals:**

- Forward per-entity `bases[]` to OpenRegister (that's `anonymisation-bases-passthrough`).
- Surface `prohibitionMatch` on extract or consolidated-entities responses (those are the two sibling changes).
- Build the publication-prep flow that creates `publicationConsent` records (`consent-management`).
- Persist anything DocuDesk-side. The gate is stateless beyond its in-memory cache.

## Decisions

### D1. Gate runs in DocuDesk, not in OpenRegister

The prohibition matcher and the 422-on-missing logic live in DocuDesk's anonymise controller / service layer. OpenRegister stays a generic anonymise primitive. The `publicationProhibition` records live in DocuDesk's consent register; coupling OR to a DocuDesk-specific schema is unjustified. Other Conduction apps that call OR's anonymise endpoint own their own gate.

### D2. Reuse `PolicyMatchService` from `entity-publication-policies`

The matcher specced in `entity-publication-policies` is the same matcher we need — load active prohibition + standing-consent records into an in-memory cache, match by `(matchType, entityType, value)`, return at most one match. For this change, only the prohibition portion of the cache is consulted. ADR-011 — reuse before reimplementing.

**Trade-off:** Either change can land first; whichever lands first scaffolds the service.

### D3. Confidence threshold for hard gating: 0.85 (configurable)

Detected entities at confidence ≥ 0.85 that match a prohibition rule MUST be present in the to-be-anonymised set. Lower-confidence matches MAY be overridden. 0.85 is the threshold the entity recognition pipeline already uses (`EntityDetectionService` config). Above 0.85 the privacy-safe default is "trust the match" (over-anonymising < unmasking protected people). Configurable via `docudesk.prohibition.high_confidence_threshold`.

### D4. `acknowledgedOverrides` accepted on every request

The override array can be sent on the first request, not only after a 422. Lets the frontend pre-populate overrides from the extract-time `prohibitionMatch` response. Removes a "retry mode" flag. Validation: the `(ruleId, entityId)` must match an active match below threshold; ≥-threshold overrides are rejected; non-matching combinations are silently ignored.

### D5. 422 body uses OpenRegister Entity canonical name

Not the literal detected text ("P. Jansen", "Pieter J.") — too noisy. Not the prohibition rule's `primaryName` (sometimes a pseudonym like "Beschermde Getuige A"). The OpenRegister `Entity` record's canonical name (e.g. "Pieter Jansen") is the operator's actionable identifier. Application logs record `ruleId` + `entityId` + file ID only — never literal text.

### D6. In-place wording fix to `entity-publication-policies`

The in-flight `entity-publication-policies` change asserts that generic anonymisation flows "do not consult these policies". Strictly true for workflow integration but misleading for read-only access. Tighten the wording in two spec files inside the parent change folder — this is an amendment to in-flight specs, not a delta override here.

## Risks / Trade-offs

- **PolicyMatchService doesn't exist as code yet** → tasks include "verify availability; scaffold from spec if absent".
- **Prohibition rule deleted between extract and anonymise** → cache invalidation on object-changed events; the gate releases if a rule is deleted between the operator's extract view and their anonymise click. Acceptable — deletion is an explicit decision.
- **Override for a high-confidence match that drops to low-confidence on re-extract** → gate evaluates against live extraction; override correctly applies. Inverse case: 422, operator retries without the override.

## Migration Plan

1. Confirm `PolicyMatchService` is available (or scaffold from `entity-publication-policies` spec).
2. Land controller + service changes for the gate + override mechanism.
3. Land the in-place wording fix to `entity-publication-policies` spec files.

**Rollback:** Remove the gate (early-return before validation). Override rejection becomes a no-op.

## Seed Data

Not applicable. `publicationProhibition` seed records are owned by `entity-publication-policies`.

## Open Questions

- Final config-key naming for the threshold — provisional `docudesk.prohibition.high_confidence_threshold`. Resolve during apply by checking what `EntityDetectionService` already reads.
