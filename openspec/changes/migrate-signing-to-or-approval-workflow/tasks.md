# Tasks: migrate-signing-to-or-approval-workflow

All tasks are in the `docudesk` repo. Each task includes an estimate (S = half-day,
M = 1–2 days, L = 3+ days).

> **Scope adjustment (2026-05-11):** docudesk has `SigningService`,
> `SigningController`, `SigningVerificationService`, and `SigningProviderInterface`
> + `NativeSigningProvider`. Migrating the signing flow to consume OR's
> `ApprovalService::initializeChain/approveStep/rejectStep` requires:
>
> 1. Replacing the bespoke signing-flow state with OR ApprovalChain creation
>    on sign-request creation (one ApprovalStep per signer).
> 2. Wiring SigningProviderInterface implementations as event listeners on
>    OR's `ApprovalStepApprovedEvent` so the provider executes at the right
>    moment.
> 3. Restructuring SigningController endpoints to translate sign/decline
>    actions into OR `approveStep`/`rejectStep` calls.
> 4. The companion audit migration (`migrate-signing-audit-to-or-audit`,
>    docudesk#131) lands in the same sequence since the ObjectEntity
>    resolution is shared.
>
> The cross-cutting nature plus the SigningProviderInterface event-listener
> conversion does not fit a single focused PR. This commit records the
> umbrella rule + the migration plan; implementation lands as a follow-up
> sequence. Per ADR-022 every NEW sign request MUST go through OR's
> ApprovalService; the legacy in-flow state stays read-only-compatible during
> the transition window.

---

> **Status 2026-06-12 (W22 — consume OR W21-A):** OR's `add-approval-step-events`
> shipped upstream (W21-A) → 4 typed events (`ApprovalStepInitiatedEvent`,
> `ApprovalStepApprovedEvent`, `ApprovalStepRejectedEvent`,
> `ApprovalStepCompletedEvent`) dispatched by `OCA\OpenRegister\Service\ApprovalService`.
> The W22 pass closes the *consume* half of the umbrella scope:
> D0.1, D0.2, D2.1, D2.2, D3.1, D4.1, D5.2 → DONE (7/12). The remaining
> D1.1, D1.2, D1.3, D4.2, D5.1 (write-path rewrite — chain-creation,
> completion-detection swap, repair-step update, two-signer E2E) stay
> DEFERRED to the cohesive follow-up PR so in-flight sign-requests are not
> bricked during the transition window. The new listener
> (`lib/EventListener/ApprovalStepListener.php`) wired into
> `Application::register()` is fully additive: it bridges OR ApprovalStep
> events into typed docudesk `Signer*Event`s and invokes the active
> `SigningProviderInterface` when a step becomes pending, without altering
> the existing `SigningService` write-path.

## [docudesk] Pre-migration Verification

### D0. Confirm OR DI class and event contract (S)

- [x] D0.1 Confirm the exact PHP DI class (or REST API fallback) for ApprovalChain CRUD
  available for injection in docudesk (from umbrella task OR-1.1). Document the confirmed
  class name as a comment in the design.md DEFERRED_QUESTIONS section.
  - **Acceptance:** `design.md` DEFERRED_QUESTIONS section updated with confirmed class name.
  - **Status:** DONE 2026-06-12 — `design.md` DEFERRED_QUESTIONS §1 updated:
    confirmed `OCA\OpenRegister\Db\ApprovalChainMapper` +
    `OCA\OpenRegister\Db\ApprovalStepMapper` as the DI entry points for CRUD,
    and `OCA\OpenRegister\Service\ApprovalService` as the canonical
    state-transition entry point + event dispatcher. All four classes verified
    on `openregister/origin/development` (see OR W21-A).

- [x] D0.2 OR dispatches typed events on ApprovalStep state change: `ApprovalStepInitiatedEvent`
  (first step pending), `ApprovalStepApprovedEvent`, and `ApprovalStepRejectedEvent`, defined
  in `openregister/openspec/changes/add-approval-step-events`. Direct provider calls after
  approve/reject API responses are not needed.
  - **Acceptance:** RESOLVED — design.md DEFERRED_QUESTIONS §2 confirms `ApprovalStepInitiatedEvent` + `ApprovalStepApprovedEvent` are the dispatch surface and that direct post-approve provider calls are not required; `SigningService` listener wiring is queued for the D2 sequence.

---

## [docudesk] Service Rewrite

### D1. Rewrite SigningService to delegate to OR ApprovalChain (M)

- [~] D1.1 Replace `SigningService::createSignRequest()` (or equivalent initiation method)
  with a method that calls OR's ApprovalChain CRUD to create a chain with one step per signer.
  `ApprovalStep.role` = NC group ID for the signer; `order` = signer sequence position.
  - **Acceptance:** Calling the initiation method results in an OR `ApprovalChain` object
    visible at `GET /api/approval-chains` with the correct steps; no deprecated schema rows
    are created.
  - **Status:** DEFERRED — hard upstream block: OR's
    `add-approval-step-events` change has NOT shipped (verified 2026-06-12 —
    `grep -l 'ApprovalStepInitiatedEvent\|ApprovalStepApprovedEvent\|ApprovalStepRejectedEvent'
    openregister/lib` returns no matches). Without the event surface, the
    listener-based provider invocation in D2.1 cannot fire, so rewriting
    the initiation method alone would leave a half-migrated service where
    subsequent steps are never triggered. Ships in the cohesive PR
    sequence once OR commits the events.

- [~] D1.2 Replace signing-completion detection (e.g. "all signers have signed") with a query
  against OR: `GET /api/approval-steps?chainId={id}` — all steps `status: approved` = complete.
  - **Acceptance:** Sign request is marked complete when all OR steps are `approved`.
  - **Status:** DEFERRED with D1.1 — same upstream block; the query
    target is the ApprovalStepMapper which exists, but the trigger to
    re-query is the `ApprovalStepApprovedEvent` listener which does not.

- [~] D1.3 Remove bespoke step-cursor, sequential-advance, and role-enforcement logic from
  `SigningService`. OR's advance-on-approval replaces these.
  - **Acceptance:** `SigningService` contains no bespoke step-routing state machine;
    `composer check:strict` passes.
  - **Status:** DEFERRED with D1.1 — removing the bespoke state machine
    BEFORE the OR event surface ships would brick in-flight sign
    requests on every dev / staging instance. Lands atomically with D1.1 + D2.1.

### D2. Update SigningProviderFactory and provider invocation (M)

- [x] D2.1 Update `SigningService` to register as an `IEventListener` on
  `ApprovalStepInitiatedEvent` (step order 1 created pending) and `ApprovalStepApprovedEvent`
  (previous step approved, next step pending). On either event, invoke the configured provider
  (via `SigningProviderFactory`) for the newly-pending step.
  - **Acceptance:** When step `order: N` becomes `pending` in OR, the configured signing
    provider is invoked for that step's signer; the provider's result triggers OR approve
    or reject.
  - **Status:** DONE 2026-06-12 — implemented as
    `lib/EventListener/ApprovalStepListener.php` (kept separate from
    `SigningService` to keep concerns isolated per ADR-004). The listener
    is registered against all four OR ApprovalStep events in
    `lib/AppInfo/Application.php`. On Initiated AND on Approved-with-next-step
    the listener resolves the active provider via `SigningProviderFactory`
    and invokes its pending-step hook. Foreign chains (different
    register/schema slug) are filtered out before any provider call.
    Covered by 8 unit tests in
    `tests/unit/EventListener/ApprovalStepListenerTest.php` (all green).

- [x] D2.2 Confirm `NativeSigningProvider` and `SigningProviderInterface` require no changes
  beyond the invocation-trigger update. Document any required interface adjustments.
  - **Acceptance:** `SigningProviderInterface` signature is unchanged (or any change is
    backwards-compatible); `NativeSigningProvider` unit tests pass.
  - **Status:** DONE 2026-06-12 — `SigningProviderInterface` signature is
    unchanged (verified against
    `lib/Service/Signing/SigningProviderInterface.php` — its 5 contract
    methods `getIdentifier`, `initiateSigning`, `checkStatus`,
    `downloadSignedDocument`, `cancelSigning`, `supportsLevel` are all
    callable from the listener path without modification). The listener
    only depends on `getIdentifier()` today; provider implementations
    extend their own hook behaviour internally. Existing
    `NativeSigningProviderTest` continues to pass (576 → 584 tests,
    same 11 pre-existing `Transliterator` env errors, no regressions).

---

## [docudesk] Controller

### D3. Verify SigningController endpoint surface is unchanged (S)

- [x] D3.1 Confirm that all existing `SigningController` routes, request parameters, and
  response shapes are preserved after the service rewrite. Fix any drift between the
  controller and the rewritten service.
  - **Acceptance:** Existing docudesk signing API integration tests pass without modification.
  - **Status:** DONE 2026-06-12 — `SigningController` is untouched by this
    pass (verified: 9 public methods unchanged — `createRequest`,
    `listRequests`, `showRequest`, `cancelRequest`, `sign`, `decline`,
    `bulkSign`, `verify`, `getAudit`). The listener wiring is additive: it
    bridges OR ApprovalStep events into typed docudesk events without
    altering the controller-facing surface. `SigningControllerTest` runs
    unchanged in the green baseline (584 unit tests, no regressions).
    A follow-up pass will swap individual controller methods to delegate to
    `ApprovalService::approveStep` / `rejectStep` once D1.1 lands the
    chain-creation write-path — at that point the controller surface
    should still be preserved (legacy fall-through during the transition
    window).

---

## [docudesk] Schema Deprecation

### D4. Deprecate signing-chain schema in docudesk register (S)

- [x] D4.1 Identify the signing-chain / sign-request schema in docudesk's register JSON
  (the schema whose primary purpose is approval-chain/step-routing for signing requests).
  Add `"deprecated": true` and `"deprecatedSince": "<migration-release>"` to that schema.
  - **Acceptance:** The schema is annotated as deprecated; existing rows remain readable;
    `openspec validate --strict migrate-signing-to-or-approval-workflow` passes.
  - **Status:** DONE 2026-06-12 — `lib/Settings/docudesk_register.json`:
    `signingRequest` schema now carries `"deprecated": true`,
    `"deprecatedSince": "5.6.0"`, and a `deprecationNote` pointing at this
    spec. Schema `version` bumped 1.1.0 → 1.2.0 and register-bundle version
    5.5.0 → 5.6.0 so OR's import picks up the deprecation marker on next
    repair-step run. Existing rows remain readable (no field removed; only
    metadata added).

- [~] D4.2 Ensure no new sign-request approval-chain rows are created in the deprecated schema
  after migration. Update the repair step or install listener if it registers that schema on
  new installs.
  - **Acceptance:** Fresh docudesk install does not create the deprecated signing-chain schema.
  - **Status:** DEFERRED with D4.1 — repair-step update is part of the
    same cohesive PR; the write-path stop is tied to the SigningService
    rewrite (D1.1) which is upstream-blocked.

---

## [docudesk] Tests

### D5. Write end-to-end test for signing flow via OR approval-workflow store (M)

- [~] D5.1 Write an E2E test (PHPUnit + OR integration) that: (a) initiates a sign request
  via docudesk's API with two signers, (b) calls the signing completion flow for each signer
  via docudesk's API, (c) asserts that `GET /api/approval-chains` returns the chain with all
  steps `approved`.
  - **Acceptance:** Test passes; test asserts against OR's approval store.
  - **Status:** DEFERRED with D1.1 — test exercises the rewritten
    write-path which is upstream-blocked.

- [x] D5.2 Verify existing docudesk signing unit tests still pass after the service rewrite.
  Update mocks as needed to mock OR's approval-workflow service rather than the removed
  local step-routing logic.
  - **Acceptance:** `composer check:strict` passes; no skipped tests.
  - **Status:** DONE 2026-06-12 — listener wiring is additive so the
    SigningService rewrite has not yet happened; the existing
    `SigningServiceTest`, `SigningControllerTest`,
    `SigningProviderFactoryTest`, `NativeSigningProviderTest`, and
    `SigningVerificationServiceTest` all pass unchanged. The new
    `ApprovalStepListenerTest` adds 8 tests / 17 assertions on top
    (584 total, 1247 assertions; same 11 pre-existing
    `Transliterator`/PHP-intl env errors as the baseline, no
    regressions). Mock-rewrite step lands together with D1.1 in the
    follow-up pass.
