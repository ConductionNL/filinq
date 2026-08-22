# multi-tenant-hardening Specification (delta)

---
status: proposed
---

## Purpose

Multiple organisations operate on one Filinq instance with shared-nothing
isolation: every Filinq object family (consents, prohibitions/policies,
signing requests, templates, huisstijl, dossiers, batch jobs, correspondence,
generated documents) is scoped to an OpenRegister Organisation. Filinq
consumes OR's organisation model and enforcement (`_rbac`/`_multitenancy`
defaults) instead of bypassing it, gets a per-organisation template library
and branding, and produces organisation-scoped dashboards and reports.
Closes the tenant-isolation dimension of GH #283.

## ADDED Requirements

### Requirement: Tenant identity is the OpenRegister Organisation (REQ-DDMTH-001)

Filinq MUST use OpenRegister's Organisation (identified by its UUID) as the
only tenant identity, consumed via OR's `OrganisationService`
(active-organisation resolution, membership, `isOrganisationAdmin()`), per
the hydra umbrella spec `tenant-fleet-wide-consumption` and ADR-022. Objects
MUST carry their organisation exclusively in OR's object envelope
(`@self.organisation`), stamped by OR from the creator's active organisation.
Filinq MUST NOT add an organisation/tenant property to any schema in
`lib/Settings/filinq_register.json`, MUST NOT write the organisation stamp
itself, and MUST NOT introduce any app-local tenant schema, tenant service,
tenant middleware or tenant lifecycle logic.

#### Scenario: New objects are stamped with the creator's active organisation

- GIVEN a user whose active OR organisation is A
- WHEN the user creates a consent record, a template and a dossier through Filinq
- THEN each stored object's OR envelope carries organisation A
- AND no Filinq code path passed an organisation value into the save call
- @e2e tests/e2e/spec-coverage/multi-tenant.spec.ts

#### Scenario: No app-local tenant construct exists

- GIVEN the Filinq codebase at the end of this change
- WHEN `lib/` and `lib/Settings/filinq_register.json` are inspected
- THEN no schema declares an organisation/tenant property
- AND no `Tenant*`-shaped class, middleware or service exists in Filinq
- @e2e exclude static codebase shape, not a browser surface — enforced by hydra's tenant anti-pattern grep gate and pinned by PHPUnit (tests/unit/Service/MultiTenantGuardrailsTest.php)

### Requirement: OpenRegister enforcement is on by default; bypasses are removed (REQ-DDMTH-002)

Filinq MUST NOT pass `_rbac: false` or `_multitenancy: false` to any
OpenRegister `ObjectService` call on a user-request path. The 29 bypass call
sites verified at HEAD across `ConsentService`, `PolicyCrudService`,
`PolicyMatchService`, `PolicyRetroactiveService`,
`GrondslagenSummaryService`, `GrondslagProposalService` and
`BasesResolverService` MUST be removed so calls use OR's enforcing defaults.
The only permitted exception is a single named system-context seam for
non-user paths (background sweeps, instance-admin aggregates) that (a) is the
only code allowed to relax enforcement, (b) requires a literal reason string,
and (c) logs every use with reason and caller. The seam MUST NOT be reachable
from any request parameter.

#### Scenario: Consent reads and writes re-enter OR enforcement

- GIVEN the consent service after this change
- WHEN any consent list, read, create or update runs for a normal user request
- THEN the underlying OR call is made without `_rbac: false` and without `_multitenancy: false`
- AND OR's RBAC and organisation filtering apply to the result
- @e2e exclude call-argument shape is not browser-observable — pinned by PHPUnit argument-capture tests (tests/unit/Service/ConsentServiceTest.php) and the behavioural isolation scenarios of REQ-DDMTH-003

#### Scenario: The system seam is explicit and logged

- GIVEN the background objection-deadline sweep needs to scan all organisations' pending consents
- WHEN it executes without a user session
- THEN it obtains cross-organisation access only through the named system-context seam with a literal reason
- AND a log entry records the seam use, the reason and the caller
- @e2e exclude background-job seam with no UI surface — covered by PHPUnit (tests/unit/Service/SystemContextSeamTest.php)

### Requirement: Shared-nothing isolation across all Filinq object families (REQ-DDMTH-003)

A user whose active organisation is A MUST NOT be able to read, list, search,
update or delete organisation B's Filinq objects — consents, prohibitions/
policies, signing requests, templates, template versions, huisstijl,
dossiers, batch jobs, correspondence, generated documents — through any
Filinq endpoint, any Filinq view, or the OpenRegister object API. This
MUST hold for the cross-tenant consent forgery documented in GH #283: a
consent update addressed to another organisation's consent record MUST be
rejected and MUST NOT modify the record. Failure mode MUST be fail-closed: if
the active organisation cannot be resolved, Filinq surfaces MUST show no
organisation-scoped objects rather than all objects.

#### Scenario: GH #283 cross-tenant consent forgery fails

- GIVEN a consent record with `consentStatus: pending` in organisation B
- AND an authenticated user whose active organisation is A
- WHEN the user calls `PUT api/consents/{id}` on that record with `{"publicationDecision": "approved", "consentStatus": "granted"}`
- THEN the request is rejected with 403 or 404
- AND the stored record still has `consentStatus: pending` and no `publicationDecision`
- @e2e tests/e2e/workflows/multi-tenant-isolation.spec.ts

#### Scenario: Organisation B's objects are invisible in organisation A

- GIVEN seeded objects (a consent, a template, a dossier, a signing request, a batch job) in organisation B
- AND a user whose active organisation is A
- WHEN the user opens the Consent, Templates, MyDocuments/dossier and SigningRequests views and their pickers
- THEN none of organisation B's objects appear in any listing, search result or picker
- @e2e tests/e2e/workflows/multi-tenant-isolation.spec.ts

#### Scenario: Unresolvable organisation fails closed

- GIVEN a user for whom no active organisation can be resolved
- WHEN the user opens an organisation-scoped Filinq view
- THEN the view shows an empty state
- AND no cross-organisation data is returned
- @e2e exclude requires forcing an inconsistent OR membership state not reproducible in the e2e environment — covered by PHPUnit (tests/unit/Service/ActiveOrganisationFailClosedTest.php)

### Requirement: Per-organisation template library and branding (REQ-DDMTH-004)

Template listings, template pickers and document generation MUST resolve
templates and huisstijl (branding) within the caller's active organisation
only. Document generation (correspondence, PDF header/footer, letter
generation) MUST resolve its huisstijl in this order: the active
organisation's configured default huisstijl (see REQ-DDMTH-008) → the active
organisation's sole huisstijl object when exactly one exists → neutral
built-in styling. Generation MUST NOT use another organisation's huisstijl or
a hard-coded global default. Office-file templates (sibling change
`office-template-authoring`) follow the same scoping because they share the
`template` schema.

#### Scenario: Each organisation generates with its own branding

- GIVEN organisation A with huisstijl "Demostad blauw" and organisation B with huisstijl "Rivierstad groen"
- WHEN a user in each organisation generates a letter from the same seeded template content
- THEN organisation A's output carries "Demostad blauw" logo/colours and organisation B's output carries "Rivierstad groen"
- AND neither organisation's template picker offered the other organisation's templates
- @e2e tests/e2e/workflows/multi-tenant-isolation.spec.ts

### Requirement: Organisation-scoped dashboards and reporting (REQ-DDMTH-005)

Dashboard counters and generated reports MUST be computed over the caller's
active organisation only (batch reports and anonymisation reports alike), and
every generated report MUST state which organisation it covers (name and
UUID). An instance-wide report across organisations MUST be available only
to instance admins, MUST go through the REQ-DDMTH-002 seam, and MUST be
labelled as covering all organisations.

#### Scenario: Dashboard counts only the active organisation

- GIVEN 3 pending consents in organisation A and 5 in organisation B
- WHEN a user with active organisation A opens the Filinq dashboard
- THEN the pending-consent counter shows 3
- @e2e tests/e2e/spec-coverage/multi-tenant.spec.ts

#### Scenario: Batch report is labelled with its organisation

- GIVEN a completed batch anonymisation run in organisation A
- WHEN its report is generated
- THEN the report states organisation A's name and UUID as its scope
- AND contains no documents from any other organisation
- @e2e tests/e2e/spec-coverage/multi-tenant.spec.ts

### Requirement: Legacy objects are backfilled to the default organisation (REQ-DDMTH-006)

A one-time repair step MUST backfill Filinq-owned objects whose OR envelope
carries no organisation to OpenRegister's default organisation
(`getDefaultOrganisationUuid()`), so pre-existing single-tenant data remains
visible after enforcement turns on. The step MUST modify only the envelope
organisation (no data properties), MUST skip immutable audit rows
(`signingAuditEntry`), MUST be idempotent, and MUST log a per-schema count of
backfilled objects. Objects already stamped with an organisation MUST NOT be
changed.

#### Scenario: Null-organisation objects become default-organisation objects

- GIVEN pre-upgrade objects with no organisation stamp across several Filinq schemas
- WHEN the repair step runs twice
- THEN after the first run each such object carries the default organisation and per-schema counts are logged
- AND the second run changes nothing
- AND `signingAuditEntry` rows are untouched either way
- @e2e exclude repair-step data migration with no browser surface — covered by PHPUnit (tests/unit/Migration/OrganisationBackfillTest.php)
