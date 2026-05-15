## Context

`ConsentService::createConsentRequest()` exists and is correct — given `{documentId, entityType, entityText, register, schema, extra}` it persists a `publicationConsent` record with `consentStatus: "pending"`, computes `objectionDeadline` from configuration, and (per `entity-publication-policies`) consults `PolicyMatchService` to apply pre-emption. The canonical `consent-management` spec (REQ-CONS-07: CONS-048 / CONS-050) flagged that this method has no automated caller — invocation is programmatic via direct `POST /api/consents` only. The publication-clearance stack is plumbing without a tap.

The first draft of this change proposed adding the tap on the anonymise endpoint: a parallel `unredactedEntities[]` field carrying per-entity decisions that the controller would route through `createConsentRequest()`. Two flaws were identified on review:

1. **Two decision channels for the same operator decision.** Wave 1.3 (`entity-relation-grondslagen`) already gives operators a per-entity decision primitive on the `EntityRelation` row — `skipAnonymization: true` means "leave this entity visible in the document". That's the same operator action publication-clearance regulates. A parallel `unredactedEntities[]` on anonymise duplicates the decision surface.
2. **422-on-every-first-anonymise for WOO-pending entities.** With consent-creation at anonymise time, every entity that doesn't match a prohibition or standing consent (the common case requiring the 28-day WOO workflow) would 422 the anonymise call until the objection window closes. Operators can't run anonymise without first running anonymise. The 28-day clock has to start at decision time, not at anonymise time.

This rewrite collapses the two channels into one and moves the trigger to decision time:

- `skipAnonymization: false → true` on a relation IS the publication-clearance decision.
- OR (Wave 1.3 + the amend on PR #1503) dispatches `EntityRelationDecisionUpdatedEvent` after the PATCH commits.
- DocuDesk listens, calls `createConsentRequest()` per skip-flipped entity.
- The 28-day clock starts the moment the operator flipped the switch.
- The anonymise endpoint stays unchanged in shape; it gains a runtime guard against anonymising a file whose skip-marked entities still have unresolved-and-blocking consents.

The change interacts with three already-specced or in-flight changes:

- `entity-relation-grondslagen` (OR — PR #1503) ships the `skipAnonymization` flag, the PATCH endpoint, and (in the amend) the `EntityRelationDecisionUpdatedEvent`. Hard dep.
- `entity-publication-policies` (DocuDesk) defines `PolicyMatchService` and its pre-emption logic inside `createConsentRequest`. Hard dep.
- `anonymisation-grondslagen-and-prohibition-gate` (DocuDesk) carries the prohibition-gate scaffold on the `entities[]` path. Soft dep — either side of the prohibition gate (anonymise-path or unredact-path) can land first; the second consumes the same `PolicyMatchService`.

## Goals / Non-Goals

**Goals:**

- Close the canonical `consent-management` REQ-CONS-07 gap — `createConsentRequest()` gets an automated caller.
- Move the 28-day WOO objection clock to decision time so the legal timer starts when the operator commits to publishing unredacted, not when they hit anonymise.
- Reuse `skipAnonymization` (Wave 1.3) as the single decision primitive — no parallel `unredactedEntities[]` array, no new request fields on the anonymise endpoint.
- Keep `createConsentRequest` idempotent on `(documentId, entityKey)` so multiple relations for the same entity (multiple text positions of the same person) resolve to one consent record.
- Guard the anonymise endpoint defensively: do not anonymise a file whose skip-marked entities still have pending consents in their objection window.
- Prohibition handling: PATCH-time client-side warning + listener-side reversal — never silently anonymise or unmask a prohibition-matched entity.

**Non-Goals:**

- Build a separate publication-prep controller / endpoint. The decision lives on the relation; the listener closes the loop.
- Real notification dispatch (SMTP, postal address handling, retry logic). Separate change.
- Multi-document publication-prep in a single call. Separate change.
- Multi-basis legal-basis serialisation. Defer to follow-up — `legalBasis` is set later via `PUT /api/consents/{id}`.
- Auto-resolution of contact information from external directories.
- Final publication action (output assembly, push to overheid.nl).
- Per-relation publication semantics. `skipAnonymization` stays per-relation for layout reasons; consent records are per-entity-per-document.

## Decisions

### D1. The trigger is `EntityRelationDecisionUpdatedEvent`, not the anonymise call

`skipAnonymization: false → true` on a relation IS the operator's "publish this entity unredacted" decision. OR's `EntityRelationMapper::updateDecisionMetadata` dispatches `EntityRelationDecisionUpdatedEvent` after persisting the change (added in the amend to PR #1503). DocuDesk's `DocuDeskEventListener` subscribes and routes the event to a handler that calls `ConsentService::createConsentRequest()` for the relation's entity.

**Rationale:**

- The decision and the consent record happen in the same operator action — the click that flips skip. No follow-up "submit" step.
- The 28-day WOO clock starts at decision time, which is the correct legal moment (the operator committed to publication; the clock should already be running by the time anonymise is invoked).
- Single decision channel: there's no `unredactedEntities[]` to keep in sync with `skipAnonymization` on the relation.
- Operationally separable: the anonymise endpoint stays focused on file mutation; the consent layer is event-driven.

**Trade-off:** The trigger is post-commit — by the time DD's listener fires, the PATCH has already returned to the client. If the policy layer rejects the consent (prohibition match), the listener has to reverse the PATCH (a second write). This eventual-consistency split is the price of post-commit dispatch; the alternative (a vetoable pre-event in OR) would couple OR's persistence semantics to DD's policy logic and was rejected as cross-app leakage. See D4 for how reversal is handled.

### D2. `createConsentRequest` is idempotent on `(documentId, entityKey)` for `scope: "document"` records

A document can contain multiple relations for the same entity (multiple text positions of "Jan Janssen"). Each `skipAnonymization: true` PATCH fires its own event. Without idempotency, each event would create a duplicate consent record per relation. The fix: `createConsentRequest` first looks up an existing `scope: "document"` record matching `(documentId, entityKey)`; if found, it updates the operator-controlled fields and preserves workflow state.

**Lookup semantics:**

1. If `entityKey` is supplied: match by `(documentId, entityKey, scope = "document")`.
2. If `entityKey` is null: fall back to `(documentId, entityText, scope = "document")`. Legacy records and edge cases.
3. `scope: "entity"` records (standing consents) are NEVER matched as duplicates of a per-document call — they live in a different conceptual space.

**On-match update rules:**

| Field | Behaviour |
|---|---|
| `entityType` | Updated (rare — only when detector reclassified) |
| `legalBasis` | Updated (operator may have set it via the consent UI between events) |
| `notes` | Updated |
| `contactEmail` | Updated |
| `contactAddress` | Updated |
| `notificationStatus` | **Preserved** |
| `notificationSentAt` | **Preserved** |
| `objectionDeadline` | **Preserved** (28-day clock keeps running across re-events) |
| `objectionReceivedAt` | **Preserved** |
| `objectionReason` | **Preserved** |
| `consentStatus` | **Preserved** (workflow state) |
| `policyMatch` | **Preserved unless it was null and is now non-null**: a rule that newly applies can be set; an existing reference is not cleared by re-event. |
| `publicationDecision` | **Preserved** |

**Rationale:**

- Multiple relations on the same entity → one consent record. Operator-perceived semantic, not per-relation.
- Re-event (e.g. operator toggles skip off then on again) doesn't restart the WOO clock.
- The exception for `policyMatch` (null → non-null) lets a newly-added standing consent reach an in-flight pending record via the re-event path. The retroactive logic in `entity-publication-policies` handles the more aggressive cases (rule mutation) separately.

### D3. Three outcomes from `PolicyMatchService` resolve as documented in `entity-publication-policies`

The listener calls `createConsentRequest` and lets `PolicyMatchService` (already wired) decide the outcome. Three branches:

| Match result | Consent record state | Listener follow-up |
|---|---|---|
| **Prohibition match** | Create rejected: `createConsentRequest` throws `PolicyRejectedException` (new typed exception — extends the existing exception hierarchy). | Reverse PATCH (D4). Dispatch notification (D4). |
| **Standing-consent match** | `consentStatus: consent_given`, `notificationStatus: skipped`, `objectionDeadline: null`, `policyMatch: <standing-consent uuid>`. | None — consent is final, operator may proceed to anonymise. |
| **No match** | `consentStatus: pending`, `notificationStatus: pending`, `objectionDeadline: <now + configured period>`, `policyMatch: null`. WOO timer starts. | None — operator monitors the consent record through its 28-day window via existing UI. |

All three outcomes are pre-existing semantics from `entity-publication-policies`. This change adds no new branching logic to `PolicyMatchService`; only the trigger that hits `createConsentRequest`.

### D4. Prohibition match: listener reverses the PATCH + dispatches a Nextcloud notification

The post-commit event means the PATCH has already returned `200 OK` to the client by the time the listener discovers a prohibition match. The listener handles this with three actions:

1. **Reverse the PATCH.** Call `EntityRelationMapper::updateDecisionMetadata($relation, ['skipAnonymization' => false], $actingUser)`. This emits its own audit-trail entry (recording the reversal explicitly) AND dispatches a follow-up event (idempotency in the listener prevents an infinite loop — see below).
2. **Dispatch a Nextcloud notification.** Use the standard `\OCP\Notification\IManager` API to notify the acting user. The notification text identifies which prohibition rule matched and which entity was blocked. Clicking the notification navigates to the relevant document review surface so the operator can adjust.
3. **Audit context.** The audit-trail entry from step 1 includes a structured note (`reason: "policy_rejection"`, `prohibitionRule: <uuid>`, `originalEvent: <ref>`) so reviewers can trace the operator's attempt + the system's reversal.

**Loop prevention.** Reversing the PATCH dispatches `EntityRelationDecisionUpdatedEvent` again with `skipAnonymization: true → false`. The listener checks `isSkipAnonymizationActivated()` (returns true only for `false → true` transitions). Reversal events return false and are ignored — no further action.

**Client-side widget pre-check (D6) is the first line of defence.** The listener's reversal is the backstop for non-widget clients (curl, automation, third-party UIs) that bypass the client check.

**Rationale:**

- Privacy-fail-loud principle. Silently anonymising a prohibited-but-skip-marked entity would betray the operator's expressed intent. Reversing the PATCH and notifying surfaces the conflict.
- Eventual consistency (PATCH succeeded, then reversed) is acceptable because the consent layer is the authoritative truth. The state visible to a downstream consumer querying after the listener finishes is the consistent state.
- A vetoable pre-event in OR would have prevented the brief inconsistency but would couple OR to DD's policy layer. The brief inconsistency is the cost of clean app boundaries.

### D5. Defensive anonymise-time runtime check

The anonymise endpoint gains a runtime check before delegating to OR's `anonymizeDocument`:

```
1. Read all EntityRelation rows for the target file where skipAnonymization = true.
2. For each, look up the corresponding publicationConsent record by (documentId, entityKey).
3. For each consent record found:
     - consentStatus in {consent_given, anonymized} → not blocking.
     - consentStatus = pending AND objectionDeadline < now → not blocking (window closed).
     - consentStatus = pending AND objectionDeadline >= now → BLOCKING (still in objection window).
     - consentStatus = objection_received → status depends on publicationDecision:
         - publicationDecision = anonymize → not blocking (operator decided to anonymise anyway).
         - publicationDecision = publish_with_consent → not blocking (operator overrode).
         - publicationDecision = pending → BLOCKING (objection still under review).
4. If any relation is blocking, return HTTP 422 with a structured body listing the blocking entities and the consent records' current state.
5. Otherwise, proceed to anonymisation as usual.
```

**The 422 body shape:**

```json
{
  "error": "<localised string>",
  "blockingConsents": [
    {
      "consentId": "<uuid>",
      "entityText": "<string>",
      "consentStatus": "pending",
      "objectionDeadline": "2026-05-22T11:00:00Z",
      "reason": "objection_window_open"
    }
  ]
}
```

**Rationale:**

- The "422 on first anonymise" failure mode the old draft suffered from required EVERY skip to delay anonymise. The new check only fires when the consent layer says "this is still blocking" — which is the legitimate case (the WOO window is open and the operator hasn't waited it out yet).
- For most workflows, the operator marks skip → consent record is auto-resolved (standing-consent match) → anonymise call proceeds immediately. Only entities entering the WOO workflow block anonymise, and only until the window closes.
- The check is defensive — the legitimate path is for the operator to wait for the objection window or trigger an early decision via the consent UI. Anonymise blocking is the safety net.

### D6. Widget pre-checks the prohibition list client-side

The Anonymisation widget (currently smoke-test grade, evolving toward production publication-prep) loads `GET /apps/docudesk/api/policy/prohibitions` once per session and caches the result. When the operator hovers or clicks the skip switch on an entity, the widget normalises the entity text and checks against the cached prohibition rules (exact, normalized, BSN, KvK match types). On a match:

- Switch is rendered disabled.
- Tooltip / inline note explains "This entity is on the publication prohibition list (rule: <ruleName>). Cannot be published unredacted."
- The widget never sends the PATCH for that entity.

**Rationale:**

- Operator UX: the skip is visibly impossible, no surprise notification + reversal flow.
- Performance: prohibition list is typically small (a few dozen rules); client-side cache is cheap; the matching primitives mirror the server-side `PolicyMatchService`.
- The listener's PATCH-reversal (D4) is the defensive backstop. Non-widget clients (curl, automation, third-party UIs) that bypass the client check still hit the reversal path.

**Trade-off:** the client-side normalisation must match the server's. The widget uses the same four match types (`exact`, `normalized`, `bsn`, `kvk`) and the same normalisation rules (Latin transliteration + lowercase). A shared JS implementation can be extracted later if drift becomes a problem; for v1 each side implements independently with a documented spec for the normalisation rules.

### D7. Notification dispatch stays stubbed in v1

Per the original draft's D5 (unchanged in this rewrite): publicationConsent records created with `consentStatus: pending` carry `notificationStatus: pending` and a computed `objectionDeadline`. The system does NOT automatically send any email or postal notification.

Operators advance `notificationStatus` to `sent` manually via the existing `PUT /api/consents/{id}` once they've sent the notification through their existing out-of-band channel. This reaffirms the canonical `consent-management` CONS-049.

A separate change `publicationconsent-notification-dispatch` will add the real SMTP / postal stack. When it lands, the stub becomes a real dispatch; this change's spec doesn't need to be replaced.

## Risks / Trade-offs

- **Listener failure means the consent record isn't created.** Mitigation: the listener wraps the `createConsentRequest` call in `try/catch`, logs failures loudly, and emits a notification to the acting user that the consent creation failed (so they can retry by toggling skip off and back on, or contact admin). Listener failure does NOT roll back the PATCH — eventual consistency.

- **Reversal-loop on prohibition handling.** Mitigation: `isSkipAnonymizationActivated()` returns true only on `false → true` transitions. The reversal write is `true → false`, which doesn't re-trigger the prohibition handler. Verified by unit test.

- **Race: operator runs anonymise WHILE the listener is mid-execution.** Possible if the operator clicks skip and immediately clicks anonymise. Mitigation: the anonymise defensive check (D5) re-reads the consent records, so by the time it runs the listener has either committed or failed. If the listener failed AND the operator is racing, anonymise proceeds as if no consent record exists — this is a degraded case the operator will see in the consent register afterward. For v1 acceptable; if it becomes a problem a small delay or a transactional read can be added.

- **`(documentId, entityKey)` uniqueness across event re-events.** Mitigation per D2: lookup-before-create. If two events fire near-simultaneously for the same entity (rare — relations are sequenced one at a time per PATCH), the second event sees the record from the first and updates rather than duplicates. Database-level uniqueness constraint could be added later for belt-and-braces; not required for v1.

- **Anonymise blocking the operator on a 28-day WOO window.** This is correct behaviour, not a bug — the legal workflow requires waiting. Operators can short-circuit a particular entity by toggling skip off (anonymise it instead) or by explicitly entering a `publicationDecision` via the consent UI. Document this in the user-facing help text.

- **Widget client-side normalisation diverging from server.** Mitigation per D6: documented spec for normalisation rules, both sides implement independently. If drift is observed, extract a shared JS module.

- **Cross-change ordering — `EntityRelationDecisionUpdatedEvent` doesn't exist yet on `development`.** Mitigation: hard dep on PR #1503's amend. This change's implementation cannot land before #1503 + the amend merge. If `PolicyMatchService` (`entity-publication-policies`) isn't available yet, the listener fails-closed (treat as "no policy matches" → `pending` record with WOO workflow). The check on `class_exists` covers this transition window.

## Migration Plan

1. **Land OR PR #1503 + the amend.** This change's hard dep. Once merged on OR's `development`, the event class and dispatch are available.
2. **Land DocuDesk `entity-publication-policies` (PR #147).** This change's other hard dep. Once merged, `PolicyMatchService` is available.
3. **This change's apply phase** delivers:
   - `DocuDeskEventHandler` branch for `EntityRelationDecisionUpdatedEvent`.
   - `ConsentService::createConsentRequest` idempotency upgrade.
   - `AnonymizationService` defensive runtime check.
   - Widget client-side prohibition pre-check.
   - Tests, docs, CHANGELOG entry.
4. **Release.** The publication-clearance pipeline is now operationally driveable.

**Rollback:** Disable the event listener (remove the branch in `DocuDeskEventHandler::dispatchPolicyRetroactive` or the equivalent location). The PATCH endpoint stays functional but no consent records are created automatically. Existing consent records are untouched. The system reverts to the canonical CONS-048 / CONS-050 "no automated caller" gap — recoverable to pre-change behaviour.

## Seed Data

Not applicable — this change introduces no new schemas or seed objects. publicationConsent records are created at runtime via the new listener-driven flow.

## Open Questions

- **Should we add a `consentId` field to `EntityRelation` so the listener can record the consent record's UUID back on the relation?** Provisional: no — the lookup by `(documentId, entityKey)` is fast (indexed). Adding a denormalised back-reference would couple OR's schema to DD's consent records, which violates the app-boundary discipline elsewhere in the design. Confirm at apply time.

- **Notification UX details** — what does the prohibition-blocked notification look like? Provisional: standard Nextcloud notification with title "Publication of '<entity>' was prevented by a privacy rule", body referencing the rule name + a deep link to the document. Confirm at apply time with frontend.

- **`PolicyRejectedException` — new typed exception, or reuse existing?** Provisional: introduce a new exception in `lib/Exception/PolicyRejectedException.php`. The existing exception hierarchy is for generic validation; this is a semantic rejection from the policy layer specifically. Confirm at apply time.

- **Should the defensive check be runnable as a dry-run query** (`GET /api/anonymization/precheck/{fileId}` returning the list of blocking consents without attempting anonymise)? Provisional: defer — the 422 from the actual anonymise call gives the same information; the dry-run is a polish feature for a follow-up.
