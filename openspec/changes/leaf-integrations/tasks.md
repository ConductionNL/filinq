# Tasks — leaf-integrations

## 1. Declare the leaves (config)

- [x] 1.1 Add `configuration.linkedTypes` to `signingRequest` (`["mail","calendar"]`),
  `signerRecord` (`["contacts"]`), `publicationConsent` (`["mail","calendar","deck"]`),
  `correspondence` (`["mail"]`), `generatedDocument` (`["files"]`), and `dossier`
  (`["files","deck"]`) in `lib/Settings/filinq_register.json`. No other schema gets a
  block; no property or `required` list changes.
- [x] 1.2 Add `configuration.mailObjectTemplate` to `publicationConsent` (sender →
  `contactEmail`, subject → `notes`) and `correspondence` (subject-derived
  `caseReference` context), cross-checking every template key against that schema's
  `properties` map (an unknown key fails the whole register import).
- [x] 1.3 Bump `info.version` so `SettingsInitializer` re-imports on existing installs
  (the dialect-without-bump trap recorded in `filinq-mcp-adoption` task 1.5).
- [x] 1.4 Validate: JSON parses with a duplicate-key-rejecting loader; schema count
  unchanged; every schema not named in 1.1/1.2 byte-identical.

## 2. Host the leaves on the record surfaces (frontend)

- [x] 2.1 Ensure the signing-request, consent, correspondence, generated-document, and
  dossier detail surfaces mount the registry's enabled leaf tabs/widgets for their object
  (shared registry tab host per ADR-019) — reusing the host wiring from
  `document-detail-leaf-widgets`, not a parallel tab system.
- [x] 2.2 Graceful degradation: with Mail / Calendar / Contacts / Deck absent, the
  corresponding leaf is hidden and each surface renders without error.
- [x] 2.3 nl + en translations for any new tab labels (ADR-007 / ADR-025).

## 3. Verify

- [ ] 3.1 (not run: needs the live instance with NC Mail) Import the register into OpenRegister on the dev instance: zero
  configuration-validation errors; NC Mail's sidebar lists the three link-target schemas
  and offers create-from-email for consent and correspondence.
- [x] 3.2 Create-from-email produces records in their initial state only — assert no
  `publicationDecision`, no `consentStatus` advance, no generation, no send.
- [x] 3.3 Assert the MCP surface is unchanged by this change: no tool exposes
  `signerRecord` or `publicationConsent` after the leaves are enabled (extend the MCP
  surface probe assertion rather than a one-off grep).
- [x] 3.4 Component/integration tests: leaves render when their apps are enabled, hidden
  when absent; no second write path for `deadline` / `objectionDeadline` / `fileId`.
- [x] 3.5 CHANGELOG entry.

## What this change did NOT do, and why

- **2.1 and 2.2 stay open.** Hosting OTHER apps' leaves on Filinq's detail
  surfaces needs the shared registry tab host, and Filinq consumes
  `@conduction/nextcloud-vue` 2.39, which does not ship it. That is the same
  cross-repo deferral `document-detail-leaf-widgets` D-1.1 already records, and
  authoring a parallel tab system here is exactly what the task forbids. The
  declarations land now, so the surfaces light up the day the host arrives.
- **Built instead, because the leaves were invisible either way:** Filinq
  contributed no leaf at all and shipped no `leaves` webpack entry, so the
  documents it holds could not be seen from any other app's page. The
  `filinq-documents` leaf now has both halves and its own bundle.
- **3.1 waits on an instance.** The register import is asserted against the LIVE
  schema rows in `tests/e2e/workflows/leaf-integrations.spec.ts`, which the
  nightly runs; it was not run by hand here.
- **3.4 is half done.** The parity, bundle and degradation assertions exist; a
  component test of a leaf rendering has no runner in this checkout, where
  `node_modules` is absent.

## Built on build/openspecs-3 (10 Oct 2026)

- **2.1:** nc-vue 2.65 ships `CnIntegrationTab` and `CnLeafMountHost`, so the
  deferral above is over. `src/components/DocumentLeafTabs.vue` now takes its
  leaf ids as a prop and renders a mount-mode leaf through `CnLeafMountHost`.
  `leafIdsForSchema()` in `src/services/documentLeafTabs.js` reads each
  schema's `linkedTypes` (a copy of the register, pinned by
  `tests/vitest/documentLeafTabs.spec.js`) and maps the legacy `mail` id to the
  registry's `email`. Mounted on `SigningRequestDetail`, `DossierDetail` and
  `ConsentDetail`. Correspondence and generated documents have no detail view
  of their own, so there is no surface to mount on; their `linkedTypes` still
  serve NC Mail's sidebar.
- **2.2:** a leaf whose app is absent (`available: false`) is left out, the
  others render, and with none left the section is not drawn
  (`tests/vitest/documentLeafTabs.spec.js`, `src/components/DocumentLeafTabs.spec.js`).
- **3.4:** `src/components/DocumentLeafTabs.spec.js` mounts the real component
  in jsdom against a registry snapshot. No second write path: the host only
  reads the registry.
- Boards: no Fq board draws a leaf section on these detail pages, so the
  section sits at the bottom of each page, as on the document sidebar.

## Acceptance criteria

- Six schemas carry the declared `linkedTypes` (two also `mailObjectTemplate`); no other
  schema is touched; the register imports cleanly on an existing install.
- Mail linkage, calendar deadlines, signer contacts, pipeline files, and follow-up deck
  cards all render through the registry on their record surfaces, and every leaf degrades
  gracefully.
- The agent-facing MCP surface is byte-for-byte unchanged.
