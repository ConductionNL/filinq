# Tasks: work-stamp-text-on-every-page

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 5. -->

## 1. Stamping

- [ ] 1.1 Add `lib/Service/PdfStampService.php` with `stamp()` on the mPDF page import loop, placements `diagonal`, `footer` and `both` (REQ-PST-001). Verify: PHPUnit stamps a three-page fixture with one landscape page and asserts three pages, the landscape page kept, and the text extracted from each page.
- [ ] 1.2 Refuse a non-PDF, an encrypted PDF and a file above the limit with their codes (REQ-PST-001). Verify: PHPUnit for each refusal.

## 2. Command for sibling apps

- [ ] 2.1 Add `DocumentStampRequestedEvent` and `DocumentStampRequestedListener`, registered in `lib/AppInfo/Application.php` (REQ-PST-002). Verify: PHPUnit dispatches the real event and reads the stamped PDF from the result slot; a listener failure leaves the event unhandled with code `failed` and no exception.
- [ ] 2.2 Run decidiq's `GET /api/papers/{fileId}/view` against a local install with both apps (REQ-PST-002). Verify: the served PDF shows the member's name and the date on every page; the result is noted in the PR body.

## 3. Docs

- [ ] 3.1 Add a section to `docs/features/` for app developers: the event, its fields, placements and refusal codes (REQ-PST-002). Verify: the docs page renders.
