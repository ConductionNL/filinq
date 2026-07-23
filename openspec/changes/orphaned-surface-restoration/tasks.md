# Tasks: orphaned-surface-restoration

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Remove dead code

- [ ] 1.1 Delete `src/router/index.js` and any legacy shell file whose sole importer was it (REQ-DDOSR-001)
  - Confirm `grep -rn "router/index" src/` is empty before and after; the manifest-derived router in `src/main.js` remains the only router; app boots unchanged; legacy files (`Views.vue`/`MainMenu.vue`/`FolderFilesNavigation.vue`/`FileNavigation.vue`) either deleted (sole importer was the router) or explicitly allow-listed (2.1).

## 2. Reachability guard

- [ ] 2.1 Add the reachability guard unit test `tests/unit/reachability.spec.js` (REQ-DDOSR-002)
  - Static assertions (no live NC): every `registry.js` component resolves with a recognised `kind`; every manifest `type:"custom"` page `component` maps to a `kind:"page"` registry entry and vice-versa; every page-level view under `src/views/**/` is reachable or on the `KNOWN_HEADLESS` allow-list with a reason; allow-list seeded with financial-extraction + print-queue ("backend live, no built UI, tracked separately") and `BulkSigningPanel` (`owned-by:bulk-signing-field-builder`).

## 3. Restore Correspondence

- [ ] 3.1 Register `CorrespondenceIndex` and add its manifest page + menu entry (REQ-DDOSR-003)
  - `registry.js` `kind:'page'`; manifest `type:"custom"` page `/correspondence`; menu entry `order:55` (route = page id); no data-path change (existing correspondence store/controller); reachable end-to-end.

## 4. Restore signing authoring + verify (gated)

- [ ] 4.1 Register `SigningRequestForm` + `SignatureVerification` as reachable pages (REQ-DDOSR-004)
  - `registry.js` `kind:'page'`; manifest pages `/signing/new` and `/signing/verify/:fileId`; linked from the Signing index ("New") and request detail ("Verify"); no new top-level menu.
- [ ] 4.2 Gate trust/mutation actions behind the existing security posture (REQ-DDOSR-004)
  - Create-form send/provider/identity controls defer semantics to `signing-trust-rebuild`/`signer-identity-rails`; where unlanded, expose only non-trust composition (draft); verify page renders `SigningController::verify()` verdict verbatim with engine attribution, minting no new trust claim.

## 5. Restore policy reachability (labels deferred)

- [ ] 5.1 Register `ProhibitionIndex`, `StandingConsentIndex` (pages) + their `*FormModal` (modals) and add deep-link manifest pages (REQ-DDOSR-005)
  - Manifest `type:"custom"` pages `/policy/prohibitions` and `/policy/standing-consents`, no menu entry (menu owned by `publication-policy-labels-and-nav`); modals registered `kind:"modal"`; pages resolve and are deep-link reachable; `PolicyController` routes consumed unchanged.

## 6. Quality

- [ ] 6.1 Run the guard + existing JS unit suite green; verify no manifest schema-ref regressions (REQ-DDOSR-002)
  - `npm run test:unit` passes including `reachability.spec.js`; manifest validates against `app-manifest-v2.schema.json`; all restored `type:"custom"` pages resolve at build.
- [ ] 6.2 Playwright e2e `tests/e2e/spec-coverage/orphaned-surface-restoration.spec.ts` proving each restored surface opens (REQ-DDOSR-003, REQ-DDOSR-004, REQ-DDOSR-005)
  - Test through the UI on the dev instance: Correspondence menu opens the page; Signing "New" opens the signer-chain form; a request detail "Verify" opens the verify page; deep-linking `/policy/prohibitions` and `/policy/standing-consents` renders the lists; no 404/blank.
- [ ] 6.3 i18n EN for any new UI strings (menu label, links, verify attribution) — keys in English (REQ-DDOSR-003)
  - New strings added to `l10n/en.json` source; existing view strings unchanged.
- [ ] 6.4 Documentation `docs/features/orphaned-surface-restoration.md` + run `openspec validate orphaned-surface-restoration --strict`
  - Documents the reachability guard, the headless allow-list, and the signing-trust gating dependency; MCP screenshots of the restored surfaces (ADR-010).
