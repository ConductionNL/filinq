# Tasks: work-document-notes

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 7. -->

## 1. The shared component

- [ ] 1.1 File the nextcloud-vue half (a file as `CnNotesCard` source over `/remote.php/dav/comments/files/{fileId}`) and bump `@conduction/nextcloud-vue` to the minor that ships it (D2) (REQ-WDN-001). Verify: the issue URL is in `proposal.md` under Cross-app dependencies, and `package-lock.json` resolves the new minor.

## 2. Screens

- [ ] 2.1 Add the notes panel beside the viewer in `FileViewerPage.vue` (D3) (REQ-WDN-001, REQ-WDN-002). Verify: Playwright adds a note on My documents and reads it back in the Files sidebar's comments tab.
- [ ] 2.2 Add the panel under the viewer in `DossierDetail.vue` for the selected document (D3) (REQ-WDN-002). Verify: Playwright switches documents and sees each document's own notes.
- [ ] 2.3 Add a "Notes" row action to `IntakeIndex.vue` opening a dialog in `src/dialogs/` for the intake document's file (D3) (REQ-WDN-002). Verify: Playwright as registrar adds a note on an intake row and a second user in the same group reads it.
- [ ] 2.4 Ask for `oc:comments-count` in the My documents `PROPFIND` and show the count on rows with notes (D4) (REQ-WDN-004). Verify: Playwright reads the count after adding a note; a Vitest case covers the parsing.

## 3. Checks, strings and docs

- [ ] 3.1 Prove notes stay on their file: anonymise and share a document with a note and check neither result carries it (D5) (REQ-WDN-003). Verify: Playwright opens the anonymised copy and finds no notes; Newman reads the comments of the copy's file id and gets none.
- [ ] 3.2 Dutch and English strings (ADR-005), `@spec` tags on the new methods, and a section in `docs/features/` with a screenshot of the panel (ADR-010). Verify: `npm run check:l10n` and `npm run lint` pass, and the docs page renders.
