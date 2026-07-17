# Design: entity-search

## Context

Verified at HEAD (DocuDesk `development` @ spec/market-gap-wave2 base, and
OpenRegister HEAD):

- **Where detected entities actually live**: in OpenRegister, not in
  DocuDesk. OR's `oc_openregister_entities` table is the per-instance entity
  catalogue (`id`, `uuid`, `type`, `value`, `category`, `organisation`,
  `detected_at`); `oc_openregister_entity_relations` holds one row per
  occurrence (`entityId`, `fileId`, `objectId`, `emailId`, `chunkId`,
  `positionStart/End`, `confidence`, `detectionMethod`, `anonymized`,
  `anonymizedValue`, `bases[]`, `skipAnonymization`). They are populated by
  OR's `TextExtractionService` detection pipeline that DocuDesk's
  extract/anonymise flows already drive.
- **OR already exposes a read API**: `GdprEntitiesController` —
  `GET /api/entities` (`search` = case-insensitive substring on `value`,
  `type`/`category` exact filters, pagination, per-entity `relationCount`),
  `GET /api/entities/{id}` (entity + its relations), `/api/entities/types`,
  `/api/entities/categories`, `/api/entities/stats`. Rows are **organisation
  scoped fail-closed** for non-admins (OR issue #1825 logic: non-admin with
  no accessible organisation sees an empty set; cross-tenant detail reads
  404).
- **Not** in `anonymizationLink` (that schema only pairs `sourceFileId` ↔
  `anonymizedFileId` with run metadata — both facetable, so it answers "is
  this file anonymised / what is its source" in both directions) and **not**
  in NC file metadata. This change therefore specs against OR's catalogue.
- `RiskLevelService::getRiskLevel(int $fileId): string` (OR) returns the
  per-file risk classification derived from detections.
- `dossier.@self.folder` (dossier register) binds a dossier to an NC folder
  node id — dossier membership of a file is resolvable from its parent
  folder.
- DocuDesk's `processing-activity-export` capability (status done) declares
  DocuDesk's processing activities as `x-openregister-processing`
  annotations in `docudesk_register.json` and explicitly forbids a
  DocuDesk-side aggregation/export engine (OR-PA-7 owns the export).
- Wave-1 `woo-request-workflow` (sibling change, referenced not modified)
  collects candidate documents into a request dossier via its collection
  step (REQ-DDWRW-003) and its own dedupe.

## Goals / Non-Goals

**Goals:**

- One gated surface answering "which documents contain this entity", with
  enough context per hit (dossier, anonymisation state, risk) to act on it.
- Make the surface itself AVG-defensible: fail-closed access gate + Art. 30
  processing log, no new PII store.
- Feed discovery results into the Woo-verzoek collection step without
  duplicating collection/dedupe logic.

**Non-Goals:**

- No new detection or indexing engine — the surface reads what OR's
  detection pipeline already persisted. Documents never extracted have no
  entities and are invisible here (stated in the UI empty-state).
- No full-text content search (that is NC/INDICA territory); search is over
  the *detected-entity catalogue* only.
- No changes to OR's entity API, schema or scoping (OR specs are not
  assigned to this change; see Deferred below).
- No entity deletion/merge management UI (OR owns catalogue lifecycle).
- No client-side calls to OR's `/api/entities` from this surface — the gate
  and the log live server-side in DocuDesk.

## Decisions

### D1 — Consume OR's catalogue through a DocuDesk chokepoint (justified non-pass-through)

DocuDesk ships `EntitySearchService` + `EntitySearchController`
(`api/entity-search`, `api/entity-search/{entityUuid}`). Per ADR-022 a bare
OR proxy would be forbidden (redundant-controller gate); these endpoints are
justified because they add three things that must not be client-side:

1. the fail-closed **group permission gate** (D3),
2. the **Art. 30 processing-log write** (D4) — logging in the browser would
   be bypassable,
3. **cross-register enrichment** (D2) joining OR relations with DocuDesk's
   `anonymizationLink` and `dossier` objects.

Mechanism: the service resolves OR's `EntityMapper`, `EntityRelationMapper`
and `RiskLevelService` lazily via the DI container by FQCN string (the
existing `EmlPdfAssemblyService`/`EmlBackend` cross-app pattern), so DocuDesk
stays loadable without OR; without OR the endpoints return an explanatory
503-style empty state. Tenant scoping is preserved by reusing OR's
`OrganisationService` to resolve the caller's accessible organisation UUIDs
and filtering catalogue queries to them (admins unscoped) — mirroring the
`GdprEntitiesController` #1825 rule so DocuDesk can never show a wider set
than OR itself would. **Deferred question**: OR should export a reusable
`EntityQueryService` so this scoping rule lives once (OR spec not assigned
here; recorded as a follow-up, not silently duplicated forever).

### D2 — Occurrence enrichment (entity detail)

For one entity, group its relations by `fileId` (relations with only
`objectId`/`emailId` are listed under "other occurrences" with their kind):

| Facet | Source (verified) |
|---|---|
| File name/path | `IRootFolder->getById(fileId)` (first node the caller may read; unreadable files render as "no access" rows, never leak names) |
| Dossier membership | file's ancestor folder ids matched against `dossier.@self.folder` via OR ObjectService search |
| Anonymisation state | `anonymizationLink` faceted by `sourceFileId` (state + `anonymizedFileId`) or `anonymizedFileId` (the hit *is* a derivative) |
| Risk level | OR `RiskLevelService::getRiskLevel(fileId)` |
| Per-occurrence | `confidence`, `anonymized`, `detectionMethod` from the relation rows |

File-permission note: entity rows are organisation-scoped, but a file hit the
caller cannot read is shown only as an opaque "1 document without access"
aggregate — the gate grants entity search, not file read rights.

### D3 — Permission gate (fail-closed)

`docudesk.entity_search.allowed_groups` (IAppConfig, JSON array of NC group
ids; default `[]`). A user may use the surface iff they are an admin or a
member of a listed group. Enforced in the controller on **every**
entity-search route (`#[NoAdminRequired]` + explicit in-method gate — the
semantic-auth pattern); non-members get 403 with a neutral body. The nav
entry is hidden for non-members (cosmetic; the server gate is authoritative).
Rationale for group-gating over per-request purpose approval: this matches
NC's admin-delegation idiom, is auditable, and the Art. 30 log (D4) provides
the per-use accountability; a workflow-approval gate would be new machinery
disproportionate to a should-have.

### D4 — Processing log without a second PII store (AVG Art. 30)

Every search and every detail view writes one append-only `entitySearchLog`
object (`document` register):

- `action` (`search` | `detail`), `performedBy`, `performedAt`
- search: `queryDigest` = sha256 of the lower-cased trimmed query — **the raw
  searched value is never stored** (a searched BSN in a log would itself be a
  new unprotected PII copy; the digest still lets an auditor test a known
  value against the log), `typeFilter`, `categoryFilter`, `resultCount`
- detail: `entityRef` (OR entity **uuid** — a pointer into the catalogue, not
  a value copy), `occurrenceCount`

The schema carries a `docudesk-entity-search` `x-openregister-processing`
annotation (purpose: targeted PII discovery for Woo/AVG case handling;
rechtsgrond `public-task`; `logReads: true`), so the activity surfaces in the
platform verwerkingsregister exactly like DocuDesk's existing activities.
The write is part of the request path and **fail-closed**: if the log write
fails, the search/detail response is refused (an unlogged PII lookup must not
happen). Log objects are never updated or deleted by the app (append-only by
code; OR audit trail covers the rest).

**Relationship note (touch discipline):** the canonical
`processing-activity-export` spec says DocuDesk declares "four" activities.
This change adds a fifth via the same declared mechanism; the enumeration in
that canonical spec needs a one-word amendment in a follow-up — that file is
not assigned to this change, so the amendment is recorded as a deferred
question instead of edited here.

### D5 — Woo-request collection handoff (presence-gated, reference-only)

On an entity detail, "Collect into Woo-verzoek" appears when the
`woo-request-workflow` capability is present AND at least one `wooRequest`
is in `collecting`. It sends the selected file hits to that change's existing
collection step (which owns copying, hashing and dedupe — REQ-DDWRW-003/004);
this change adds no collection logic of its own. Without the workflow the
action is hidden (not broken). The handoff itself is also written to the
entity-search log (`action: detail` entry carries `collectedInto` request
ref).

## OpenRegister service usage (ADR-001)

| Operation | Service |
|---|---|
| Entity/relation reads | OR `EntityMapper`/`EntityRelationMapper` via lazy container resolution (D1), org-scoped via OR `OrganisationService` |
| Risk level | OR `RiskLevelService::getRiskLevel()` |
| Dossier membership | OR ObjectService search on `dossier` (`@self.folder`) |
| Anonymisation state | OR ObjectService faceted search on `anonymizationLink` |
| Processing log | OR ObjectService `saveObject()` on `entitySearchLog` (no custom tables) |

ADR-011 check: sha256 via PHP `hash()`; BSN/IBAN validation is NOT
re-implemented — search is verbatim-substring over already-detected values
(OR's `BsnFormat` etc. did the validating at detection time).

## Declarative vs imperative

- **Declarative**: the `entitySearchLog` schema + its
  `x-openregister-processing` annotation (the Art. 30 declaration is data);
  register-i18n tags on user-facing string fields; manifest pages.
- **Imperative (justified)**: the permission gate (authz decision on the
  request path), the log write (must be atomic with the read it accounts
  for), and the enrichment joins (cross-register aggregation with NC
  filesystem lookups).

## Seed Data

One demo log object so the admin surface renders non-empty (placeholder,
nil-UUID pattern; no real query values by construction):

```json
{
  "@self": {"register": "document", "schema": "entitySearchLog", "slug": "seed-entity-search-log-001"},
  "action": "search",
  "queryDigest": "0000000000000000000000000000000000000000000000000000000000000000",
  "typeFilter": "PERSON",
  "categoryFilter": "",
  "resultCount": 0,
  "performedBy": "demo-operator",
  "performedAt": "2026-07-01T09:00:00+00:00"
}
```

No seed entities: the catalogue belongs to OR and is populated by real
extraction runs (e2e tests seed it by extracting fixture documents).

## Security Considerations

- Fail-closed gate on every route; empty `allowed_groups` = admins only.
- Organisation scoping mirrors OR's #1825 rule; DocuDesk can never widen it.
- Unreadable files never leak names/paths (opaque no-access aggregate, D2).
- Log stores digests and catalogue pointers, never raw searched values (D4).
- Log write failure blocks the response (no unlogged lookups).
- No new external calls; all processing stays local.

## Risks / Trade-offs

- [Substring search on `value` may be slow on large catalogues] → OR already
  serves this query for its own UI; pagination is mandatory; if it degrades,
  an index on `openregister_entities.value` is an OR-side follow-up.
- [Scoping-rule duplication with OR's controller] → single method,
  unit-pinned against the same fail-closed semantics; OR-side
  `EntityQueryService` extraction recorded as deferred.
- [Gate + log make the surface slower than a raw OR call] → accepted: this
  surface is deliberate, low-frequency case work, not autocomplete.
- [Digest-only logging weakens "what was searched" forensics] → accepted
  trade-off vs creating a PII store; auditors can verify candidate values
  against digests, and detail-view logs carry exact entity refs.

## Migration Plan

Additive: one schema + seed + annotation (register version bump, boot
import), new service/controller/routes/views, one admin setting. No existing
schema changes; no data migration. Rollback = remove routes/UI; log objects
remain readable.

## Open Questions

- OR-side reusable `EntityQueryService` (single home for the #1825 scoping
  rule) — OR follow-up.
- `processing-activity-export` canonical spec's "four activities" enumeration
  → "five" — one-line follow-up amendment when this lands.
- Fuzzy/normalised matching (e.g. BSN with/without dots-spaces) — would need
  an OR-side normalised-value column; out of scope for v1 verbatim-substring
  search.
