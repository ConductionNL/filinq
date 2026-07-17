---
kind: code
---

# Proposal: entity-search

## Why

"Find every document containing this BSN / person / IBAN" is the discovery
question DocuDesk cannot answer today, and it is the question both the market
and the law keep asking:

- **INDICA Woo** monetises exactly this: cross-system search to find
  Woo-relevant and privacy-relevant content before a case is even opened
  (research-competitors.md, the discovery category next to ZyLAB/Octobox).
- The repo inventory lists **no cross-document search** as a notable absence:
  DocuDesk detects entities per file and reviews them per batch, but an
  operator who is handed one BSN has no surface that returns the documents
  containing it.
- A **GDPR data-subject request** starts with precisely this lookup (AVG
  Art. 15: which documents mention this person?), and a Woo-verzoek's
  collection step (wave-1 `woo-request-workflow`, REQ-DDWRW-003) starts from
  folders the operator already knows — entity search is the missing way to
  *find* the folders and files they don't.

The data already exists — DocuDesk just has no window onto it. Verified at
OpenRegister HEAD: every detection is persisted in OR's entity catalogue
(`oc_openregister_entities`: type, value, category, organisation) with one
`oc_openregister_entity_relations` row per occurrence (fileId, positions,
confidence, `anonymized`, `bases[]`, `skipAnonymization`), exposed via
`GET /api/entities` (substring search on value, type/category filters,
organisation-scoped fail-closed, relation counts — `GdprEntitiesController`)
and `GET /api/entities/{id}` (entity + relations), with
`RiskLevelService::getRiskLevel(fileId)` for per-file risk. Detected entities
do NOT live in DocuDesk's `anonymizationLink` objects (those only pair source
and anonymized file) nor in file metadata. DocuDesk-side, `anonymizationLink`
resolves a file's anonymisation state in both directions (facetable
`sourceFileId`/`anonymizedFileId`) and `dossier.@self.folder` resolves dossier
membership.

Entity search is, however, itself PII processing: it is a targeted lookup of a
person's identifiers across an organisation's documents. Shipping it without
an access gate and without accountability would create the surveillance tool
the AVG forbids. So the surface is permission-gated and every use is recorded
as a processing-log entry (AVG Art. 30), feeding the platform
verwerkingsregister that the existing `processing-activity-export` capability
already wires DocuDesk into.

## What Changes

- **Entity search surface**: a gated manifest page that searches OR's entity
  catalogue by value (substring), entity type and category, listing matches
  with type, value, category and occurrence count.
- **Entity detail view**: for one entity, every occurrence across documents —
  file name/path, dossier membership (via the dossier folder binding),
  per-occurrence confidence and anonymized flag, the document's anonymisation
  state (via `anonymizationLink`) and OR's per-file risk level.
- **Permission gate**: access restricted to admin-configured Nextcloud groups
  (`docudesk.entity_search.allowed_groups`, empty default = admins only,
  fail-closed); enforced server-side on every entity-search endpoint.
- **Processing log** (AVG Art. 30): every search and every entity-detail view
  writes an append-only `entitySearchLog` OR object (actor, timestamp, action,
  query digest — never the raw searched value — filters, result count, entity
  reference). The activity is declared as a `docudesk-entity-search`
  `x-openregister-processing` annotation so it appears in the platform
  verwerkingsregister alongside DocuDesk's existing four activities.
- **Woo-request discovery handoff**: from an entity detail, matched documents
  can be collected into an open Woo-verzoek via the wave-1
  `woo-request-workflow` collection step (presence-gated; that change is
  referenced, not modified).

## Capabilities

### New Capabilities

- `entity-search`: gated cross-document entity discovery — search OR's
  detected-entity catalogue, per-entity occurrence view with dossier/
  anonymisation/risk context, fail-closed group permission gate, Art. 30
  processing log, and Woo-request collection handoff.

### Modified Capabilities

<!-- none — OR's entity catalogue/API, anonymizationLink, dossier binding,
     processing-activity-export and woo-request-workflow are consumed
     unchanged. The processing-activity-export canonical spec enumerates
     "four" declared activities; amending that count is recorded as a
     deferred follow-up in design.md, not silently edited here. -->

## Impact

- `lib/Settings/docudesk_register.json`: new `entitySearchLog` schema in the
  `document` register with the `docudesk-entity-search`
  `x-openregister-processing` annotation; seed data; register version bump.
- New `lib/Service/EntitySearchService.php` (OR catalogue query with
  organisation scoping, occurrence enrichment, gate check, log writes) + new
  `lib/Controller/EntitySearchController.php` with `api/entity-search/*`
  routes (justified non-pass-through per ADR-022: the endpoints add an authz
  gate, Art. 30 logging and cross-register enrichment that must not be
  client-side).
- `src/manifest.json` + new views: gated Entity search index and entity
  detail with occurrence table and Woo-collection action.
- Admin settings: `docudesk.entity_search.allowed_groups`.
- Consumes (unchanged): OR entity catalogue + `RiskLevelService`
  (presence-gated — DocuDesk loads without OR), `anonymizationLink` lookups,
  `dossier` folder binding, `woo-request-workflow` collection (presence-
  gated), platform Art. 30 register via `processing-activity-export`.
- Evidence: INDICA cross-system discovery category
  (research-competitors.md), repo-inventory notable absence (no
  cross-document search), GDPR data-subject-request use case; OR HEAD
  verification of the entity catalogue above.
