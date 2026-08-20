---
kind: code
depends_on:
  - ocr-trigger-surface
---

# Proposal: flow-operations

## Why

Nextcloud Flow is the platform's native no-code automation engine and a
distribution channel in its own right: the ecosystem research names
**workflow_ocr** and **workflow_pdf_converter** as long-lived community
apps whose entire value is "hang one document operation on a Flow event",
and the app store carries a dedicated Flow/workflow discovery category
where document-processing operations are found by admins who have never
heard of DocuDesk. **Stirling-PDF**'s no-code "pipelines" theme shows the
same demand outside Nextcloud: admins want *file arrives → processing
happens* without writing code. (Intelligence DB competitor rows:
`workflow-ocr`, `nextcloud-flow`, `stirling-pdf`.)

DocuDesk today has zero Flow presence, verified at HEAD `9cc14407`: no
`OCP\WorkflowEngine` reference anywhere in `lib/`, no
`RegisterOperationsEvent` listener, and `appinfo/info.xml` carries only
`<category>organization</category>`. Every DocuDesk capability is
pull-only — a human must open the app and click. That leaves the most
common municipal automation wishes unserved:

- "every scan dropped in `/Inkomend` gets OCR" — the exact use case
  workflow_ocr exists for, which DocuDesk can now serve natively because
  wave-1 `ocr-trigger-surface` gives `OcrService` an invocation surface;
- "everything tagged `woo-verzoek` enters anonymisation intake";
- "every outbound decision letter is converted to PDF/A for the archive"
  (Archiefwet posture, `pdfa3-conversion` capability exists at HEAD);
- "validate every upload against the document-type profile"
  (`document-validation-checks` capability exists at HEAD).

All four engines exist and are reachable only imperatively. Flow is the
cheapest possible automation surface: the engine owns triggers ("File
created", "Tag assigned" — verified on the server's
`OCA\WorkflowEngine\Entity\File::getEvents()`), rule matching and the
admin/user configuration UI; DocuDesk only ships operations.

## What

- **Four Flow operations**, each an `OCP\WorkflowEngine\ISpecificOperation`
  bound to the file entity, registered via a `RegisterOperationsEvent`
  listener and offered in the Flow admin/personal settings UI:
  1. **Anonymise document** — runs the anonymisation intake pipeline
     (extract, detect, grondslag proposals, policy matching) and routes
     the document into the human review queue; it NEVER sets the
     `documentReview` checked gate and never exports an unreviewed
     redaction (aligned with `anonymization-review-workbench`).
  2. **Run OCR** — delegates to the wave-1 `ocr-trigger-surface` pipeline
     (`OcrService::processFile()` + persisted `ocrResult`), respecting
     the same admin settings (enable toggle, languages, DPI).
  3. **Convert to PDF/A** — produces a PDF/A-3 sibling artifact via the
     existing conversion services; the source file is never modified.
  4. **Run validation checks** — runs
     `DocumentValidationService::validate()` with an operation-configured
     document-type profile and records the findings.
- **Asynchronous by contract**: `onEvent()` only matches and enqueues a
  queued background job per file; processing never runs inside the
  triggering request (workflow_ocr precedent). Output files produced by
  the operations are marked so they never re-trigger a flow (loop guard,
  the classic workflow_pdf_converter failure mode).
- **A processing log**: every run persists a `flowOperationRun`
  OpenRegister object (operation, file, status, timings, result summary,
  error); failures are reported to the file owner via Nextcloud
  notifications declared in the verified `x-openregister-notifications`
  dialect (per the `docudesk-notifications` capability conventions).
- **Flow-category distribution**: `appinfo/info.xml` additionally
  declares the app-store `workflow` category so DocuDesk is discoverable
  where workflow_ocr and workflow_pdf_converter are found today.

## Capabilities

### New Capabilities

- `flow-operations`: DocuDesk's Nextcloud Flow integration — the four
  registered file operations, their asynchronous execution and loop
  guard, the `flowOperationRun` processing log with failure
  notifications, and the Flow app-store category declaration.

### Modified Capabilities

- None. `ocr-trigger-surface` (wave-1, in-flight) is a dependency, not a
  modification: this change adds `flow` as a `triggeredBy` attribution on
  its `ocrResult` object — an additive enum value coordinated in
  design.md, no requirement of that change moves.

## Impact

- **Backend**: new `lib/Flow/` namespace — four operation classes, a
  `RegisterFlowOperationsListener`, and a `FlowOperationJob` (QueuedJob);
  listener registration in `lib/AppInfo/Application.php`. All processing
  delegates to existing services (`AnonymizationService`, `OcrService`,
  `PdfConversionService`/`Pdfa3ConversionService`,
  `DocumentValidationService`) — no engine work.
- **Register JSON**: new `flowOperationRun` schema (additive,
  union-merge) with lifecycle + failure-notification dialect annotations.
- **Frontend**: Flow operator registration script (loaded via
  `LoadSettingsScriptsEvent`) so the four operations render in the Flow
  rule builder with their options; a Flow-runs listing so admins can see
  recent runs and failures.
- **info.xml**: adds `<category>workflow</category>` (keeps
  `organization`).
- **No external services**: all four operations run existing local
  engines; processing stays 100% on the server.
- **Dependency**: the Run OCR operation requires wave-1
  `ocr-trigger-surface` (the `ocrResult` object and honest status
  derivation); the anonymise operation honours the
  `anonymization-review-workbench` checked gate wherever that change has
  landed, and degrades to intake-only (never auto-commit) where it has
  not.
