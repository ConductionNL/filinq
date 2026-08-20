# Tasks: unified-search-provider

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Deep-link registry

- [x] 1.1 Add the `deepLinks[]` block to `src/manifest.json` (REQ-DDUSP-002)
  - Two entries (procest `{registerSlug, schemaSlug, urlTemplate, displayName}` shape): `templates/template → /apps/docudesk/templates/{uuid}` ("Template") and `signing/signingRequest → /apps/docudesk/signing/{uuid}` ("Signing request"); slugs lowercase; display names English source strings.

## 2. Searchable opt-in correction

- [x] 2.1 Correct `searchable` flags in `lib/Settings/docudesk_register.json` (REQ-DDUSP-001)
  - Keep `true` on `template` + `signingRequest`; set `false` on schemas with no reachable detail route (`generatedDocument`, `correspondence`, `financialExtraction`, `glAccountBooking`, `glAccountMappingRule`, `signerRecord`, `signingAuditEntry`, `batchCorrespondenceJob`, `anonymizationLink`, `base`); keep `dossier` `false` pending `dossier-management-ui` (design.md D3).

## 3. Version-gated re-import

- [x] 3.1 Bump register `info.version` and `appinfo/info.xml` `<version>` together (REQ-DDUSP-004)
  - `docudesk_register.json` `info.version` 7.3.0 → next with changelog entry; `info.xml` 0.0.37 → next; both advance so the OR repair step re-imports the corrected flags.

## 4. Consume OR's provider (no bespoke provider)

- [x] 4.1 Confirm no `OCP\Search\IProvider` is registered by DocuDesk (REQ-DDUSP-003)
  - No new PHP provider/service; scoping inherited from OR `openregister_objects` (`searchObjectsPaginated` `_rbac`/`_multitenancy`); `info.xml`/`Application.php` register nothing search-related.

## 5. Quality

- [x] 5.1 PHPUnit unit seam `tests/unit/Settings/UnifiedSearchConsistencyTest.php` (REQ-DDUSP-005)
  - Loads register + manifest JSON; asserts every `searchable:true` schema has a matching `deepLinks` entry whose `urlTemplate` maps to an existing manifest page route, no `deepLinks` entry names a non-searchable/absent schema, and both versions advanced vs merge base. Runs offline (no live NC).
- [ ] 5.2 Playwright e2e `tests/e2e/spec-coverage/unified-search-provider.spec.ts` (REQ-DDUSP-002, REQ-DDUSP-003)
  - On the OR-backed dev instance: create a template + a signing request, type a fragment of each into the NC global search bar, assert both appear under the OpenRegister objects provider and activating a result navigates to `/apps/docudesk/templates/{uuid}` / `/apps/docudesk/signing/{uuid}`; assert an RBAC-restricted object does not appear. Test through the UI.
- [x] 5.3 i18n EN + NL for the two `displayName` strings (REQ-DDUSP-002)
  - English source keys; NL via register-i18n / `l10n`.
- [x] 5.4 Documentation `docs/features/unified-search-provider.md` + run `openspec validate unified-search-provider --strict`
  - Documents the consume-OR-provider posture, the deep-link contract, the searchable-opt-in discipline, and the dossier/document deferrals; MCP screenshot of a search result deep-linking into DocuDesk (ADR-010).
