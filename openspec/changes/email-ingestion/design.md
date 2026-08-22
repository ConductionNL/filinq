# Design: email-ingestion

## Context

Verified at HEAD:

- **`EmlBackend`** (`lib/Service/Conversion/EmlBackend.php`): part of the
  `PdfConversionService` cascade; `canHandle()` claims `message/rfc822` +
  `.eml` **only** (no `.msg`); `isAvailable()` = tenant flag
  `filinq.conversion.backends.eml_enabled` AND OR's
  `TextExtractionService::parseEmlStructured` present (checked dynamically
  by FQCN string — Filinq loads without OR); `convert()` walks
  parse → assemble → write-beside-source.
- **`EmlPdfAssemblyService`**: stateless `assemble(EmlStructure)` → PDF/A-3b
  bytes via mPDF + the shared Twig `TemplateRenderer` (envelope template,
  per-attachment pages toggle, byte-cap placeholder dividers, nesting cap 3
  matching OR's parser). CB #156 revises this chain to consume OR's
  **anonymised** EML structures (Conduction/openregister#241) — independent
  of, and untouched by, this change.
- **OR's `EmlStructure`** headers at HEAD: `from`, `to`, `cc`, `subject`,
  `date`, `messageId` — **no `In-Reply-To` / `References`** (the docblock
  allows "extras", but `EmlParser` only surfaces `messageId`). Threading
  headers must therefore be read by Filinq itself (D4).
- **No ingestion machinery exists**: no watched folder, no mailbox client,
  no email record schema, no filing. Filinq's `BackgroundJob/` already
  hosts cron jobs (`FolderExtractionJob` etc.) — the pattern to follow,
  including the redaction-at-scale lesson: bounded work per tick.
- Dossier filing target: the `dossier` register's `@self.folder` binding —
  filing into a dossier = placing the files in its bound folder (the same
  membership model the sibling `dossier-management-ui` change formalises;
  this change works standalone because the binding exists at HEAD).
- Wave-1 `woo-request-workflow` REQ-DDWRW-004 deliberately scoped
  email-thread dedupe as future work with an extensible enum — the threading
  metadata captured here (D4) is what that future step will consume
  (referenced, not modified).

## Goals / Non-Goals

**Goals:**

- One ingestion path: `.eml` lands in a watched folder → filed into the
  mapped dossier as source + PDF/A-3b derivative + `emailDocument` record.
- Idempotent, restart-safe, bounded scanning; failures visible, never
  silent.
- Threading metadata persisted now so thread dedupe is possible later.
- A clean, documented boundary for mailbox connectivity (OpenConnector).

**Non-Goals:**

- No IMAP/POP/Graph client, no mailbox credentials, no polling of remote
  mailboxes in Filinq (D5).
- No native `.msg` (Outlook CFBF) parsing (D6) and no PST/MBOX bulk-archive
  splitter in v1 (the cluster's *bulk import PST/MBOX* story needs a
  container-format decision first — deferred).
- No auto-matching of emails to cases/dossiers by content ("automatically
  suggest matching case") — v1 maps inbox folder → dossier statically;
  suggestion is a follow-up once matching heuristics have an owner.
- No thread *dedupe* (that is woo-request-workflow's future step); this
  change only captures the metadata.
- No changes to the conversion cascade, the assembly service or CB #156's
  anonymised-EML work.

## Decisions

### D1 — `emailDocument` schema in the `document` register

One record per ingested email: `sourceFileRef` (the filed `.eml`, required),
`pdfFileRef` (PDF/A-3b derivative, null until converted), `dossierRef`,
`subject`, `fromAddress`, `toAddresses[]`, `ccAddresses[]`, `sentAt`,
`messageId`, `inReplyTo`, `references[]`, `threadKey`, `attachmentCount`,
`attachmentNames[]`, `contentHash` (sha256 of the raw `.eml`), `status`
(enum `received` | `filed` | `failed`), `failureReason`, `ingestSource`
(enum `watched-folder` | `manual`), `ingestedAt`. Required:
`sourceFileRef`, `status`, `ingestedAt`. `objectNameField: subject`.

Envelope PII (from/to/subject) IS stored on the record: the record *is* the
correspondence-register entry the mailbox cluster asks for
("register incoming email in correspondence log"), and the same data sits in
the filed document itself; the schema carries a `filinq-email-ingestion`
`x-openregister-processing` annotation (rechtsgrond `public-task`, data
categories PERSON/EMAIL) so the activity is declared in the platform Art. 30
register — same follow-up note as entity-search: the
`processing-activity-export` canonical activity enumeration gains one entry
in a follow-up amendment (file not assigned to this change).

Rejected alternative: reusing the `correspondence` schema — verified at
HEAD it models *outbound, staff-generated* batch correspondence
(`recipientId`, `generatedBy`, `templateId`); inbound email is a different
lifecycle and would corrupt that schema's semantics.

### D2 — Watched-folder scan as a bounded cron job

`filinq.email_ingestion.inbox_folders` (IAppConfig JSON): array of
`{folderId, dossierRef}` mappings, managed in admin settings.
`EmailIngestionJob` (cron `TimedJob`) scans each mapped inbox for `.eml` /
`.msg` files, processing at most `filinq.email_ingestion.files_per_tick`
(default 25) per run — the redaction-at-scale bounded-work lesson; a large
PST-export drop drains over successive ticks rather than starving the
instance.

Per file: hash → **idempotency check** (an `emailDocument` with the same
`contentHash`, or same `messageId` + same `dossierRef`, already exists →
the file is a re-drop; it is removed from the inbox and the existing record
is left untouched) → parse → thread-extract → file → convert → record.
**Filing = moving the `.eml` into the mapped dossier's bound folder** (the
inbox stays clean; the dossier owns the source), with the PDF/A-3b
derivative written beside it by the existing cascade. Failures leave the
file in the inbox and write a `failed` record with `failureReason` — visible
in the status surface, never silent (the hydra swallowed-catch lesson).

### D3 — Conversion reuses the existing cascade, filing never blocks on it

The PDF/A-3b derivative is produced by the existing `PdfConversionService`
cascade (which selects `EmlBackend`). Decision: **file first, convert
second** — if conversion fails or `eml_enabled` is off, the email is still
`filed` (source `.eml` in the dossier, record written, `pdfFileRef` null)
with a visible "not converted" state and a retry action, because the
archival obligation is about capturing the record; a conversion outage must
not lose mail. Retry re-runs conversion only (idempotent on the record).

### D4 — Threading metadata: Filinq reads the raw headers

OR surfaces only `messageId` at HEAD, so `EmailIngestionService` extracts
`In-Reply-To` and `References` itself from the raw RFC 5322 header block of
the `.eml` (bounded read of the header section; RFC 2047 decoding not needed
for these id-shaped headers). `threadKey` = the first id in `References`,
else `In-Reply-To`, else the email's own `messageId` — normalized (angle
brackets stripped, lower-cased). This is deliberately metadata-only: the
wave-1 woo-request-workflow's future email-thread dedupe consumes it; no
dedupe verdict is computed here. **Deferred question**: OR's `EmlParser`
surfacing `In-Reply-To`/`References` as header extras would let this
extraction be deleted — OR-side follow-up, not assigned here.

### D5 — IMAP boundary: OpenConnector owns mailbox fetch

Decision: **no mailbox connectivity in Filinq.** Rationale:

- Source-system connectivity, polling and credential custody are
  OpenConnector's domain — exactly the zgw-document-bridge precedent (case
  systems fetch via OpenConnector; Filinq processes staged items), and
  ADR-064 custody: secrets (IMAP passwords/OAuth tokens) never sit in a
  document app's config.
- The watched folder is already the union interface: manual `.eml` exports,
  a mounted mail-archive share, and an OpenConnector IMAP flow all deliver
  the same way.

The documented contract for the optional IMAP feed: an OpenConnector flow
authenticates to the mailbox, writes each message as a raw `.eml` file into
the mapped watched folder (filename convention `<messageId-hash>.eml`), and
Filinq's idempotency (D2) absorbs redeliveries. Filinq ships a negative
guarantee: no IMAP client code, no mailbox credential setting (spec
REQ-DDEIN-005). Building the reference OpenConnector flow is recorded as a
follow-up for the OpenConnector backlog (not assigned to this change).

### D6 — `.msg` files: visible failure, not silent skip

`.msg` (Outlook CFBF) has no parser at HEAD anywhere in the stack. Decision:
the scanner ingests the file's presence — a `failed` record with
`failureReason: unsupported-format` and the file left in the inbox — so an
operator sees exactly which mails did not make it and can re-export as
`.eml`. Silently skipping would fake a clean archive. Native `.msg` support
is deferred (needs an OR-side parser decision; recorded as an open
question).

### D7 — Surfaces

- **Email-ingestion status page** (manifest page, `CnIndexPage` +
  `CnDataTable`): ingested emails with subject, from, sent date, dossier,
  status chip (`filed` / `filed, not converted` / `failed` + reason),
  thread indicator; filter by status/dossier; manual re-scan action.
- **Dossier context**: within a dossier, its ingested emails are visible
  (the filed `.eml`/PDF appear in the folder listing naturally; the
  `emailDocument` records enrich them with envelope/thread metadata when the
  sibling dossier-management-ui detail is present — presence-gated,
  hidden-not-broken; standalone, the status page filters by dossier).
- **Admin settings**: inbox-folder → dossier mapping editor, per-tick
  limit, with the IMAP boundary explained inline.

## OpenRegister service usage (ADR-001)

| Operation | Service |
|---|---|
| `emailDocument` CRUD | OR ObjectService (no custom tables) |
| EML parsing | OR `TextExtractionService::parseEmlStructured` (presence-gated FQCN resolution — the existing EmlBackend pattern) |
| PDF/A-3b derivative | existing `PdfConversionService` cascade (unchanged) |
| Dossier filing | existing `@self.folder` binding (unchanged) |
| Art. 30 declaration | `x-openregister-processing` annotation on `emailDocument` |

ADR-011 check: sha256 via PHP `hash()`; RFC 5322 header-line extraction is
app-owned because no OR helper exposes these headers at HEAD (D4, with the
deletion path documented); no date/slug/BSN utilities duplicated.

## Declarative vs imperative

- **Declarative**: the `emailDocument` schema + processing annotation;
  register-i18n on user-facing string fields; inbox mapping as
  configuration data; manifest pages.
- **Imperative (justified)**: the scan/file/convert pipeline (filesystem
  side effects + cross-service orchestration in a background job); raw
  header extraction (byte-level parsing); idempotency checks (content-hash
  queries before writes).

## Seed Data

One seed `emailDocument` so surfaces render non-empty (placeholder refs,
nil-hash pattern, demo-municipality flavour):

```json
{
  "@self": {"register": "document", "schema": "emailDocument", "slug": "seed-email-woo-verzoek-ontvangst"},
  "sourceFileRef": "seed-file-woo-verzoek-ontvangst-eml",
  "pdfFileRef": "seed-file-woo-verzoek-ontvangst-pdf",
  "dossierRef": "demostad-woo-2025-017",
  "subject": "Woo-verzoek handhavingsbesluiten horeca",
  "fromAddress": "verzoeker@example.org",
  "toAddresses": ["woo@demostad.example"],
  "sentAt": "2026-06-20T08:41:00+00:00",
  "messageId": "seed-0001@example.org",
  "inReplyTo": null,
  "references": [],
  "threadKey": "seed-0001@example.org",
  "attachmentCount": 1,
  "attachmentNames": ["machtiging.pdf"],
  "contentHash": "0000000000000000000000000000000000000000000000000000000000000000",
  "status": "filed",
  "ingestSource": "watched-folder",
  "ingestedAt": "2026-06-20T09:00:00+00:00"
}
```

## Security Considerations

- Ingested emails are untrusted input: parsing is delegated to OR's
  hardened parser (nesting cap 3); the assembly never executes attachment
  content; Filinq's own header extraction reads header *lines* only and
  never evaluates content.
- No mailbox credentials anywhere in Filinq (D5 — negative-guard spec
  requirement and test).
- The background job runs as system; filed files land under the dossier
  folder's existing ACLs — the inbox mapping is admin-configured, so an
  admin decides which dossier receives which inbox.
- Failure reasons are logged and surfaced but never include message bodies.
- All processing stays local (no external API calls).

## Risks / Trade-offs

- [Inbox mapping is static (folder → one dossier)] → accepted for v1;
  auto-suggestion of a matching dossier is deferred (Non-Goals) — the
  cluster's suggestion story needs a matching-heuristic owner first.
- [File-first means unconverted `.eml`s can sit in dossiers] → deliberate
  (D3): capture beats conversion; the not-converted state is visible and
  retryable.
- [Header extraction duplicates a sliver of parsing] → bounded to two
  id-shaped headers, unit-pinned, with a documented deletion path once OR
  surfaces the extras.
- [Duplicate emails across *different* dossiers are not deduped] →
  intentional: idempotency is per dossier (same mail may legitimately
  belong to two dossiers); cross-dossier thread dedupe is the
  woo-request-workflow future step.
- [`.msg`-heavy municipalities see many failures] → the failure is the
  feature (visible gap + re-export guidance); native support deferred.

## Migration Plan

Additive: one schema + seed (register version bump, boot import), new
service/job/controller/routes/views, two admin settings. Rollback = disable
the job + remove routes/UI; `emailDocument` records and filed documents
remain readable. No data migration.

## Open Questions

- OR-side: surface `In-Reply-To`/`References` in `EmlParser`'s header
  extras (deletes D4's extraction) — OR follow-up.
- Native `.msg` (Outlook CFBF) parsing — OR-side parser decision.
- PST/MBOX bulk-archive splitting (the cluster's migration story) — needs a
  container-format decision; candidate for a later wave with
  redaction-at-scale-style work units.
- Reference OpenConnector IMAP flow (D5 contract) — OpenConnector backlog.
- `processing-activity-export` canonical activity enumeration ("four") —
  one-line follow-up amendment covering the wave-2 additions.
