---
kind: code
---

# Proposal: email-ingestion

## Why

Email is where municipal case content actually arrives, and the clock is
running: VNG's guidance puts the **Woo email-archiving obligation at the end
of 2026** — work emails of officials fall under the Woo and must be
archivable and retrievable as documents. The market already sells this:
**Visma Circle** auto-registers incoming email into the zaak/DMS, and the
DocuDesk canonical-feature **mailbox-integration cluster** collects the
verbatim user stories: *import incoming email as official document*, *assign
imported email to existing case dossier*, *automatically suggest matching
case for incoming email*, *register incoming email in correspondence log*,
*bulk import PST/MBOX email archive*, *detect and remove duplicate emails
during migration*.

DocuDesk already owns half the machinery, verified at HEAD:

- `EmlBackend` (conversion cascade, `message/rfc822` + `.eml` only) delegates
  parsing to OR's `TextExtractionService::parseEmlStructured()` (headers
  `from`/`to`/`cc`/`subject`/`date`/`messageId`, body, attachments, nesting
  cap 3) and hands the structure to `EmlPdfAssemblyService::assemble()`,
  which emits **PDF/A-3b** bytes (envelope template, per-attachment pages,
  size-capped placeholders) — CB #156 tracks the follow-up that assembles
  OR-**anonymised** EML structures.
- What is missing is everything around it: nothing *ingests* — no watched
  folder, no mailbox, no email-as-document record, no dossier filing, and no
  threading metadata (OR's parser surfaces `messageId` but not
  `In-Reply-To`/`References` at HEAD), which the wave-1
  `woo-request-workflow` explicitly deferred email-thread dedupe on.

Without ingestion, an operator archiving a Woo-relevant mail thread today
exports `.eml` files into a folder and gets — nothing: no conversion, no
record, no dossier.

## What Changes

- **`emailDocument` schema** (`document` register): one record per ingested
  email — source `.eml` file ref, PDF/A derivative ref, dossier ref,
  envelope metadata (subject, from, to/cc, sent date), threading metadata
  (`messageId`, `inReplyTo`, `references[]`, `threadKey`), attachment
  manifest, content hash, ingest status (`received → filed | failed`) with
  visible failure reason.
- **Watched-folder ingestion**: admin-configured inbox folders, each mapped
  to a target dossier; a cron background job scans mapped inboxes for new
  `.eml`/`.msg` files in bounded work units, files each email into the
  mapped dossier's folder, converts it to PDF/A-3b through the existing
  conversion cascade, and writes the `emailDocument` record. Idempotent by
  content hash + messageId (re-scans and re-drops never duplicate).
- **`.msg` handling**: accepted into the inbox but recorded as `failed`
  with reason `unsupported-format` (visible, retryable after conversion to
  `.eml`); native Outlook-MSG parsing is a deferred OR-side decision.
- **Threading metadata**: DocuDesk parses `In-Reply-To`/`References` from
  the raw RFC 5322 header block itself (OR does not surface them at HEAD)
  and derives a normalized `threadKey`, kept for the wave-1
  woo-request-workflow's future email-thread dedupe (referenced, not
  modified).
- **IMAP boundary (decided and documented)**: mailbox fetching does NOT
  enter DocuDesk. Source-system connectivity and mailbox credentials belong
  to **OpenConnector** (the zgw-document-bridge precedent); an optional
  IMAP poll is an OpenConnector flow whose contract is simply "deliver
  `.eml` files into the watched folder". DocuDesk stores no mailbox
  credentials and ships no IMAP client.
- **Ingestion surface**: an email-ingestion status view (ingested emails,
  status chips, visible failures) and the ingested emails listed within
  their dossier.

## Capabilities

### New Capabilities

- `email-ingestion`: watched-folder email ingestion — `.eml` exports (and an
  optional OpenConnector-delivered IMAP feed) filed as PDF/A-3b documents
  into dossiers with an `emailDocument` record, idempotent scanning,
  threading metadata for future dedupe, and a fail-visible status surface.

### Modified Capabilities

<!-- none — the conversion cascade (EmlBackend/EmlPdfAssemblyService,
     pdf-conversion capability), the dossier folder binding and the
     woo-request-workflow are consumed unchanged; CB #156's anonymised-EML
     assembly evolves the cascade independently of this change. -->

## Impact

- `lib/Settings/docudesk_register.json`: `emailDocument` schema in the
  `document` register, seed data, register version bump.
- New `lib/Service/EmailIngestionService.php` (scan, parse, thread-header
  extraction, filing, record writes) + `lib/BackgroundJob/EmailIngestionJob.php`
  (cron `TimedJob`, bounded per tick) + `lib/Controller/EmailIngestionController.php`
  with `api/email-ingestion/*` routes (status list, inbox-mapping admin
  read, manual re-scan trigger).
- Admin settings: `docudesk.email_ingestion.inbox_folders` (folder →
  dossier mapping), `docudesk.email_ingestion.files_per_tick`.
- `src/manifest.json` + views: email-ingestion status page; ingested-email
  rows in the dossier context.
- Consumes (unchanged): OR `parseEmlStructured` (presence-gated hard
  prerequisite, as `EmlBackend` already does), the existing
  `PdfConversionService` cascade for the PDF/A-3b derivative, the dossier
  `@self.folder` binding for filing.
- Boundary: optional IMAP fetch = OpenConnector flow writing into the
  watched folder (documented contract; zero DocuDesk mailbox code).
- Evidence: canonical-feature mailbox-integration cluster (user stories
  above), VNG Woo email-archiving obligation end 2026, Visma Circle
  auto-registration; CB #156 (eml→PDF/A chain) as the verified base.
