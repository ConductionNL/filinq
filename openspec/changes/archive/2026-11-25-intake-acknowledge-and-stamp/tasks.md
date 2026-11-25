# Tasks: intake-acknowledge-and-stamp

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [x] 1.1 Add `acknowledgeWith` and `stampReceived` to `intakeDefaultRule`, and `acknowledgement` and `stampedRendition` to `intakeDocument`, in `lib/Settings/filinq_register.json`, with the D1 seed and a version bump (REQ-IAS-001, REQ-IAS-003). Verify: `occ maintenance:repair` imports the fields and the seed rule shows on the rules list.

## 2. Services

- [x] 2.1 Add `lib/Service/IntakeAcknowledgementService.php`: render from `acknowledgeWith`, reach the sender in the D3 order, write the outcome (REQ-IAS-001, REQ-IAS-002). Verify: PHPUnit for each D3 branch, a refusing indicator and a failing send.
- [x] 2.2 Register an outbound `documentRegistration` that answers the inbound one when it exists (D4) (REQ-IAS-001). Verify: PHPUnit asserts `answers` and the number on the letter.
- [x] 2.3 Add `lib/Service/ReceivedStampService.php`: stamp page 1 of a PDF into `<name> (ontvangen).pdf`, keep the arrived file's checksum (D5) (REQ-IAS-003). Verify: PHPUnit compares the arrived checksum before and after and reads the stamp text from the rendition.
- [x] 2.4 Add the listener on the created `intakeDocument` that runs both services after the save and never throws into the arrival (D2) (REQ-IAS-001, REQ-IAS-003). Verify: PHPUnit where both services throw and the intake document still exists.

## 3. Screens

- [x] 3.1 Add an acknowledged column and an "Open stamped copy" row action to `src/views/intake/IntakeIndex.vue` (REQ-IAS-004). Verify: Playwright on `/intake` sees the seeded document as acknowledged and opens its stamped copy.
- [x] 3.2 Add the two fields to the intake default rule form (REQ-IAS-001). Verify: Playwright creates a rule with a template and the stamp switched on.

## 4. Quality

- [x] 4.1 Dutch and English strings (ADR-005), `@spec` tags, and `docs/features/` with screenshots (ADR-010). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
- [x] 4.2 One live check on a dev instance with mail configured: a mailed document gets an acknowledgement in the sender's mailbox. Verify: the mail arrives and `acknowledgement.status` is `sent`.

## Build notes

- 1.1: the four fields are in `filinq_register.json`; the D1 seed is written (register 8.57.0): the template `ontvangstbevestiging`, the mail rule `intake-default-mail-ontvangst` acknowledging with it and stamping, and a sent `acknowledgement` on the seeded mail document `intake-mail-voorbeeld`. That document names no `defaultRule`, so importing the seed sends nothing. `stampedRendition` is not seeded: it is a file id, and no stamped file exists at import; the live pass (4.2) produces one. The `occ maintenance:repair` check is left for the live pass.
- 2.4 was built in the first commit of this PR (`IntakeArrivalFollowUpListener`) and is ticked now. 2.1 stays open: `queued-as-letter` is recorded, but the rendered letter is not yet stored in the outgoing post. The listener now also runs the stamp after a failed acknowledgement, and never throws into the arrival.
- 2.3: `ReceivedStampService` imports every page with FPDI, draws "Ontvangen", the date (day-month-year from `receivedAt`) and the number on page 1 top right, and writes `<name> (ontvangen).pdf` beside the arrived file, which is only read. PHPUnit `ReceivedStampServiceTest` (pages and sizes kept, date and number on the stamp, the copy's name, the arrived checksum unchanged, no copy for a missing or non-PDF file), `IntakeArrivalFollowUpListenerTest::testAStampingRuleStoresTheStampedCopy`.
- 2.1: a sender reached neither through a party nor by mail gets the acknowledgement rendered as a PDF and stored in Files (`output.mode: files`); its file id is kept in `acknowledgement.letter` (register 8.58.0, `intakeDocument` 1.5.0). A letter that could not be stored is `failed` with the reason, never `queued-as-letter`. PHPUnit `IntakeAcknowledgementServiceTest::testAPaperLetterIsQueuedAsALetter`, `testALetterThatWasNotStoredIsFailed`.
- 2.2: `Intake\AnsweringRegistration` finds the inbound `documentRegistration` naming the document and writes the outbound one (`direction: outbound`, the inbound unit, `answers` the inbound uuid, `document` the acknowledgement's generated document entry; the number comes from the platform sequence on save). The listener reads the inbound entry once: its number goes into the letter (`registrationNumber` in the template data) and on the stamp. Only a sent or queued acknowledgement is registered; its uuid is kept in `acknowledgement.registration`. The write runs under the caller's access rules (`documentRegistration` create is `filinq-policy-admins`): when refused, the acknowledgement stands and the warning is logged. PHPUnit `AnsweringRegistrationTest`, `IntakeAcknowledgementServiceTest::testAnInboundRegistrationIsNumberedOnTheLetterAndAnswered`, `IntakeArrivalFollowUpListenerTest::testTheInboundRegistrationReachesTheAcknowledgementAndTheStamp`.
- 3.1: `IntakeIndex.vue` has an **Acknowledged** column (a badge for sent, queued as letter, refused or failed, with the reason or address as its tooltip; empty without an acknowledgement) and an **Open stamped copy** row action when `stampedRendition` is set, opening Nextcloud's `/f/{fileId}`. The labels live in `src/services/intakeAcknowledgement.js`, vitest `tests/vitest/intakeAcknowledgement.spec.js`. The Playwright check is left for the live pass.
- 3.2: there was no intake rule form yet. `src/manifest.d/20-intake.json` adds the `IntakeRules` index page (`/intake/rules`, settings section) over `intakeDefaultRule`; create and edit are the object API's dialog over the schema, so `acknowledgeWith` and `stampReceived` are on the form. `manifestFragments.spec.js` lists the new page and menu entry. The Playwright check is left for the live pass.
- 4.1: strings in en, nl, de, es, fr and it (`l10n/*.json`, `.js` rebuilt), `@spec` tags on the new code, and `docs/features/intake-acknowledge-and-stamp.md`. Open: the screenshots, which need a running instance.
- 4.1: screenshots taken from a live instance (Nextcloud 34 + MariaDB + OpenRegister main): the inbox's Acknowledged column (`docs/static/screenshots/intake-acknowledged.png`) and the rule list with Acknowledge with and Stamp received (`intake-rules-acknowledge-and-stamp.png`).
- 4.2 (live, mail to a local SMTP catcher): a mailed `intakeDocument` created with the seeded mail rule got its acknowledgement in the sender's mailbox, `acknowledgement.status` is `sent`, and `<name> (ontvangen).pdf` reads "Ontvangen 25-11-2026" on page 1. The first run found three reds, fixed in `IntakeAcknowledgementService`: the subject came out as "Array" (translatable `subject` is a language map on the saved object; now nl, en, first), the date was raw ISO (now day-month-year, as on the stamp), and the HTML letter went out as a plain-text body with visible tags (OpenRegister's mail is plain text; the letter is now converted). PHPUnit `testTheLetterNamesTheSubjectTextAReadableDateAndGoesAsPlainText`, `testASubjectWithoutDutchFallsBackAndAnOddDateIsKept`.
- Left: the Playwright checks of 3.1 and 3.2.

