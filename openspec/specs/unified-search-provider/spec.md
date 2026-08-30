# unified-search-provider Specification

## Purpose
TBD - created by archiving change unified-search-provider. Update Purpose after archive.
## Requirements
### Requirement: Only navigable schemas are searchable (REQ-DDUSP-001)

The Filinq register definition MUST flag `searchable: true` only on schemas
whose objects have a reachable detail surface — at HEAD `template` (route
`/templates/:id`) and `signingRequest` (route `/signing/:id`). Schemas without a
reachable detail route MUST be `searchable: false` so OpenRegister's provider
does not return results a user can neither navigate to nor identify; this
includes audit entries, signing sessions, GL-account mapping rules, batch job
records, anonymisation links, generated-document/correspondence records and base
grondslag reference data. `dossier` MUST remain `searchable: false` until a
dossier detail route exists (owned by `dossier-management-ui`).

#### Scenario: Audit and session schemas are not searchable

- GIVEN the register at HEAD flags signing audit entries and sessions `searchable:true` with no detail route
- WHEN this change is applied
- THEN `signingAuditEntry`, `signingSession`, `glAccountMappingRule`, `base` and the other route-less schemas are `searchable:false`, and only `template` and `signingRequest` remain `searchable:true`
- @e2e exclude declarative register flag — asserted by the consistency unit test (tests/unit/Settings/UnifiedSearchConsistencyTest.php)

#### Scenario: A route-less schema produces no search result

- GIVEN a `signingAuditEntry` object exists
- WHEN a user searches for a term contained in it
- THEN it does not appear as a Filinq result in unified search (schema not flagged searchable)
- @e2e exclude provider indexing behaviour is owned by OpenRegister; Filinq supplies only the declarative flag, asserted by the unit test

### Requirement: Deep links resolve result URLs for searchable schemas (REQ-DDUSP-002)

For every schema flagged `searchable: true`, `src/manifest.json` MUST contain a
`deepLinks` entry `{ registerSlug, schemaSlug, urlTemplate, displayName }` (SLUG
form, lowercase) whose `urlTemplate` deep-links into the manifest shell detail
page, so OpenRegister's `DeepLinkRegistryService` can render each result's URL
and display name. At HEAD this MUST cover `templates/template →
/apps/filinq/templates/{uuid}` ("Template") and `signing/signingRequest →
/apps/filinq/signing/{uuid}` ("Signing request"). No `deepLinks` entry MUST
reference a schema that is not `searchable: true`.

#### Scenario: Signing request is findable and deep-links

- GIVEN a signing request whose document subject contains "Kapvergunning" and which the user may read
- WHEN the user types "Kapvergunning" in the Nextcloud global search bar
- THEN the signing request appears under the OpenRegister objects provider labelled "Signing request"
- AND activating it navigates to `/apps/filinq/signing/{uuid}` and renders the request detail
- @e2e exclude blocked upstream — OpenRegister's shared search provider ignores manifest deepLinks and returns the raw `/apps/openregister/api/objects/...` URL (ConductionNL/openregister#2060, live-verified 2026-07-24). Re-enable this scenario as a real e2e once that lands; asserting it today would either fail or have to assert the wrong URL.

#### Scenario: Deep links cover exactly the searchable schemas

- GIVEN the register and manifest at HEAD
- WHEN the searchable schema slugs are compared to `deepLinks[].schemaSlug`
- THEN `template` and `signingRequest` each have a `deepLinks` entry with the correct `urlTemplate`, and no `deepLinks` entry names a non-searchable schema
- @e2e exclude declarative cross-file consistency — asserted by the unit test (tests/unit/Settings/UnifiedSearchConsistencyTest.php)

### Requirement: Filinq consumes OR's provider without registering its own (REQ-DDUSP-003)

Filinq MUST NOT register a Nextcloud `OCP\Search\IProvider`; it MUST
participate in unified search solely through OpenRegister's `openregister_objects`
provider. Result scoping — RBAC read access and organisation (tenant) isolation —
MUST be inherited fail-closed from the OR provider's
`searchObjectsPaginated(_rbac:true, _multitenancy:true)` path; Filinq MUST add
no code that could widen the result set beyond what OR would return.

#### Scenario: No bespoke provider is registered

- GIVEN this change is applied
- WHEN the app's registrations are inspected
- THEN Filinq registers no `OCP\Search\IProvider` and adds no search PHP service — only register flags and manifest deep links
- @e2e exclude static registration assertion — covered by the unit test and code review (no IProvider in Application.php / info.xml)

#### Scenario: RBAC-restricted object is hidden from search

- GIVEN a signing request the searching user has no OR RBAC read access to
- WHEN they search for its subject
- THEN it does not appear (OR provider delegates to `searchObjectsPaginated(_rbac:true)`), and Filinq adds no path that reveals it
- @e2e exclude enforced and tested in OpenRegister's provider security contract; Filinq adds no code path

### Requirement: Version-gated re-import of corrected flags (REQ-DDUSP-004)

This change MUST bump both the register `info.version` in
`lib/Settings/filinq_register.json` and the app `<version>` in
`appinfo/info.xml`, because OpenRegister's register repair step only re-imports
schema definitions when the version advances. Without both bumps the corrected
`searchable` flags never reach OpenRegister.

#### Scenario: Upgrade re-imports the corrected flags

- GIVEN an instance running the previous Filinq version with the register already imported
- WHEN the app upgrades and the OR repair step runs
- THEN the register import re-runs (version gate passes) and the corrected `searchable` flags are applied in OpenRegister
- @e2e exclude repair-step import mechanics are owned and tested by OpenRegister; the paired version bump is asserted by the unit test comparing `info.xml` and register versions advanced together

### Requirement: Register↔manifest consistency is unit-tested offline (REQ-DDUSP-005)

The app MUST ship a PHPUnit test that, without a live Nextcloud instance, loads
`filinq_register.json` and `src/manifest.json` and asserts: every
`searchable:true` schema has exactly one matching `deepLinks` entry whose
`urlTemplate` corresponds to an existing manifest page route; no `deepLinks`
entry references a non-searchable or absent schema; and the register
`info.version` and `info.xml` `<version>` both advanced versus the merge base.

#### Scenario: Inconsistent config fails the test

- GIVEN a schema flagged `searchable:true` with no corresponding `deepLinks` entry
- WHEN the consistency test runs
- THEN it fails naming the schema missing a deep link
- @e2e exclude offline file-consistency assertion — this requirement IS the unit test (tests/unit/Settings/UnifiedSearchConsistencyTest.php)

