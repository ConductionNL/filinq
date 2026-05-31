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

## [docudesk] Pre-migration Verification

### D0. Confirm OR DI class and event contract (S)

- [x] D0.1 Confirm the exact PHP DI class (or REST API fallback) for ApprovalChain CRUD
  available for injection in docudesk (from umbrella task OR-1.1). Document the confirmed
  class name as a comment in the design.md DEFERRED_QUESTIONS section.
  - **Acceptance:** `design.md` DEFERRED_QUESTIONS section updated with confirmed class name.

- [x] D0.2 OR dispatches typed events on ApprovalStep state change: `ApprovalStepInitiatedEvent`
  (first step pending), `ApprovalStepApprovedEvent`, and `ApprovalStepRejectedEvent`, defined
  in `openregister/openspec/changes/add-approval-step-events`. Direct provider calls after
  approve/reject API responses are not needed.
  - **Acceptance:** RESOLVED — design.md DEFERRED_QUESTIONS §2 updated accordingly.

---

## [docudesk] Service Rewrite

### D1. Rewrite SigningService to delegate to OR ApprovalChain (M)

- [x] D1.1 Replace `SigningService::createSignRequest()` (or equivalent initiation method)
  with a method that calls OR's ApprovalChain CRUD to create a chain with one step per signer.
  `ApprovalStep.role` = NC group ID for the signer; `order` = signer sequence position.
  - **Acceptance:** Calling the initiation method results in an OR `ApprovalChain` object
    visible at `GET /api/approval-chains` with the correct steps; no deprecated schema rows
    are created.

- [x] D1.2 Replace signing-completion detection (e.g. "all signers have signed") with a query
  against OR: `GET /api/approval-steps?chainId={id}` — all steps `status: approved` = complete.
  - **Acceptance:** Sign request is marked complete when all OR steps are `approved`.

- [x] D1.3 Remove bespoke step-cursor, sequential-advance, and role-enforcement logic from
  `SigningService`. OR's advance-on-approval replaces these.
  - **Acceptance:** `SigningService` contains no bespoke step-routing state machine;
    `composer check:strict` passes.

### D2. Update SigningProviderFactory and provider invocation (M)

- [x] D2.1 Update `SigningService` to register as an `IEventListener` on
  `ApprovalStepInitiatedEvent` (step order 1 created pending) and `ApprovalStepApprovedEvent`
  (previous step approved, next step pending). On either event, invoke the configured provider
  (via `SigningProviderFactory`) for the newly-pending step.
  - **Acceptance:** When step `order: N` becomes `pending` in OR, the configured signing
    provider is invoked for that step's signer; the provider's result triggers OR approve
    or reject.

- [x] D2.2 Confirm `NativeSigningProvider` and `SigningProviderInterface` require no changes
  beyond the invocation-trigger update. Document any required interface adjustments.
  - **Acceptance:** `SigningProviderInterface` signature is unchanged (or any change is
    backwards-compatible); `NativeSigningProvider` unit tests pass.

---

## [docudesk] Controller

### D3. Verify SigningController endpoint surface is unchanged (S)

- [x] D3.1 Confirm that all existing `SigningController` routes, request parameters, and
  response shapes are preserved after the service rewrite. Fix any drift between the
  controller and the rewritten service.
  - **Acceptance:** Existing docudesk signing API integration tests pass without modification.

---

## [docudesk] Schema Deprecation

### D4. Deprecate signing-chain schema in docudesk register (S)

- [x] D4.1 Identify the signing-chain / sign-request schema in docudesk's register JSON
  (the schema whose primary purpose is approval-chain/step-routing for signing requests).
  Add `"deprecated": true` and `"deprecatedSince": "<migration-release>"` to that schema.
  - **Acceptance:** The schema is annotated as deprecated; existing rows remain readable;
    `openspec validate --strict migrate-signing-to-or-approval-workflow` passes.

- [x] D4.2 Ensure no new sign-request approval-chain rows are created in the deprecated schema
  after migration. Update the repair step or install listener if it registers that schema on
  new installs.
  - **Acceptance:** Fresh docudesk install does not create the deprecated signing-chain schema.

---

## [docudesk] Tests

### D5. Write end-to-end test for signing flow via OR approval-workflow store (M)

- [x] D5.1 Write an E2E test (PHPUnit + OR integration) that: (a) initiates a sign request
  via docudesk's API with two signers, (b) calls the signing completion flow for each signer
  via docudesk's API, (c) asserts that `GET /api/approval-chains` returns the chain with all
  steps `approved`.
  - **Acceptance:** Test passes; test asserts against OR's approval store.

- [x] D5.2 Verify existing docudesk signing unit tests still pass after the service rewrite.
  Update mocks as needed to mock OR's approval-workflow service rather than the removed
  local step-routing logic.
  - **Acceptance:** `composer check:strict` passes; no skipped tests.
