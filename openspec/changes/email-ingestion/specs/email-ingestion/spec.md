# email-ingestion Specification (delta)

---
status: proposed
---

## Purpose

Watched-folder email ingestion: `.eml` exports (and an optional
OpenConnector-delivered IMAP feed) dropped into admin-mapped inbox folders
are filed into their mapped dossier as source `.eml` + PDF/A-3b derivative
(via the existing conversion cascade) with an `emailDocument` record carrying
envelope and threading metadata. Scanning is a bounded, idempotent cron job;
failures (including unsupported `.msg`) are visible records, never silent
skips. Mailbox connectivity and credentials stay out of DocuDesk
(OpenConnector boundary). Threading metadata is captured for the wave-1
woo-request-workflow's future email-thread dedupe (referenced, not
modified). Evidence: the mailbox-integration canonical-feature cluster, the
VNG Woo email-archiving obligation (end 2026) and Visma Circle's
auto-registration.

## ADDED Requirements

### Requirement: Email-document schema and register import (REQ-DDEIN-001)

The app MUST declare an `emailDocument` schema in the `document` register:
`sourceFileRef` (required), `pdfFileRef`, `dossierRef`, `subject`,
`fromAddress`, `toAddresses[]`, `ccAddresses[]`, `sentAt`, `messageId`,
`inReplyTo`, `references[]`, `threadKey`, `attachmentCount`,
`attachmentNames[]`, `contentHash` (sha256 of the raw `.eml`), `status`
(enum `received`|`filed`|`failed`), `failureReason`, `ingestSource`
(enum `watched-folder`|`manual`), `ingestedAt` (required, with `status`).
All data MUST be stored as OpenRegister objects (ADR-001) with a register
version bump for boot import. The schema MUST carry a
`docudesk-email-ingestion` `x-openregister-processing` annotation so the
activity appears in the platform AVG Art. 30 verwerkingsregister. The
existing outbound `correspondence` schema MUST NOT be reused or modified.

#### Scenario: Register import creates the schema and seed

- GIVEN DocuDesk and OpenRegister installed
- WHEN `ConfigurationService::importFromApp()` runs on boot
- THEN the `emailDocument` schema exists in the `document` register, the seeded demo email record is queryable, and the processing annotation is declared
- @e2e exclude boot-time register import with no UI surface of its own — covered by PHPUnit register-import assertions (tests/unit/Settings/)

### Requirement: Bounded, idempotent watched-folder ingestion (REQ-DDEIN-002)

The app MUST scan admin-configured inbox folders
(`docudesk.email_ingestion.inbox_folders`: mappings of folder → target
dossier) via a cron background job processing at most
`docudesk.email_ingestion.files_per_tick` (default 25) files per run, so a
bulk drop drains over successive ticks without starving the instance.
Ingestion MUST be idempotent: a file whose `contentHash` — or whose
`messageId` for the same target dossier — matches an existing
`emailDocument` MUST NOT produce a second record or a second filed copy
(the re-dropped file is removed from the inbox). Each ingested `.eml` MUST
be moved into the mapped dossier's bound folder (`@self.folder`) and
produce one `emailDocument` record. A failure MUST leave the file in the
inbox and write a `failed` record with a `failureReason` — a file MUST
never be skipped silently.

#### Scenario: Dropped emails are filed into the mapped dossier

- GIVEN an inbox folder mapped to a dossier and two `.eml` files dropped into it
- WHEN the ingestion job runs
- THEN both `.eml` files are moved into the dossier's folder and two `emailDocument` records exist with `status: filed`, envelope metadata and content hashes
- @e2e tests/e2e/workflows/email-ingestion.spec.ts

#### Scenario: Re-dropped email does not duplicate

- GIVEN an already-ingested email's `.eml` dropped into the same mapped inbox again
- WHEN the ingestion job runs
- THEN no second `emailDocument` record and no second filed copy exist, and the re-dropped file is removed from the inbox
- @e2e exclude idempotency race is background-job logic — covered by PHPUnit (tests/unit/Service/EmailIngestionServiceTest.php::testRedropIsIdempotent)

#### Scenario: Bulk drop drains in bounded ticks

- GIVEN 60 `.eml` files dropped at once and a per-tick limit of 25
- WHEN the job runs three times
- THEN 25, 25 and 10 files are processed respectively and all 60 are filed
- @e2e exclude work-unit budgeting — covered by PHPUnit (tests/unit/BackgroundJob/EmailIngestionJobTest.php)

### Requirement: PDF/A-3b conversion via the existing cascade; filing never blocks on it (REQ-DDEIN-003)

Each filed email MUST get a PDF/A-3b derivative produced by the existing
`PdfConversionService` cascade (`EmlBackend` → OR `parseEmlStructured` →
`EmlPdfAssemblyService`), written beside the filed `.eml`, recorded in
`pdfFileRef`. No new conversion or rendering engine may be introduced, and
the cascade itself MUST NOT be modified. When conversion fails or is
disabled (`docudesk.conversion.backends.eml_enabled` off, or OpenRegister
absent), the email MUST still be filed (`status: filed`, `pdfFileRef` null)
with a visible not-converted state and a retry action that re-runs
conversion only — a conversion outage MUST NOT lose or defer the capture of
mail.

#### Scenario: Filed email gets its PDF/A derivative

- GIVEN a mapped inbox and a dropped `.eml` with one attachment
- WHEN ingestion completes
- THEN the dossier folder contains the `.eml` and a PDF/A-3b derivative, and the record's `pdfFileRef` points at it
- @e2e tests/e2e/workflows/email-ingestion.spec.ts

#### Scenario: Conversion outage still captures the mail

- GIVEN `eml_enabled` set to false
- WHEN an `.eml` is ingested
- THEN it is filed with `pdfFileRef` null and the status surface shows "not converted" with a retry action
- AND retrying after re-enabling produces the derivative without a second record
- @e2e tests/e2e/spec-coverage/email-ingestion.spec.ts

### Requirement: Threading metadata is captured for future dedupe (REQ-DDEIN-004)

For every ingested email the app MUST persist `messageId`, `inReplyTo`,
`references[]` and a normalized `threadKey` (first of `References`, else
`In-Reply-To`, else the email's own `messageId`; angle brackets stripped,
lower-cased). Because OR's parser surfaces only `messageId` at HEAD, the
app MUST extract `In-Reply-To`/`References` from the raw RFC 5322 header
block itself, reading header lines only. The app MUST NOT compute any
dedupe verdict from this metadata — thread deduplication remains the
woo-request-workflow's future step, which this metadata feeds.

#### Scenario: A reply carries its thread key

- GIVEN an ingested email A and a dropped reply B whose `In-Reply-To` and `References` point at A's message id
- WHEN B is ingested
- THEN B's record stores A's message id in `inReplyTo` and `references`, and both records share the same `threadKey`
- @e2e exclude header-extraction arithmetic — covered exhaustively by PHPUnit fixture emails (tests/unit/Service/EmailIngestionServiceTest.php::testThreadingHeadersExtracted)

### Requirement: Mailbox connectivity stays out of DocuDesk (REQ-DDEIN-005)

The app MUST NOT contain an IMAP/POP/Graph client and MUST NOT store any
mailbox credential in its configuration. The optional mailbox feed is an
OpenConnector flow whose documented contract is: deliver each message as a
raw `.eml` file into the mapped watched folder; DocuDesk's idempotency
absorbs redeliveries. The admin settings surface MUST document this
boundary where the inbox mapping is configured.

#### Scenario: No mailbox credential surface exists

- WHEN DocuDesk's settings surfaces and configuration keys are inspected
- THEN no mailbox host/username/password/token setting exists, and the inbox-mapping admin section explains the OpenConnector delivery contract
- @e2e exclude negative code/config guard — covered by PHPUnit (tests/unit/Settings/ negative-guard assertions on the settings registry and config keys)

### Requirement: Unsupported formats fail visibly (REQ-DDEIN-006)

A `.msg` (or otherwise unparseable) file in a mapped inbox MUST produce a
`failed` `emailDocument` record with `failureReason: unsupported-format`
(or the parse error) and MUST remain in the inbox — never a silent skip —
so the archive shows exactly which mails were not captured. The failure
row MUST advise re-export as `.eml`. Failure reasons MUST NOT include
message body content.

#### Scenario: A dropped .msg is a visible failure

- GIVEN a `.msg` file dropped into a mapped inbox
- WHEN the ingestion job runs
- THEN a `failed` record with reason `unsupported-format` appears in the status surface and the file remains in the inbox
- @e2e tests/e2e/spec-coverage/email-ingestion.spec.ts

### Requirement: Email-ingestion status surface (REQ-DDEIN-007)

The app MUST provide an email-ingestion status manifest page (`CnIndexPage`
+ `CnDataTable`: subject, from, sent date, dossier, status chip including
the not-converted and failed states with reasons, thread indicator; filters
by status and dossier; manual re-scan action), per ADR-012
(`@conduction/nextcloud-vue` components) and ADR-003 (NL Design tokens via
Nextcloud CSS variables, no hardcoded colors). Every `NcSelect` MUST carry
an `inputLabel`; modals/dialogs MUST live in their own files under
`src/modals/`/`src/dialogs/`. Controller routes backing this surface MUST
carry explicit auth attributes.

#### Scenario: Status page shows filed and failed rows

- GIVEN one filed email and one failed `.msg` ingestion
- WHEN the status page renders
- THEN the filed row shows its dossier and status chip and the failed row shows the unsupported-format reason
- @e2e tests/e2e/spec-coverage/email-ingestion.spec.ts
