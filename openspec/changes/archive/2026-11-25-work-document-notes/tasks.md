# Tasks: work-document-notes

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 7. -->

## 1. The shared component

- [x] 1.1 File the nextcloud-vue half (a file as `CnNotesCard` source over `/remote.php/dav/comments/files/{fileId}`) and bump `@conduction/nextcloud-vue` to the minor that ships it (D2) (REQ-WDN-001). Verify: the issue URL is in `proposal.md` under Cross-app dependencies, and `package-lock.json` resolves the new minor.

## 2. Screens

- [x] 2.1 Add the notes panel beside the viewer in `FileViewerPage.vue` (D3) (REQ-WDN-001, REQ-WDN-002). Verify: Playwright adds a note on My documents and reads it back in the Files sidebar's comments tab.
- [x] 2.2 Add the panel under the viewer in `DossierDetail.vue` for the selected document (D3) (REQ-WDN-002). Verify: Playwright switches documents and sees each document's own notes.
- [x] 2.3 Add a "Notes" row action to `IntakeIndex.vue` opening a dialog in `src/dialogs/` for the intake document's file (D3) (REQ-WDN-002). Verify: Playwright as registrar adds a note on an intake row and a second user in the same group reads it.
- [x] 2.4 Ask for `oc:comments-count` in the My documents `PROPFIND` and show the count on rows with notes (D4) (REQ-WDN-004). Verify: Playwright reads the count after adding a note; a Vitest case covers the parsing.

## 3. Checks, strings and docs

- [x] 3.1 Prove notes stay on their file: anonymise and share a document with a note and check neither result carries it (D5) (REQ-WDN-003). Verify: Playwright opens the anonymised copy and finds no notes; Newman reads the comments of the copy's file id and gets none.
  - Verified live 25 Nov 2026 (NC 34 + MariaDB + OpenRegister main): `tests/e2e/document-notes.spec.ts` 6/6 green. 'the anonymised copy carries no notes' writes a note, anonymises (analyse, review mark, checked mark, anonymise) and reads the copy's file id on Nextcloud's comments endpoint: no comment (the request a Newman check would make). 'a share link of a document with a note does not carry the note' (new) downloads and opens the link as an outsider: the file, no note. Needs `FILINQ_E2E_ANALYSED_FILE_ID`, an admin document whose findings are all certain.
- [x] 3.2 Dutch and English strings (ADR-005), `@spec` tags on the new methods, and a section in `docs/features/` with a screenshot of the panel (ADR-010). Verify: `npm run check:l10n` and `npm run lint` pass, and the docs page renders.
  - Notes strings are in `l10n/en.json` and `l10n/nl.json` (`npm run check:l10n-js` up to date); `docs/features/document-notes.md` with the live panel screenshot `docs/static/screenshots/document-notes-panel.png`, linked from `docs/features/README.md`.
