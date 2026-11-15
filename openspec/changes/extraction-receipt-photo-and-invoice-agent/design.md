# Design: extraction-receipt-photo-and-invoice-agent

Kind: code. One route that stores and reads a photo, and one verdict on
every extraction, stored and sent as a sibling event.

## Context

Read at development `2088cc1f`.

- `appinfo/routes.php:276` `extraction#financial` (`POST
  api/extraction/financial`), :277 corrections, :280
  `glAccountSuggestion#suggestAccount`.
- `lib/Controller/ExtractionController.php:91` `financial()`, marked
  `@NoAdminRequired` in its docblock, reads the request params and calls
  `extractFinancial()`.
- `lib/Service/FinancialExtractionService.php`: `VALID_DOC_TYPES`
  `receipt`, `supplier-invoice` (:76); `extractFinancial()` (:236) resolves
  the file (:292, 400 without `fileId` or `documentUri` at :300), gets text
  (`resolveText()`, :985, embedded PDF text or Tesseract), runs heuristics
  (`runExtraction()`, :430, which also reports `reconciled`, :488) and the
  optional model step (:509), saves `financialExtraction` with `fields`,
  `fieldConfidence`, `overallConfidence` (:244-253), and dispatches
  `FinancialExtractionCompletedEvent` when `callbackEvent` is true (:949).
  Checksum-validated fields are locked (`lockedFields()`, :810).
- `lib/Event/FinancialExtractionCompletedEvent.php:155` `toPayload()`
  carries no extraction id and no verdict. REQ-FIN-05 says the payload is
  "exactly" its listed keys.
- `lib/Event/GlAccountSuggestedEvent.php` is a sibling event "intentionally
  NOT merged into that shipped contract" (its header; REQ-GLS-06).
- `lib/Service/UploadPolicyService.php:84` `check()` reads a file's type
  from its bytes against the active upload policy.
- `lib/Service/OcrService.php` converts PDF pages with Imagick (:354-375)
  and has no orientation handling.
- `financialExtraction` (`lib/Settings/filinq_register.json`, 1.0.0) is
  read-restricted to `docudesk-financial-admins`.
- The archived design of `financial-document-field-extraction`
  (`openspec/changes/archive/2026-07-12-financial-document-field-extraction/design.md:41`)
  records the non-goal "No DocuDesk UI" with the reason "the scan-en-herken
  screen lives in shillinq".

New: `ReceiptCaptureService`, the capture route, `UnattendedVerdict`,
`ExtractionVerdictEvent`, four `financialExtraction` fields, the threshold
setting.

## Goals / Non-goals

Goals: one call from a phone stores a photo and reads it; every
extraction says whether it can be trusted without a person, and why not.

Non-goals: a camera screen in filinq, booking, a native app.

## Decisions

### D1. The capture is a route; the camera sits on shillinq's screen

`POST api/extraction/capture` takes a multipart `file` plus `docType`,
`sourceApp` and `callbackEvent`. A phone browser opens the camera from a
file input with `capture`, so no app is needed. The screen that holds that
input is shillinq's, as the archived non-goal records. Alternative
considered: a filinq capture page that shillinq links to. Rejected: it
reverses a recorded decision without the lane lead's ruling, and puts a
bookkeeping screen in the document app.

### D2. Check, turn upright, store, then read

The service checks the bytes with `UploadPolicyService::check()`, accepts
JPEG, PNG, HEIC and PDF up to 20 MB, turns a photo upright from its EXIF
orientation and converts HEIC to JPEG with Imagick. It stores the file in
the session user's Files as `Receipts/<yyyy>/<mm>/<date>-<time>.<ext>` and
then calls `extractFinancial()` with the new file id. Without HEIC support
in Imagick a HEIC photo answers 415 "This photo format cannot be read on
this server. Take the photo as JPEG." Alternative considered: read the
photo without storing it. Rejected: the receipt is the evidence for the
booking and must stay findable.

### D3. The verdict is computed once, at extraction time, and kept

`UnattendedVerdict` answers `pass` only when all hold: `supplierName` and
one of `supplierIban`, `supplierKvk`, `supplierVatId` are present;
`issueDate` and `totalIncl` are present; each of those is at or above the
threshold; the totals reconciled; no field was filled by the model alone;
and no earlier `financialExtraction` has the same supplier identity and
invoice number. Otherwise `review`, with one reason code per failed check
(`missing-field`, `low-confidence`, `not-reconciled`, `model-only`,
`possible-duplicate`). The verdict, the reasons and the threshold used are
saved on the object. Alternative considered: an `x-openregister-calculations`
field computed on read. Rejected: the verdict must say what it was when
shillinq acted, even after the admin moves the threshold.

### D4. A sibling event, not a changed contract

After the completed event, filinq dispatches
`nl.conduction.filinq.extraction.verdict` with `extractionId`,
`documentUri`, `sourceApp`, `docType`, `verdict`, `reasons` and
`threshold`. The completed event stays exactly as REQ-FIN-05 lists it,
following the precedent of `gl-account.suggested`. Alternative considered:
add the keys to the completed event. Rejected: REQ-FIN-05 says "exactly".

### D5. The threshold is an admin setting

`extraction_unattended_threshold`, default 0.9, between 0.6 and 1.0, set
on the admin settings page. The lower bound is the pipeline's own
`LOW_CONFIDENCE_THRESHOLD` (:183): a field the model may still overwrite
can never pass.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| verdict | imperative, stored at extraction time | it must record what held when a consumer acted (D3) |
| duplicate check | imperative query on `financialExtraction` | a lookup across objects at one moment |
| capture and storage | imperative service | file bytes, orientation, storage |
| notifying the consumer | typed event on the Nextcloud event bus | the existing extraction contract's channel |

## Seed data

`financialExtraction` moves to 1.1.0 with `unattendedVerdict` (enum
`pass`, `review`), `verdictReasons` (array of strings), `verdictThreshold`
(number) and `capturedFileId` (integer). No seed objects: the schema is
read-restricted and ships none today.

## Risks / trade-offs

- A `pass` on a wrong reading. The verdict never books anything; shillinq
  decides per supplier, and the threshold starts high.
- Photos hold more than the receipt, such as a face or a card number next
  to it. The photo stays in the user's own Files; filinq sends it nowhere.
- The duplicate check reads extractions of other users. It runs on the
  server and returns only a reason code, never the other extraction.

## Open questions

- Should the verdict also require the supplier to be known in shillinq?
  This change leaves supplier trust to shillinq, which holds the history.
