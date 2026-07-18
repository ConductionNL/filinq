# Design: archiefwet-retention-engine

## Context

### Verified at OpenRegister HEAD (ebedbdd5a)

OR ships **three** overlapping archival surfaces. This change picks one and
documents why:

1. **Records-management retention stack** (chosen). Per-object archival
   metadata in `ObjectEntity.retention`:
   `archiefnominatie` (`vernietigen` | `bewaren` | `nog_niet_bepaald`),
   `archiefstatus` (`nog_te_archiveren` | `gearchiveerd` | `vernietigd` |
   `overgebracht`), `classificatie`, `bewaartermijn` (ISO-8601 duration),
   `selectielijstBron`, `archiefactiedatum`, `legalHold{...}`. Populated at
   object creation by `RetentionService::applyArchivalMetadata()` from the
   schema-level `archive` config: `enabled`, `classificatie`,
   `defaultNominatie`, `defaultBewaartermijn`, `bewaartermijnOverride` +
   `overrideReason`, `afleidingswijze` (`afgehandeld` | `termijn` |
   `eigenschap`), `bronEigenschap`, `closureField`, `procestermijn`.
   Selectielijst lookup resolves `classificatie` against a register/schema
   designated in OR's archival settings
   (`selectielijstRegister`/`selectielijstSchema`; entry fields read:
   `categorie`, `omschrijving`, `bewaartermijn`, `archiefnominatie`, `bron`,
   `toelichting`). `recalculateArchiefactiedatum()` re-derives the date when
   the trigger field changes. Disposal: `DestructionCheckJob` collects rows
   past `archiefactiedatum` with nominatie `vernietigen` (excluding active
   legal holds and rows already listed) into a destruction-list object with
   status `in_review`; `ArchivalController` exposes
   `GET/POST /api/archival/destruction-lists[...]/approve|reject`
   (`approve_all` / `approve_partial` with per-object exclusion reasons,
   optional dual approval), `GET /api/archival/certificates`;
   `DestructionService::generateCertificate()` produces a
   `verklaring_van_vernietiging` (approvers, counts by schema and by
   selectielijst category, compliance statement "Conform Archiefwet 1995 en
   Archiefbesluit 1995, artikelen 6-8", `immutable: true`). Updates on
   destroyed/transferred objects are rejected with 409
   `OBJECT_DESTROYED`/`OBJECT_TRANSFERRED`. Settings also carry
   `destructionListRegister/Schema`, `archivalRegister`,
   `destructionCheckInterval`, `notificationLeadDays`,
   `defaultExtensionPeriod`, `destructionBatchSize`.
2. **`x-openregister-archival` annotation** (rejected for records). Shape at
   HEAD: `{retention: {default: "<ISO-8601>", rules: [{condition, retention,
   reason}]}}` — `retention` MUST be an object; a bare string
   (`retention: P7Y`, as the canonical document-register spec currently
   words it) fails `ArchivalAnnotationValidator` with 422 on schema save.
   The hourly `ArchivalRetentionTask` **deletes** rows past
   `_created + retention` with no approval step, no bewaren path, and no
   trigger-event anchor other than creation time. That is precisely what the
   Archiefwet forbids for records (destruction only via an approved
   vernietigingslijst), so the annotation is reserved for operational logs.
3. **TMLO stack** (`ObjectEntity.tmlo`, `TmloService`, `/api/tmlo/*`) — out
   of scope here; consumed by the dependent `tmlo-mdto-metadata` change,
   which also documents the retention-vs-tmlo overlap.

Schema `archive` config is a first-class `Schema` entity column
(`Schema::setArchive()`, json type); `Schema::hydrate()` maps top-level keys
through generic setters, so an `archive` object on a schema entry in
`docudesk_register.json` is expected to survive
`ConfigurationService::importFromApp()`. **Uncertainty, stated**: the
config-import path was traced through `hydrate()` only; task 1.1 pins this
end-to-end with a unit test, and an OR issue is filed if import drops the key.

### Verified at DocuDesk HEAD (this branch, 9cc14407)

- Five registers (`consent`, `signing`, `templates`, `document`, `dossier`);
  none carries retention data. No register slug `archief` exists in any
  fleet app's `lib/Settings/*.json` (collision check done).
- Wave-1 `woo-publicatie-pipeline` defines
  `publicationRecord.destructionDate` + `destructionDateSource` and
  propagates them to OpenCatalogi `retentionExpiresAt` + `retentionNote`
  (RET-003). Wave-1 `zgw-document-bridge` defines the `bridge` register whose
  `externalDocument` carries zaaksysteem metadata; a source-supplied
  vernietigingsdatum arrives through that mapping.
- Canonical `document-register` spec (status `implementing`) declares
  `x-openregister-archival.retention: P7Y` on `correspondence` and `P1Y` on
  `batchCorrespondenceJob` in the pre-HEAD string shape — reconciled by this
  change (see D7).

Stakeholders: gemeente archivists/records managers (vernietigingslijst
review), DIV/informatiebeheer (selectielijst maintenance), Woo teams
(destruction-date propagation, Dordrecht 407973), and the decidesk finding
that incumbent vendors contractually exclude archiving.

## Goals / Non-Goals

**Goals:**

- Selectielijst semantics (waardering bewaren/vernietigen + termijn) on every
  DocuDesk record, computed by OR from a configured trigger event + term.
- A vernietigingslijst workflow an archivist can actually operate, ending in
  a verklaring van vernietiging.
- Transfer state for permanent records; destruction-date propagation into
  the wave-1 publication pipeline and bridge.

**Non-Goals:**

- No retention date math, destruction execution, certificate generation or
  approval logic in DocuDesk (OR owns it; ADR-022/ADR-011).
- No TMLO/MDTO export, no e-depot transport (next change), no legal holds
  (e-discovery-legal-hold).
- No editing of the dossier-register canonical spec (sibling ownership).
- No full selectielijst editor UI; entries are register objects maintained
  via standard object CRUD + OR bulk import.

## Decisions

### D1 — Consume OR's records-management stack; DocuDesk ships no retention logic

All computation (`archiefactiedatum` from afleidingswijze + bewaartermijn,
recalculation on trigger change), eligibility scanning, destruction lists,
approval semantics, execution and certificates stay in OR. DocuDesk's only
backend addition is a thin `RetentionSurfaceService` that (a) reads retention
verdicts for UI display, and (b) implements the propagation rule of D6.
Rejected alternative — a DocuDesk-side scheduler mirroring
`files_retention` — recreates exactly the inadequacy the ecosystem report
documents and duplicates OR's authz/audit path.

### D2 — Selectielijst + workflow storage in a new `archief` register (ADR-001)

Three schemas, no custom tables:

- **`selectielijstEntry`** — exactly the field contract
  `lookupSelectielijstEntry()` reads: `categorie` (unique code, e.g. "3.2"),
  `omschrijving`, `bewaartermijn` (ISO-8601 duration), `archiefnominatie`
  (enum `bewaren` | `vernietigen`), `bron` (e.g. "Selectielijst gemeenten en
  intergemeentelijke organen 2020"), `toelichting`.
- **`destructionList`** — the storage home OR's
  `destructionListRegister/Schema` settings point at (`status`, `createdAt`,
  `objectCount`, `objects[]`, `approvals[]`, `rejections[]` — the shape
  `DestructionService::createDestructionList()` writes).
- **`destructionCertificate`** — home for the generated verklaring van
  vernietiging (`type: verklaring_van_vernietiging`, `destructionDate`,
  `approvers[]`, counts, `complianceStatement`, `immutable: true`).

Rationale: OR deliberately does not ship these homes — its settings expect
the deploying organisation to designate a register. DocuDesk is the records
app of the fleet, so it provides them. The register slug `archief` collides
with nothing at HEAD. Open question: promote selectielijst master data to an
OR-owned shared register once a second app consumes it (unification
feedback rule); recorded on the tracking issue.

Certificates and destruction lists are themselves permanent records
(`bewaren`) — the `destructionList`/`destructionCertificate` schemas carry
`archive` config with `defaultNominatie: bewaren` so they can never appear on
a destruction list.

### D3 — Wiring OR's archival settings is an explicit, admin-visible step

DocuDesk admin settings gain an "Archiefbeheer" section that shows OR's
current archival settings (`GET /api/settings/archival`, an OR endpoint) and
offers a one-click "Koppel archiefregister" action which PUTs
`selectielijstRegister/Schema`, `destructionListRegister/Schema` and
`archivalRegister` pointing at the `archief` register. Rejected alternative —
silently writing OR app config from a DocuDesk repair step — violates the
MDM trust rule (repair steps cannot own another app's config) and hides a
municipal governance decision. The panel warns when settings point elsewhere
(another app may legitimately own them) and never overwrites without the
admin action.

### D4 — Retention categories: schema-level defaults + trigger fields

`archive` config lands on record schemas as data in
`docudesk_register.json`:

| Schema (register) | classificatie | afleidingswijze | trigger |
|---|---|---|---|
| `correspondence` (document) | placeholder "3.2" (zakelijke correspondentie) | `afgehandeld` | `closureField: generatedAt` |
| `generatedDocument` (document) | placeholder | `afgehandeld` | `closureField: generatedAt` |
| `publicationRecord` (document, wave-1) | placeholder | `eigenschap` | `bronEigenschap: publicatiedatum` |

Every classificatie value ships as an **explicit placeholder pending
selectielijst-manager confirmation** (the REQ-DREG-ALINK-01 convention) —
category numbers are a municipal appraisal decision, not a developer guess.
Dossier-side (`dossier` schema archive config keyed on a dossier closure
date) is REQUIRED by this capability (REQ-DDARE-006) but expressed only as
engine behaviour: the dossier-register canonical spec file is owned by a
sibling change, and this change does not edit it. The relationship is:
this engine defines *what* retention stamping means for a dossier; the
dossier capability owner lands the closure-date field the trigger needs.

Per-object category **override** (a specific dossier deviates from its
schema default): OR exposes `extendArchiefactiedatum()` semantics via
retention endpoints, but no public per-object classificatie write was found
at HEAD. **Uncertainty, stated**: the spec requires the override surface;
if OR lacks the endpoint at apply time, an OR issue is filed and the UI
degrades to schema-level categories + date extension — never a DocuDesk-side
write around OR's immutability guards.

### D5 — Disposal workflow UI calls OR's API directly

The Archiefbeheer UI (archivist-facing) is manifest-driven per ADR-012:
vernietigingslijsten index (`CnIndexPage`/`CnDataTable`: status chip, object
count, created, approvers), list detail (objects with title, schema,
register, archiefactiedatum, selectielijst category; per-object exclusion
with mandatory reason for partial approval; reject with mandatory reason),
certificates index. All calls go straight from the frontend to OR's
`/api/archival/*` — DocuDesk adds **no** pass-through controllers
(redundant-controller gate; ADR-022). Access: OR guards these endpoints
(403 for non-archivists per its spec); DocuDesk hides the navigation entry
for users without the archivist capability but never relies on hiding for
security.

### D6 — Destruction-date propagation precedence (single source, logged)

One rule, implemented in `RetentionSurfaceService` and consumed by the
wave-1 publication pipeline:

1. If the record's document originates from the zaaksysteem bridge and the
   staged metadata carries a source-supplied vernietigingsdatum, that date
   wins (the zaaksysteem is the master record — Arnhem 407824 boundary), and
   `destructionDateSource` names the source system.
2. Otherwise `retention.archiefactiedatum` (nominatie `vernietigen`) is
   used, `destructionDateSource` = "Archiefwet 1995, selectielijst categorie
   {classificatie}".
3. No date → `destructionDate` stays empty; publication is not blocked
   (destruction dates are propagation metadata, not a readiness gate).

The wave-1 field names and the RET-003 `retentionNote` behaviour toward
OpenCatalogi are reused verbatim — this change only supplies the value that
wave-1 expected an operator to type.

### D7 — Reconcile the document-register archival annotations

- `correspondence` moves from `x-openregister-archival` auto-delete to
  engine-managed destruction (MODIFIED requirement in the document-register
  delta): same 7-year term, but the term now lives in the selectielijst
  entry, and destruction happens only via an approved vernietigingslijst.
- `batchCorrespondenceJob` (operational log, not a record class) keeps
  annotation-driven auto-delete, but the annotation is rewritten to the
  object shape OR HEAD validates:
  `{"retention": {"default": "P1Y"}}`.
- Risk noted: the canonical requirements being modified belong to
  `docudesk-adopt-or-abstractions` (status implementing); the modification is
  mechanism-only (term and validation posture unchanged) and lands at archive
  time.

### D8 — Frontend per ADR-012 / ADR-003

`CnIndexPage`/`CnDataTable`/`CnFormDialog` throughout; status chips via NC
CSS variables (NL Design tokens, no hardcoded colors); retention block on the
document detail page (categorie, waardering, archiefactiedatum, status) reads
the `retention` block OR already renders on objects. Modals in their own
files under `src/modals/`; NcSelect with `inputLabel`.

## OpenRegister service usage (ADR-001 / config.yaml)

| Operation | OR surface |
|---|---|
| Retention stamping + schedule computation | `RetentionService::applyArchivalMetadata()` / `recalculateArchiefactiedatum()` (implicit via object save; configured by `archive` schema config) |
| Selectielijst lookup | `lookupSelectielijstEntry()` against the `archief` register (designated via archival settings) |
| Eligibility + list generation | `DestructionCheckJob` (interval via `destructionCheckInterval`) |
| Review/approve/reject/certificates | `/api/archival/destruction-lists*`, `/api/archival/certificates` (frontend-direct) |
| Immutability | OR 409 `OBJECT_DESTROYED` / `OBJECT_TRANSFERRED` |
| Audit | OR audit trail + destruction-list/certificate objects |

ADR-011 check: no new validation/formatting/date utilities in DocuDesk;
ISO-8601 durations are passed opaquely to OR.

## Declarative-vs-imperative decision (ADR-031)

- **Declarative**: retention stamping and schedule computation ride the
  `archive` schema config (pure register JSON data); `selectielijstEntry`
  et al. are plain schemas with `hardValidation: true`; log-schema
  auto-delete uses the `x-openregister-archival` object shape.
- **Imperative (justified exceptions)**: (a) destruction-date propagation
  (D6) — a cross-register precedence rule writing another record's fields;
  each propagation appends to the wave-1 `publicationLogEntry` trail;
  (b) the admin "Koppel archiefregister" action — a deliberate cross-app
  settings write behind an explicit admin click.

## Seed Data

Shipped in `docudesk_register.json` `objects[]` (nil-UUID/`seed-*`
placeholders, demo-municipality flavour, category numbers marked TODO):

```json
{
  "@self": {"register": "archief", "schema": "selectielijstEntry", "slug": "selectielijst-2020-correspondentie"},
  "categorie": "TODO-3.2",
  "omschrijving": "Zakelijke correspondentie (placeholder — categorienummer te bevestigen door selectielijstbeheerder)",
  "bewaartermijn": "P7Y",
  "archiefnominatie": "vernietigen",
  "bron": "Selectielijst gemeenten en intergemeentelijke organen 2020",
  "toelichting": "Vernietigen 7 jaar na afhandeling."
}
```

```json
{
  "@self": {"register": "archief", "schema": "selectielijstEntry", "slug": "selectielijst-2020-verordening"},
  "categorie": "TODO-2.1",
  "omschrijving": "Vaststellen van verordeningen (placeholder — categorienummer te bevestigen)",
  "bewaartermijn": "P0D",
  "archiefnominatie": "bewaren",
  "bron": "Selectielijst gemeenten en intergemeentelijke organen 2020",
  "toelichting": "Blijvend bewaren; overbrengen naar archiefbewaarplaats."
}
```

No seed `destructionList`/`destructionCertificate` objects — those are
runtime artifacts; unit tests build fixtures with nil-UUIDs.

## Risks / Trade-offs

- [OR archival settings are instance-global] → only one selectielijst
  register per instance; the admin panel surfaces the current owner and
  requires an explicit click to rewire (D3). Multi-app contention → open
  question / OR unification issue.
- [`archive` key dropped by config import] → pinned by a unit test on the
  imported schema (task 1.1); OR issue if it fails; degradation = admin sets
  archive config through OR's schema UI, engine semantics unchanged.
- [No per-object classificatie override endpoint at OR HEAD] → D4
  degradation path + OR issue; never a DocuDesk-side write.
- [Canonical document-register modification races
  `docudesk-adopt-or-abstractions`] → mechanism-only MODIFIED delta (D7);
  flagged in the PR for the owning change's reviewer.
- [Archivist authorization] → relies on OR's 403 guard on `/api/archival/*`;
  DocuDesk adds no weaker parallel path (no pass-through controllers).
- [Wrong seeded category numbers cause unlawful destruction] → the seeds ship
  as explicit `TODO-*` placeholders during authoring, and REQ-DDARE-009 makes
  replacing them with real selectielijst-manager-approved numbers a hard
  **apply-blocker**: a PHPUnit seed-lint fails while any `TODO-*` categorie
  remains, so the change cannot be marked done or applied to production with
  placeholders. See §Production-enablement below.

## Migration Plan

Additive: new register + schemas + seeds (register version bump, boot
import), `archive` config keys on existing record schemas (additive JSON),
new admin panel section + Archiefbeheer pages, thin service. Rollback:
remove UI/service; register objects and retention metadata remain inert
(OR ignores schemas its settings do not point at). No data migration; the
`correspondence` annotation swap only changes which mechanism deletes future
expired rows.

## Production-enablement (apply-blocker, REQ-DDARE-009)

The seeded `selectielijstEntry.categorie` values ship as `TODO-*`
placeholders for authoring only. Before this change is applied to production
or marked done, every placeholder MUST be replaced with a **real VNG
selectielijst category number confirmed by the responsible
selectielijst-manager** (records-appraisal sign-off), each with its correct
`archiefnominatie` and `bewaartermijn`. This is a hard apply-blocker, not a
follow-up: OpenRegister computes real destruction dates from these numbers
(REQ-DDARE-003), so a placeholder or wrong code drives a wrong or absent
retention schedule (unlawful or missed destruction). A PHPUnit seed-lint
(task 1.4 / 4.1) fails the gate while any `TODO-*` categorie is present. This
mirrors the flow-operations E3 posture (real selectielijst-approved retention
on processing-log schemas is likewise an apply-blocker).

## Open Questions

- Promote selectielijst master data + destruction homes to an OR-owned (or
  hydra-ADR'd shared) register once a second app consumes them?
- Should `notificationLeadDays` pre-destruction notifications surface in
  DocuDesk's notification center (OR emits INotification to archivists
  already)? Deferred until OR's notification is verified live.
