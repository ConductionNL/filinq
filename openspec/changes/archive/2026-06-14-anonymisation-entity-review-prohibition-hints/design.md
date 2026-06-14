## Context

The consolidated-entities endpoint (per the existing `anonymization-entity-review` capability) returns per-entity rollups across a batch of files: `type`, `value`, `highestConfidence`, `fileCount`, `included`. The frontend's review UI needs two more things: the prohibition status (so the toggle can render locked-on) and a suggested-bases hint (so the grondslag picker isn't empty by default).

## Goals / Non-Goals

**Goals:**

- Embed `prohibitionMatch` per entity in the consolidated-entities response, using `highestConfidence` for the threshold check.
- Embed `suggestedBases[]` per entity, populated by inheritance from the dossier(s) the batch's files belong to.
- Stay additive and non-breaking: pre-change clients reading only the existing fields keep working.

**Non-Goals:**

- The gate itself (lives in `anonymisation-prohibition-gate`).
- The extract endpoint's `prohibitionMatch` (lives in `anonymisation-bases-passthrough`).
- Enforce `suggestedBases` server-side. The operator's payload `bases[]` is taken verbatim by the anonymise endpoint.

## Decisions

### D1. Use `highestConfidence` for the threshold check

The endpoint already exposes `highestConfidence` (max across the batch). Using it for the `prohibitionMatch.highConfidence` flag mirrors the worst-case risk view: if any file in the batch detected the entity above threshold, the whole rollup is high-confidence.

### D2. `suggestedBases` is the union over dossiers, deduplicated

If files are spread across multiple dossiers, union the dossiers' `bases[]`. If a file is not in any dossier (orphan), contribute nothing. If a dossier has empty `bases[]` (draft), contribute nothing. Empty union → `suggestedBases: []`.

**Rationale:** Most entities in a dossier share the dossier's grondslagen. Pre-filling beats forcing the operator to pick from scratch for every entity. Union (not intersection) keeps the picker permissive — operator removes what doesn't apply.

### D3. `suggestedBases` is a hint, not enforced

The actual `bases[]` chosen by the operator is sent on the anonymise request (per `anonymisation-bases-passthrough`) and may differ from `suggestedBases`. The server does not validate that operator-supplied bases are a subset of suggested.

### D4. Same `prohibitionMatch` shape as extract

`null` or `{ ruleId, ruleName, highConfidence }`. The frontend reuses one renderer for both surfaces.

## Risks / Trade-offs

- **PolicyMatchService doesn't exist as code yet** → covered by `anonymisation-prohibition-gate` scaffolding; until then `prohibitionMatch` returns `null`.
- **Files spread across many dossiers — large union** → bounded by the dossier vocabulary (six canonical Woo Art. 5 grondslagen for now). Acceptable.
- **Files added/removed from a batch between read and anonymise** → the consolidated rollup is computed per-request; subsequent edits force a re-read. Acceptable.

## Migration Plan

1. Confirm `PolicyMatchService::matchProhibition` is available.
2. Land the resolver for dossier → union-of-bases.
3. Land the response field additions.

**Rollback:** Remove the two new fields from the response.

## Seed Data

Not applicable. Dossiers + bases are seeded via `add-dossier-schema`. Prohibition rules are seeded via `entity-publication-policies`.

## Open Questions

- **Should `prohibitionMatch.highConfidence` be exposed server-side or computed client-side from `score` + a known threshold?** Provisional: expose server-side so the frontend doesn't need to know the threshold. Pinned for both extract and consolidated-entities by D4.
