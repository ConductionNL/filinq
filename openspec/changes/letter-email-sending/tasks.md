# Tasks: letter-email-sending

### Task 1: The service sends a composed letter and logs it
- **spec_ref**: `openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-composed-letter-can-be-sent-by-email-req-lcs-01`
- **files**: `lib/Service/CorrespondenceService.php` (`send()`: compose with `format: email`, build the message with `IMailer::createMessage()`, optional PDF attachment, log `sent`), `lib/Settings/filinq_register.json` (correspondence schema: `sent` status, `sentAt`, `sentTo`, `sentBy`), `tests/unit/Service/CorrespondenceServiceSendTest.php`
- **acceptance_criteria**:
  - GIVEN a recipient with an address WHEN `send()` runs THEN `IMailer::send()` is called once with that address and the composed body, and the log has status `sent`
  - GIVEN `attachPdf: true` THEN the message carries one PDF attachment
- [ ] Implement
- [ ] Test

### Task 2: Refusals and failures
- **spec_ref**: `openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-send-that-cannot-happen-says-why-and-records-nothing-as-sent-req-lcs-02`
- **files**: `lib/Service/CorrespondenceService.php`, `lib/Controller/CorrespondenceController.php` (`send()`, `#[NoAdminRequired]`, read access on the recipient first, 404 / 422 mapping), `appinfo/routes.php` (`correspondence#send`, `POST api/correspondence/send`), `tests/unit/Controller/CorrespondenceControllerTest.php`
- **acceptance_criteria**:
  - GIVEN no address THEN 422 "Deze ontvanger heeft geen e-mailadres" and no `IMailer::send()` call
  - GIVEN a recipient the caller cannot read THEN 404 before anything is composed
  - GIVEN `IMailer::send()` throws THEN the log has status `failed` with the reason and the answer has no stack trace
- [ ] Implement
- [ ] Test

### Task 3: The Correspondence page sends
- **spec_ref**: `openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-composed-letter-can-be-sent-by-email-req-lcs-01`
- **files**: `src/views/correspondence/CorrespondenceIndex.vue` ("Versturen per e-mail" button, a confirm dialog in `src/dialogs/` showing address and subject, a PDF checkbox), `src/store/modules/correspondence.js` (`send` action), `l10n/nl.json`, `l10n/en.json`
- **acceptance_criteria**:
  - GIVEN a composed letter WHEN the officer confirms THEN one request goes to `api/correspondence/send` and the row shows "Verstuurd"
  - GIVEN a 422 THEN the dialog shows the message and stays open
- [ ] Implement
- [ ] Test

### Task 4: Link the message through the email leaf when it is there
- **spec_ref**: `openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-composed-letter-can-be-sent-by-email-req-lcs-01`
- **files**: `lib/Service/CorrespondenceService.php` (after a send, link through OpenRegister's email integration when available; absent leaf is a no-op, not an error)
- **acceptance_criteria**:
  - GIVEN the leaf is not installed WHEN a letter is sent THEN the send succeeds and no link is attempted
- [ ] Implement
- [ ] Test

### Task 5: Verification and the row
- **files**: `tests/e2e/spec-coverage/letter-email-sending.spec.ts` (send against the dev mail catcher), `openspec/parity/capabilities.json`
- [ ] `@spec` tags on changed methods (gate 16) and the e2e scenarios (gate 19)
- [ ] When tasks 1 to 4 are done: set row sh-letter `filinq` to `yes`, cite REQ-LCS-01, archive this change
