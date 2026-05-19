## Context

The anonymise call accepts an `entities[]` array; each entry has `text`, `entityType`, `score`. There is no place for a per-entity legal basis. After the OpenRegister paired change `entity-relation-grondslagen` lands, OR's `EntityRelation` row accepts an optional `bases` JSON column and the anonymise endpoint persists + strips it before forwarding to OpenAnonymiser. DocuDesk's job is to forward operator-picked bases verbatim.

The extract endpoint currently returns detected entities without any indication of prohibition status. Re-running the matcher in the frontend duplicates work and forces the frontend to know the high-confidence threshold. Embedding the match result in the extract response keeps the frontend a thin renderer.

## Goals / Non-Goals

**Goals:**

- Forward per-entity `bases[]` from the operator's picker into OpenRegister's anonymise call. DocuDesk does not persist bases itself.
- Surface per-entity `prohibitionMatch` on the extract response so the frontend can render prohibition state without re-running the matcher.
- Stay additive and non-breaking: pre-change clients that don't send `bases` and don't read `prohibitionMatch` keep working unchanged.

**Non-Goals:**

- The gate itself (lives in `anonymisation-prohibition-gate`).
- The consolidated-entities review extensions (live in `anonymisation-entity-review-prohibition-hints`).
- Persist bases DocuDesk-side. Single source of truth: `EntityRelation`.
- Validate that base UUIDs resolve. Frontend picker output is trusted; OR also doesn't validate.

## Decisions

### D1. Forward bases verbatim — no validation, no local persistence

DocuDesk's controller treats `bases[]` as opaque pass-through. Two storage locations means two writes that can disagree. Reading bases for an `EntityRelation` goes through OR.

### D2. `prohibitionMatch` shape matches the consolidated-entities endpoint

`null` or `{ ruleId, ruleName, highConfidence }`. The frontend uses `highConfidence` to render the toggle as locked-on (true) vs. flag-with-confirm (false). Computing `highConfidence` server-side keeps the threshold a server-side concern.

### D3. Threshold inclusive — confidence ≥ threshold is high-confidence

The threshold (default 0.85, configurable via `docudesk.prohibition.high_confidence_threshold`) is treated as inclusive at extract time. The gate in `anonymisation-prohibition-gate` uses the same threshold + the same inclusivity. Identical evaluation at both surfaces removes off-by-one surprises.

### D4. Mixed payloads — only entries with bases carry the field forward

If three entries are sent and only one has `bases` populated, the forwarded body has `bases` on that one entry; the other two have no `bases` field (or `null`, depending on JSON encoder defaults). Empty `bases: []` is forwarded as `[]`, not omitted.

## Risks / Trade-offs

- **Order-of-landing — DocuDesk's bases payload is a no-op until OR's column exists** → Mitigation: the field is forwarded but silently lost on OR until the migration runs. Persist-then-strip semantics are unchanged for callers that don't pass `bases`. Acceptable.
- **PolicyMatchService doesn't exist as code yet** → Mitigation: `anonymisation-prohibition-gate` covers scaffolding the service. This change can call it once available; until then the `prohibitionMatch` field returns `null` (no prohibition records = no matches).
- **Extract response inflation** → Each detected entity gains `prohibitionMatch`. For a doc with hundreds of entities, response grows by a few KB. Acceptable.

## Migration Plan

1. Confirm `PolicyMatchService::matchProhibition` is available (lands via `anonymisation-prohibition-gate` if not already present from `entity-publication-policies`).
2. Land controller + service changes for the bases pass-through.
3. Land the extract-response field addition.

**Rollback:** Remove the new field from the response. The bases field becomes silently ignored. Acceptable for emergency rollback.

## Seed Data

Not applicable. The `base` vocabulary used for the bases pass-through is seeded via `add-dossier-schema` (six canonical Woo Art. 5 grondslagen).

## Open Questions

- None — the response-shape and pass-through semantics are pinned by D1–D4 above.
