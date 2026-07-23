# Design: unified-search-provider

## Context

Verified at HEAD (OpenRegister + DocuDesk):

- **OR owns the only provider.** `openregister/lib/Search/ObjectsProvider.php`
  implements `OCP\Search\IProvider` (`openregister_objects`). Its class docblock:
  leaf apps *do not* register their own `IProvider`; they participate by (a)
  flagging schemas `searchable = true` in their register and (b) claiming
  `(register, schema)` deep-link pairs. Non-searchable schemas are opted out via
  a request-scoped cache (`ObjectsProvider` L86, L361–383). Results run through
  `ObjectService::searchObjectsPaginated(_rbac: true, _multitenancy: true)`, so
  RBAC and organisation isolation apply; excerpts come from the rendered
  (redacted) object.
- **Deep links.** `openregister/lib/Service/DeepLinkRegistryService.php`
  registers `(registerSlug, schemaSlug) → urlTemplate` (+ displayName) and
  resolves result URLs. The manifest shape (verified in `procest/src/manifest.json`)
  is an array of `{ registerSlug, schemaSlug, urlTemplate, displayName }`.
- **Procest precedent.** `procest/openspec/specs/case-search-via-or-unified-search`
  flags exactly the schemas with a reachable detail route, adds matching
  `deepLinks`, and version-bumps both the register and `info.xml`. Config-only,
  no PHP.
- **DocuDesk state.** `docudesk_register.json` already has `searchable:true` on
  ~13 schemas but the manifest has **no** `deepLinks`. Reachable detail routes at
  HEAD (from `src/manifest.json`): `/templates/:id` (schema `template`, register
  `templates`) and `/signing/:id` (schema `signingRequest`, register `signing`).
  Dossier and document objects have no detail route (dossier-management-ui /
  document-detail-leaf-widgets are active).

## Goals / Non-Goals

**Goals:**

- Make DocuDesk's navigable objects findable in NC global search, deep-linking
  into the manifest shell, by opting into OR's provider the fleet way.
- Stop the register's over-broad `searchable` flags from producing dead results.
- Guarantee register↔manifest consistency with a unit seam that runs offline.

**Non-Goals:**

- No `OCP\Search\IProvider` in DocuDesk (would duplicate OR's provider — the
  exact ADR-022 failure mode; the brief's "IProvider(s)" framing is superseded by
  this HEAD finding).
- No DocuDesk-side scoping/RBAC code — inherited from the OR provider contract.
- No full-text/PII-entity search (entity-search owns gated PII discovery over
  the entity catalogue; this is object title/subject search over the object
  catalogue).
- No new detail routes — dossier/document deep-links wait on their owning changes.

## Decisions

### D1 — Consume OR's provider; contribute only config

DocuDesk adds `deepLinks` to `src/manifest.json` and corrects `searchable` flags
in `docudesk_register.json`. It registers no provider and writes no search PHP.
Organisation scoping fail-closed is satisfied *because* results are served by
OR's `searchObjectsPaginated(_rbac:true,_multitenancy:true)` — the leaf can only
narrow (via `searchable`), never widen. This is the ADR-022-correct shape and
matches procest.

### D2 — Deep-link only navigable schemas

Add `deepLinks` entries for the two schemas with a reachable detail route today:

| registerSlug | schemaSlug | urlTemplate | displayName |
|---|---|---|---|
| `templates` | `template` | `/apps/docudesk/templates/{uuid}` | Template |
| `signing` | `signingRequest` | `/apps/docudesk/signing/{uuid}` | Signing request |

URLs are history-mode paths (DocuDesk's router is `mode:'history'`, base
`/apps/docudesk`; detail routes `/templates/:id`, `/signing/:id` accept the
object uuid). `registerSlug`/`schemaSlug` use SLUG form (lowercase) per the
manifest contract. Display names are English source strings (register-i18n /
l10n localise the Dutch).

### D3 — Correct the searchable opt-in

`searchable:true` is retained only where a hit is navigable and identifiable;
otherwise set to `searchable:false`:

- **Keep true**: `template`, `signingRequest` (deep-linked, D2).
- **Set false** (no reachable detail route → would be a dead result):
  `generatedDocument`, `correspondence`, `financialExtraction`,
  `glAccountBooking`, `glAccountMappingRule`, `signerRecord`,
  `signingAuditEntry`, `batchCorrespondenceJob`, `anonymizationLink`, and `base`
  (grondslag reference data, never a user search target).
- **Deferred true**: `dossier` — remains `searchable:false` here; flips to
  `true` + gains a deepLink when `dossier-management-ui` ships a detail route.
  The document-object surface likewise waits on `document-detail-leaf-widgets`.

Rationale: the OR provider indexes any `searchable:true` schema; leaving audit
entries / sessions / mapping rules searchable pollutes global search with rows
users cannot open. Procest's requirement 1 ("schemas without a standalone detail
route SHALL NOT be flagged") is the governing discipline.

### D4 — Version-gated re-import

Bump `docudesk_register.json` `info.version` (7.3.0 → next) and
`appinfo/info.xml` `<version>` (0.0.37 → next) together, because OR's register
repair step only re-imports schema definitions when the version advances. Both
must move or the corrected flags never reach OR.

## Data / contract shapes

`deepLinks[]` entry (manifest):

```json
{ "registerSlug": "signing", "schemaSlug": "signingRequest",
  "urlTemplate": "/apps/docudesk/signing/{uuid}", "displayName": "Signing request" }
```

Consumed by `DeepLinkRegistryService::register(registerSlug, schemaSlug,
urlTemplate, displayName)`; `{uuid}` is substituted per result.

## Unit seam (the testable contract)

`tests/unit/Settings/UnifiedSearchConsistencyTest.php` (PHPUnit, no live NC):

- loads `docudesk_register.json` + `src/manifest.json`;
- asserts every schema with `searchable:true` has exactly one `deepLinks` entry
  whose `schemaSlug`/`registerSlug` match and whose `urlTemplate` base path
  corresponds to an existing manifest page `route`;
- asserts no `deepLinks` entry references a `searchable:false`/absent schema;
- asserts `info.version` and `info.xml` `<version>` both advanced vs the merge
  base (guards the re-import gate).

## Security Considerations

- Scoping is inherited fail-closed from OR's provider (`_rbac`,
  `_multitenancy`); DocuDesk adds no path that could widen results.
- Excerpts are derived by OR from the rendered/redacted object — no raw sensitive
  field is exposed by the leaf.
- Setting audit/session/mapping schemas `searchable:false` also removes them from
  a surface where their contents could otherwise be skimmed via search snippets.

## Risks / Trade-offs

- [Corrected flags don't reach OR without a version bump] → D4 bumps both; the
  unit seam asserts it.
- [Deferred dossier/document search leaves two target types unsurfaced now] →
  accepted: deep-linking a routeless schema yields dead results; they land with
  their owning UI changes (dependencies declared).
- [`{uuid}` vs `{id}` mismatch in the detail route] → detail routes resolve OR
  objects by uuid; verified against the existing `/templates/:id` / `/signing/:id`
  data paths; e2e asserts an activated result navigates and renders.

## Migration Plan

Config-only + version bumps. On upgrade the OR repair step re-imports the
register with corrected `searchable` flags; the manifest ships the `deepLinks`.
Rollback = revert the JSON edits and version bumps (search reverts to today's
un-deep-linked, over-broad state).

## Open Questions

- Should `correspondence`/`generatedDocument` become searchable once
  orphaned-surface-restoration gives correspondence a reachable page? Provisional:
  yes for correspondence when it has a detail route; tracked as a follow-up, not
  pre-flagged here (avoid dead results before the route exists).
- OR-side: whether the provider should expose a per-app display-name override
  for register vs schema — OR follow-up, not required here.
