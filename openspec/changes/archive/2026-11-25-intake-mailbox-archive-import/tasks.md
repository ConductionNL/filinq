# Tasks: intake-mailbox-archive-import

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [x] 1.1 Add `mailArchiveImport` with the D2 properties, lifecycle and the finished notification to `lib/Settings/filinq_register.json`, seed and version bump (REQ-MAI-001). Verify: `occ maintenance:repair` imports it; the seeds show on the import page.

## 2. Splitting

- [x] 2.1 Add `lib/Service/MailArchiveSplitter.php` for MBOX (streamed), ZIP and PST through a probed `readpst` (D1) (REQ-MAI-002). Verify: PHPUnit with a fixture MBOX of five messages including an escaped `>From` line, a ZIP of three `.eml`, and a missing `readpst`.
- [x] 2.2 List the archive's folders into `folderMap` for the mapping step (D4) (REQ-MAI-003). Verify: PHPUnit on a fixture with two folders.

## 3. Import

- [x] 3.1 Add `lib/Service/MailArchiveImportService.php` and the `TimedJob` processing one bounded unit per run with a saved cursor (D3) (REQ-MAI-004). Verify: PHPUnit that stops after one unit and resumes at the cursor.
- [x] 3.2 File mapped messages through `email-ingestion`'s filing and send unmapped ones to `IntakeService::receiveMessage()` (D4) (REQ-MAI-003). Verify: PHPUnit counts `filed` and `toInbox` on the fixture.
- [x] 3.3 Idempotency on a second run (D5) (REQ-MAI-005). Verify: PHPUnit imports the fixture twice and the second run counts only `skippedDuplicate`.
- [x] 3.4 Routes to start, read and cancel an import with access checks on the file and the dossiers (D6) (REQ-MAI-001). Verify: Newman for start, read, cancel and a refused dossier.

## 4. Screens

- [x] 4.1 Add the import page with file pick, folder mapping, progress and the failure list, and register it in `src/manifest.json` (REQ-MAI-001, REQ-MAI-003). Verify: Playwright starts an import of the fixture, maps one folder, and reads the counts after the job ran.

## 5. Quality

- [x] 5.1 Dutch and English strings (ADR-005), `@spec` tags, `docs/features/` with screenshots (ADR-010), PHPUnit at 75% on new code (ADR-009). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`. — 25 Nov 2026, live sandbox (Nextcloud 34, MariaDB, OpenRegister main): screenshots of the folder mapping and the import list in `docs/features/mail-archive-import.md`. The live run found two reds: the file picker had no confirm button (fixed in its own PR), and the import job ran without a user so OpenRegister refused every intake write (5 of 5 failed; now 5 to the inbox, and a second run skips them as duplicates). Coverage with pcov over the full unit suite: 455 of 504 statements, 90.3%, over the 12 lib files carrying this change's `@spec` tag. `npm run test:l10n` and `npm run lint` (0 errors) pass; `composer check:strict` reports only findings inherited from development.
