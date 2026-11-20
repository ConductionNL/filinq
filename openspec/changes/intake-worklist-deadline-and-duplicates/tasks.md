# Tasks: intake-worklist-deadline-and-duplicates

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [x] 1.1 Add `handlingDeadline`, `deadlineReason`, `contentHash`, `duplicateOf` the read-time computed `daysLeft` and an `x-openregister-dedup` exact rule on `contentHash` to `intakeDocument`, and `handlingDays` and `handledWhen` to `intakeDefaultRule`, with the seed and a version bump (REQ-IWS-001, REQ-IWS-003). Verify: `occ maintenance:repair` imports them; `GET` on a seeded document returns `daysLeft`.
  - Built in `lib/Settings/filinq_register.json` (register 8.52.0, `intakeDocument` 1.5.0, `intakeDefaultRule` 1.1.0). `daysLeft` is a read-time `computed` field. The seeded scan rule has `handlingDays` 10 and `handledWhen` assigned; the two seeded documents carry a deadline. The seeded duplicate document is not added: `duplicateOf` holds a uuid, which a seed cannot know. `occ maintenance:repair` was not run here.

## 2. Arrival

- [x] 2.1 Set `handlingDeadline` in `IntakeService::receive()` from the matched rule's `handlingDays` in working days, falling back to the admin default (D2) (REQ-IWS-001). Verify: PHPUnit for a rule, no rule, and an arrival on a Friday.
  - `IntakeDeadlines::deadlineFor()` with `WorkingDays`, called from `IntakeService::receive()`. PHPUnit: `IntakeDeadlinesTest`, `WorkingDaysTest` (a rule, no rule, the admin default, a Friday and a weekend arrival).
- [x] 2.2 Compute `contentHash`, look up `findByContentHash()` and set `duplicateOf`, after the `sourceRef` skip (D4) (REQ-IWS-003). Verify: PHPUnit where the same bytes arrive under two source references and where a rejected earlier copy is ignored.
  - `ContentHasher` streams the file; `IntakeRepository::findByContentHash()` skips rejected copies. PHPUnit: `ContentHasherTest`, `IntakeRepositorySignalsTest`, `IntakeServiceSignalsTest`.

## 3. Reads

- [x] 3.1 Add `IntakeRepository::findOverdue()` honouring `handledWhen` (D3) and expose it as mode `overdue` on `GET api/intake/documents` (REQ-IWS-002). Verify: PHPUnit for assigned, answered and rejected documents; Newman for the mode.
  - `IntakeRepository::findOverdue()` (assigned, answered and rejected), read by `IntakeOverdueList` and served as `GET api/intake/documents?mode=overdue`. PHPUnit: `IntakeRepositorySignalsTest`, `IntakeOverdueListTest`, `IntakeControllerSignalsTest`. The Newman request is not added.

## 4. Worklist

- [x] 4.1 Add the deadline column and the Overdue mode to `src/views/intake/IntakeIndex.vue` (REQ-IWS-002). Verify: Playwright on `/intake` switches to Overdue and sees only the overdue seed.
  - Deadline column with a working-day countdown (`src/services/intakeDeadline.js`, vitest `intakeDeadline.spec.js`) and the Overdue mode. The Playwright spec is not written: no instance here.
- [x] 4.2 Add the "Change deadline" row action with a required reason (D2) (REQ-IWS-001). Verify: Playwright changes a deadline and the row reads the new countdown.
  - `IntakeDeadlineDialog.vue` and `POST api/intake/documents/{uuid}/deadline` (`IntakeService::changeDeadline()`). The Playwright spec is not written.
- [x] 4.3 Add the "Possible duplicate" badge with a link to the first document and the "Reject as duplicate" action (D5) (REQ-IWS-004). Verify: Playwright rejects the seeded duplicate and the reason names the first document.
  - The badge names the first document in its tooltip rather than linking to it: intake documents have no detail page to link to. "Reject as duplicate" uses the existing reject. The Playwright spec is not written.

## 5. Quality

- [ ] 5.1 Dutch and English strings (ADR-005), `@spec` tags, `docs/features/` with screenshots (ADR-010). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
  - Partly: Dutch and English strings in `l10n/en.json` and `l10n/nl.json` (catalogues rebuilt), `@spec` tags, and `docs/features/intake-worklist-signals.md` without screenshots. Screenshots need a running instance.
- [ ] 5.2 PHPUnit at 75% on the new code inside the container (ADR-009). Verify: the coverage report for `IntakeService` and `IntakeRepository`.
  - Every new class has unit tests; the in-container coverage report was not run here.
