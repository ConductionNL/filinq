---
kind: code
tracking_issue: https://github.com/ConductionNL/docudesk/issues/234
depends_on: [archiefwet-retention-engine]
---

# Proposal: e-discovery-legal-hold

## Why

When a municipality faces litigation, an audit, or a Woo-besluit appeal, the
records involved MUST NOT be destroyed — even when their Archiefwet
retention says they are due. Destroying evidence under a running procedure
is a liability the `archiefwet-retention-engine` makes *more* acute, not
less: once destruction is automated behind an approval workflow, a case
team needs a first-class way to freeze records that is stronger than
"remember to reject them from the vernietigingslijst each month". The
commercial category (ZyLAB and peers) bundles this into full eDiscovery
review platforms; municipalities that only need hold-and-freeze are forced
to buy the whole suite. Evidence: tracking issue
ConductionNL/docudesk#234; ZyLAB category from the woo-request-workflow
research row (`mg2026-woo-request-workflow`).

**Verified at OpenRegister HEAD (ebedbdd5a)**: OR already enforces
per-object legal holds — `retention.legalHold` (`active`, `reason`,
`placedBy`, `placedDate`, `history[]` preserved on release),
`LegalHoldService::placeHold/releaseHold/hasActiveHold`, REST at
`/api/archival/legal-holds` (+ bulk at `/api/retention/legal-holds/bulk`),
and `DestructionCheckJob` excludes held objects from destruction lists.
What is missing is the *case level*: municipalities hold a matter (a
lawsuit, an audit, a Woo appeal) covering many documents and dossiers, with
a custodian, owner notifications, a release procedure and a searchable
register. Per ADR-022 DocuDesk adds exactly that case layer and delegates
every freeze decision to OR.

## What

A tightly-scoped hold-and-freeze capability — explicitly NOT an eDiscovery
review platform:

1. **Hold case register**: a `legalHoldCase` schema (document register)
   with matter type (litigation | audit | woo-appeal | other), reason,
   explicit scope (document and dossier references — no query-based
   scopes), custodian, and a declarative `active → released` lifecycle.
2. **Freeze enforcement via OR**: activating a case places an OR legal
   hold (reason = case reference) on every in-scope OR object; held
   records are excluded from vernietigingslijsten by OR itself and the
   Archiefbeheer UI shows the exclusion. Releasing a case lifts only the
   holds this case placed, and only on objects no *other* active case
   still covers (overlap-safe by design).
3. **Notifications**: affected document/dossier owners are notified on
   place and on release.
4. **Audit trail**: OR's `legalHold.history` plus the case object's own
   audit trail; released cases are retained (bewaren) — a hold register is
   itself a record.
5. **Searchable register**: a hold-register surface (filter by status,
   type, custodian) plus an active-hold indicator on document/dossier
   detail that also disables destruction-adjacent actions.

## Capabilities

### Added Capabilities

- `e-discovery-legal-hold`: case-level legal holds for
  litigation/audit/Woo-appeal matters — scope + custodian + reason on a
  hold case, destruction freeze enforced through OpenRegister's per-object
  legal holds (overriding the retention engine's disposal), owner
  notifications, overlap-safe release with a full audit trail, and a
  searchable hold register. Hold + freeze + audit only; no review
  platform.

### Modified Capabilities

- `document-register`: hosts the new `legalHoldCase` schema.

## Affected Projects

- [x] Project: `docudesk` — `legalHoldCase` schema + seed,
  `LegalHoldCaseService` (fan-out/release orchestration, overlap
  accounting), hold register UI + detail indicators, notifications, this
  OpenSpec change.
- Consumed: `openregister` — `retention.legalHold`, `LegalHoldService`,
  `/api/archival/legal-holds*`, DestructionCheckJob hold exclusion.
- Depends on: `archiefwet-retention-engine` (the disposal workflow the
  freeze overrides; Archiefbeheer surfaces that display exclusions).

## Out of Scope

- Document review, tagging, redaction-for-production, TAR/analytics,
  custodian interviews, export-for-counsel — the ZyLAB feature set
  (design.md Non-Goals); DocuDesk ships hold + freeze + audit only.
- Query/saved-search-based hold scopes (explicit references only in this
  wave; re-evaluated scopes are a follow-up).
- Freezing Nextcloud file deletion at the storage layer — OR holds freeze
  record destruction; storage-layer locking is an open question
  (design.md).
- The dossier-register canonical spec file (sibling ownership) — dossier
  scope entries are expressed as capability behaviour.

## Success Criteria

- `openspec validate e-discovery-legal-hold --strict` exits 0.
- Activating a case places OR legal holds on every in-scope record; those
  records no longer appear on new vernietigingslijsten while held.
- Two overlapping cases on one record: releasing one keeps the record held
  until the second is released.
- Owners are notified on place and release; the case and OR hold history
  survive release intact; released cases remain queryable.
- The hold register filters by status/type/custodian; document/dossier
  detail shows the active-hold indicator.
- `composer check:strict` and the unit suite pass with zero new violations.
