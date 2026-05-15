## 1. Event listener wiring

- [ ] 1.1 Add a branch in `lib/EventListener/DocuDeskEventHandler.php` (or a new dedicated handler class — caller's choice based on existing structure) handling `OCA\OpenRegister\Event\EntityRelationDecisionUpdatedEvent`. Use the event's `isSkipAnonymizationActivated()` helper to filter — only `false → true` transitions trigger the consent flow. `bases`-only changes and `true → false` reversal events MUST be no-ops.
- [ ] 1.2 Register the listener in `lib/AppInfo/Application.php`'s `register()` via `$context->registerEventListener(EntityRelationDecisionUpdatedEvent::class, …)`. Reuse the existing `DocuDeskEventListener` if it's the right place, or add a focused new listener if `DocuDeskEventListener` is already too crowded.
- [ ] 1.3 Inside the handler: resolve the relation's entity context (`entityId`, `entityText`, `entityType`, `entityKey`) and document context (`fileId` → file resolution → `documentId` per DD's convention for publicationConsent records). Hand both to `ConsentService::createConsentRequest()`.
- [ ] 1.4 Wrap the `createConsentRequest` call in try/catch with three branches:
  - `PolicyRejectedException` (new typed exception, task 1.6) → reverse the PATCH (task 1.7) + dispatch notification (task 1.8).
  - Other `Exception` → log at error level with relation/entity context; emit a notification to the operator that consent creation failed and they should retry. Do NOT reverse the PATCH on generic errors — the operator's decision stands, the consent record is missing.
  - Success → log at info level with the resulting `consentId` + `consentStatus`. No further action.
- [ ] 1.5 Loop-prevention check: the listener inspects `isSkipAnonymizationActivated()` before doing any work. Reversal writes (`true → false`) return false and are dropped before reaching the consent flow. Verify with a unit test: dispatch a reversal event, confirm `createConsentRequest` is NOT called.
- [ ] 1.6 Add `lib/Exception/PolicyRejectedException.php` (NEW). Typed exception thrown by `createConsentRequest` when `PolicyMatchService` returns a prohibition match. Carries the rule UUID + rule name so the listener can include them in the notification text.
- [ ] 1.7 Implement PATCH reversal: call `EntityRelationMapper::updateDecisionMetadata($relation, ['skipAnonymization' => false], $actingUser)` via OR's container. This re-dispatches the event with the reversal transition; the listener's `isSkipAnonymizationActivated()` filter (task 1.5) prevents the loop.
- [ ] 1.8 Dispatch a Nextcloud notification via `\OCP\Notification\IManager`. App `docudesk`, subject `publication_prohibition_blocked`. Parameters: rule UUID, rule name, entity text, document UUID. Notification copy lives in `l10n/`; rendering on the client side links back to the document review surface.

## 2. ConsentService idempotency

- [ ] 2.1 In `lib/Service/ConsentService.php::createConsentRequest()`, before the existing creation path, look up an existing `publicationConsent` record:
  - Primary: `(documentId, entityKey, scope = "document")`.
  - Fallback if `entityKey` is null on input: `(documentId, entityText, scope = "document")`.
  - `scope: "entity"` records are NEVER considered for matching.
- [ ] 2.2 On match, update only operator-controlled fields: `entityType`, `legalBasis`, `notes`, `contactEmail`, `contactAddress`. Preserve workflow state: `notificationStatus`, `notificationSentAt`, `objectionDeadline`, `objectionReceivedAt`, `objectionReason`, `consentStatus`, `publicationDecision`. `policyMatch` is preserved with one exception: if previously null and the current `PolicyMatchService::match()` returns a match, set it; never clear.
- [ ] 2.3 On no-match, create a new record (existing behaviour). Workflow state is whatever `PolicyMatchService` resolves to (auto-resolved for standing-consent, pending for no-match).
- [ ] 2.4 On prohibition-match outcome from `PolicyMatchService`, throw `PolicyRejectedException` (task 1.6) instead of creating/updating the record.
- [ ] 2.5 Add a `wasUpdated` flag on the return shape (boolean: true if existing record was matched, false if new). Listener uses this for logging; future consumers may surface it in API responses.

## 3. Defensive anonymise-time check

- [ ] 3.1 In `lib/Service/AnonymizationService.php` (or wherever the anonymise entry point delegates to OR), add a pre-anonymisation check method `assertNoBlockingConsents(int $fileId): void`. Behaviour:
  - Read all EntityRelation rows for the file with `skipAnonymization = true`.
  - For each, look up the corresponding `publicationConsent` record by `(documentId, entityKey)`. If the lookup returns nothing, log a warning (relation marked skip but no consent record — likely listener failure) and treat the relation as not-blocking (operator's decision stands).
  - For each found record, classify as blocking or not-blocking per the table in design.md §D5.
  - If any are blocking, collect into a structured list and throw a `BlockingConsentException` (new typed exception, task 3.3).
- [ ] 3.2 In the anonymise controller (`lib/Controller/AnonymizationController.php`), catch `BlockingConsentException` and return HTTP 422 with the structured body per design.md §D5 (`blockingConsents[]` array with `consentId`, `entityText`, `consentStatus`, `objectionDeadline`, `reason`).
- [ ] 3.3 Add `lib/Exception/BlockingConsentException.php` (NEW). Carries the list of blocking consent records so the controller can serialise them into the 422 body.
- [ ] 3.4 Call `assertNoBlockingConsents` at the top of the anonymise pipeline, before delegating to OR. If it throws, no file mutation occurs.

## 4. Widget client-side prohibition pre-check

- [ ] 4.1 In `src/views/anonymization/AnonymizationWidget.vue` (the smoke-test widget; will evolve toward production publication-prep), on mount, load `GET /apps/docudesk/api/policy/prohibitions` once and cache in the store.
- [ ] 4.2 Add a normalisation helper in `src/utils/policyMatch.js` (or similar) implementing the four match types (`exact`, `normalized`, `bsn`, `kvk`) per the spec in `entity-publication-policies` design. Match the server-side normalisation rules exactly (Latin transliteration + lowercase). Document the spec at the top of the file so server-side and client-side stay aligned.
- [ ] 4.3 Per entity row in the review table, evaluate the entity against the cached prohibition list. On match:
  - Render the skip switch disabled.
  - Add a red tooltip / inline note: "{entity} is on the publication prohibition list (rule: {ruleName}). Cannot be published unredacted."
  - Do not allow the click to PATCH.
- [ ] 4.4 Refresh the cached prohibition list when the operator opens a new document for review (or on a configurable interval). Stale caches across long sessions are an accepted v1 limitation.

## 5. Unit tests

- [ ] 5.1 `tests/unit/EventListener/DocuDeskEventHandlerSkipFlipTest.php` (NEW) — cover the new event-handling branch:
  - skip-flip event → `createConsentRequest` called with correct arguments.
  - `bases`-only change event → `createConsentRequest` NOT called.
  - reversal event (`true → false`) → `createConsentRequest` NOT called.
  - `PolicyRejectedException` from `createConsentRequest` → `EntityRelationMapper::updateDecisionMetadata` called with `skipAnonymization: false` + notification dispatched.
  - Generic exception → logged at error level + notification dispatched + PATCH NOT reversed.
  - Loop prevention: simulate the reversal write firing a follow-up event; verify it's a no-op.
- [ ] 5.2 Extend `tests/unit/Service/ConsentServiceTest.php` with idempotency cases:
  - First call → creates new record, returns `wasUpdated: false`.
  - Re-call with same `(documentId, entityKey)` → matches existing, updates operator-controlled fields, preserves workflow state, returns `wasUpdated: true`.
  - Re-call with null `entityKey` and matching `entityText` → fallback match works.
  - Re-call against a `scope: "entity"` record → no match (different scope), new `scope: "document"` record created.
  - Re-call when `policyMatch` was null and a new standing-consent now matches → `policyMatch` is set to the new rule.
  - Re-call when `policyMatch` was non-null and current match is null → `policyMatch` preserved (not cleared).
  - Prohibition match outcome → `PolicyRejectedException` thrown, no record created/updated.
- [ ] 5.3 `tests/unit/Service/AnonymizationServiceBlockingConsentTest.php` (NEW) — cover the defensive check:
  - File with no skip-marked relations → no check fires, anonymise proceeds.
  - File with skip-marked relations but no consent records → warning logged, treat as not-blocking, proceed.
  - File with `consent_given` consent for the skipped entity → not blocking, proceed.
  - File with `pending` consent whose `objectionDeadline` has passed → not blocking, proceed.
  - File with `pending` consent whose `objectionDeadline` is in the future → blocking, throws `BlockingConsentException`.
  - File with `objection_received` + `publicationDecision: anonymize` → not blocking, proceed.
  - File with `objection_received` + `publicationDecision: pending` → blocking, throws.
  - Mixed: one blocking + one not-blocking → throws with only the blocking entity listed.
- [ ] 5.4 `tests/unit/Controller/AnonymizationControllerBlockingConsentTest.php` (NEW) — the controller's 422 mapping:
  - `BlockingConsentException` thrown by the service → controller returns 422 with structured body matching design.md §D5.
- [ ] 5.5 `tests/unit/Exception/PolicyRejectedExceptionTest.php` (NEW) — minimal coverage: constructor stores rule UUID + name; getters return them.

## 6. Integration tests (Newman)

- [ ] 6.1 Extend `tests/newman/docudesk-api.postman_collection.json`:
  - PATCH skip on a relation matching no policy → consent record created in `pending` state, `objectionDeadline` set.
  - PATCH skip on a relation matching a standing consent → consent record created in `consent_given` state, `policyMatch` populated.
  - PATCH skip on a relation matching a prohibition → PATCH succeeds; follow-up GET on the relation shows `skipAnonymization: false` (reversed); notification visible (skip notification check if Newman can introspect notifications; otherwise verify reversal only).
  - Anonymise a file with no skip-marked relations → 200 (regression).
  - Anonymise a file with a skip-marked relation whose consent is `consent_given` → 200.
  - Anonymise a file with a skip-marked relation whose consent is `pending` in window → 422 with `blockingConsents[]`.
  - Anonymise a file with a skip-marked relation whose consent is `pending` past window → 200.
- [ ] 6.2 Verify idempotency in Newman: PATCH skip on relation A, then PATCH skip on relation B (same entity, same document) → only one consent record exists; `wasUpdated: true` on second event's log entry.

## 7. Cross-change regression check

- [ ] 7.1 Verify Wave 1.2 `entity-publication-policies` retroactive force-resolve still works after this change lands. Specifically: creating a new prohibition that matches an in-flight `pending` consent should still trigger retroactive resolution per the existing `PolicyRetroactiveService` logic, regardless of how that consent was originally created (direct POST vs event-listener).
- [ ] 7.2 Verify Wave 1.3 `entity-relation-grondslagen` PATCH endpoint behaviour is unchanged for non-DD callers (i.e. callers that don't subscribe to the event). PATCH returns 200 with the same response body shape; consent creation is purely a downstream side-effect.
- [ ] 7.3 Run the full unit suite + Newman suite against a live stack with all three changes (Wave 1.2 + Wave 1.3 + this change) applied. Confirm no regressions in either existing change's behaviour.

## 8. Documentation

- [ ] 8.1 Update `docs/features/publication-consent-process.md` with the event-driven trigger description. Replace any text that references `unredactedEntities[]` (none exists yet, but worth a search to be sure). Add a sequence diagram showing: operator clicks skip → PATCH → OR audit + event → DD listener → createConsentRequest → PolicyMatchService outcome → consent record state.
- [ ] 8.2 Add a CHANGELOG entry under "Added": "Operators flipping `skipAnonymization: true` on an EntityRelation row automatically creates a publicationConsent record. The 28-day WOO objection clock starts at decision time, not at anonymise time."
- [ ] 8.3 Add a CHANGELOG entry under "Behavior changes": "Anonymise calls now return HTTP 422 when any skip-marked relation has a pending publicationConsent record in its objection window. Wait for the window to close, or set `publicationDecision` on the consent record via the existing consent UI, then retry."
- [ ] 8.4 Update the `WAVE-1-SMOKE-TESTS.md` (or its successor) — add a Wave 3 section covering the new flows: PATCH skip → consent record creation; prohibition reversal flow; anonymise blocking on pending consent.
- [ ] 8.5 Verify ADR-008 (controller→service→mapper layering) is respected by the new listener. The listener calls `ConsentService` (not the mapper directly). Document confirmation.

## 9. Quality and verification

- [ ] 9.1 Run `composer check:strict` — PHPCS, PHPMD, Psalm, PHPStan, unit tests. Clean. Fix pre-existing issues in touched files per the workflow rule.
- [ ] 9.2 Run `openspec validate publication-clearance-via-anonymise` — clean.
- [ ] 9.3 Manual smoke against a live stack:
  - Set up: a document with detected entities; one entity prohibited; one entity matching a standing consent; one entity matching nothing.
  - Click skip on each entity through the widget. Verify the prohibited one is locked + warning visible (D6). Verify the standing-consent one results in an auto-resolved consent record. Verify the no-match one results in a pending consent record with the WOO timer running.
  - Click anonymise. Verify 422 with the pending entity listed (blocking).
  - Set `publicationDecision: anonymize` on the pending consent via consent UI. Re-anonymise → success.
  - Re-PATCH skip on the same relation → verify no duplicate consent record (idempotency).
- [ ] 9.4 Confirm Nextcloud notifications appear correctly for prohibition-blocked attempts (visible in the user's notification bell, deep-link works).
