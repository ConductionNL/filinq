---
kind: code
depends_on: []
---

# Proposal: intake-pdf-password-removal

Matrix row `in-pdf-password` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Written in the OpenSpec pass of 27 September 2026.

## Why

A supplier sends a password-protected PDF. filinq sees the lock and stops:
`DocumentValidationService::encryptionFindings()`
(`lib/Service/DocumentValidationService.php:306`) reports "The PDF is
encrypted or password-protected and cannot be anonymised." and nothing
offers a way past it. The clerk opens the file elsewhere, prints it to a
new PDF and uploads that copy. The copy loses its link to what arrived.

The row sits in intake, filinq's core area. It also has demand and a
competitor behind it.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `in-pdf-password` | Remove the password from an encrypted PDF so it can be processed. | no: the check detects and refuses, `grep -ri 'decrypt\|qpdf\|pikepdf\|password' lib` finds no removal step |

### Demand

- changelog: https://github.com/paperless-ngx/paperless-ngx/pull/11656

### Competitors rated yes

- Paperless-ngx (source read at v3.2.1): "src/documents/bulk_edit.py:1012
  remove_password() opens the PDF with pikepdf and the supplied password
  (:1055) and saves it unprotected; exposed at /api/documents/remove_password/".
  Evidence: https://github.com/paperless-ngx/paperless-ngx/pull/11656

## What changes

- A remove-password action on the encrypted-PDF finding. The clerk types
  the password once. filinq writes an unlocked copy beside the original and
  runs the validation checks again on the copy.
- The same action on an intake document whose file is locked, so a
  registrar can unlock it before assigning it.
- The password is used once, in memory. It is never stored, logged or put
  in an audit entry.
- The unlocking runs on the server with the `qpdf` binary, probed the way
  `OcrService::isTesseractAvailable()` probes Tesseract. No external
  service is called.
- A final document is never touched. The original stays as it arrived.

## Capabilities

### New capabilities

- `pdf-password-removal`: unlock a password-protected PDF into a new copy,
  with the password used once and never kept.

### Modified capabilities

None. `document-validation-checks` keeps reporting the lock; this change
adds the way out.

## Impact

- New `lib/Service/PdfPasswordRemovalService.php` and a route
  `POST api/documents/{fileId}/remove-password`.
- `src/components/ValidationFindingsPanel.vue`: an action on the
  `pdf-encrypted` finding. `src/views/intake/IntakeIndex.vue`: the same
  action as a row action when the intake file is locked.
- `src/views/settings/Settings.vue`: a status line saying whether `qpdf` is
  installed, next to the Tesseract line.
- `docs/features/`: a short section with a screenshot.

## Out of scope

- Guessing or cracking a password. The clerk supplies it.
- Removing a signature or certificate-based encryption.
- Encrypted Office files and encrypted e-mail bodies. The archived
  `eml-pdf-assembly` change leaves e-mail decryption to OpenRegister.
