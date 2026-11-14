# Tasks: intake-acknowledge-and-stamp

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [ ] 1.1 Add `acknowledgeWith` and `stampReceived` to `intakeDefaultRule`, and `acknowledgement` and `stampedRendition` to `intakeDocument`, in `lib/Settings/filinq_register.json`, with the D1 seed and a version bump (REQ-IAS-001, REQ-IAS-003). Verify: `occ maintenance:repair` imports the fields and the seed rule shows on the rules list.

## 2. Services

- [x] 2.1 Add `lib/Service/IntakeAcknowledgementService.php`: render from `acknowledgeWith`, reach the sender in the D3 order, write the outcome (REQ-IAS-001, REQ-IAS-002). Verify: PHPUnit for each D3 branch, a refusing indicator and a failing send.
- [ ] 2.2 Register an outbound `documentRegistration` that answers the inbound one when it exists (D4) (REQ-IAS-001). Verify: PHPUnit asserts `answers` and the number on the letter.
- [x] 2.3 Add `lib/Service/ReceivedStampService.php`: stamp page 1 of a PDF into `<name> (ontvangen).pdf`, keep the arrived file's checksum (D5) (REQ-IAS-003). Verify: PHPUnit compares the arrived checksum before and after and reads the stamp text from the rendition.
- [x] 2.4 Add the listener on the created `intakeDocument` that runs both services after the save and never throws into the arrival (D2) (REQ-IAS-001, REQ-IAS-003). Verify: PHPUnit where both services throw and the intake document still exists.

## 3. Screens

- [ ] 3.1 Add an acknowledged column and an "Open stamped copy" row action to `src/views/intake/IntakeIndex.vue` (REQ-IAS-004). Verify: Playwright on `/intake` sees the seeded document as acknowledged and opens its stamped copy.
- [ ] 3.2 Add the two fields to the intake default rule form (REQ-IAS-001). Verify: Playwright creates a rule with a template and the stamp switched on.

## 4. Quality

- [ ] 4.1 Dutch and English strings (ADR-005), `@spec` tags, and `docs/features/` with screenshots (ADR-010). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
- [ ] 4.2 One live check on a dev instance with mail configured: a mailed document gets an acknowledgement in the sender's mailbox. Verify: the mail arrives and `acknowledgement.status` is `sent`.

## Build notes

- 1.1: the four fields are in `filinq_register.json` (register 8.55.0); the D1 seed is not written yet, so the box stays open.
- 2.1 and 2.4 were built in the first commit of this PR (`IntakeAcknowledgementService`, `SenderReach`, `IntakeArrivalFollowUpListener`) and are ticked now. The listener now also runs the stamp after a failed acknowledgement, and never throws into the arrival.
- 2.3: `ReceivedStampService` imports every page with FPDI, draws "Ontvangen", the date (day-month-year from `receivedAt`) and the number on page 1 top right, and writes `<name> (ontvangen).pdf` beside the arrived file, which is only read. PHPUnit `ReceivedStampServiceTest` (pages and sizes kept, date and number on the stamp, the copy's name, the arrived checksum unchanged, no copy for a missing or non-PDF file), `IntakeArrivalFollowUpListenerTest::testAStampingRuleStoresTheStampedCopy`.
- Left: 2.2 (the outbound registration, D4: no reader finds an inbound registration by document yet, so the stamp carries the date only), 3.1 and 3.2 (screens), 4.1 (docs with screenshots), 4.2 (live).

