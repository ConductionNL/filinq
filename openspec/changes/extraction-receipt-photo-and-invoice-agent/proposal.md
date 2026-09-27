---
kind: code
depends_on: []
---

# Proposal: extraction-receipt-photo-and-invoice-agent

Matrix rows `exp-receipt-photo` and `pur-ai-agent` from shillinq's matrix
(`openspec/parity/capabilities.json` in ConductionNL/shillinq), both rated
partial there and owned by filinq. Written in the OpenSpec pass of 27
September 2026.

## Why

An employee pays for parking, takes the receipt home and forgets it. The
market answer is to photograph it on the spot and have it read. filinq
reads receipts, but only a file that is already in Nextcloud.

`POST api/extraction/financial` (`appinfo/routes.php:276`) takes a
`fileId` or a `documentUri` (`FinancialExtractionService::resolveRequestParams()`,
`lib/Service/FinancialExtractionService.php:292`) and answers 400 "Either
fileId or documentUri is required" without one. Nothing in filinq takes a
photo and stores it first. shillinq's receipt screen can only re-request a
read for a receipt that already has a source document, and has no camera
or upload control (shillinq matrix evidence, `src/views/ReceiptCapture.vue:573`).

A trusted supplier's invoice still needs a person. Every extraction
carries per-field confidence already (`fieldConfidence` and
`overallConfidence`, REQ-FIN-04, saved at
`FinancialExtractionService.php:244-253`), but nothing turns that into a
decision a consumer can act on. shillinq asks for a booking account
(`POST api/extraction/{id}/suggest-account`, `appinfo/routes.php:280`) and
an operator must press "Use suggestion" on every invoice.

### Matrix rows (shillinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `exp-receipt-photo` | Photograph a receipt on your phone and have it read and categorised. | partial: filinq reads the receipt and shillinq maps the category (`ExtractionPrefillService.php:207 mapReceiptFields`); no filinq or shillinq screen can take the photo |
| `pur-ai-agent` | Let an assistant process and code incoming purchase invoices for you. | partial: an account is suggested per draft and an operator must accept it; nothing processes an invoice on its own |

Both rows come from shillinq's matrix and are owned by filinq. The missing
halves this change specifies:

- `exp-receipt-photo`: a phone-friendly capture that takes the photo and
  starts the read, where today no filinq or shillinq screen can take one.
- `pur-ai-agent`: per-field confidence and an unattended verdict on each
  extraction, so shillinq can let a trusted supplier invoice pass without a
  person. Per-field confidence is built (REQ-FIN-04); the verdict is not.
  The booking itself stays shillinq's.

### Competitors rated yes

For `exp-receipt-photo`:

- Exact Online: "Met Scan & Herken scan je in een handomdraai bonnen en
  facturen met je smartphone of tablet via app of e-mail". Evidence:
  https://www.exact.com/nl/producten/boekhouden/scan-herken
- Moneybird: "Maak een foto van een bon ... Moneybird herkent bedrag, btw
  en leverancier en boekt de meeste facturen zelf". Evidence:
  https://www.moneybird.nl/functies/ and
  https://helpcenter.moneybird.nl/nl/articles/207939-scan-en-herken-voor-inkomende-documenten
- SnelStart: "Onbeperkt bonnen scannen via app" and "Fotografeer (of scan)
  ze ... worden je inkoopfacturen en bonnen automatisch ingeboekt".
  Evidence: https://www.snelstart.nl/ondernemer/inzicht and
  https://www.snelstart.nl/ondernemer/instap
- Odoo: `module_hr_expense_extract` "Send bills to OCR to generate
  expenses", and "Upload receipts" with digitisation. Evidence:
  https://www.odoo.com/documentation/19.0/applications/finance/expenses.html

For `pur-ai-agent`:

- Exact Online: "Purchase Agent: Automatische verwerking van
  inkoopfacturen. Herkent afwijkingen in inkoopfacturen en doet slimme
  suggesties om transacties op de juiste grootboekrekening te plaatsen".
  Evidence: https://www.exact.com/nl/producten/boekhouden/features-en-prijzen
- Moneybird: "leest Moneybird de bijlage en zet het document zo volledig
  mogelijk klaar", and "Automatisch opslaan" for contacts whose last five
  documents scored 95% or higher. Evidence:
  https://helpcenter.moneybird.nl/nl/articles/677276-de-inkoopworkflow-instellen

## What changes

- A capture route that takes a photo or a scan straight from a phone:
  filinq checks what the bytes are, turns the photo upright, stores it in
  the user's Files under `Receipts/<year>/<month>/`, and starts the read.
  The answer is the extraction, and the usual completed event fires.
- Every extraction gets a verdict for unattended processing: `pass` or
  `review`, with the reasons. `pass` needs the key fields present, every
  one at or above a confidence the admin sets, the totals reconciled, the
  checksums valid and no earlier invoice with the same number from the
  same supplier.
- The verdict is stored on the extraction and sent as a sibling event, so
  the completed event's contract stays as it is.
- The admin settings get the confidence a `pass` needs (default 0.9).

## Capabilities

### New capabilities

- `receipt-photo-capture`: a photo or scan taken on a phone, stored and
  read in one call.

### Modified capabilities

- `financial-document-field-extraction`: each extraction carries an
  unattended verdict with its reasons, stored and sent as a sibling event.

## Impact

- `lib/Settings/filinq_register.json`: `financialExtraction` gains
  `unattendedVerdict`, `verdictReasons`, `verdictThreshold` and
  `capturedFileId`. Register version bump.
- New `lib/Service/Extraction/ReceiptCaptureService.php`, route
  `POST api/extraction/capture`.
- `lib/Service/FinancialExtractionService.php`: compute and store the
  verdict. New `lib/Service/Extraction/UnattendedVerdict.php` and
  `lib/Event/ExtractionVerdictEvent.php`.
- `src/views/settings/Settings.vue`: the threshold field.
- `docs/features/`: a section on the capture route and the verdict.

## Out of scope

- A camera screen in filinq. The archived design of
  `financial-document-field-extraction` records the non-goal "No DocuDesk
  UI" because "the scan-en-herken screen lives in shillinq". The camera
  control sits on shillinq's receipt screen and calls this route.
- Booking an invoice. filinq says whether an extraction can be trusted;
  shillinq decides whether to book it.
- A native phone app. A phone browser opens the camera from a file input.
- Reading a receipt from an e-mail. That is the intake channels' work.

## Cross-app dependencies

- shillinq: a "Photograph a receipt" entry on its receipts screen, a file
  input that opens the phone camera and posts to `POST
  api/extraction/capture` with `docType` `receipt`, `sourceApp` and
  `callbackEvent`; its existing `ExtractionCompletedListener` then makes
  the draft.
- shillinq: listen to `nl.conduction.filinq.extraction.verdict` and decide,
  per supplier and with its own history, whether a `pass` invoice is booked
  without a person.
