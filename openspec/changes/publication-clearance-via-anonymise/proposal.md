## Why

The `entity-publication-policies` change established the publicationConsent / publicationProhibition / standing-consent data model and stated that `ConsentService::createConsentRequest()` is the entry point. The canonical `consent-management` spec (REQ-CONS-07: CONS-048 / CONS-050) flagged that this method has no automated caller — consents can only be created by directly hitting `POST /api/consents`. The whole publication-clearance stack (prohibition matching, standing consents, WOO 28-day workflow) sits behind a method that nothing calls.

The original draft of this change proposed extending the anonymise endpoint with a parallel `unredactedEntities[]` field as the trigger. On review, that design has a key defect: WOO-pending entities (no policy match) would surface a 422 from the anonymise call on every first attempt, because their 28-day clock can't have ticked yet — leaving the operator unable to run anonymise until the WOO window closes. The fix isn't to retry-loop the anonymise call; it's to **move the consent-creation trigger off anonymise entirely** and onto the operator's per-entity decision moment.

That moment already has a primitive: `skipAnonymization: true` on an `EntityRelation` row (Wave 1.3 — `entity-relation-grondslagen`). Operationally that flag means "publish this entity unredacted in the document", which is exactly the decision the consent workflow regulates. Two decision channels for the same operator decision was the bug; unifying them is the fix.

This change subscribes to the new `EntityRelationDecisionUpdatedEvent` (OR — added in the amend to PR #1503) and, when `skipAnonymization` flips from `false` to `true`, calls `ConsentService::createConsentRequest()` for that entity. `PolicyMatchService` inside `createConsentRequest` handles pre-emption as before: standing-consent match → auto-resolved `consent_given`; prohibition match → consent rejected + listener reverses the PATCH + emits operator notification; no match → `pending` consent record with the WOO 28-day clock starting at decision time. The anonymise endpoint itself is unchanged in shape; it gains a defensive runtime check that rejects anonymise calls where a skip-marked relation still has an unresolved consent in its objection window.

## What Changes

- **NEW listener:** `DocuDeskEventListener` subscribes to `EntityRelationDecisionUpdatedEvent` (from OpenRegister, added in PR #1503's amend). When the event reports `skipAnonymization: false → true`, the listener calls `ConsentService::createConsentRequest()` for the entity referenced by the relation. The listener handles the three `PolicyMatchService` outcomes:
  - **Standing-consent match** → consent record auto-resolved (`consentStatus: consent_given`, `policyMatch` populated, `notificationStatus: skipped`). No further operator action required for this entity.
  - **No match** → consent record created with `consentStatus: pending`, `notificationStatus: pending`, `objectionDeadline` computed at the moment the operator flipped the skip switch. The 28-day clock starts now.
  - **Prohibition match** → consent record creation is rejected by `PolicyMatchService`. The listener (a) reverses the PATCH by calling `EntityRelationMapper::updateDecisionMetadata` to set `skipAnonymization: false`, (b) emits a Nextcloud notification to the acting user explaining which prohibition rule blocked the decision, (c) writes a structured audit-trail entry on the relation noting the attempted-and-reversed flip.

- **MODIFIED:** `ConsentService::createConsentRequest()` becomes idempotent on `(documentId, entityKey)` for `scope: "document"` records. Multiple `EntityRelation` rows for the same entity on the same document (multiple text positions) trigger the event multiple times but resolve to one consent record — the second-through-Nth event finds the existing record and updates operator-controlled fields without creating duplicates. Workflow state (`notificationStatus`, `notificationSentAt`, `objectionDeadline`, `objectionReceivedAt`, `objectionReason`, `consentStatus`, `policyMatch`, `publicationDecision`) is preserved across re-events; only operator-controllable fields (`entityType`, `contactEmail`, `contactAddress`, `legalBasis`, `notes`) are updated.

- **MODIFIED:** The anonymise endpoint gains a **defensive runtime check** (input shape unchanged). Before delegating to OR's `anonymizeDocument`, DocuDesk reads all `skipAnonymization: true` relations for the target file and verifies each one has a corresponding `publicationConsent` record in a non-blocking state — `consent_given`, `anonymized`, or a `pending` whose objection window has closed. If any skip-marked relation has a `pending` consent record whose `objectionDeadline` has not yet passed, the anonymise call MUST return HTTP 422 listing the still-blocking entities. This is not the "every first attempt fails" trap — it only fires when the operator is trying to anonymise while a WOO objection clock is still running, which is the legitimately-block-able case.

- **MODIFIED:** The widget's review surface (Wave 1.2 / 1.3 smoke-test widget, evolving toward the production publication-prep page) pre-checks the prohibition list client-side. When the operator hovers / clicks the skip switch on an entity that matches an active prohibition, the UI surfaces a red warning and disables the switch with an explanation. The server-side listener's PATCH-reversal is the defensive backstop for non-widget clients.

- **NO new endpoints, NO new schemas, NO new request fields.** The anonymise endpoint's payload and response shapes are unchanged. publicationConsent records (already an OR object), `EntityRelation.skipAnonymization` (Wave 1.3), and the existing dossier vocabulary are reused.

### Out of scope

- **Real notification dispatch** — email or postal templates, SMTP integration, dispatch retry, postal-address handling. publicationConsent records carry `notificationStatus: pending` and the objection deadline; operators advance status manually via `PUT /api/consents/{id}` once they've sent the notification through their existing out-of-band channel. A separate change `publicationconsent-notification-dispatch` adds the real stack.
- **Multi-basis legal-basis serialisation.** In v1 the listener creates the consent record with `legalBasis: null`; operators set the legal basis(es) through the existing consent UI's `PUT /api/consents/{id}` afterward. If multi-basis becomes operationally important, a follow-up change can introduce a sentinel-tagged notes serialisation (the design was drafted for the previous proposal — see git history).
- **Publication bases on the PATCH endpoint.** OR's `EntityRelationMapper::updateDecisionMetadata` whitelist remains `{bases, skipAnonymization}` — `bases` keeps its Wave 1.3 semantics ("Woo Art. 5 grondslagen for ANONYMISATION", not "Woo Art. 3 grounds for PUBLICATION"). Publication bases live on `publicationConsent.legalBasis` and `notes` only.
- **Multi-document publication-prep** in a single call. Each anonymise call is per-file. A folder/batch publication-prep flow is a separate change.
- **A separate "publication-prep" controller / endpoint.** The decision is captured by the existing per-relation PATCH; the listener closes the loop. No new endpoint is needed.
- **Final publication action** (output assembly, push to overheid.nl, etc.) — substantial separate work.
- **Per-relation publication decisions** (skipping one occurrence of an entity while anonymising another). `skipAnonymization` remains per-relation as a layout primitive, but consent records are per-entity. A single skip on any relation triggers one consent record covering the entity on that document.

## Capabilities

### New Capabilities

(none — this change extends existing capabilities without introducing new ones.)

### Modified Capabilities

- `consent-management`: `ConsentService::createConsentRequest()` becomes idempotent on `(documentId, entityKey)` for `scope: "document"` records; gains an automated caller via the new `EntityRelationDecisionUpdatedEvent` listener; notification dispatch stays stubbed (existing CONS-049 reaffirmed).
- `anonymization`: the anonymise endpoint gains a defensive runtime check rejecting calls that would anonymise a file while skip-marked relations still have unresolved-and-still-blocking consent records. Endpoint payload and response shapes are unchanged.

## Impact

- **Code (docudesk):**
  - `lib/EventListener/DocuDeskEventHandler.php` — new branch handling `EntityRelationDecisionUpdatedEvent`. Detect `isSkipAnonymizationActivated()`; resolve the entity/document context from the relation; call `ConsentService::createConsentRequest()`; on prohibition rejection, call `EntityRelationMapper::updateDecisionMetadata` to reverse the PATCH and dispatch a Nextcloud notification.
  - `lib/AppInfo/Application.php` — register the new event listener.
  - `lib/Service/ConsentService.php` — `createConsentRequest` becomes idempotent on `(documentId, entityKey)`. Lookup, partial update of operator-controlled fields, preservation of workflow state. Falls back to `entityText` when `entityKey` is null.
  - `lib/Service/AnonymizationService.php` — defensive runtime check before delegating to OR. Reads skip-marked relations for the file; queries `publicationConsent` records by `(documentId, entityKey)`; rejects with 422 when any pending consent's `objectionDeadline` is in the future.
  - `src/views/anonymization/AnonymizationWidget.vue` (or its production-grade successor) — client-side prohibition pre-check before allowing the skip switch to flip. Disabled state + tooltip for prohibited entities.

- **API contract:**
  - Anonymise endpoint: payload shape unchanged. Response shape unchanged in the success case. Adds HTTP 422 as a new failure response with a structured body listing the still-blocking consent records.
  - No new endpoints.

- **Cross-app:**
  - **Hard dep on `entity-relation-grondslagen` (OR)** — PR #1503's amend ships the `EntityRelationDecisionUpdatedEvent` this change subscribes to. Cannot ship before #1503 merges.
  - **Hard dep on `entity-publication-policies` (DocuDesk)** — `PolicyMatchService` is the policy resolver `createConsentRequest` uses internally. Cannot ship before that change merges.
  - **Soft dep on `anonymisation-grondslagen-and-prohibition-gate` (DocuDesk)** — the prohibition gate on `entities[]` (current anonymise) and this change's prohibition-handling on `skipAnonymization=true` use the same `PolicyMatchService`; either change can ship first.
  - No further OR changes required.

- **Privacy / compliance:** Closes the gap from canonical `consent-management` REQ-CONS-07 / CONS-048 / CONS-050 — publicationConsent creation now has an automated trigger via the per-entity decision moment. WOO publication clearance becomes an operationally-driveable workflow. The 28-day objection clock starts at decision time, not at anonymise time — which is the correct legal moment.

- **Performance:** One `createConsentRequest` call per `skipAnonymization` flip event. Listener executes synchronously after the PATCH but the PATCH already returned to the client at that point (event is post-commit). For typical operator workflow (a handful of skips per document review), negligible.

- **Migration:** None. publicationConsent records created from the new flow have the same shape as records created via direct `POST /api/consents`. Existing records are unaffected. Idempotency on re-event preserves prior workflow state.

- **Tests:**
  - Unit tests for the event listener (skip-flip happy path, prohibition rejection + PATCH reversal, no dispatch on non-skip-flip changes).
  - Unit tests for the idempotent `createConsentRequest` (new record, update existing, fallback to entityText, scope=document filter, workflow-state preservation).
  - Unit tests for the defensive anonymise-time check (no-block case, pending-but-expired case, pending-and-blocking case).
  - Integration test: PATCH `skipAnonymization: true` on a relation → consent record exists for the entity. PATCH on a prohibition-matched entity → PATCH reverses + notification dispatched. Anonymise call against a file with an in-window pending consent → 422.
