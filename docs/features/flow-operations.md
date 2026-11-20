# Flow operations

## Overview

Filinq offers four operations to Nextcloud Flow (Settings → Flow), so a rule
such as "when a file is created in /Inkomend, run OCR" processes documents
without anyone opening Filinq. Flow owns the triggers ("File created",
"Tag assigned", ...) and the rule checks; Filinq only ships the operations.

| Operation | What it does | Options |
|-----------|--------------|---------|
| Anonymise document | Detects the personal data and puts the document in the anonymisation review queue. It never produces the anonymised copy and never marks the document reviewed. | none |
| Run OCR | Recognises the text of a scan or image with Filinq's OCR settings; the OCR result records that a Flow rule started it. | none |
| Convert to PDF/A | Saves a PDF/A-3 copy named `<name>.pdfa.pdf` next to the original, or in a target folder in the owner's own files. The original is never changed. | target folder |
| Run validation checks | Checks the document against the validation profile of a document type and records the findings. | document type |

## How a run works

1. The rule matches during the upload or tag request. Filinq only records a
   run in status *queued* and queues a background job; nothing is processed
   in that request.
2. On the next cron run the job starts the run (*running*), opens the file as
   its owner and runs the operation.
3. The run ends *succeeded*, *failed* (with a reason) or *skipped* (nothing to
   do, for example a file OCR does not apply to).

A second trigger for the same file and operation while a run is queued or
running is not queued again. A file a Filinq operation wrote (the PDF/A copy)
never starts another run: its event ends in a *skipped* run with reason
`own_output`.

## What is recorded

Every run is a `flowOperationRun` object: operation, file, owner, status,
reason, trigger, start and end time, a result summary and the produced file.
The summary holds counts and verdicts only, never document content: entity
counts per type for anonymisation, confidence and text length for OCR,
findings for validation.

The **Flow runs** page in Filinq's settings menu lists recent runs. A failed run
notifies the file owner through a Nextcloud notification naming the file, the
operation and the reason.

## Gates that still apply

- Anonymisation runs the same prohibition gate: a document under an absolute
  publication prohibition ends the run *failed*, and a gate that cannot be
  asked counts as refusing.
- OCR follows the admin settings: OCR switched off or Tesseract missing ends
  the run *failed* with `ocr_disabled` or `tesseract_unavailable`.
- A document type that has no validation profile cannot be saved in a rule.
- Converting a non-PDF document is refused when a PDF of the same name already
  sits next to it, so a run never replaces a file a person made.

Filinq is listed in the app store's *workflow* category as well as
*organization*.
