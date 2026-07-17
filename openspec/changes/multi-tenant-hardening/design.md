# Design: multi-tenant-hardening

## Context

Verified at HEAD:

- **OpenRegister owns tenancy.** `lib/Db/Organisation.php` (openregister):
  UUID identity, `users`, `groups`, `owner`, `authorization` (RBAC bag),
  `status` lifecycle (`active`/`suspended`/`deprovisioning`), quotas.
  `lib/Service/OrganisationService.php`: `getUserOrganisations()`,
  `getActiveOrganisation()`, `setActiveOrganisation()`,
  `hasAccessToOrganisation()`, `isOrganisationAdmin()`,
  `getOrganisationForNewEntity()` (stamps new objects with the active
  organisation), `getDefaultOrganisationUuid()`. `ObjectService` read/write
  methods take `bool $_rbac = true`, `bool $_multitenancy = true` — both
  **default ON**.
- **The hydra umbrella spec `tenant-fleet-wide-consumption`** (canonical in
  hydra `openspec/specs/`) forbids app-local tenant schemas/services and
  mandates OR Organisation as the tenant identity; ADR-022 mandates consuming
  OR abstractions rather than duplicating authz. A hydra grep gate flags new
  `Tenant*` classes outside openregister.
- **DocuDesk opts out today**: 29 call sites in 8 `lib/Service/**` files pass
  `_rbac: false` and/or `_multitenancy: false` (inventory below).
  `MetadataService.php:203` carries the precedent comment: "Security (C2):
  `_rbac:false` / `_multitenancy:false` removed — OR's" — i.e. one service was
  already fixed this way.
- **GH #283 (open, CRITICAL)** documents the exploit: all consent endpoints
  accept any authenticated session and force both flags off, so any user in
  any tenant reads and overwrites any consent decision.
- **Settings are instance-global**: `publication_objection_period_days`,
  `docudesk_woo_entity_profiles` (WOO anonymization profile,
  `WooProfileService`) and all toggles live in IAppConfig
  (`admin-settings` spec, `batch-anonymization` REQ on profiles).
- **Templates/huisstijl/dossiers/signing/batches are OR objects** across five
  registers (`consent`, `signing`, `templates`, `document`, `dossier` —
  verified in `lib/Settings/docudesk_register.json`), so organisation
  stamping and read-filtering come from OR's envelope (`@self.organisation`),
  not from schema properties. The Vue frontend reads most collections through
  OR's object API (`useObjectStore`), which applies RBAC + multitenancy
  server-side — the frontend is largely tenant-correct for free once the
  PHP-side bypasses are gone.

### Bypass inventory (verified at HEAD; the work list for D2)

| File | Call sites | Flags forced |
|---|---|---|
| `lib/Service/ConsentService.php` | 2 (L814, L829) | `_rbac:false` |
| `lib/Service/PolicyCrudService.php` | 5×2 | `_rbac:false` + `_multitenancy:false` |
| `lib/Service/PolicyMatchService.php` | 2 | `_rbac:false` |
| `lib/Service/PolicyRetroactiveService.php` | 2 (1 with `_multitenancy:false`) | mixed |
| `lib/Service/GrondslagenSummaryService.php` | 4×2 | `_rbac:false` + `_multitenancy:false` |
| `lib/Service/GrondslagProposalService.php` | 1×2 | `_rbac:false` + `_multitenancy:false` |
| `lib/Service/BasesResolverService.php` | 1 | `_rbac:false` |
| `lib/Service/MetadataService.php` | 0 (removed as C2) | — |

GH #283 additionally names `ConsentUpdateHandler` and `ConsentCrudService`;
their call paths run through the services above and are re-verified at apply
time.

## Goals / Non-Goals

**Goals**

1. Every DocuDesk object family is organisation-scoped by OR enforcement:
   shared-nothing between organisations on one instance.
2. Close the tenant-isolation dimension of GH #283.
3. Per-organisation template library + branding (huisstijl).
4. Per-organisation overrides for the settings that are policy per
   organisation: consent/objection period, WOO anonymization profile, default
   huisstijl.
5. Organisation-scoped dashboards and reports that state their organisation.

**Non-Goals**

- No organisation management UI or lifecycle logic in DocuDesk (OR-owned).
- No cross-organisation sharing model.
- No app-local tenant schema, service, middleware or controller (hydra
  tenant anti-pattern gate would flag it).
- Not the CSRF / field-whitelisting fixes of GH #283 (security wave).
- No per-organisation feature licensing or quota logic (OR quotas).

## Decisions

### D1 — Tenant identity: the OR Organisation UUID, via OR's envelope

An organisation **is** an OR Organisation; its UUID is the only tenant
identifier DocuDesk ever handles. Objects carry it in OR's own envelope
(`@self.organisation`), stamped by OR from the creator's active organisation
(`getOrganisationForNewEntity()`). DocuDesk adds **no** `organisation`
property to any schema and never writes the stamp itself — schema-level
tenant fields are exactly the anti-pattern `tenant-fleet-wide-consumption`
forbids, and a writable field would let a client forge its tenant.

Rejected: an app-level `organisationId` property per schema (forgeable,
duplicates OR, flagged by the hydra gate); NC groups as tenant identity
(groups are membership transport, not identity — OR already maps them).

### D2 — Bypass removal, with one explicit system-context seam

Every `_rbac: false` / `_multitenancy: false` argument in the inventory is
removed so calls fall back to OR's ON defaults. Call sites are not blindly
sed-stripped: each one is classified first —

1. **User-request path** (the vast majority: consent CRUD, policy match on a
   user-initiated anonymisation, grondslag summaries for a visible document):
   flags removed, OR enforces, done.
2. **Legitimately cross-organisation path**: only instance-admin aggregates
   and background sweeps qualify (e.g. `ObjectionDeadlineChecker` iterating
   all organisations' pending consents from a background job with no user
   session). These go through one named seam — a small
   `SystemOperationContext`-style wrapper method that (a) is the only place
   allowed to pass `_multitenancy: false`, (b) requires a literal reason
   string, (c) logs every use with reason + caller. Background jobs that act
   per-object (notifications) keep organisation attribution because the
   object envelope carries it.

The success criterion is grep-shaped on purpose: zero bypass flags outside
the seam. This mirrors the C2 fix already applied to `MetadataService`.

Rejected: keeping silent per-call bypasses "where tests fail" (that is the
defect); a DocuDesk middleware re-implementing org checks (ADR-022 violation
— OR's provider is the authz rule).

### D3 — Per-organisation template library + branding are OR-scoping, not new features

`template`, `templateVersion` (register `templates`) and `huisstijl`
(register `document`) are OR objects; with D1+D2 in force their listings,
pickers and detail reads are organisation-scoped automatically. The change
therefore specs the **behaviour** (org A's templates/huisstijl invisible in
org B; generation resolves branding within the active organisation) and adds
only glue: the generation path (`TemplateRenderer` / correspondence /
`PdfService` header-footer resolution) must resolve the huisstijl through the
active organisation's `organisationSettings.defaultHuisstijl` (D4) with a
fall-through to the organisation's sole huisstijl object, and must not
hard-code a global default. Office-file templates from the sibling
`office-template-authoring` change inherit the same scoping (same schema,
same register) — no coordination needed beyond both being additive.

### D4 — `organisationSettings`: one OR object per organisation, in the `document` register

Per-organisation settings become data, not config (ADR-001: all data via
OpenRegister; IAppConfig cannot scope by organisation). New schema
`organisationSettings` in the existing `document` register (the register that
already carries the instance-shaped objects `huisstijl`,
`anonymizationLink`):

| Property | Type | Meaning |
|---|---|---|
| `name` | string | Display label (defaults to the organisation name) |
| `consentPeriodDays` | integer, 1–365, nullable | WOO objection period override; null = inherit instance default |
| `anonymizationProfile` | object, nullable | WOO entity-category profile override (same shape as `docudesk_woo_entity_profiles`: `anonymize[]` / `keepVisible[]`) |
| `defaultHuisstijl` | string (uuid ref to `huisstijl`), nullable | Branding used by generation when set |
| `notes` | string, nullable | Free-text admin context |

One object per organisation, created lazily on first override; the object's
OR envelope organisation stamp **is** the key (no `organisationUuid`
property — D1). Write access: organisation admins
(`OrganisationService::isOrganisationAdmin()`) and instance admins.

**Resolution order** (single helper, used by every consumer):
organisation `organisationSettings` value → instance IAppConfig default →
code default. `SettingsService` exposes
`getEffectiveSetting(name)` resolving against the caller's active
organisation; `WooProfileService` and the consent-deadline computation read
through it. Instance-global settings (register wiring, enrichment toggles,
signing provider) are deliberately NOT per-organisation — a provider or
schema binding differing per org would fork runtime behaviour in ways the
support model can't carry.

Rejected: JSON-encoded per-org maps inside IAppConfig (unscoped, unauditable,
invisible to OR RBAC); a settings bag on OR's Organisation entity (OR's
entity is fleet-shared master data — app-specific keys don't belong there,
and DocuDesk may not modify OR).

### D5 — Organisation-scoped dashboards and reports

Dashboard counters (`dashboard` capability) and batch/anonymisation reports
(`BatchReportService`) compute within the caller's active organisation —
which is simply what OR returns once D2 lands; the requirement pins it so a
future "optimised" raw query cannot regress it. Every generated report
labels the organisation it covers (name + UUID) to satisfy the Dordrecht
per-organisation sync-report requirement. An instance admin needing an
all-organisations view uses the D2 seam explicitly (logged), and the output
is labelled "all organisations".

### D6 — Legacy rows: backfill to the default organisation

Objects created before OR stamped organisations (or while DocuDesk forced
`_multitenancy:false` writes) may carry a null/foreign organisation. A
one-time repair step backfills **DocuDesk-owned** objects with a null
organisation stamp to OR's default organisation
(`getDefaultOrganisationUuid()`), so they stay visible to the incumbent
(single-tenant) user base instead of vanishing when enforcement turns on.
Constraints: the step touches only the envelope organisation (no data
properties, PUT-semantics not in play), skips immutable audit rows
(`signingAuditEntry` — never mutate sealed audit entries), and logs a count
per schema. Multi-org instances review the log and reassign via OR tooling.

## OpenRegister service usage (ADR-001 / ADR-022)

| Operation | OR abstraction |
|---|---|
| Organisation identity, membership, active org, org-admin check | `OrganisationService` (consume only) |
| Object stamping + read filtering | `ObjectService` with `_rbac`/`_multitenancy` defaults (stop overriding) |
| `organisationSettings` CRUD | `ObjectService::saveObject()/findAll()` |
| Legacy backfill | Repair step writing via `ObjectService` under the D2 seam |
| Isolation audit of blocked access | OR's audit trail (no app-local audit rows) |

## Declarative-vs-imperative decision (ADR-031)

Isolation is **not implemented in DocuDesk at all** — it is OR's declarative
enforcement, un-bypassed. The only imperative additions are the settings
resolution helper (pure function over two lookups), the system-context seam
(a guarded wrapper), and the one-time backfill. No new controller duplicates
an OR read path (the frontend keeps using OR's object API; hydra gate
`redundant-controller` applies). `organisationSettings` write-authorisation
uses the schema `authorization` block plus the org-admin check at the single
service write path.

## Seed Data

Shipped in `docudesk_register.json` `objects[]` (placeholder identifiers
only, nil-UUID pattern so fixtures can never collide with live data):

```json
{
  "@self": {"register": "document", "schema": "organisationSettings", "slug": "demostad-organisation-settings"},
  "name": "Demostad",
  "consentPeriodDays": 42,
  "anonymizationProfile": {
    "anonymize": ["PERSON", "BSN", "PHONE", "EMAIL", "IBAN", "ADDRESS"],
    "keepVisible": ["ORGANIZATION", "LOCATION", "DATE"]
  },
  "defaultHuisstijl": "00000000-0000-0000-0000-000000000001",
  "notes": "Seed: demo organisation override — 6-week objection period."
}
```

Seed import runs on boot in whatever organisation context the import uses;
the seeded object demonstrates the override shape and the settings UI. e2e
fixtures create two throwaway organisations via OR's API (nil-UUID-named,
`seed-org-a` / `seed-org-b` slugs) to exercise isolation both ways.

## Security Considerations

- Closes the tenant-isolation dimension of **GH #283** (CRITICAL): reads and
  writes re-enter OR's RBAC + multitenancy enforcement. The
  `@NoCSRFRequired` removal and update-field whitelisting stay with the
  security wave (GH #282–#304) — this change must not silently absorb or
  block those fixes.
- Fail-closed: an absent/unresolvable active organisation yields **no**
  objects, never all objects. The D2 seam is the only bypass and it cannot be
  reached from a request parameter.
- `organisationSettings` is written only by organisation admins / instance
  admins; a member changing their own org's objection period would weaken a
  WOO legal deadline.
- The backfill (D6) runs under the seam, is idempotent, and never touches
  sealed audit rows.
- No secrets, tokens or new public endpoints. New settings surface reuses
  the existing authenticated settings routes plus org-admin guards.

## Risks / Trade-offs

- **Behavioural surprise on upgrade**: objects invisible after enforcement
  turns on (foreign/null organisation). Mitigated by D6 backfill + a
  release-note check; under-showing is the safe failure mode.
- **Background jobs** (deadline checker, batch queues) run without a user
  session; if a job path accidentally relies on active-organisation
  resolution it returns nothing. Each job is inventoried in apply and either
  iterates organisations explicitly through the seam or acts per-object.
- **Sibling-wave interaction**: the security wave edits the same consent
  services. Coordination is by small PRs + the grep-shaped success criterion
  (flag removal is idempotent and conflict-light).
- **Instance admins lose the implicit God-view** they currently get from the
  bypasses; the labelled all-organisations report path (D5) is the
  replacement.

## Migration Plan

1. Register bump: add `organisationSettings` (additive; existing objects
   unaffected).
2. Ship the settings resolution helper with instance-default fallback —
   before any bypass removal, so behaviour is identical while overrides are
   absent.
3. Remove bypass flags service-by-service (smallest: `BasesResolverService`;
   largest: `PolicyCrudService`), each with its isolation tests.
4. Ship the D6 backfill repair step in the same release as the first flag
   removal.
5. Scope dashboards/reports last (pure read-path).

## Open Questions

- Should the `document` register housing `organisationSettings` be revisited
  if a future change introduces a dedicated `settings`/`organisation`
  register? (Placement is additive and movable at a version bump.)
- Dordrecht's "isolated publication environments" may eventually require
  per-organisation OpenCatalogi publication endpoints; the
  `woo-publicatie-pipeline` handoff would then need an
  `organisationSettings` extension (endpoint ref per org). Deferred until
  that change lands and the requirement is concrete.
