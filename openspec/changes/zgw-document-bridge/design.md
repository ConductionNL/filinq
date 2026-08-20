# Design: zgw-document-bridge

## Context

DocuDesk today only processes files that already live in Nextcloud. Verified at
HEAD:

- `lib/Settings/docudesk_register.json` declares five registers (`consent`,
  `signing`, `templates`, `document`, `dossier`); none models external-system
  provenance or staging.
- The `dossier` register's `dossier` schema binds a Nextcloud folder
  (`@self.folder`) to Woo grondslagen (`bases[]`) and a review timestamp
  (`checkedOn`) — the natural pick-up unit for bridge documents.
- All OR access goes through `SettingsService::getObjectService()` returning
  `OCA\OpenRegister\Service\ObjectService`, queried with
  `['@self' => ['register' => ..., 'schema' => ...]]` (pattern:
  `ConsentCrudService::listConsents()`).
- Admin settings render via `lib/Settings/DocuDeskAdmin.php` +
  `src/views/settings/`; the SPA is manifest-driven (`src/manifest.json`,
  pages incl. `MyDocuments`, `Dashboard`).

On the platform side, OpenConnector owns external connectivity (shared brief
boundary): its `synchronization-engine` spec defines the Source →
Synchronization → SynchronizationContract triad, bidirectional
(extern→intern pull, intern→extern push), with file handling and dedup; its
`stuf-adapter` spec covers StUF messaging; its `synced-from-tab` spec renders
per-object sync provenance inside OpenRegister. DocuDesk therefore specifies
**only the target-side contract**: which OR objects OpenConnector reads/writes,
what the status fields mean, and what DocuDesk's UI shows.

Stakeholders: gemeente Woo/anonymisation teams (Dordrecht 407973, Arnhem
407824, Den Helder 306597) whose documents are mastered in Rx.Enterprise,
Djuma, zaaksysteem.nl or a generic ZGW DRC.

## Goals / Non-Goals

**Goals:**

- An OR-backed staging model (`bridge` register) that OpenConnector can sync
  ZGW/StUF documents + metadata into, ≤24h fresh.
- A processing-status state machine that lets DocuDesk process staged copies
  and hand redaction results back for write-back — with the zaaksysteem
  remaining the master record at all times.
- Per-source connection health in admin settings; source provenance visible on
  documents.

**Non-Goals:**

- No ZGW/StUF client code in DocuDesk (no HTTP calls to a DRC, no SOAP) — that
  is OpenConnector's synchronization configuration.
- No zaak management (starting/updating zaken) — only documents and their
  metadata.
- No conflict resolution UI for concurrent edits: staged copies are read-only
  inputs; DocuDesk output is always a *new* derivative.
- No hard `info.xml` dependency on OpenConnector.

## Decisions

### D1 — Staging lives in a new `bridge` OR register (ADR-001)

Two schemas, no custom tables:

- **`bridgeSource`** — one object per configured case-system source:
  `name`, `sourceType` (enum `zgw-drc` | `stuf-zds`), `vendor` (free label:
  "Rx.Enterprise", "Djuma", "zaaksysteem.nl"), `synchronizationId` (UUID of the
  OpenConnector Synchronization serving this source), `syncIntervalMinutes`,
  `lastSyncAt` (date-time), `lastSyncStatus` (enum `success` | `error`),
  `lastSyncError` (string), `active` (boolean).
- **`externalDocument`** — one staging record per synced informatieobject:
  `sourceId` (bridgeSource ref), `externalId` (informatieobject
  identificatie/URL in the source system), `zaakIdentificatie`, `title`,
  `filename`, `format`, `creatiedatum`, `vertrouwelijkheidaanduiding`,
  `versie`, `stagedFileRef` (Nextcloud file id of the staged copy),
  `contentHash` (sha256 of staged content), `syncedAt`, `processingStatus`
  (see D3), `dossierRef` (dossier object UUID, empty until picked up),
  `resultFileRef` (redacted derivative file id), `resultExternalId`
  (identificatie of the informatieobject created by write-back),
  `writeBackError`.

Rationale: OpenConnector's engine already writes OR objects as sync targets and
tracks per-object provenance; a register is queryable, RBAC-guarded, audited
and MCP-exposed for free. Alternative (staging in plain NC folders only) loses
metadata, status and provenance. Register slug `bridge` collides with no
sibling app register at HEAD.

### D2 — Bridge contract: OpenConnector writes objects, DocuDesk writes status

Inbound leg: an admin configures, in OpenConnector, one Synchronization per
source with target = OR register `bridge`, schema `externalDocument` (mapping
ZGW `EnkelvoudigInformatieObject` / StUF-ZDS document fields to the properties
above; the file itself lands in a per-source staging folder via OpenConnector's
file handling, and the mapping sets `stagedFileRef`). OpenConnector updates
`bridgeSource.lastSyncAt/lastSyncStatus/lastSyncError` at the end of each run
(a small post-sync mapping step; the alternative — DocuDesk polling
OpenConnector's log API — couples DocuDesk to OpenConnector's REST surface and
breaks when OpenConnector is absent).

Outbound leg: a second, push-direction Synchronization watches `bridge` /
`externalDocument` objects with `processingStatus = ready_for_writeback` and
performs the DRC/StUF write; on success it sets `processingStatus =
written_back` + `resultExternalId`, on failure `writeback_failed` +
`writeBackError`.

DocuDesk only ever transitions statuses on its side of the fence
(`staged → in_processing → processed → ready_for_writeback`) and never calls
the case system.

**Uncertainty, stated per the brief**: OpenConnector's engine demonstrably
supports intern→extern push and file handling (synchronization-engine spec),
but the exact mapping features needed for "create new informatieobject +
relate to zaak" may need an OpenConnector-side mapping/rule addition. This
change pins the *contract* (the status fields and their semantics); if a gap
surfaces during apply, it is filed as an OpenConnector issue and the outbound
leg degrades to `ready_for_writeback` objects waiting (visible in the admin
panel), never to DocuDesk calling ZGW itself.

### D3 — Processing-status state machine (single source of truth)

```
staged ──> in_processing ──> processed ──> ready_for_writeback ──> written_back
   ^              │                                   │
   └── (reset) ───┘                                   └──> writeback_failed ──> ready_for_writeback (retry)
```

- `staged` — synced, untouched (set by OpenConnector on create/update).
- `in_processing` — attached to a dossier / an anonymisation run started.
- `processed` — a redaction/consent/publish flow produced `resultFileRef`.
- `ready_for_writeback` — operator (or auto-policy) released the result.
- `written_back` / `writeback_failed` — set by OpenConnector's push leg.

Declared as `x-openregister-lifecycle` on the `externalDocument` schema with
canonical `initial: staged` and the transition list above, so the guard is
declarative (ADR-031 default) and invalid transitions are rejected by OR — no
imperative state machine in DocuDesk.

### D4 — Write-back = NEW informatieobject; original untouched (decided)

The redacted derivative goes back to the DRC as a **new
EnkelvoudigInformatieObject** related to the same zaak (via a new
zaakinformatieobject relation), with metadata carrying: a title suffixed
"(geanonimiseerd)", the relation to the original's identificatie, and DocuDesk
processing metadata (processing date, DocuDesk version, anonymisation profile)
in the beschrijving/kenmerken mapping. The original informatieobject is never
updated, re-versioned or deleted.

Rationale: Arnhem 407824 and the master-record principle — a metadata update
or new *version* of the original would make DocuDesk a co-master of the
original record and break the zaaksysteem's audit trail; a sibling
informatieobject preserves the original bit-for-bit and is the pattern
Woo-publication tooling downstream expects (publish the derivative, never the
original). Rejected alternative: metadata-only update on the original
(insufficient — the derivative file itself must reach the DRC so the
zaaksysteem holds the published rendition too).

### D5 — Freshness health is computed, not stored

Health per source = function of `active`, `lastSyncStatus` and
`now − lastSyncAt` against the 24h SLA (Dordrecht ≤24h):

- `fresh` — last successful sync < 24h ago,
- `stale` — active but last successful sync ≥ 24h ago,
- `failing` — `lastSyncStatus = error`,
- `inactive` — `active = false`.

Computed in `BridgeService::getSourceHealth()` at read time (no cron writing a
derived field; storing it would just create a second freshness problem).
Surfaced by `GET api/bridge/sources` and rendered in the admin panel. This is
imperative but is *presentation aggregation*, not persisted state — no ADR-031
exception needed because nothing is written.

### D6 — Dossier pick-up reuses the dossier register unchanged

"Attach to dossier" copies/links the staged file into the dossier's Nextcloud
folder (the unit the existing folder-batch anonymisation and grondslagen
capabilities operate on) and sets `externalDocument.dossierRef` +
`processingStatus = in_processing`. The dossier schema is not modified; the
back-link lives on `externalDocument`, keeping the bridge concern out of the
dossier capability.

### D7 — Frontend per ADR-012

- **Bridge status panel**: a section in DocuDesk admin settings
  (`src/views/settings/`) listing sources with `CnDataTable` (name, vendor,
  type, last sync, health chip) — colors via Nextcloud CSS variables/NL Design
  tokens (ADR-003), no hardcoded colors.
- **Source badge**: MyDocuments rows and the document detail header show a
  badge "Zaaksysteem: {vendor}" when a file id matches an
  `externalDocument.stagedFileRef` (lookup batched per listing). Provenance
  detail (sync timestamps) links to OpenRegister's synced-from tab rather than
  duplicating it.
- New manifest wiring only where needed; no new top-level menu entry (the
  bridge is an admin + provenance concern, not a workspace).

## OpenRegister service usage (ADR-001)

| Operation | OR service |
|---|---|
| List/read sources + staged docs | `ObjectService::searchObjects()` with `@self.register = bridge` |
| Status transitions from DocuDesk | `ObjectService::saveObject()` — carrying ALL fields forward (PUT semantics; partial updates would null ZGW metadata) |
| Lifecycle guard | declarative `x-openregister-lifecycle` on `externalDocument` (canonical `initial:` key) |
| Provenance | OpenConnector SynchronizationContract + OR synced-from tab (consumed, not reimplemented) |
| Audit | OR object audit trail (free with register storage) |

ADR-011 check: no new validation/formatting utilities — hashing uses PHP
`hash('sha256', ...)`; date/UUID handling stays in OR.

## Declarative-vs-imperative decision (ADR-031)

- **Declarative**: `externalDocument` lifecycle (`x-openregister-lifecycle`),
  schema-level archival annotation deferred (originals are mastered
  externally; the staging copy carries no independent retention duty —
  recorded as an open question).
- **Imperative (justified exceptions)**: (a) `BridgeService` health
  computation — read-time aggregation across source telemetry (presentation,
  not persisted); (b) attach-to-dossier — file-system side effect (copy/link
  into the dossier folder) which no `x-openregister-*` dialect expresses;
  (c) the external ZGW/StUF I/O itself — external integration, and it lives in
  OpenConnector, not DocuDesk.

## Seed Data

Shipped in `docudesk_register.json` `objects[]` (nil-UUID/slug style, demo
municipality flavour):

```json
{
  "@self": {"register": "bridge", "schema": "bridgeSource", "slug": "demostad-zaaksysteem"},
  "name": "Demostad zaaksysteem (ZGW DRC)",
  "sourceType": "zgw-drc",
  "vendor": "zaaksysteem.nl",
  "synchronizationId": "00000000-0000-0000-0000-000000000000",
  "syncIntervalMinutes": 60,
  "lastSyncAt": "2026-07-15T06:00:00+00:00",
  "lastSyncStatus": "success",
  "active": true
}
```

```json
{
  "@self": {"register": "bridge", "schema": "externalDocument", "slug": "demostad-zaak-2026-0042-besluit"},
  "sourceId": "demostad-zaaksysteem",
  "externalId": "https://zaken.demostad.example/documenten/api/v1/enkelvoudiginformatieobjecten/00000000-0000-0000-0000-000000000000",
  "zaakIdentificatie": "ZAAK-2026-0042",
  "title": "Besluit omgevingsvergunning Dorpsstraat 1",
  "filename": "besluit-omgevingsvergunning.pdf",
  "format": "application/pdf",
  "creatiedatum": "2026-06-02",
  "vertrouwelijkheidaanduiding": "openbaar",
  "versie": 1,
  "stagedFileRef": "seed-staged-demostad-zaak-2026-0042",
  "contentHash": "0000000000000000000000000000000000000000000000000000000000000000",
  "syncedAt": "2026-07-15T06:00:12+00:00",
  "processingStatus": "staged"
}
```

A second seed `externalDocument` in status `written_back` (with
`resultExternalId`) demonstrates the full round trip for demos/tests.

## Risks / Trade-offs

- [OpenConnector mapping gap for "new informatieobject + zaak relation"] →
  contract pins semantics; outbound objects wait visibly in
  `ready_for_writeback`; OpenConnector issue filed at apply time. Never
  fail-open into DocuDesk-side HTTP.
- [Staged copies duplicate storage] → accepted; staging folder is per-source
  and prunable once `written_back` (retention question logged below).
- [Vendor variance (Rx.Enterprise vs Djuma vs zaaksysteem.nl)] → variance is
  absorbed in OpenConnector source/mapping config; DocuDesk schema uses the
  common ZGW metadata subset only.
- [Slug/property drift between this contract and OpenConnector mappings] →
  the contract table in the spec is the review checklist; register version
  bump + gate 28/30 manifest/register validation on change.
- [Cross-tenant leakage of staged documents] → `bridge` register objects carry
  standard OR RBAC/organisation scoping; the staging folder inherits NC
  folder ACLs; spec requires read scoping in the listing endpoint.

## Migration Plan

Additive only: new register + schemas + seeds (register version bump →
imported by `ConfigurationService::importFromApp()` on boot), new
service/controller/routes, new UI panel. No existing schema changes, no data
migration. Rollback = remove routes/UI; register objects remain inert.

## Open Questions

- Retention of staged copies after `written_back` (delete after N days vs keep
  for audit) — deferred; default keep, admin-configurable later.
- Whether `bridgeSource` should be admin-writable in DocuDesk UI or only via
  OpenConnector setup flow — this change ships read-only health UI; source
  CRUD stays in OpenConnector.
