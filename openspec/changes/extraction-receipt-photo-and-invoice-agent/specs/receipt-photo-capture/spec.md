# receipt-photo-capture Specification (delta)

## Purpose

A receipt or invoice photographed on a phone is stored and read in one
call. The camera control lives on the consumer's screen; filinq checks,
stores and reads. Matrix row `exp-receipt-photo` (shillinq matrix, owned
by filinq).

## ADDED Requirements

### Requirement: One call stores a photo and starts the read (REQ-ERP-001)

Filinq MUST offer `POST api/extraction/capture` for an authenticated
user, taking a multipart `file` with `docType` (`receipt` or
`supplier-invoice`), `sourceApp` and `callbackEvent`. It MUST store the
file in the session user's own Files under `Receipts/<yyyy>/<mm>/`, MUST
then run the financial extraction on the stored file exactly as
`POST api/extraction/financial` does, MUST record the stored file id as
`capturedFileId` on the `financialExtraction`, and MUST answer 201 with
the extraction. When `callbackEvent` is true the completed event MUST
fire as it does today.

Rows: `exp-receipt-photo` (shillinq matrix)

#### Scenario: An employee photographs a parking receipt

- GIVEN an employee on a phone who takes a photo of a parking receipt through shillinq's receipt screen
- WHEN shillinq posts the photo to `api/extraction/capture` with `docType` `receipt` and `callbackEvent` true
- THEN the photo is in the employee's Files under `Receipts/2026/09/`, the answer carries the total and the date read from it, and the completed event reaches shillinq
- @e2e exclude the camera control is shillinq's screen; covered here by a Newman multipart request and PHPUnit on `ReceiptCaptureService`, and in shillinq by its own Playwright test

### Requirement: The photo is checked and turned upright before it is read (REQ-ERP-002)

The route MUST check the file type from its bytes through the upload
policy, MUST accept JPEG, PNG, HEIC and PDF up to 20 MB, and MUST refuse
anything else with 415. It MUST turn a photo upright according to its
EXIF orientation before reading it. It MUST convert HEIC to JPEG when the
server can; when it cannot, it MUST answer 415 "This photo format cannot
be read on this server. Take the photo as JPEG." and MUST NOT store the
file.

Rows: `exp-receipt-photo` (shillinq matrix)

#### Scenario: A sideways photo is read correctly

- GIVEN a JPEG receipt photo taken with the phone held sideways, EXIF orientation 6
- WHEN it is posted to the capture route
- THEN the stored image is upright and the extracted total matches the receipt
- @e2e exclude image handling on bytes; covered by PHPUnit with a rotated fixture photo

#### Scenario: A file that is not a photo or a PDF is refused

- GIVEN a file named `bon.jpg` whose bytes are a ZIP archive
- WHEN it is posted to the capture route
- THEN the answer is 415 and nothing is stored in the user's Files
- @e2e exclude API contract; covered by a Newman request and PHPUnit
