# Design: anonymization-review-workbench

## Context

Verified at HEAD (worktree `spec/market-gap-2026-07`, filinq @
origin/development):

- `src/views/anonymization/EntityReviewTable.vue` already implements search,
  type filter, selected/total counters, bulk select/deselect of visible rows,
  a per-entity grondslag (bases) `NcSelect`, a per-entity skip toggle, and an
  "Apply dossier grondslagen to visible" action. It emits
  `toggle | bulk-select | bulk-deselect | bases-change | skip-change |
  confidence-change`.
- `src/views/anonymization/AddManualEntityModal.vue` posts operator-supplied
  text to OpenRegister's chunk-aware matcher
  (`POST /api/files/{fileId}/manual-entities`, OR route
  `fileText#addManualEntity`, verified present in OR `appinfo/routes.php`);
  one `EntityRelation` is created per occurrence found.
- `AnonymizationService::extractAndDetectEntities()` (lib/Service/, line 192)
  delegates to OR `TextExtractionService::extractFile()` +
  `EntityRelationMapper::findEntitiesForFile()`, then calls
  `GrondslagProposalService::applyProposals()` (fill-only-when-empty per
  entity type, admin config key `filinq.grondslagen.entity_type_bases` —
  CB #122 is therefore already implemented server-side) and
  `attachProhibitionMatches()` via `PolicyMatchService`.
- `PolicyMatchService::match()` already evaluates BOTH rule kinds and returns
  `kind: 'prohibition' | 'standing_consent'` (prohibitions win on conflict),
  but only `matchProhibition()` is consumed today — standing consents are
  never surfaced during review.
- `PolicyController` + `PolicyCrudService` provide full CRUD for
  `publicationProhibition` and standing-consent `publicationConsent`
  (scope `entity`) objects under `/api/policy/prohibitions` and
  `/api/policy/standing-consents`; the `ProhibitionIndex` /
  `StandingConsentIndex` views exist but are deep-link only.
- `publicationProhibition` schema: required `primaryName`, `entityType`
  (enum PERSON/ORGANIZATION/OTHER — this is the rule "category"),
  `matchRules`, `reason`, `active`; optional `legalAuthority`, `severity`,
  `validFrom/validUntil`, etc. It has NO grondslag linkage today.
  `publicationConsent` has `scope` (`document`/`entity`), `matchRules`,
  `legalBasis` (free string) — also no `bases` linkage.
- The `dossier` schema has `bases` (array of `base` slugs) and `checkedOn`
  (dossier-level reviewed timestamp; an event listener regenerates the
  grondslagen summary when it changes). There is NO per-document checked
  state anywhere.
- In-app viewers exist: `src/components/viewers/PdfViewer.vue`,
  `WordViewer.vue`, `TextViewer.vue`, hosted by
  `src/views/fileViewer/FileViewerPage.vue`.
- The `anonymizationLink` schema links `sourceFileId` → `anonymizedFileId`,
  which gives the workbench the right-hand ("anonymized") pane for free.

## Goals / Non-Goals

**Goals:**

- One review workbench per document composing the verified pieces above.
- Human-checked gate per document that anonymize-commit and export respect.
- Pre-application of org rule lists (both policy kinds) during review.
- Grondslag per rule (`bases` on both policy schemas) and grondslag proposal
  surfacing/override in the UI.

**Non-Goals:**

- No visual redaction canvas (draw-a-box image redaction) — separate gap,
  separate change.
- No changes to detection engines or anonymisation execution — OpenRegister
  owns `TextExtractionService`, `EntityRelationMapper`,
  `FileService::anonymizeDocument` (project boundary).
- No changes to the WOO consent/objection lifecycle (consent-management) or
  the prohibition gate semantics (`anonymisation-prohibition-gate`) — the
  workbench consumes both.
- No batch-scale/queueing work — that is the sibling `redaction-at-scale`
  change.

## Decisions

### D1 — The org rule lists ARE the existing policy objects, extended

GH #62/#63 ask for org-level "always anonymise" / "never anonymise" lists.
Verified at HEAD, Filinq already has exactly these semantics as OR objects:
`publicationProhibition` (an entity that must never be published ⇒ always
anonymise) and standing-consent `publicationConsent` with `scope: entity`
(entity may stay visible ⇒ never anonymise). **Decision: do not introduce a
new rule schema.** Extend both schemas with an optional `bases` array
(grondslag slugs referencing `base` objects, same dialect as
`dossier.bases`), keep `entityType` as the rule category, and surface both
lists in the workbench. Rejected alternative: a new `anonymisationRule`
schema — would duplicate `matchRules` matching, split `PolicyMatchService`
into two rule stores, and orphan the existing PolicyController surface.

### D2 — Per-document checked state is a new `documentReview` OR object

The gate needs durable, auditable, per-file state. ADR-001 forbids custom
tables, batch state is ephemeral ICache, and `EntityRelation` rows live in
OR's own store (not schema-extensible from Filinq). **Decision: new
`documentReview` schema** in `lib/Settings/filinq_register.json` keyed by
`fileId` (idempotency key, mirroring `anonymizationLink.sourceFileId`), with
`checkedOn`, `checkedBy`, `entityCountAtCheck`, `manualEntityCount`, and
`note`. Editing detections after checking MUST invalidate the check (the
gate compares current entity-set fingerprint against
`entityCountAtCheck`/`checkedOn` versus the relations' last change) — a
stale "checked" mark is worse than none. Mirrors the proven dossier-level
`checkedOn` pattern one level down.

### D3 — Gate enforcement is server-side, in both commit paths

Blocking only in the UI would be decorative. `POST
/api/anonymization/anonymize/{fileId}` and `POST
/api/anonymization/batch/{batchId}/anonymize` MUST return HTTP 409 when the
target file(s) lack a valid `documentReview` — same enforcement style as the
existing prohibition gate (`AnonymizationService::runProhibitionGate`).
Admin escape hatch: `IAppConfig` key `filinq.review.checked_gate`
(`enforced` default | `advisory`) so pilots that only do spot-checks are not
dead-ended; `advisory` still records the gate result in the response.

### D4 — Standing consents pre-apply as excluded-with-badge, prohibitions stay included-and-locked-hint

Extraction already attaches `prohibitionMatch`. Add the symmetric
`standingConsentMatch` (same `{ruleId, ruleName}` shape, from the same
`PolicyMatchService::match()` call — one matcher, one pass, prohibitions win
on conflict as already implemented). Review semantics: prohibition match ⇒
`included: true` pre-set + warning badge (unchecking requires explicit
confirmation; the existing prohibition gate still catches it at commit);
standing-consent match ⇒ `included: false` pre-set + info badge. Both remain
operator-overridable — the review is the human-in-the-loop mandate, rules are
defaults, except that prohibition overrides are re-checked by the existing
commit gate.

### D5 — Selection-to-entity reuses the OR chunk matcher, not offsets

The preview panes render extracted text/PDF, whose coordinates do not map
1:1 onto OR's chunk offsets across formats. **Decision:** text selection
pre-fills `AddManualEntityModal` (value = selection, type picker, bases
picker pre-filled from `GrondslagProposalService` mapping for the chosen
type) and submits to the existing OR `manual-entities` endpoint, which
matches occurrences chunk-aware server-side. Rejected: client-computed
character offsets — fragile across PDF/Word/Text viewers and duplicates OR
matching logic (ADR-022).

### D6 — Declarative vs imperative (ADR-031)

- `documentReview`, `bases` on the two policy schemas: **declarative** schema
  additions in `lib/Settings/filinq_register.json`; `documentReview` gets
  no lifecycle annotation (it is a single-state marker object, created and
  invalidated, not a workflow).
- Gate enforcement, standing-consent match attachment, selection-to-entity:
  **imperative** — these are lifecycle guards and NLP-adjacent review
  orchestration, valid ADR-031 exceptions (lifecycle guard, NLP pipeline),
  implemented in existing services (`AnonymizationService`,
  `PolicyMatchService`, controllers).
- No new aggregations/calculations/notifications — nothing else to declare.

### D7 — OpenRegister usage (ADR-001) and frontend (ADR-012)

All persisted state is OR objects via the existing AppHost/ObjectService
pattern: `documentReview` (new), `publicationProhibition` /
`publicationConsent` (extended), `base` (read), `anonymizationLink` (read,
for the anonymized pane), `EntityRelation` rows via
`EntityRelationMapper` (read) and OR `manual-entities` (write). No custom
tables. Frontend uses `@conduction/nextcloud-vue` where applicable
(`CnFormDialog` for modals-style forms; the workbench body is a
domain-specific split view hosted like `FileViewerPage`, reusing the
existing `Dd*` components and viewers); NL Design System tokens via NC CSS
variables only (ADR-003). ADR-011 check: matching, BSN handling and slug
logic all already live in OR/`PolicyMatchService` — nothing re-implemented.

## Seed Data

Municipality-flavoured examples shipped as register/test seeds:

```json
// base (existing schema) — grondslag referenced by rules and proposals
{ "name": "Woo art. 5.1.2.e — eerbiediging persoonlijke levenssfeer",
  "description": "Uitzonderingsgrond voor persoonsgegevens in Woo-publicaties (AVG art. 4/6)." }

// publicationProhibition — org-level ALWAYS-anonymise rule, now with bases
{ "primaryName": "Beschermde getuige zaak KG ZA 24/123",
  "entityType": "PERSON",
  "matchRules": [ { "type": "normalized", "value": "Beschermde Getuige A" } ],
  "reason": "gerechtelijk bevel",
  "legalAuthority": "Rechtbank Den Haag KG ZA 24/123",
  "bases": [ "woo-art-5-1-2-e" ],
  "severity": "critical",
  "active": true }

// publicationConsent (scope: entity) — org-level NEVER-anonymise rule
{ "scope": "entity",
  "documentId": "00000000-0000-0000-0000-000000000000",
  "entityType": "ORGANIZATION",
  "entityText": "Gemeente Voorbeeldstad",
  "matchRules": [ { "type": "normalized", "value": "Gemeente Voorbeeldstad" } ],
  "consentStatus": "consent_given",
  "legalBasis": "Bestuursorgaan zelf — geen derde-belanghebbende",
  "bases": [ "woo-art-5-1-2-e" ],
  "active": true }

// documentReview — the per-document checked gate marker
{ "fileId": 424242,
  "checkedOn": "2026-07-16T14:30:00Z",
  "checkedBy": "w.devries",
  "entityCountAtCheck": 37,
  "manualEntityCount": 2,
  "note": "Twee handmatige BSN-passages toegevoegd op p. 4." }
```

Seed task: extend the register seed with two `base` grondslagen, one
prohibition and one standing consent carrying `bases`, so the workbench
demos rule pre-application on a clean install.

## Risks / Trade-offs

- [Gate friction at volume — 55.000 docs/yr cannot each be hand-checked] →
  the `advisory` mode (D3) plus the sibling `redaction-at-scale` sampling-QA
  change; the gate stays per-document truth either way.
- [Check-then-edit staleness] → D2 invalidation: any entity mutation after
  `checkedOn` voids the review; scenario-tested.
- [Preview highlight fidelity in PDF pane] → highlights are best-effort
  (text-layer matches); the entity TABLE remains the source of truth for
  decisions, so a missed highlight never hides an entity from review.
- [Schema additive changes to two shipped policy schemas] → `bases` is
  optional, absent-safe in `PolicyCrudService`/`PolicyMatchService`
  (pre-change rules keep matching identically); union-merge the register
  JSON, never hand-pick (memory: union-merge drops modifications).
- [Two policy kinds pre-applying could confuse operators] → distinct badges
  + a legend in the workbench; prohibitions win on conflict exactly as the
  matcher already implements.

## Migration Plan

1. Register JSON: add `documentReview` schema + `bases` on both policy
   schemas (additive; imported via `ConfigurationService::importFromApp()` on
   boot — no data migration, existing rules simply have no `bases`).
2. Backend: `standingConsentMatch` attachment, `DocumentReviewController`
   (+ routes), gate checks in both anonymize paths (behind
   `filinq.review.checked_gate`).
3. Frontend workbench + wiring; nav entry.
4. Rollback: config `advisory` disables gate blocking; the workbench view is
   additive (existing widget flows keep working untouched).

## Open Questions

- Should the checked gate also block the CSV batch report download
  (`batchReport`) or only anonymize-commit/export of documents? Provisional:
  only commit/export block; the report is an audit artifact and stays
  readable.
- `publicationProhibition.entityType` enum is `PERSON|ORGANIZATION|OTHER`
  while detection types are richer (BSN, IBAN, EMAIL…). Widening the enum is
  a separate, potentially breaking policy change — the workbench maps
  detection types onto the existing three categories for rule matching
  (as `PolicyMatchService` already does) and does NOT widen the enum.
