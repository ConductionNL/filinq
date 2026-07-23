---
kind: code
---

# Proposal: unified-search-provider

## Why

A user who types a template name, a signing-request subject, a dossier or a
document title into the Nextcloud global search bar finds nothing from DocuDesk
today. "Surface documents/dossiers/signing-requests via a search provider" is an
explicit unspecced leaf opportunity (R4-ecosystem-leaves.md §B.6 and §E.5:
"Unified Search provider … mirrors procest `case-search-via-or-unified-search`;
currently no search-provider spec"), and cross-object findability is a recurring
market/user signal (R3-user-wishes.md ranks fast filterable search highly; the
whole IDP/e-discovery category — Rossum, Reveal, ZyLAB — sells on "find it
across the corpus", R2-competitors.md §A.8/§A.10).

The correct way to deliver this is **consume, not build** (ADR-022). Verified at
OpenRegister HEAD: OR ships the **single fleet-wide** Nextcloud unified-search
provider — `openregister/lib/Search/ObjectsProvider.php` (30 KB,
`OCP\Search\IProvider` `openregister_objects`), with a `DeepLinkRegistryService`
that resolves result URLs/labels from each app's manifest `deepLinks`. Its
docblock is explicit: *"leaf apps do not register their own OCP\Search\IProvider;
they participate by claiming (register, schema) pairs and flagging schemas
searchable = true. Excerpts are derived exclusively from the rendered [redacted]
object."* Results flow through
`ObjectService::searchObjectsPaginated(_rbac: true, _multitenancy: true)`, so
RBAC and tenant (organisation) scoping apply fail-closed — a leaf never widens
the set, it only opts in. Procest already did exactly this
(`procest/openspec/specs/case-search-via-or-unified-search`): flag schemas
searchable + add `deepLinks` + version-bump; **zero PHP, zero bespoke provider**.

### HEAD-verification surprise (this is a cleanup, not a greenfield)

DocuDesk's register already carries `"searchable": true` on ~13 schemas
(`lib/Settings/docudesk_register.json`: `template` L596, `signingRequest` L1664,
`dossier` L2445, `generatedDocument` L1071, `correspondence` L892,
`financialExtraction` L1190, `glAccountBooking` L1319, `glAccountMappingRule`
L1402, `signerRecord` L1923, `signingAuditEntry` L2082, `base` L2367,
`batchCorrespondenceJob` L2586, `anonymizationLink` L2889) — but the manifest has
**zero `deepLinks`** (`grep -n "deepLink" src/manifest.json` is empty). Per the
OR provider contract and procest's opt-in discipline, a schema flagged
`searchable:true` with **no reachable detail route** produces a *dead result*: a
hit the user can neither navigate to nor identify. DocuDesk today has many such
schemas (audit entries, sessions, mapping rules, base grondslagen) that would
surface as unnavigable noise the moment the provider indexes them. So this change
is as much **correcting an over-broad, un-deep-linked opt-in** as it is adding
the four target surfaces.

## What Changes

- **Deep-link registry** (`src/manifest.json`, new `deepLinks[]`): map each
  genuinely-navigable schema to its detail route + display name, following the
  procest `{ registerSlug, schemaSlug, urlTemplate, displayName }` shape
  (verified in `procest/src/manifest.json` and consumed by OR's
  `DeepLinkRegistryService::register()`):
  - `templates / template → /apps/docudesk/templates/{uuid}` ("Template") —
    reachable detail page exists (`/templates/:id`).
  - `signing / signingRequest → /apps/docudesk/signing/{uuid}` ("Signing
    request") — reachable detail page exists (`/signing/:id`).
- **Searchable opt-in correction** (`lib/Settings/docudesk_register.json`): keep
  `searchable:true` only on schemas that have (or will imminently have) a
  reachable detail surface; set `searchable:false` on schemas with no detail
  route so they never produce dead search results (audit entries, sessions,
  mapping rules, base grondslagen, job records, etc.). `dossier` and the
  document-object schema are deep-linked/kept-searchable only when their detail
  routes land (see dependencies).
- **Version-gated re-import**: bump `docudesk_register.json` `info.version`
  (currently 7.3.0) and `appinfo/info.xml` `<version>` (currently 0.0.37) so the
  OR register repair step re-imports the corrected `searchable` flags on upgrade
  (OR import is version-gated — procest requirement 3).
- **No bespoke provider, no PHP service**: DocuDesk registers no
  `OCP\Search\IProvider`; it consumes OR's `openregister_objects` provider. The
  organisation-scoping-fail-closed requirement is met *by consuming the provider*
  (its `_rbac`/`_multitenancy` contract), not by re-implementing scoping.

## Capabilities

### New Capabilities

- `unified-search-provider`: DocuDesk's navigable business objects (templates and
  signing requests now; dossiers and documents as their detail routes land) are
  findable from the Nextcloud global search bar via OpenRegister's single search
  leaf, deep-linking into DocuDesk's manifest shell pages, with RBAC/organisation
  scoping inherited fail-closed from the OR provider.

### Modified Capabilities

<!-- none. OR's ObjectsProvider + DeepLinkRegistryService are consumed
     unchanged. The register's searchable flags are DocuDesk-owned config
     (this register is DocuDesk's), so correcting them is not an OR spec edit. -->

## Impact

- `src/manifest.json`: new `deepLinks[]` block (template, signingRequest now).
- `lib/Settings/docudesk_register.json`: `searchable` flags corrected to match
  navigability; `info.version` bump + changelog entry.
- `appinfo/info.xml`: `<version>` bump (register re-import gate).
- New `tests/unit/Settings/UnifiedSearchConsistencyTest.php` (PHPUnit, the unit
  seam): loads the two JSON files and asserts every `searchable:true` schema has
  a `deepLinks` entry whose `urlTemplate` points at an existing manifest page
  route, and no `deepLinks` entry names a non-searchable schema. Pure file
  assertion — no live NC.
- Consumes unchanged: OR `openregister_objects` provider, `DeepLinkRegistryService`,
  `ObjectService::searchObjectsPaginated` (RBAC + multitenancy).
- **Declared dependencies (referenced, not modified)**:
  - `dossier-management-ui` — until a dossier detail route exists, `dossier` is
    not deep-linked (kept `searchable:false` here to avoid dead results); when
    that change lands its detail route, a `dossier` deepLink + `searchable:true`
    follow.
  - `document-detail-leaf-widgets` — same for the document-object detail surface.
  - `entity-search` (sibling wave-2 change) — distinct capability (gated PII
    discovery over OR's entity catalogue); this change is title/subject
    object-search over the object catalogue. No overlap.
- Evidence: R4 §B.6, §E.5; R2 §A.8/§A.10 (discovery category); R3 search demand;
  OR `ObjectsProvider.php` docblock + `DeepLinkRegistryService.php`; procest
  `case-search-via-or-unified-search` spec; HEAD greps above.
