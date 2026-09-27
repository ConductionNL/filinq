# Tasks: intake-worklist-deadline-and-duplicates

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [ ] 1.1 Add `handlingDeadline`, `deadlineReason`, `contentHash`, `duplicateOf` and the read-time computed `daysLeft` to `intakeDocument`, and `handlingDays` and `handledWhen` to `intakeDefaultRule`, with the seed and a version bump (REQ-IWS-001, REQ-IWS-003). Verify: `occ maintenance:repair` imports them; `GET` on a seeded document returns `daysLeft`.

## 2. Arrival

- [ ] 2.1 Set `handlingDeadline` in `IntakeService::receive()` from the matched rule's `handlingDays` in working days, falling back to the admin default (D2) (REQ-IWS-001). Verify: PHPUnit for a rule, no rule, and an arrival on a Friday.
- [ ] 2.2 Compute `contentHash`, look up `findByContentHash()` and set `duplicateOf`, after the `sourceRef` skip (D4) (REQ-IWS-003). Verify: PHPUnit where the same bytes arrive under two source references and where a rejected earlier copy is ignored.

## 3. Reads

- [ ] 3.1 Add `IntakeRepository::findOverdue()` honouring `handledWhen` (D3) and expose it as mode `overdue` on `GET api/intake/documents` (REQ-IWS-002). Verify: PHPUnit for assigned, answered and rejected documents; Newman for the mode.

## 4. Worklist

- [ ] 4.1 Add the deadline column and the Overdue mode to `src/views/intake/IntakeIndex.vue` (REQ-IWS-002). Verify: Playwright on `/intake` switches to Overdue and sees only the overdue seed.
- [ ] 4.2 Add the "Change deadline" row action with a required reason (D2) (REQ-IWS-001). Verify: Playwright changes a deadline and the row reads the new countdown.
- [ ] 4.3 Add the "Possible duplicate" badge with a link to the first document and the "Reject as duplicate" action (D5) (REQ-IWS-004). Verify: Playwright rejects the seeded duplicate and the reason names the first document.

## 5. Quality

- [ ] 5.1 Dutch and English strings (ADR-005), `@spec` tags, `docs/features/` with screenshots (ADR-010). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
- [ ] 5.2 PHPUnit at 75% on the new code inside the container (ADR-009). Verify: the coverage report for `IntakeService` and `IntakeRepository`.
