# Tasks: share-document-links

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11. -->

## 1. Register

- [ ] 1.1 Add `documentShare` with its lifecycle (`active`, `expired`, `revoked`) and authorization cascade, open `archiveJob.create` to authenticated users, add one seed share, and bump the register version (D4, D6) (REQ-SDL-005). Verify: `occ maintenance:repair` imports it, the seed share shows on the Shared links page, and a clerk's `archiveJob` write through OpenRegister answers 201.

## 2. Service and routes

- [ ] 2.1 Add `lib/Service/Share/LinkShareGateway.php` and `NextcloudLinkShareGateway` over `OCP\Share\IManager` (D1, D2) (REQ-SDL-001). Verify: Newman on the instance creates, reads and deletes a link share through the route in 2.3.
- [ ] 2.2 Add `DocumentShareService`: one document shared as itself with its version recorded; expiry required, Nextcloud's stricter maximum and password rule applied; a `documentShare` written, and a failed write deletes the new share again (D3, D5, D6) (REQ-SDL-001, REQ-SDL-003, REQ-SDL-005). Verify: PHPUnit with a stub gateway covers each path, including the refused record write; coverage on the new PHP is at least 75% (ADR-009).
- [ ] 2.3 Add `POST api/shares`, `POST api/shares/preflight` and `DELETE api/shares/{id}` with `#[NoAdminRequired]`, the session user as sharer, and a check that only the sharer or an admin takes a share back (REQ-SDL-001, REQ-SDL-004). Verify: Newman for each route, and a second user's DELETE answers 403.
- [ ] 2.4 Extend `CaseArchiveService` with a manifest over chosen file ids and the step that writes the archive with its manifest into `Shared bundles`, recording the `fileId`; a failed job write stops the share (D4) (REQ-SDL-002). Verify: PHPUnit asserts the archive holds the chosen files and `manifest.json`, a file over the ceiling is listed as left out, and a refused job write raises.
- [ ] 2.5 Add `ExpiredShareCloserJob`: records past expiry move to `expired`, records whose share is gone in Files move to `revoked`, bundle archives are deleted (D7) (REQ-SDL-004). Verify: PHPUnit with a clock and a stub gateway; `occ background-job:execute` on the instance closes the seeded share.

## 3. Screens

- [ ] 3.1 Add "Share by link" as a row action and a bulk action in `MyDocumentsIndex.vue`, opening `src/modals/ShareByLinkDialog.vue` with expiry, optional password, a note and, for a bundle, a name and the preflight result (REQ-SDL-001, REQ-SDL-002, REQ-SDL-003). Verify: Playwright shares one document and then three, copies each link, and opens both in a logged-out context.
- [ ] 3.2 Show a file's confidentiality label in the dialog and require a confirmation before sharing (D8) (REQ-SDL-006). Verify: Playwright on a labelled fixture reads the warning and cannot share without ticking the confirmation.
- [ ] 3.3 Add the "Shared links" page (`SharedLinksIndex.vue`, a `CnIndexPage` over the user's `documentShare` objects) reached from a header action on My documents, with "Take back" per row (REQ-SDL-004). Verify: Playwright takes a link back and a logged-out context then gets Nextcloud's "not found" page.

## 4. Strings and docs

- [ ] 4.1 Dutch and English strings (ADR-005), `@spec` tags on new methods, and a section in `docs/features/` with screenshots of the dialog and the Shared links page (ADR-010). Verify: `npm run check:l10n`, `npm run lint` and `composer check:strict` pass, and the docs page renders.
