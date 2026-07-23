# Unified Search provider

DocuDesk surfaces its documents in **Nextcloud's global (Unified) search** without
registering a search provider of its own. It consumes OpenRegister's shared
`openregister_objects` provider (ADR-022): a schema opts in with `searchable:true`
and becomes navigable through a manifest `deepLinks[]` entry. Organisation scoping,
RBAC and pagination are inherited from OpenRegister's `searchObjectsPaginated`
(`_rbac` / `_multitenancy`) — DocuDesk owns no `OCP\Search\IProvider`.

## The searchable ⟺ deep-link invariant

A hit is only useful if the user can open it. Therefore every `searchable:true`
schema **must** have a matching `deepLinks[]` route, and no deep-link may name a
schema that is not searchable or does not exist. This change corrects a previously
over-broad opt-in: many schemas (audit entries, sessions, mapping rules, base
grondslagen, GL bookings, correspondence logs, the anonymisation link, …) were
`searchable:true` but had **no reachable detail route**, so they would have
surfaced as dead, unnavigable results.

After this change only two schemas remain searchable, each with a deep-link:

| Register  | Schema           | Deep-link route                       | Label            |
|-----------|------------------|---------------------------------------|------------------|
| templates | `template`       | `/apps/docudesk/templates/{uuid}`     | Template         |
| signing   | `signingRequest` | `/apps/docudesk/signing/{uuid}`       | Signing request  |

## Deferrals

- `dossier` and `document` stay non-searchable until they gain reachable detail
  surfaces — owned by the `dossier-management-ui` and `document-detail-leaf-widgets`
  changes respectively. When those land they add their own `searchable:true` +
  `deepLinks[]` pair.

## Enforcement

`tests/unit/Settings/UnifiedSearchConsistencyTest.php` locks the invariant offline:
only `template` + `signingRequest` are searchable, the searchable set and the
deep-link set are bijective, each `urlTemplate` maps onto a real manifest detail
route, and no bespoke search provider is declared in `appinfo/info.xml`.

Re-import: the register `info.version` (→ 7.5.0) and `appinfo/info.xml` `<version>`
(→ 0.0.38) both advance so OpenRegister's repair step re-imports the corrected flags.
