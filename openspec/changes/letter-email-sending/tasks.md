# Tasks: letter-email-sending

### Task 1: The service sends a composed letter and logs it
- **spec_ref**: `openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-composed-letter-can-be-sent-by-email-req-lcs-01`
- **files**: `lib/Service/CorrespondenceService.php` (`send()`: compose with `format: email`, build the message with `IMailer::createMessage()`, optional PDF attachment, log `sent`), `lib/Settings/filinq_register.json` (correspondence schema: `sent` status, `sentAt`, `sentTo`, `sentBy`), `tests/unit/Service/CorrespondenceServiceSendTest.php`
- **acceptance_criteria**:
  - GIVEN a recipient with an address WHEN `send()` runs THEN `IMailer::send()` is called once with that address and the composed body, and the log has status `sent`
  - GIVEN `attachPdf: true` THEN the message carries one PDF attachment
- [x] Implement
- [x] Test
  - Built: `CorrespondenceService::compose()` (the composing half of `generate()`, which still logs what it composes) and `logSent()`; `CorrespondenceSendService::send()` reads the recipient, composes the email body and, asked for, the PDF of the same letter, sends through `IMailer` and logs one entry (`sent` with `sentAt`, `sentTo`, `sentBy`, or `failed` with the reason). Schema `correspondence` 1.4.0, register 8.73.0. `tests/unit/Service/CorrespondenceServiceSendTest.php` runs the real services over doubled mailer, object store and renderer.

### Task 2: Refusals and failures
- **spec_ref**: `openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-send-that-cannot-happen-says-why-and-records-nothing-as-sent-req-lcs-02`
- **files**: `lib/Service/CorrespondenceService.php`, `lib/Controller/CorrespondenceController.php` (`send()`, `#[NoAdminRequired]`, read access on the recipient first, 404 / 422 mapping), `appinfo/routes.php` (`correspondence#send`, `POST api/correspondence/send`), `tests/unit/Controller/CorrespondenceControllerTest.php`
- **acceptance_criteria**:
  - GIVEN no address THEN 422 "Deze ontvanger heeft geen e-mailadres" and no `IMailer::send()` call
  - GIVEN a recipient the caller cannot read THEN 404 before anything is composed
  - GIVEN `IMailer::send()` throws THEN the log has status `failed` with the reason and the answer has no stack trace
- [x] Implement
- [x] Test
  - Built: `CorrespondenceSendRefused` carries 400/404/422; the recipient is read with the caller's rights before anything is composed; Nextcloud's mail mode `null` is the "no mail server" refusal; a mailer that throws or refuses the address logs `failed` and the route answers 502 with a sentence and no server text. Routes `correspondence#send` and `correspondence#sendPreview`, in `openapi.json`. `tests/unit/Controller/CorrespondenceControllerSendTest.php`.

### Task 3: The Correspondence page sends
- **spec_ref**: `openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-composed-letter-can-be-sent-by-email-req-lcs-01`
- **files**: `src/views/correspondence/CorrespondenceIndex.vue` ("Versturen per e-mail" button, a confirm dialog in `src/dialogs/` showing address and subject, a PDF checkbox), `src/store/modules/correspondence.js` (`send` action), `l10n/nl.json`, `l10n/en.json`
- **acceptance_criteria**:
  - GIVEN a composed letter WHEN the officer confirms THEN one request goes to `api/correspondence/send` and the row shows "Verstuurd"
  - GIVEN a 422 THEN the dialog shows the message and stays open
- [x] Implement
- [x] Test
  - Built: "Send by email" on the single-recipient form opens `src/dialogs/CorrespondenceSendDialog.vue`, which asks `POST api/correspondence/send/preview` for the address and subject (same refusals as the send) and offers the PDF checkbox; the store's `sendByEmail()` sends, the page shows "Sent to {address}", and a refusal stays in the open dialog. jest `src/dialogs/CorrespondenceSendDialog.spec.js`, `src/store/modules/correspondence.spec.js`.

### Task 4: Link the message through the email leaf when it is there
- **spec_ref**: `openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-composed-letter-can-be-sent-by-email-req-lcs-01`
- **files**: `lib/Service/CorrespondenceService.php` (after a send, link through OpenRegister's email integration when available; absent leaf is a no-op, not an error)
- **acceptance_criteria**:
  - GIVEN the leaf is not installed WHEN a letter is sent THEN the send succeeds and no link is attempted
- [ ] Implement
- [ ] Test
  - Open: OpenRegister's email leaf links Nextcloud Mail messages by mail account and message id (`EmailLinkService::linkEmail(LinkTarget, EmailMessageRef)`). A message sent through `IMailer` has neither, so there is nothing to link yet; the send does not touch the leaf, which is the absent-leaf behaviour this task asks for. Linking needs either sending through the Mail app or an OpenRegister link type for outbound mail.

### Task 5: Verification and the row
- **files**: `tests/e2e/spec-coverage/letter-email-sending.spec.ts` (send against the dev mail catcher), `openspec/parity/capabilities.json`
- [ ] `@spec` tags on changed methods (gate 16) and the e2e scenarios (gate 19)
  - `@spec` tags are on every new and changed method. The e2e spec `tests/e2e/spec-coverage/letter-email-sending.spec.ts` is written: the send, the PDF choice and the 422 refusal against route-stubbed server answers, and the real send read from a Mailpit-compatible catcher when `E2E_MAIL_CATCHER_URL`, `E2E_LETTER_TEMPLATE_ID` and `E2E_LETTER_RECIPIENT` are set. Not run: it needs a live instance with a mail catcher, so this box stays open.
- [ ] When tasks 1 to 4 are done: set row sh-letter `filinq` to `yes`, cite REQ-LCS-01, archive this change
