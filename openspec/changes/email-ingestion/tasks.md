# Tasks: email-ingestion

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add the `emailDocument` schema to the `document` register in `lib/Settings/filinq_register.json` (REQ-DDEIN-001)
  - Properties per design.md D1; required `sourceFileRef`/`status`/`ingestedAt`; `objectNameField: subject`; `filinq-email-ingestion` `x-openregister-processing` annotation; register-i18n tags on user-facing string fields; register version bump with changelog entry; the seed record from design.md Seed Data (placeholder refs, nil hash); `correspondence` schema untouched.

## 2. Backend

- [ ] 2.1 Implement `lib/Service/EmailIngestionService.php` scan + file + record pipeline (REQ-DDEIN-002, REQ-DDEIN-006)
  - Inbox mapping from `filinq.email_ingestion.inbox_folders`; sha256 + messageId-per-dossier idempotency (re-drop removes the inbox file, no duplicate record/copy); move-into-dossier-folder filing via the existing `@self.folder` binding; failures leave the file and write `failed` records with reasons (no body content, no silent skips); `.msg` → `unsupported-format`.

- [ ] 2.2 Implement threading-header extraction (REQ-DDEIN-004)
  - Raw RFC 5322 header-line read for `In-Reply-To`/`References` (OR surfaces only `messageId` at HEAD — deletion path documented); normalized `threadKey` derivation; no dedupe verdicts.

- [ ] 2.3 Wire conversion through the existing cascade with file-first semantics (REQ-DDEIN-003)
  - `PdfConversionService`/`EmlBackend` untouched; derivative beside the filed `.eml`; conversion failure/disabled → still `filed` with `pdfFileRef` null + retry that re-runs conversion only (idempotent on the record).

- [ ] 2.4 Add `lib/BackgroundJob/EmailIngestionJob.php` (cron `TimedJob`) with bounded work units (REQ-DDEIN-002)
  - `filinq.email_ingestion.files_per_tick` default 25; registered in `info.xml`; safe re-entry (idempotency makes overlapping runs harmless).

- [ ] 2.5 Add `lib/Controller/EmailIngestionController.php` + `api/email-ingestion/*` routes and admin settings (REQ-DDEIN-005, REQ-DDEIN-007)
  - Status list, conversion-retry, manual re-scan; explicit auth attributes on every method; admin settings section with the inbox → dossier mapping editor and the OpenConnector boundary text; NO mailbox host/credential setting anywhere (negative guard).

## 3. Frontend

- [ ] 3.1 Email-ingestion status manifest page (REQ-DDEIN-007)
  - `CnIndexPage`/`CnDataTable`; status chips incl. not-converted and failed-with-reason; status/dossier filters (`NcSelect` with `inputLabel`); retry + re-scan actions; manifest schema refs use slugs; NL Design tokens.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for EmailIngestionService/Job (idempotency, per-tick budget, threading extraction on fixture emails incl. missing headers, file-first conversion outage, `.msg` failure record, no-credential negative guard) — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.

- [ ] 4.2 Playwright e2e specs `tests/e2e/workflows/email-ingestion.spec.ts` + `tests/e2e/spec-coverage/email-ingestion.spec.ts` covering the `@e2e`-referenced scenarios end-to-end with OpenRegister on the Postgres dev instance
  - Drop fixture `.eml`/`.msg` files into a mapped inbox, run the job, verify filing + PDF/A derivative + status page through the UI; nldesign-theme accessibility pass.

- [ ] 4.3 i18n: EN + NL for all new UI strings (status chips, failure reasons, settings section, boundary text)
  - Keys in English.

- [ ] 4.4 Documentation `docs/features/email-ingestion.md` with Playwright MCP screenshots (ADR-010); run `openspec validate email-ingestion --strict`
  - Documents the watched-folder contract, the OpenConnector IMAP boundary, the file-first conversion semantics and the Woo email-archiving obligation context.
