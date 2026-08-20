# Design: e-discovery-legal-hold

## Context

### Verified at OpenRegister HEAD (ebedbdd5a)

- `LegalHoldService::placeHold(ObjectEntity, string $reason)` writes
  `retention.legalHold = {active: true, reason, placedBy, placedDate,
  history[]}`; `releaseHold()` moves the current hold into `history[]`
  (with `releasedBy`, `releasedDate`, `releaseReason`) and sets
  `active: false`; `hasActiveHold()` reads it back;
  `bulkPlaceHold(schemaId, registerId, reason)` schedules a
  `BulkLegalHoldJob` (whole-schema scope — too coarse for case scopes, not
  used here).
- REST: `POST/GET/DELETE /api/archival/legal-holds*` and
  `POST /api/retention/legal-holds` (+ `/bulk`).
- `DestructionCheckJob` excludes objects with `retention.legalHold.active
  = true` from destruction lists (archival-destruction-workflow REQ-001),
  so the freeze is enforced at the platform layer, not by DocuDesk UI
  discipline.

**Limitation, stated**: OR's hold is a single slot per object (`active` +
one `reason`). Two municipal matters can cover the same record; releasing
matter A must not unfreeze a record matter B still needs. OR has no
multi-hold model — the case layer therefore owns the overlap accounting
(D3) and an OR issue proposing native multi-hold is filed at apply time.

### DocuDesk side

`archiefwet-retention-engine` (dependency) supplies the disposal workflow
being overridden and the Archiefbeheer surfaces where exclusions become
visible. Records live as OR objects across the `document` register (and
dossiers in the sibling-owned `dossier` register — scope entries reference
them without touching that canonical spec). ZyLAB comparison (Non-Goals):
the commercial category bundles hold with review/production tooling;
municipalities needing only defensible preservation currently buy the
suite — the tight scope IS the product decision.

Stakeholders: juridische zaken (litigation), concerncontrol/auditors,
Woo coordinators (bezwaar/beroep on Woo besluiten), archivists (whose
vernietigingslijsten shrink while holds are active), record owners
(notified).

## Goals / Non-Goals

**Goals:**

- A case-level hold object with matter type, reason, explicit scope,
  custodian and a declarative lifecycle.
- Platform-enforced destruction freeze (OR legal holds, primary) plus a
  Nextcloud file-lock (`files_lock`) backstop against raw file deletion,
  overlap-safe release, owner notifications, full audit trail, searchable
  register.

**Non-Goals (the ZyLAB boundary):**

- No document review UI, tagging, TAR/analytics, production/export sets,
  custodian questionnaires or matter workflow beyond active→released.
- No query-based or self-updating scopes this wave.

## Decisions

### D1 — `legalHoldCase` schema in the document register (ADR-001)

One schema, no custom tables: `name`, `holdType` (enum `litigation` |
`audit` | `woo-appeal` | `other`), `reason` (mandatory), `caseReference`
(external matter/zaak reference), `custodian` (NC user id responsible for
the hold), `scopeDocuments[]` (record object UUIDs), `scopeDossiers[]`
(dossier object UUIDs), `status` (lifecycle, D2), `placedBy`, `placedAt`,
`releasedBy`, `releasedAt`, `releaseReason`, `notifiedOwners[]` (audit of
who was notified). Scope is explicit references only: a query scope
re-evaluates over time and silently grows/shrinks a legal obligation —
rejected for this wave.

The schema carries `archive` config `defaultNominatie: bewaren` (the hold
register is itself a record) and MUST NOT carry `x-openregister-archival`.

### D2 — Declarative case lifecycle

```
active ──> released
```

Declared as `x-openregister-lifecycle` with canonical `initial: active`
and the single transition `active → released`; released is terminal (a new
matter = a new case, preserving one immutable trail per matter). Release
requires `releaseReason` (guarded in the release flow; the lifecycle
prevents any other transition path).

### D3 — Freeze fan-out and overlap-safe release (the one imperative core)

`LegalHoldCaseService`:

- **Activate** (on create): for each in-scope object (documents + dossier
  objects), place an OR legal hold with
  `reason = "docudesk-hold-case:{caseUuid}"` via OR's legal-hold API. If
  the object is ALREADY held: record the pre-existing hold in the case
  audit and do not overwrite it (single-slot limitation) — the case still
  lists the object; the overlap ledger (below) covers it. Partial failures
  are retried and surfaced on the case detail (per-object fan-out status);
  a case is `active` only when every in-scope object is verifiably held.
- **Overlap ledger**: coverage is derived, never duplicated state — an
  object is covered by every `active` case whose scope contains it
  (indexed query on `scopeDocuments`/`scopeDossiers`).
- **Release**: for each in-scope object, release the OR hold **only if**
  no other `active` case covers it; otherwise re-stamp the OR hold reason
  to the surviving case's reference. OR's `releaseHold()` preserves the
  full history slot; the case object records `releasedBy/At/Reason`.

Rejected alternative — DocuDesk-side freeze checks in the disposal UI:
that would be UI discipline where OR already enforces at the
DestructionCheckJob layer; the case layer must never become the
enforcement point (fail-closed stays platform-side).

### D4 — Notifications

On activate and on release, notify the owner of each in-scope
document/dossier (NC notification manager; absolute icon URL per the NC
gotcha), plus the custodian. Notified users are recorded in
`notifiedOwners[]`. Rationale: a hold changes what an owner may do with
their records; silent freezes get violated innocently.

### D5 — Register + detail surfaces per ADR-012 / ADR-003

- **Hold register**: manifest page with `CnIndexPage`/`CnDataTable` —
  columns name, type, status chip, custodian, scope counts, placedAt;
  filters status/type/custodian; case detail with scope list, per-object
  fan-out status, audit block, release action (`CnFormDialog`, mandatory
  reason). Modals in `src/modals/`; NcSelect with `inputLabel`; NC CSS
  variables only.
- **Detail indicator**: document/dossier detail shows an active-hold badge
  (naming the case(s)) and disables destruction-adjacent actions; the
  Archiefbeheer vernietigingslijst detail (retention engine) already shows
  OR's hold exclusions — this change adds the case name resolution there.
- Backend: `LegalHoldCaseController` for case CRUD-orchestration
  (create/release trigger fan-out — genuinely more than an OR
  pass-through), every method with explicit auth attribute + guard; case
  read access via OR RBAC; hold placement authority restricted to a
  designated group (records-manager/juridisch), verified server-side.

### D6 — File-level freeze backstop via `files_lock` (REQ-DDEDL-007)

The OR record hold (D3) freezes *record destruction* — it does not stop a
user with Files/WebDAV/sync access from deleting the underlying Nextcloud
file. That gap is a real evidence-spoliation risk under a running matter.
**Decision:** alongside every OR hold, place an app-scoped lock on the
document's file node via OCP `\OCP\Files\Lock\ILockManager`
(`ILock::TYPE_APP`, owner = the DocuDesk app id — an app lock, not a user
lock, so it is not tied to the placing user's session and blocks all raw file
mutation). The lock provider is the `files_lock` app; DocuDesk consumes the
OCP interface, never `files_lock` internals.

- **Primary vs backstop:** the OR record freeze stays authoritative for
  Archiefwet destruction; the file lock is strictly a storage-layer backstop.
  Neither replaces the other.
- **Overlap-safe:** the file lock follows the SAME overlap ledger as the OR
  hold (D3) — a file is unlocked only when no other active case covers it.
- **Honest degradation:** probe `ILockManager::isLockProviderAvailable()`;
  when false (`files_lock` not installed) the case fan-out status records the
  backstop as unavailable and the UI never claims file-level protection — the
  OR record freeze still applies.
- **Imperative:** lock/unlock fan-out joins the existing per-object fan-out
  status (visible, retried, audited), an ADR-031-justified imperative side
  effect exactly like the OR-hold fan-out.

Rejected: a user-scoped lock (`TYPE_USER`) — tied to the placing custodian
and liftable by them, defeating the freeze; a WebDAV-token lock — transient.

## OpenRegister service usage (ADR-001 / config.yaml)

| Operation | OR surface |
|---|---|
| Case CRUD + search | `ObjectService::saveObject()`/`searchObjects()` on `document`/`legalHoldCase` (PUT-semantic: carry ALL fields forward) |
| Case lifecycle | `x-openregister-lifecycle` (canonical `initial: active`) |
| Freeze / unfreeze | OR legal-hold API (`/api/archival/legal-holds*`; service-level `LegalHoldService` semantics) |
| Freeze enforcement | OR `DestructionCheckJob` hold exclusion (consumed, not reimplemented) |
| Audit | OR object audit trail + `retention.legalHold.history` |

ADR-011 check: no new validation/formatting utilities; UUID handling stays
in OR.

## Declarative-vs-imperative decision (ADR-031)

- **Declarative**: case schema + lifecycle; bewaren archive config; freeze
  enforcement itself (OR's job-level exclusion).
- **Imperative (justified exceptions)**: fan-out/release orchestration with
  overlap accounting (cross-object, cross-register side effects with a
  platform single-slot limitation — no `x-openregister-*` dialect expresses
  it); the `files_lock` file-lock backstop fan-out (D6, OCP `ILockManager`
  side effect); owner notifications (external side effect). All append to the
  case audit so the imperative path stays declaratively readable.

## Seed Data

Shipped in `docudesk_register.json` `objects[]` (nil-UUID placeholders,
demo-municipality flavour):

```json
{
  "@self": {"register": "document", "schema": "legalHoldCase", "slug": "demostad-woo-bezwaar-2026-004"},
  "name": "Woo-bezwaar 2026-004 (demo)",
  "holdType": "woo-appeal",
  "reason": "Bezwaarprocedure tegen Woo-besluit 2026-004; betrokken stukken bevriezen tot afronding.",
  "caseReference": "BZW-2026-004",
  "custodian": "seed-user-juridisch",
  "scopeDocuments": ["00000000-0000-0000-0000-000000000001"],
  "scopeDossiers": [],
  "status": "active",
  "placedBy": "seed-user-juridisch",
  "placedAt": "2026-07-01T09:00:00+00:00",
  "notifiedOwners": []
}
```

## Risks / Trade-offs

- [OR single-slot hold vs multiple matters] → overlap ledger (D3) +
  re-stamp on release; OR issue for native multi-hold filed at apply time;
  the ledger is derived state, so a crash can never leave a record
  unfrozen while any active case covers it (release is the guarded path).
- [Fan-out partial failure] → per-object fan-out status on the case;
  retries; case not treated as fully protective until every object holds —
  visible, never silent.
- [NC file deletion bypasses record-level holds] → addressed (REQ-DDEDL-007,
  decision D6): an app-scoped `files_lock` file lock is placed alongside the
  OR record hold and lifted overlap-safe on release, blocking raw file
  deletion/rename/overwrite at the storage layer. When `files_lock` is not
  installed the backstop is recorded as unavailable and the OR record freeze
  still applies — the case never claims file-level protection it lacks.
- [Hold placement authority] → server-side group check on the controller
  (semantic-auth gate); UI hiding is never the control.
- [Scope mistakes (missing document)] → explicit-reference scope is
  auditable but not self-updating; the case detail supports adding scope
  entries to an active case (fan-out runs for additions); query scopes
  deferred.

## Migration Plan

Additive: one schema + seed (register version bump, boot import), one
service + controller + routes, register UI + indicators, notifications.
Rollback: remove routes/UI/service; case objects remain readable; any OR
holds still active can be released through OR's own API. No data
migration.

## Open Questions

- Native multi-hold support in OR (issue to file) — adopt and delete the
  overlap ledger when available.
- Should Woo-request cases (woo-request-workflow, wave 1) auto-propose a
  hold on their document set during bezwaar? Natural follow-up once both
  capabilities are live.
