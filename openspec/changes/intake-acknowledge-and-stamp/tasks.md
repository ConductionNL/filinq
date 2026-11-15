# Tasks: intake-acknowledge-and-stamp

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [ ] 1.1 Add `acknowledgeWith` and `stampReceived` to `intakeDefaultRule`, and `acknowledgement` and `stampedRendition` to `intakeDocument`, in `lib/Settings/filinq_register.json`, with the D1 seed and a version bump (REQ-IAS-001, REQ-IAS-003). Verify: `occ maintenance:repair` imports the fields and the seed rule shows on the rules list.

## 2. Services

- [ ] 2.1 Add `lib/Service/IntakeAcknowledgementService.php`: render from `acknowledgeWith`, reach the sender in the D3 order, write the outcome (REQ-IAS-001, REQ-IAS-002). Verify: PHPUnit for each D3 branch, a refusing indicator and a failing send.
- [ ] 2.2 Register an outbound `documentRegistration` that answers the inbound one when it exists (D4) (REQ-IAS-001). Verify: PHPUnit asserts `answers` and the number on the letter.
- [ ] 2.3 Add `lib/Service/ReceivedStampService.php`: stamp page 1 of a PDF into `<name> (ontvangen).pdf`, keep the arrived file's checksum (D5) (REQ-IAS-003). Verify: PHPUnit compares the arrived checksum before and after and reads the stamp text from the rendition.
- [ ] 2.4 Add the listener on the created `intakeDocument` that runs both services after the save and never throws into the arrival (D2) (REQ-IAS-001, REQ-IAS-003). Verify: PHPUnit where both services throw and the intake document still exists.

## 3. Screens

- [ ] 3.1 Add an acknowledged column and an "Open stamped copy" row action to `src/views/intake/IntakeIndex.vue` (REQ-IAS-004). Verify: Playwright on `/intake` sees the seeded document as acknowledged and opens its stamped copy.
- [ ] 3.2 Add the two fields to the intake default rule form (REQ-IAS-001). Verify: Playwright creates a rule with a template and the stamp switched on.

## 4. Quality

- [ ] 4.1 Dutch and English strings (ADR-005), `@spec` tags, and `docs/features/` with screenshots (ADR-010). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
- [ ] 4.2 One live check on a dev instance with mail configured: a mailed document gets an acknowledgement in the sender's mailbox. Verify: the mail arrives and `acknowledgement.status` is `sent`.
