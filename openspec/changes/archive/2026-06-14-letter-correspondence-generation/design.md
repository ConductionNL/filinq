---
status: pr-created
kind: code
issue: 42
change: letter-correspondence-generation
---

# Design: letter-correspondence-generation

## Summary

Add a dedicated letter and correspondence generation workflow to DocuDesk.
Enables government users to generate letters, beschikkingen, and other
correspondence from templates with merge fields populated from case/citizen
data. Extends the existing template-management and pdf-generation capabilities
with a correspondence-specific workflow including batch generation for
multiple recipients.

## Architecture Overview

The feature adds:

1. **CorrespondenceController** — REST endpoints for single generation, batch
   generation, and async job status queries.
2. **CorrespondenceService** — Orchestrates template fetch → data resolve →
   huisstijl apply → render → output → register log.
3. **DataResolverService** — Resolves merge data from OpenRegister objects
   (zaak, persoon, adres) by register/schema/UUID reference.
4. **BatchCorrespondenceJob** — Nextcloud queued background job for large
   batches (> 10 recipients).
5. **Correspondence register schema** — OpenRegister schema persisting each
   generated letter's metadata (template, recipient, date, case reference,
   format, status) in the `document` register.
6. **Huisstijl schema** — OpenRegister schema for organisational branding
   (logo, headerHtml, footerHtml, defaultMargins).
7. **CorrespondenceIndex.vue** — Frontend view under Sjablonen > Brieven &
   correspondentie (ADR-001 placement).
8. **Correspondence Pinia store** — Client-side state for template selection,
   data refs, options, batch management, and job polling.

## Placement (ADR-001)

> Sjablonen > Brieven & correspondentie

## API Endpoints

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | /api/correspondence/generate | NoAdminRequired | Generate single letter |
| POST | /api/correspondence/generate/batch | NoAdminRequired | Batch for N recipients |
| GET | /api/correspondence/jobs/{jobId} | NoAdminRequired + NoCSRFRequired | Poll async job |

## Request / Response shapes

### POST /api/correspondence/generate
```json
{
  "templateId": "uuid",
  "dataRefs": [{"register": "brp", "schema": "persoon", "id": "uuid"}],
  "options": {"format": "pdf|docx|html|email", "huisstijlId": "uuid", "caseReference": "Z/2026/001"},
  "filename": "brief.pdf"
}
```
Response: binary (PDF/DOCX) or JSON `{content, format, warnings}` for html/email.

### POST /api/correspondence/generate/batch
```json
{
  "templateId": "uuid",
  "recipientIds": ["uuid", "…"],
  "options": {"register": "brp", "schema": "persoon", "format": "pdf"}
}
```
≤ 10 recipients: synchronous `{results, total, completed, errors}`.
> 10 recipients: asynchronous 202 `{jobId, status, totalRecipients}`.

### GET /api/correspondence/jobs/{jobId}
```json
{"jobId": "uuid", "status": "queued|processing|completed", "total": 50, "completed": 42, "errors": 2}
```

## Declarative-vs-imperative decision

Correspondence logging maps to a new OR object save (not an
`x-openregister-lifecycle` block) because the correspondence record is written
exactly once at generation time — there is no state-machine transition. The
huisstijl lookup and the job status tracking are also imperative because they
involve side-effectful I/O (LibreOffice for DOCX, IAppConfig for job state)
that falls outside the seven schema-extension categories.

## Data flow

```
POST /api/correspondence/generate
  → CorrespondenceController::generate()
    → CorrespondenceService::generate()
      → TemplateService::getTemplate(id)
      → DataResolverService::resolve(dataRefs)
      → CorrespondenceService::loadHuisstijl(huisstijlId?)
      → CorrespondenceService::renderWithHuisstijl(content, data, huisstijl)
      → CorrespondenceService::produceOutput(html, format, pdfOptions)
      → CorrespondenceService::logCorrespondence(...)
    ← {content, format, warnings, registerEntry}
  ← DataDownloadResponse (pdf/docx) | JSONResponse (html/email)
```

## Acceptance criteria verification

| AC | Where verified |
|----|----------------|
| 1. Single letter with merge fields | CorrespondenceServiceTest::testGeneratePdf |
| 2. 50-recipient batch | CorrespondenceServiceTest::testBatchAsyncForLargeList |
| 3. DOCX output | CorrespondenceControllerTest::testGenerateReturnsJsonForHtmlFormat |
| 4. Register entry created | CorrespondenceServiceTest::testCorrespondenceLogging |
| 5. Huisstijl applied | CorrespondenceServiceTest::testGeneratePdf (huisstijl path) |
