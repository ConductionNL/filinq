## Why

The frontend's per-entity review UI needs two pieces of information that the consolidated-entities endpoint does not currently expose:

1. **`prohibitionMatch`** per entity — so the UI can render prohibition-locked entities as locked-on without re-running the matcher client-side. The same field is added on extract by the sibling change `anonymisation-bases-passthrough`; this change carries it through to the consolidated-entities surface.
2. **`suggestedBases[]`** per entity — auto-derived from the dossier's `bases[]` so the UI can pre-fill the grondslag picker. Operators still pick the final bases; this is a hint, not an enforced default.

This is split from the larger `anonymisation-grondslagen-and-prohibition-gate` umbrella into three sibling per-capability changes; the gate lives in `anonymisation-prohibition-gate`, and the anonymise-endpoint pass-through lives in `anonymisation-bases-passthrough`.

## What Changes

- **MODIFIED:** `anonymization-entity-review` capability — `GET /api/anonymization/batch/{batchId}/entities` response includes `prohibitionMatch` per entity (same shape as extract).
- **MODIFIED:** `anonymization-entity-review` capability — same endpoint response includes `suggestedBases[]` per entity, populated by union over the dossiers the batch's files belong to.

## Capabilities

### Modified Capabilities

- `anonymization-entity-review`

## Cross-app Dependencies

- **Soft** — `docudesk:entity-publication-policies` — provides the `publicationProhibition` schema + `PolicyMatchService`.
- **Soft** — `docudesk:add-dossier-schema` — provides the dossier register that owns `bases[]` for the inheritance.

## Impact

- **Code (docudesk):** the consolidated-entities handler under `lib/Controller/BatchAnonymizationController.php` (or its equivalent) + a resolver for dossier(s) → union of bases.
- **API contract:** response gains `prohibitionMatch` + `suggestedBases` per entity (additive, non-breaking).
- **Migration:** None.
