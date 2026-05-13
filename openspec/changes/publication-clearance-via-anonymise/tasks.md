## 1. Idempotency upgrade in ConsentService

- [ ] 1.1 Modify `lib/Service/ConsentService::createConsentRequest()` to look up an existing publicationConsent record matching `(documentId, entityKey)` (or `(documentId, entityText)` if `entityKey` is null on the input). The lookup must filter to `scope = "document"` records only — `scope: "entity"` standing consents must NEVER be matched as duplicates.
- [ ] 1.2 If a match exists: update the operator-controlled fields only (`entityType`, `legalBasis`, `notes`, `contactEmail`, `contactAddress`). Preserve workflow state (`notificationStatus`, `notificationSentAt`, `objectionDeadline`, `objectionReceivedAt`, `objectionReason`, `consentStatus`, `policyMatch`, `publicationDecision`).
- [ ] 1.3 Allow `policyMatch` to be POPULATED on update if it was previously null and `PolicyMatchService` now produces a match. Never allow `policyMatch` to be cleared — the operator can't downgrade a match by re-submitting; that's a rule-mutation event handled separately.
- [ ] 1.4 Add a return-type indicator (e.g. an array key `wasUpdated: bool`) so callers can distinguish "created" from "updated".
- [ ] 1.5 Unit tests: new record creation; update-by-entityKey; update-by-entityText fallback; workflow state preserved across updates; policyMatch populated when first match appears; scope=entity records never matched as duplicates.

## 2. Sentinel-tagged notes serialisation

- [ ] 2.1 Add a helper service `lib/Service/PublicationBasesNotesSerialiser.php` (or extend ConsentService with private helpers). One method: `serialise(string $existingNotes, array $additionalBases): string` returning the new notes content with the bracketed region added, replaced, or removed depending on input.
- [ ] 2.2 Implement region detection: locate `<!-- docudesk:additional-publication-bases:begin -->` and `<!-- docudesk:additional-publication-bases:end -->` in existing notes. If found, isolate the bracketed content for replacement. If only one tag is present (malformed): log a warning, treat as "no managed region", append fresh region at the end.
- [ ] 2.3 When `additionalBases` is empty (= input had only one basis), remove the bracketed region entirely; preserve everything else.
- [ ] 2.4 When `additionalBases` is non-empty, render the markdown bullet list inside the brackets per the spec.
- [ ] 2.5 Unit tests: empty existing notes + one basis → no region; empty existing notes + multiple bases → bracketed region only; existing operator notes + multiple bases → preserved + appended region; existing notes with a previous bracketed region + new bases → region replaced; bases shrink to one → region removed; malformed brackets → warning logged + clean append.

## 3. AnonymizationController integration

- [ ] 3.1 Update `lib/Controller/AnonymizationController::anonymize()` to accept a top-level optional `unredactedEntities[]` field. Validate at the entry: each entry MUST have `entityText`, `entityType` ∈ {PERSON, ORGANIZATION}, and `publicationBases` (non-empty array of strings). Optional: `entityId`, `entityKey`, `contactEmail`, `contactAddress`. Reject malformed input with HTTP 400 citing the offending field.
- [ ] 3.2 Validate the disjoint-set rule: an entity (matched by `entityKey` or `entityText` if no key) appearing in BOTH `entities[]` and `unredactedEntities[]` is rejected with HTTP 400.
- [ ] 3.3 Run the prohibition gate on `unredactedEntities[]` BEFORE invoking the existing anonymise pipeline. Use the same `PolicyMatchService` that the existing prohibition gate consults. The gate fires at any confidence (no override path on this gate). Collect rejected entries into a list.
- [ ] 3.4 If any rejections: respond HTTP 422 with body `{error, rejectedUnredacted: [...], fallback}` per the spec. Do NOT run the anonymise pipeline. Do NOT create any publicationConsent records. Atomic-fail.
- [ ] 3.5 If no rejections: run the existing anonymise pipeline (entities[] + bases passthrough + Change A's PDF conversion if outputFormat=pdf + Change B's basis-summary append if appendBasisSummary).
- [ ] 3.6 After the anonymise pipeline succeeds, iterate `unredactedEntities[]`. For each entry, call `ConsentService::createConsentRequest($documentId = $fileId, $entry.entityType, $entry.entityText, $register, $schema, [entityKey, contactEmail, contactAddress, publicationBases])`. Collect the results into `$createdConsents[]`.
- [ ] 3.7 Aggregate `createdConsents[]` into the response per the spec — each entry has `consentId`, `entityId`, `entityText`, `consentStatus`, `policyMatch`, `notificationStatus`, `objectionDeadline`, `wasUpdated`.

## 4. BatchAnonymizationController integration

- [ ] 4.1 Update `lib/Controller/BatchAnonymizationController::batchAnonymize()` to accept the same `unredactedEntities[]` field per file in the batch payload.
- [ ] 4.2 Run the prohibition gate per file. A gate-violation in one file does not block the rest of the batch — surface it as that file's per-file outcome (multi-status response shape).
- [ ] 4.3 For files that pass the gate: run anonymise + createConsentRequest per the per-doc flow.
- [ ] 4.4 Aggregate `createdConsents[]` per file in the multi-file response.

## 5. ConsentService: publicationBases handling

- [ ] 5.1 Update `createConsentRequest`'s `extra` parameter to accept a `publicationBases` array (in addition to its existing fields). When present, use the first element as `legalBasis` and pass the rest into `PublicationBasesNotesSerialiser` to merge with any existing or operator-authored notes.
- [ ] 5.2 If `publicationBases` is absent, fall back to existing behaviour — `legalBasis` and `notes` come from the `extra` array as before. Backwards-compatible.

## 6. Notification dispatch — confirm stub

- [ ] 6.1 Audit `ConsentService::createConsentRequest` and downstream — confirm that creating a record with `consentStatus: "pending"` does NOT trigger any SMTP activity, mail-job enqueue, or notification-dispatch side effect.
- [ ] 6.2 Add a unit test that creates a `pending` record and asserts no mail-related service is invoked.
- [ ] 6.3 Document the stub in CHANGELOG (per task 9.2 below) so operators understand the WOO timer starts but they must send notifications via existing channels until real dispatch lands in a follow-up change.

## 7. Unit tests (controller + service)

- [ ] 7.1 `tests/unit/Controller/AnonymizationControllerTest.php` — new test cases: `unredactedEntities` field accepted; missing required entry-level fields → 400; same entity in entities[] and unredactedEntities[] → 400; prohibition match → 422 with rejectedUnredacted; clean call → 200 with createdConsents[].
- [ ] 7.2 `tests/unit/Service/AnonymizationServiceTest.php` — orchestration tests: anonymise pipeline runs first, consent creation runs after, aggregation is correct, partial failure scenarios (createConsentRequest throws for one entry — does the call succeed for the rest? Decide: yes, but with a warning per affected entry).
- [ ] 7.3 `tests/unit/Service/ConsentServiceTest.php` extension — idempotency tests; sentinel-tagged notes tests; `wasUpdated` flag in return value; scope=entity records never matched as duplicates.
- [ ] 7.4 `tests/unit/Controller/BatchAnonymizationControllerTest.php` — batch flow with mixed per-file outcomes (some files pass the gate, some 422).

## 8. Integration tests

- [ ] 8.1 Newman / Postman: per-document anonymise with `unredactedEntities` + a clean entry → verify publicationConsent record created with `consentStatus: pending` + objection deadline; response contains createdConsents[].
- [ ] 8.2 Newman: per-document anonymise with `unredactedEntities` matching a standing consent → verify record has `consentStatus: consent_given`, `policyMatch` populated, `notificationStatus: skipped`.
- [ ] 8.3 Newman: per-document anonymise with `unredactedEntities` matching a publicationProhibition → verify HTTP 422 with rejectedUnredacted body; no records created; no anonymisation happened.
- [ ] 8.4 Newman: re-submit the same call → verify existing records updated (not duplicated); workflow state preserved; createdConsents[i].wasUpdated is true.
- [ ] 8.5 Newman: same entity in both lists → 400 with conflict identifier.
- [ ] 8.6 Newman: multiple `publicationBases` per entity → verify legalBasis = first; notes contains the sentinel-tagged region; second submit replaces region cleanly.

## 9. Documentation

- [ ] 9.1 Update `docs/features/publication-consent-process.md` describing the new flow: extract → review → submit (with both `entities[]` and `unredactedEntities[]`) → publicationConsent records created → operator drives the WOO workflow per record. Cross-link to `entity-publication-policies` for the policy-pre-emption logic and to `anonymisation-grondslagen-and-prohibition-gate` for the existing gate semantics.
- [ ] 9.2 CHANGELOG entry under "Added": `unredactedEntities[]` field on per-document and batch anonymise endpoints; idempotent createConsentRequest; sentinel-tagged additional-bases serialisation; createdConsents[] response aggregation.
- [ ] 9.3 CHANGELOG entry under "Behavior changes": HTTP 422 added as a possible response when prohibited entities are placed in unredactedEntities[]. Pre-change clients that don't supply unredactedEntities are unaffected.
- [ ] 9.4 Document the notification-dispatch stub explicitly: pending records carry the WOO timer; operators send notifications via existing channels and mark status manually until real dispatch lands.

## 10. Quality and verification

- [ ] 10.1 Run `composer check:strict` — clean. Fix any pre-existing issues in touched files.
- [ ] 10.2 Manual smoke against a live stack: configure standing consent + prohibition rules; trigger an anonymise call with mixed entities[] and unredactedEntities[]; verify the four scenarios (clean pending; standing-match; prohibition rejection; re-submit idempotency).
- [ ] 10.3 Run `openspec validate publication-clearance-via-anonymise` — clean.
