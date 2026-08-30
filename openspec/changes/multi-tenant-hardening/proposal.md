---
kind: code
---

# Proposal: multi-tenant-hardening

## Why

Dutch government Filinq deployments are increasingly **shared instances
serving multiple organisations**: Dordrecht/Drechtsteden 407973 is procured by
a service organisation working for **nine organisations** and requires
isolated publication environments plus **per-organisation sync reports**; De
Connectie 391449 (service organisation for Arnhem, Renkum, Rheden) requires a
**modular per-gemeente** deployment where each gemeente sees only its own
documents, templates and reports. This is a should-have market gap: every
serious competitor in the anonymisation/publication segment offers
tenant-isolated environments, and shared-service constructions
(samenwerkingsverbanden) are the dominant procurement vehicle in the current
tender window.

Filinq today is effectively single-tenant on a multi-tenant foundation.
OpenRegister already ships the whole tenancy substrate — an `Organisation`
entity (UUID identity, users, groups, status lifecycle, quotas, an
`authorization` RBAC bag), an `OrganisationService` (active-organisation
resolution, `hasAccessToOrganisation()`, `isOrganisationAdmin()`,
`getOrganisationForNewEntity()`), and `_rbac` / `_multitenancy` enforcement
that **defaults to ON** in every `ObjectService` read/write (verified at OR
HEAD). The hydra umbrella spec `tenant-fleet-wide-consumption` and ADR-022
mandate that apps consume exactly this model and forbid app-local tenant
constructs.

Filinq actively defeats that substrate: **29 call sites across 8 service
files** force `_rbac: false` / `_multitenancy: false` (verified at HEAD:
`ConsentService`, `PolicyCrudService`, `PolicyMatchService`,
`PolicyRetroactiveService`, `GrondslagenSummaryService`,
`GrondslagProposalService`, `BasesResolverService`; `MetadataService` already
had its bypass removed as security fix C2). The consequence is
**[CRITICAL] GH #283** (verified open): any authenticated user in any tenant
can read and forge consent/objection decisions across all documents and
tenants. Beyond the bypasses, nothing in Filinq is organisation-aware:
settings (consent period, WOO anonymization profile) are instance-global
IAppConfig, the template library and huisstijl (branding) are one shared pool,
and the dashboard aggregates every organisation's counters.

## What Changes

- **Adopt OR organisation scoping as the default for every Filinq object
  family** — consents, prohibitions/policies, signing requests, templates,
  huisstijl, dossiers, batch jobs, correspondence, generated documents. New
  objects are stamped with the creator's active OR organisation by OR itself;
  Filinq stops opting out.
- **Remove the forced `_rbac:false` / `_multitenancy:false` bypasses** (29
  call sites, 8 services). Reads and writes flow through OR with enforcement
  ON. The few legitimately cross-organisation paths (instance-admin
  aggregates, background sweeps) are moved to an explicit, documented,
  logged system-context seam instead of silent per-call bypasses. This
  closes the tenant-isolation dimension of GH #283 (the CSRF and
  field-whitelist dimensions belong to the signing/consent security wave,
  GH #282–#304).
- **Per-organisation template library and branding**: template and huisstijl
  listings, pickers and document generation resolve within the active
  organisation only.
- **Per-organisation admin settings where meaningful**: a new
  `organisationSettings` OR schema (one object per organisation) carrying the
  WOO consent/objection period, the WOO anonymization profile and the default
  huisstijl reference, with a documented resolution order (organisation
  override → instance IAppConfig default → code default). Instance-global
  settings (OpenRegister wiring, feature toggles) stay in IAppConfig.
- **Organisation-scoped dashboards and reporting**: dashboard counters and
  batch/anonymisation reports are computed within the active organisation;
  reports state which organisation they cover (Dordrecht per-org sync
  reports).
- **Shared-nothing default**: no Filinq surface returns another
  organisation's objects. Explicit cross-organisation sharing is out of
  scope.

## Capabilities

### New Capabilities

- `multi-tenant-hardening`: organisation-scoped isolation for all Filinq
  object families on OpenRegister's organisation model — bypass removal,
  per-organisation template library + branding, organisation-scoped
  dashboards/reporting, shared-nothing default.

### Modified Capabilities

- `admin-settings`: adds per-organisation settings overrides
  (`organisationSettings` schema; consent period, anonymization profile,
  default huisstijl) with a defined resolution order and an
  organisation-admin-editable settings surface. Existing instance-global
  settings requirements are unchanged.

## Impact

- `lib/Settings/filinq_register.json`: new `organisationSettings` schema in
  the `document` register, seed data, register version bump.
- 8 services lose their `_rbac:false` / `_multitenancy:false` arguments
  (`ConsentService`, `PolicyCrudService`, `PolicyMatchService`,
  `PolicyRetroactiveService`, `GrondslagenSummaryService`,
  `GrondslagProposalService`, `BasesResolverService`; `MetadataService`
  verified already clean); background/system paths get an explicit
  system-context seam.
- `SettingsService` gains organisation-aware resolution for the consent
  period and anonymization profile; `WooProfileService` reads through it.
- Template/huisstijl listing and generation paths verified organisation-
  scoped (mostly free once OR enforcement is on — the frontend already reads
  via OR's object API, which applies RBAC + multitenancy server-side).
- Dashboard endpoints and batch report generation scoped to the active
  organisation.
- No OpenRegister change: Filinq consumes existing OR abstractions
  (ADR-022); organisation CRUD, membership, switching and lifecycle stay
  OR-owned.
- Evidence: Dordrecht/Drechtsteden 407973 (9 organisations, isolated
  publication environments, per-org sync reports), De Connectie 391449
  (modular per-gemeente), GH #283 (verified open, CRITICAL).

## Out of Scope

- Explicit cross-organisation sharing of any Filinq object (shared-nothing
  is the default and the whole scope; a sharing model would be a separate
  change).
- Organisation management itself — creating/suspending organisations,
  membership, the active-organisation switcher, quotas, lifecycle
  enforcement: all OR-owned (`tenant-fleet-wide-consumption`).
- The CSRF, field-whitelist and `@NoCSRFRequired` dimensions of GH #283 —
  owned by the signing/consent security wave (GH #282–#304).
- Per-organisation NC group provisioning or user administration.

## Success Criteria

- `openspec validate multi-tenant-hardening --strict` exits 0.
- Zero `_rbac: false` / `_multitenancy: false` arguments remain in
  `lib/Service/**` outside the documented system-context seam (grep-clean).
- The GH #283 forgery scenario fails: a user whose active organisation is A
  receives no data and no write access for organisation B's consents via any
  Filinq endpoint.
- Templates, huisstijl, dossiers, signing requests, batches and policies
  created in organisation A are invisible in organisation B across index
  pages, pickers, dashboards and reports.
- An organisation admin can set a per-organisation consent period and
  anonymization profile; a document processed in that organisation uses them;
  another organisation keeps the instance defaults.
- Unit + e2e suites pass; new code ≥75% unit coverage.
