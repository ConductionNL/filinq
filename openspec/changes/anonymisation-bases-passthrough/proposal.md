## Why

Today no grondslag (legal basis) is recorded for any anonymised entity. When DocuDesk anonymises "Jan Janssen" in a document, nothing on the resulting record says *why* — no Woo Art. 5 ground, no link to the dossier's `bases[]`. Compliance reporting and audit reconstruction need this.

This change extends the per-document anonymise endpoint to accept a per-entity `bases[]` array and forwards it verbatim to OpenRegister's anonymise endpoint (which persists it on `EntityRelation` per the paired OR change `entity-relation-grondslagen`). It also extends the extract endpoint's response with a per-entity `prohibitionMatch` field so the frontend can render prohibition-locked entities without re-running the matcher client-side.

This is split from the larger `anonymisation-grondslagen-and-prohibition-gate` umbrella into three sibling per-capability changes; the gate itself lives in `anonymisation-prohibition-gate`, and the consolidated-entities review hints live in `anonymisation-entity-review-prohibition-hints`.

## What Changes

- **MODIFIED:** `anonymization` capability — the anonymise endpoint accepts an optional `bases` array per entity in the request payload. Forwarded verbatim to OpenRegister. DocuDesk does not validate UUIDs (per spec, OpenRegister also does not).
- **MODIFIED:** `anonymization` capability — the extract endpoint's response gains a `prohibitionMatch` field per detected entity: `null` (no match) or `{ ruleId, ruleName, highConfidence }`.
- **NO new schemas.** Bases live on `EntityRelation` per the paired OR change.

## Capabilities

### Modified Capabilities

- `anonymization`

## Cross-app Dependencies

- **Soft** — `openregister:entity-relation-grondslagen` — provides `EntityRelation.bases` persistence. DocuDesk's `bases[]` payload is silently dropped on the OR side until the OR migration runs.
- **Soft** — `docudesk:entity-publication-policies` — provides the `publicationProhibition` schema + `PolicyMatchService` used to compute `prohibitionMatch` at extract time.

## Impact

- **Code (docudesk):** `lib/Controller/AnonymizationController.php`, `lib/Controller/BatchAnonymizationController.php`, `lib/Service/AnonymizationService.php`, `lib/Service/BatchAnonymizeService.php`.
- **API contract:** anonymise payload gains optional per-entity `bases[]` (additive, non-breaking); extract response gains `prohibitionMatch` per entity (additive).
- **Privacy/compliance:** unblocks per-entity grondslag recording on `EntityRelation` once the OR paired change lands.
- **Migration:** None.
