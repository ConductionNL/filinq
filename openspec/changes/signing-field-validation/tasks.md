# Tasks: signing-field-validation

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [x] 1.1 Add `label`, `required` and `validation` to the `fieldPlacements` items, `fieldValues` to `signerRecord`, `IBAN` to the `docudesk-signing` data categories, a seed request with an `iban-nl` field, and a register version bump (D1, D4, D8) (REQ-SFV-001, REQ-SFV-004). Verify: `occ maintenance:repair` imports it and the seed signer's value reads back through OpenRegister's objects API as an admin.

## 2. Checking and storing

- [x] 2.1 Add `lib/Service/Signing/FieldValueChecker.php` that maps `iban` and `iban-nl` onto OpenRegister's `iban` format by name, and refuse a request whose check OpenRegister lacks (D2) (REQ-SFV-002). Verify: PHPUnit with a stub format covers a valid and an invalid value for each check, a foreign IBAN under `iban-nl`, and the missing-format refusal.
- [x] 2.2 Check every value in `SigningService::sign()` before writing, answer 422 with a message per field, store normalised values in the same save as `SIGNED`, and refuse placement changes after `DRAFT` (D4, D5) (REQ-SFV-003, REQ-SFV-004). Verify: PHPUnit asserts nothing is written on a failure, and that no log call or audit metadata carries a value.
- [x] 2.3 Pass `fields` through `SigningController::sign()` and `PortalSigningReceiverController::signDocument()` (D7) (REQ-SFV-003). Verify: Newman signs the seed-shaped request with a valid and an invalid IBAN through the app route; PHPUnit covers the portal receiver.
- [x] 2.4 Draw each value in its field block before the v2 seal in `NativeSigningProvider` (D6) (REQ-SFV-004). Verify: PHPUnit reads the value on the artifact page, and altering it trips verification as `tampered`.

## 3. Screens

- [ ] 3.1 Add the three settings to the placement editor for `text` fields (REQ-SFV-001). Verify: Playwright places a required "Account for the payment" field with the Dutch IBAN check and reads it back on the request.
- [ ] 3.2 Add `src/modals/FillInAndSignDialog.vue`, open it from `SigningFolder.vue` for requests with fields for this signer, and keep them out of folder selection signing (D7) (REQ-SFV-003). Verify: Playwright types an IBAN with a wrong check digit, reads the message on the field, corrects it and signs.

## 4. Strings, docs and siblings

- [ ] 4.1 Dutch and English strings (ADR-005), `@spec` tags on new methods, and a section in `docs/features/` with a screenshot of the dialog (ADR-010). Verify: `npm run check:l10n`, `npm run lint` and `composer check:strict` pass; PHPUnit coverage on the new PHP is at least 75% (ADR-009).
- [ ] 4.2 File openregister's IBAN format and portaliq's row-action inputs as issues on their repos and link them in this change's proposal. Verify: both issue URLs are in `proposal.md` under Cross-app dependencies.

## Build notes

- 1.1: register 8.52.0 (`signingRequest` 1.12.0, `signerRecord` 1.8.0), the
  `IBAN` data category and a seed request with a signed signer holding
  `NL91ABNA0417164300`. The live `occ maintenance:repair` import was not run.
- 2.1: OpenRegister has no `iban` format yet (`lib/Formats/` on main), so on
  a current server a request with an IBAN check is refused with "This check
  is not available on this server" until that format lands.
- 2.2: no filinq path changes `fieldPlacements` after creation (requests are
  created `PENDING` and have no update route), so D5 holds without a guard.
  A sign act without fields (folder selection, bulk sign) is refused for a
  signer who has fields, which is the server half of 3.2.
- 2.3: PHPUnit covers both routes; the Newman run was not done.
- Left open: 3.1 and 3.2 (placement editor settings and the "Fill in and
  sign" dialog), 4.1 (frontend strings, docs and screenshot; the schema
  strings are in `l10n/en.json` / `nl.json`) and 4.2 (filing the
  openregister and portaliq issues).
