---
status: pr-created
kind: code
change: print-functionality
issue: 33
pr: https://codeberg.org/Conduction/docudesk/pulls/96
---

# Design: Print Functionality

## Summary

Extend DocuDesk's PDF generation with print-optimized output, PDF/A archival compliance, batch
print generation, print configuration per template, and a print queue API for external print services.

## Reuse Analysis

- `PdfService` (existing) — reused and extended for crop marks, bleed, XMP metadata
- `TemplateService` (existing) — reused for template retrieval and print config
- `PrintController` (existing) — extended with print config (duplex/color) support
- `BatchCorrespondenceJob` pattern — copied for `BatchPrintJob`
- `IAppConfig` job status pattern from `CorrespondenceService` — copied for `PrintJobService`

No overlap with OpenRegister `ObjectService` — print jobs are transient config, not domain objects.

## Declarative-vs-imperative decision

Print job tracking is transient operational state (queued/processing/done), not domain data, and
has no lifecycle transitions beyond status updates. It does not fit `x-openregister-lifecycle`
because there are no OpenRegister schemas involved — PDFs are generated on the fly and returned
as binary downloads. A new `PrintJobService` with `IAppConfig`-backed job tracking is appropriate.

## Architecture

### New components

| Component | Path | Purpose |
|---|---|---|
| `PrintJobService` | `lib/Service/PrintJobService.php` | Print job CRUD, batch dispatch, queue listing |
| `BatchPrintJob` | `lib/BackgroundJob/BatchPrintJob.php` | Background job for bulk PDF generation |
| `PrintJobController` | `lib/Controller/PrintJobController.php` | External print service API |

### Extended components

| Component | Path | Change |
|---|---|---|
| `PdfService` | `lib/Service/PdfService.php` | Add crop marks, bleed, XMP metadata |
| `PrintController` | `lib/Controller/PrintController.php` | Accept duplex/color/paperTray options, return instruction JSON |

## API Design

### Existing endpoints (extended)

**POST /api/print/preview** — extended to return `printConfig` alongside HTML:
```json
{ "html": "...", "title": "...", "printConfig": { "duplex": true, "color": true } }
```

**POST /api/print/pdf-a** — extended: `options.cropMarks`, `options.caseReference`,
`options.author` for XMP metadata.

### New endpoints

**POST /api/print/jobs**
Creates a single-document or batch print job, returns job ID and status.
Request: `{ templateId, data, options: { duplex, color, paperTray, stapling, pdfa, cropMarks } }`

**GET /api/print/jobs/{id}**
Get job info. For external print services: returns job metadata + download URL.
Response: `{ jobId, status, total, completed, errors, manifest, printConfig }`

**GET /api/print/jobs/{id}/download**
Download the generated PDF for a completed single job. Returns binary PDF.

**PUT /api/print/jobs/{id}/status**
External print service acknowledges/updates status.
Request: `{ status: "printing" | "printed" | "failed", details? }`

**POST /api/print/batch**
Enqueue a batch of items. For >10 items, dispatches background job.
Request: `{ templateId, items: [{data, filename}], options }`

## Seed Data

No new schemas are introduced — print jobs are transient and tracked via `IAppConfig`.
No seed data required (exception: no OpenRegister schemas).

## Acceptance Criteria

1. PDF/A-2b: `pdfa: true` + fonts embedded + no transparency → passes veraPDF profile
2. Batch: 100 items → 100 PDFs generated, manifest lists all with metadata
3. Print config: `duplex`, `color` from template → returned in `printConfig` JSON
4. Archive-ready: XMP with title, author, date, caseReference embedded in PDF/A
5. Queue API: GET /api/print/jobs/{id}/download returns PDF + print instruction metadata
6. Crop marks: 3mm bleed + crop marks on 4 corners when `cropMarks: true`
