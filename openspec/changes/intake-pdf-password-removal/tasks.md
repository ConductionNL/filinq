# Tasks: intake-pdf-password-removal

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8. -->

## 1. Service and route

- [ ] 1.1 Add `lib/Service/PdfPasswordRemovalService.php`: probe `qpdf --version`, decrypt with the password on stdin into `<name> (unlocked).pdf` beside the original, refuse a final document (D1, D2, D3, D6) (REQ-PPR-001, REQ-PPR-003). Verify: PHPUnit with a fixture PDF locked with a known password, a wrong password, qpdf absent and a final document.
- [ ] 1.2 Add `POST api/documents/{fileId}/remove-password` with `#[NoAdminRequired]`, a read-access check on the file for the session user, and 422, 409 and 503 answers (REQ-PPR-001). Verify: Newman request for each answer; `grep -rn "password" lib/Service/PdfPasswordRemovalService.php` shows no logger call carrying it.
- [ ] 1.3 Run the validation checks on the copy and return the findings (D4) (REQ-PPR-002). Verify: PHPUnit asserts the response carries the copy's findings.

## 2. Screens

- [ ] 2.1 Add a "Remove password" action to the `pdf-encrypted` finding in `ValidationFindingsPanel.vue`, with a password dialog in `src/modals/` (REQ-PPR-001). Verify: Playwright on `/my-documents` unlocks the fixture and shows the copy.
- [ ] 2.2 Add the same row action on `IntakeIndex.vue` when the intake file is locked, and write `file` and `lockedOriginal` (D5) (REQ-PPR-004). Verify: Playwright on `/intake` unlocks a locked intake document and the row opens the copy.
- [ ] 2.3 Show whether qpdf is installed on the admin settings page next to the Tesseract line (REQ-PPR-003). Verify: Playwright reads the status line.

## 3. Register, strings and docs

- [ ] 3.1 Add `lockedOriginal` to `intakeDocument` in `lib/Settings/filinq_register.json`, a seed object that uses it, and a register version bump. Verify: `occ maintenance:repair` imports it and the seed object shows in `/intake`.
- [ ] 3.2 Dutch and English strings (ADR-005), `@spec` tags on the new methods, and a section in `docs/features/` with a screenshot (ADR-010). Verify: `npm run lint`, `composer check:strict` and `npm run check:l10n` pass.
