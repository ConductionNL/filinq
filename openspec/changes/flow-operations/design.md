# Design: flow-operations

## Context

Verified against the Nextcloud server checkout
(`workspace/server/lib/public/WorkflowEngine/`) and DocuDesk HEAD
`9cc14407`:

- The public Flow contract is `OCP\WorkflowEngine\IOperation` /
  `ISpecificOperation` (adds `getEntityId()`), registered by listening to
  `OCP\WorkflowEngine\Events\RegisterOperationsEvent` and calling
  `$event->registerOperation($operation)`. Scopes are
  `IManager::SCOPE_ADMIN` and `SCOPE_USER`.
- The file entity (`OCA\WorkflowEngine\Entity\File`) dispatches exactly
  the trigger events the market asks for: `File created`
  (`\OCP\Files::postCreate`), `File updated` (`postWrite`), `Tag assigned`
  (`MapperEvent::EVENT_ASSIGN`), plus rename/delete/touch/copy. Rule
  matching (folder checks, tag checks, MIME checks) is engine-owned via
  `IRuleMatcher::getMatchingOperations()` — operations implement no
  custom checks.
- `IOperation::onEvent()` runs synchronously inside the triggering
  request; the community reference implementations (workflow_ocr,
  workflow_pdf_converter) therefore enqueue a background job from
  `onEvent()` and do the work in cron. We copy that shape.
- DocuDesk has no `OCP\WorkflowEngine` reference at HEAD; existing
  QueuedJob precedent is `lib/BackgroundJob/` (five jobs, e.g.
  `FolderExtractionJob`).
- The four delegated engines all exist at HEAD:
  `AnonymizationService::extractAndDetectEntities(int $fileId)` /
  `anonymizeDocument(...)` (prohibition gate runs first, throws
  `ProhibitionGateException`), `OcrService::processFile(int $fileId)`
  (+ `needsOcr()`, `isOcrEnabled()`, availability probing),
  `PdfConversionService::convertToPdf(File $source, array $opts)`,
  `Pdfa3ConversionService::convertExistingPdf(File $source, ...)`,
  `DocumentValidationService::validate(File $file, array $record,
  ?string $documentType)` with `resolveProfile()` + `aggregate()`.
- `appinfo/info.xml` category is `organization` only.
- Notification conventions: DocuDesk declares notifications in the
  verified `x-openregister-notifications` dialect on register schemas
  (`docudesk-notifications` capability, status done); recipients are
  confirmed NC uids/groups/object-ACL only, and "fire only when status =
  X" conditions are approximated (`created`, or `scheduled` + `filter`)
  until the engine grows field-change conditions.

## Goals / Non-Goals

**Goals:**

- Give DocuDesk's four automatable engines a Flow operation each, with
  the engine owning triggers and matching.
- Keep every run observable: a persisted processing-log object per run,
  failure notifications, and a runs listing.
- Be discoverable in the Flow app-store category.

**Non-Goals:**

- No new processing engines and no engine behaviour changes — operations
  are thin dispatch shells.
- No custom Flow checks (folder/tag/MIME checks are engine-owned).
- No bulk orchestration — a flood of matched files is bounded by the
  queued-job model; municipal-scale batching remains `redaction-at-scale`.
- No Flow operation for signing, publication, or destruction — acts with
  legal effect stay deliberate human actions (same posture as
  `docudesk-mcp-adoption`'s refusals).
- No n8n/OpenConnector integration — this change is NC-native Flow only.

## Decisions

### D1 — Four `ISpecificOperation` classes bound to the file entity

One class per operation under `lib/Flow/Operation/`
(`AnonymizeDocumentOperation`, `RunOcrOperation`, `ConvertToPdfaOperation`,
`RunValidationOperation`), each `ISpecificOperation` returning the file
entity id, available in `SCOPE_ADMIN` and `SCOPE_USER` (operations act on
files the triggering user can already reach; the services re-check access
server-side). A single `RegisterFlowOperationsListener` (registered in
`Application::register()` for `RegisterOperationsEvent`) registers all
four. Rejected: one multiplexing operation with a mode option — four
operations render as four self-describing entries in the Flow rule
builder, which is the entire discoverability point.

### D2 — `onEvent()` enqueues; `FlowOperationJob` executes (workflow_ocr shape)

`onEvent()` resolves the file node from the event, calls
`IRuleMatcher::getMatchingOperations()`, and for each match enqueues one
`FlowOperationJob` (QueuedJob) with `{operation, fileId, ownerUserId,
operationConfig}` and creates the `flowOperationRun` object in status
`queued`. Nothing heavier runs in the request: OCR rasterises at 300 DPI,
LibreOffice conversion takes seconds, and a synchronous operation would
stall every upload in a matched folder. Folders and non-file entities are
ignored. Duplicate protection: an identical `{operation, fileId}` pair
with a run already `queued`/`running` is not enqueued twice (the run
object is the dedupe key).

### D3 — Loop guard: operation outputs never re-trigger flows

`ConvertToPdfaOperation` (and any operation that writes an output file)
creates files, and `\OCP\Files::postCreate` would re-enter the engine —
the classic converter-loop defect. Guard, checked in `onEvent()` before
enqueueing: (a) output artifacts carry a deterministic naming/location
convention (see D5) and a `flowOperationRun` with
`producedFileId` records every artifact this app created; a candidate
fileId matching a recorded `producedFileId` is skipped with run status
`skipped`, reason `own_output`; (b) re-processing the same
`{operation, fileId}` while a run is in flight is deduped per D2. This is
data-driven (register lookup), not filename-pattern guessing.

### D4 — Operation semantics reuse the existing gates, never bypass them

- **Anonymise document** = intake, not export. The job runs
  `extractAndDetectEntities()` (which already hangs grondslag proposals
  and policy matching) so the document lands in the review queue.
  Anonymise-commit (`anonymizeDocument()`) runs only when the
  `anonymization-review-workbench` checked gate permits it for that file
  (i.e. a valid `documentReview` exists — realistically re-runs); the
  operation never creates or updates `documentReview`, never passes
  `acknowledgedOverrides`, and a `ProhibitionGateException` fails the run
  with the gate reason in the log. Where the workbench change has not
  landed, the operation is intake-only.
- **Run OCR** delegates to `OcrService::processFile()` exactly as the
  wave-1 route does: disabled toggle → run fails with reason
  `ocr_disabled`; missing Tesseract → `tesseract_unavailable`;
  non-candidate MIME (`needsOcr()` false) → status `skipped`. The
  persisted `ocrResult` gets `triggeredBy: "flow"` — an additive enum
  value on wave-1's `manual | fallback`; coordination note recorded here
  because both changes are in-flight (the union-merged register carries
  all three values).
- **Convert to PDF/A** converts PDFs via
  `Pdfa3ConversionService::convertExistingPdf()`; non-PDF office/text
  sources go through `PdfConversionService::convertToPdf()` first. Output
  is a NEW sibling file (`<basename>.pdfa.pdf`, unique-named via the
  existing `resolveUniqueFileName` pattern) or, when configured, written
  into a target subfolder; the source is never modified or replaced.
- **Run validation checks** calls `DocumentValidationService::validate()`
  with the operation-configured document-type profile (option `documentType`,
  fed to `resolveProfile()`); findings and the `aggregate()` verdict land
  in the run's `resultSummary`. A failed aggregate (`error`) marks the run
  `failed` so the notification rule fires; `warning` stays `succeeded`
  with findings attached.

### D5 — Operation options are minimal and validated

`validateOperation()` (the `IOperation` contract) rejects malformed
configs with `UnexpectedValueException`, so a broken rule cannot be
saved. Options per operation: Anonymise — none v1; Run OCR — none v1
(admin settings drive languages/DPI, same as every other OCR surface);
Convert to PDF/A — optional `targetFolder` (relative path, created if
absent) with empty = sibling output; Run validation — `documentType`
(string, profile key). The Flow rule-builder UI for these options ships
as a small operator-registration script loaded via
`OCP\WorkflowEngine\Events\LoadSettingsScriptsEvent`
(`window.OCA.WorkflowEngine.registerOperator`), the same wiring every
Flow app uses.

### D6 — `flowOperationRun` is the processing log (declarative dialects)

New register schema `flowOperationRun` (additive, union-merge):
`operation` (enum `anonymize|ocr|pdfa|validate`), `fileId`, `fileName`,
`ownerUserId` (confirmed NC uid — the file owner at trigger time),
`status` (`queued|running|succeeded|failed|skipped`), `statusReason`,
`triggerEvent` (e.g. `postCreate`, `tag_assigned`), `startedAt`,
`finishedAt`, `resultSummary` (object: op-specific, e.g. entityCount /
confidence / findings / producedFileId), `producedFileId`,
`errorMessage`. Declared with `x-openregister-lifecycle` (canonical
`initial: queued`; transitions queued→running→succeeded|failed, and
queued→skipped) and `x-openregister-archival` retention `P1Y`
(operational processing log, same Archiefwet cat. 1.2 rationale as
`anonymizationResult` in the anonymization spec). Failure notification is
declared in the verified `x-openregister-notifications` dialect on this
schema, recipient `{"kind": "field", "field": "ownerUserId"}` — a
confirmed NC uid, satisfying the `docudesk-notifications` staff-safe
rule. Because the engine cannot yet express "only when status becomes
failed", the rule uses the documented approximation (`scheduled` +
`filter` on `status: failed`), and the precise field-change form is
deferred to `notification-updated-field-change-condition` exactly as the
`docudesk-notifications` capability prescribes.

`resultSummary` for anonymise runs carries counts only (entities per
type, proposals applied, policy matches) — NEVER detected entity values;
entity text lives where it already lives (OR entity relations behind the
review UI), and a notification/log surface must not become a second PII
store (same data-minimisation argument as wave-1's `ocrResult`).

### D7 — Declarative vs imperative (ADR-031)

- `flowOperationRun` schema + lifecycle + archival + notification rule:
  **declarative** register JSON.
- Operation classes, listener, queued job: **imperative** — event-driven
  orchestration of processing engines is a valid ADR-031 exception
  category (same ruling as wave-1 D6).
- ADR-011 check: no new utilities — file resolution, unique naming,
  conversion, validation and OCR all reuse existing service seams.
- ADR-001: run objects persisted via the existing AppHost/ObjectService
  pattern; no custom tables.

### D8 — Distribution: the `workflow` app-store category

`appinfo/info.xml` gains `<category>workflow</category>` next to
`organization` (multiple categories are legal and common). This is the
ecosystem-research insight made concrete: admins searching the store's
workflow category find workflow_ocr and workflow_pdf_converter today;
DocuDesk belongs on that shelf.

## Seed Data

```json
// flowOperationRun — an OCR run triggered by "File created" in /Inkomend
{ "operation": "ocr",
  "fileId": 812006,
  "fileName": "scan-bezwaarschrift.pdf",
  "ownerUserId": "w.devries",
  "status": "succeeded",
  "triggerEvent": "postCreate",
  "startedAt": "2026-07-17T09:12:04Z",
  "finishedAt": "2026-07-17T09:12:31Z",
  "resultSummary": { "confidence": 88.2, "textLength": 3120 },
  "errorMessage": null }

// flowOperationRun — a failed PDF/A conversion (encrypted source)
{ "operation": "pdfa",
  "fileId": 812007,
  "fileName": "besluit-2026-114.pdf",
  "ownerUserId": "w.devries",
  "status": "failed",
  "statusReason": "conversion_error",
  "triggerEvent": "tag_assigned",
  "startedAt": "2026-07-17T09:14:00Z",
  "finishedAt": "2026-07-17T09:14:09Z",
  "resultSummary": {},
  "errorMessage": "Source PDF is encrypted; PDF/A conversion refused" }
```

Seed task: the two run objects above so the Flow-runs listing renders on
a clean install (nil-consequence fixture fileIds, self-evidently fake).

## Security Considerations

- Operations execute in the background job under the file owner's
  context; the delegated services re-apply their own access checks — UI
  rule visibility is not authorization.
- The anonymise operation cannot bypass the prohibition gate (no
  `acknowledgedOverrides` channel exists in the operation config) and
  cannot mark documents reviewed.
- Notification recipients are confirmed NC uids only (file owner) — never
  external addresses (`docudesk-notifications` rule).
- `validateOperation()` rejects malformed configs at rule-save time, so a
  stored rule cannot smuggle arbitrary paths; `targetFolder` is resolved
  relative to the owner's own folder tree only.

## Risks / Trade-offs

- [A matched folder receiving hundreds of files floods the job queue] →
  one QueuedJob per file is exactly NC's designed load shape; dedupe (D2)
  prevents double-enqueue; municipal-scale bulk stays in
  `redaction-at-scale`.
- [Loop: PDF/A output re-triggers "File created"] → D3 loop guard via
  `producedFileId`; the e2e suite pins it.
- [Run OCR depends on wave-1 landing] → depends_on declared; the
  operation is not registered when `OcrService` reports OCR unavailable,
  and the run fails flagged (never silent) when disabled.
- [Notification approximation may over- or under-fire until the engine
  grows field-change conditions] → same accepted trade-off the
  `docudesk-notifications` capability already documents; the runs listing
  is the authoritative surface.
- [`SCOPE_USER` lets any user automate anonymisation intake on their own
  files] → intake creates review work but exports nothing; the checked
  gate and prohibition gate hold. If operationally noisy, admins can
  restrict via the engine's own scope configuration.

## Migration Plan

1. Register JSON: add `flowOperationRun` (additive, union-merge;
   re-validate JSON after merge).
2. Ship listener + four operations + queued job + operator-registration
   script (independently valuable: Flow rules become buildable).
3. Ship the Flow-runs listing + failure notification rule.
4. info.xml category addition rides the same release.
5. Rollback: deleting a Flow rule stops triggering; the operations are
   inert without rules; no data migration to unwind.

## Open Questions

- Should the anonymise operation optionally tag the file (e.g.
  `awaiting-review`) so a second Flow rule can chain on it? Deferred —
  chaining via tags is engine-idiomatic but needs a loop-safety review
  first.
- Per-operation run-retention: is `P1Y` right for validation runs that
  carry findings referenced by audits? Provisional `P1Y` pending
  selectielijst-manager sign-off (same placeholder pattern as
  `financialExtraction`).
