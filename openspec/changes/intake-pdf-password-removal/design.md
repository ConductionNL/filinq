# Design: intake-pdf-password-removal

Kind: code. One service, one route, one action in two places.

## Context

Read at development `2088cc1f`.

- `lib/Service/Validation/DocumentFileInspector.php:164` `isPdfEncrypted()`
  looks for `/Encrypt` in the bytes of a file that starts with `%PDF`.
- `lib/Service/DocumentValidationService.php:306` `encryptionFindings()`
  turns that into the finding `pdf-encrypted` with the message "The PDF is
  encrypted or password-protected and cannot be anonymised." (:323).
- `src/components/ValidationFindingsPanel.vue:96` labels the finding
  "Encrypted PDF". The panel is shown by `src/modals/ValidationResultModal.vue`,
  opened from `src/views/myDocuments/MyDocumentsIndex.vue:192`. The panel
  already emits one follow-up action, `ocr` (`ValidationResultModal.vue:22`,
  handled at `MyDocumentsIndex.vue:768 onOcrRequested()`).
- `src/views/intake/IntakeIndex.vue:62` renders row actions (assign at :67,
  reject at :73) on a `CnIndexPage` over `intakeDocument` objects.
- `lib/Service/OcrService.php:125` `isTesseractAvailable()` probes a binary
  with `exec('tesseract --version')`; `src/views/settings/Settings.vue:266`
  shows its status.
- `lib/Service/FinalDocumentService.php:289` `isFinal()` tells whether a
  document is frozen.
- composer requires `mpdf/mpdf` and `setasign/fpdf`; neither can open an
  encrypted PDF with a user password. There is no decryption code in `lib/`.

New: `PdfPasswordRemovalService`, the route, the dialog.

## Goals / Non-goals

Goals: unlock one PDF with a password the clerk types; keep the original;
never keep the password.

Non-goals: batch unlocking, password recovery, certificate encryption.

## Decisions

### D1. qpdf, probed, absent-safe

`qpdf --password=<pw> --decrypt <in> <out>` with the password passed on
stdin through `--password-file=-`, so it never shows in a process list.
The service probes `qpdf --version` like `isTesseractAvailable()` and
answers 503 with "qpdf is not installed on this server" when it is
missing. Alternative considered: a PHP library. None in the lock file can
decrypt, and adding one for a single action costs more than a binary the
admin already installs next to Tesseract.

### D2. A copy, never an overwrite

The unlocked file is written beside the original as
`<name> (unlocked).pdf` through Nextcloud's file API, in the same folder,
owned by the same user. The original stays. Alternative considered: a new
version of the same file. Rejected, because a version replaces what the
record points at, and the arrived file is evidence of what arrived.

### D3. The password lives for one call

The controller reads the password from the request body, passes it to the
service, and the service writes it to qpdf's stdin. It is not put in a log
context, an exception message, an audit entry or an OpenRegister object.
A wrong password answers 422 "The password is not correct" without saying
anything else about the file.

### D4. Validation runs again on the copy

After the copy is written the service runs `DocumentValidationService` on
it and returns the findings, so the clerk sees at once whether the copy
has a text layer.

### D5. Intake keeps its link

For an intake document the copy's file id is written to the
`intakeDocument` as `file`, and the arrived file id moves to a new
property `lockedOriginal`, so the record still says what came in.

### D6. Final documents are refused

`isFinal()` true answers 409 "This document is final and cannot be
changed". A final document is not edited, even into a copy on its record.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| unlocking | imperative service | a binary call on file bytes, no object lifecycle |
| intake file swap | imperative write through `ObjectService` | one property update on an existing object |
| notification | none | the clerk who unlocks is the one who asked |

## Seed data

`intakeDocument` gains `lockedOriginal` (integer, file id). The seed adds
one intake document whose `lockedOriginal` is set, so the property shows
in the demo inbox.

## Risks / trade-offs

- qpdf missing on a host. The action says so and the admin page shows it.
- A clerk unlocks a file they should not read. The action checks read
  access on the file first and uses the session user, never a payload id.

## Open questions

- Does the NLdesign demo image ship qpdf? If not, the docs name the
  package to install.
