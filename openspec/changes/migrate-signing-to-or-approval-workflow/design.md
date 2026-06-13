# Design: migrate-signing-to-or-approval-workflow

status: pr-created

## Context

OR's `approval-workflow` spec provides `ApprovalChain` CRUD and `ApprovalStep` decision
endpoints. The exact PHP DI class for approval-chain CRUD is to be confirmed during task
OR-1.1 in the umbrella change. This design refers to it as `ApprovalWorkflowService`;
if the DI class is unavailable, docudesk MAY call OR's REST API from the backend via an
HTTP client.

## File-by-File Mapping

### `lib/Service/SigningService.php` — orchestrate via OR ApprovalChain; provider factory remains

`SigningService` is rewritten to translate signing-request creation into OR ApprovalChain
operations:

| Existing operation | New implementation |
|---|---|
| Create sign request (with ordered signers) | `POST /api/approval-chains` with one step per signer; `ApprovalStep.role` = NC group ID for that signer |
| Get sign request status | `GET /api/approval-chains/{id}/objects` |
| Signer accepts / signs | `POST /api/approval-steps/{id}/approve` with optional signature metadata in `comment._meta` |
| Signer declines | `POST /api/approval-steps/{id}/reject` with reason in `comment` |
| Check "all signed" | All steps for the chain have `status: approved` (query `GET /api/approval-steps?chainId={id}`) |

The `SigningProviderFactory` is retained and injected into `SigningService`. When a step
becomes `pending` (either on chain init or after the previous step approves), `SigningService`
looks up the provider for that step and delegates signing-UI / signature-capture to it.

`SigningService` no longer manages step state: it does not track "who is the current signer",
"has this signer already signed", or "advance to next signer" — OR's advance-on-approval
handles all of that.

### `lib/Service/Signing/SigningProviderInterface.php` — unchanged; documented as OR ApprovalStep plug-in

`SigningProviderInterface` is unchanged. Its role is now explicitly documented as the
"OR ApprovalStep execution plug-in" pattern: when OR's step becomes `pending`, docudesk
invokes the configured provider to present signing UI to the signer. The provider returns
a signing result (success or decline), and `SigningService` calls OR's approve or reject
endpoint accordingly.

No changes to the interface signature are required for this migration.

### `lib/Service/Signing/NativeSigningProvider.php` — invoked on OR ApprovalStep pending transition

`NativeSigningProvider` is updated to be invoked as a side effect of an OR ApprovalStep
moving to `pending`, rather than being driven by an app-local step cursor in `SigningService`.

OR dispatches `ApprovalStepInitiatedEvent` (chain start — first step becomes pending) and
`ApprovalStepApprovedEvent` (a step is approved — next step becomes pending) via
`IEventDispatcher` (see `openregister/openspec/changes/add-approval-step-events`). Docudesk
registers `IEventListener` implementations on these two event classes; no polling or direct
post-approve provider call is required.

The provider itself (PDF manipulation, signature capture, document hash) is unchanged.

### `lib/Controller/SigningController.php` — endpoints surface unchanged; delegation via OR

The controller's public API is preserved in full. All existing routes, request parameters,
and response shapes stay the same so that callers require no changes.

The controller stops managing step state directly. It delegates to the rewritten
`SigningService` for all chain and step operations.

### Audit Emission

Signing audit events are NOT managed here. Audit for signing decisions (SIGNED, DECLINED,
COMPLETED, etc.) is covered by the `migrate-signing-audit-to-or-audit` change. This migration
ensures the signing flow emits decisions through OR's approval-workflow API; the audit listener
on those OR events is the responsibility of the audit migration.

Cross-reference: `docudesk/openspec/changes/migrate-signing-audit-to-or-audit`.

### Retention

10-year Archiefwet retention for signed documents is satisfied by OR's archival-destruction-
workflow, which is configured on the document schema in the register definition. No additional
retention logic is introduced in this migration.

## Concept Mapping Reference

| Signing-flow concept | OR ApprovalChain equivalent |
|---|---|
| Sign request | `ApprovalChain` entity (one chain per document) |
| Signer per step | `ApprovalStep.role` = NC group ID for that signer |
| Step order | `ApprovalStep.order` |
| `pending` (active signer) | `ApprovalStep.status: pending` |
| `waiting` (not yet active) | `ApprovalStep.status: waiting` |
| Sign (approve) | `POST /api/approval-steps/{id}/approve` |
| Decline | `POST /api/approval-steps/{id}/reject` |
| Advance on signing | OR's automatic advance-on-approval |
| Signature level / provider metadata | `comment._meta.signatureLevel`, `comment._meta.provider` |
| "All signed" completion | All steps for chain in `status: approved` |

## DEFERRED_QUESTIONS

1. **OR DI class name**: RESOLVED — `OCA\OpenRegister\Db\ApprovalChainMapper` and
   `OCA\OpenRegister\Db\ApprovalStepMapper` are the confirmed DI entry points for
   ApprovalChain / ApprovalStep CRUD (verified 2026-06-12 against
   `openregister/lib/Db/ApprovalChainMapper.php` + `ApprovalStepMapper.php` on
   `origin/development`). No higher-level `ApprovalChainService` exists yet;
   docudesk injects the mappers directly. `OCA\OpenRegister\Service\ApprovalService`
   (also DI-injectable) is the canonical entry point for state transitions
   (`initializeChain`, `approveStep`, `rejectStep`) and is what the docudesk
   listener observes — it is the dispatcher of the four `ApprovalStep*Event`
   classes.
2. **OR ApprovalStep IEventDispatcher event**: RESOLVED — OR dispatches four typed
   events from `OCA\OpenRegister\Event\`:
   - `ApprovalStepInitiatedEvent` — fires when a step transitions to `pending`
     (chain initialised → step 1; or previous step approved → next step).
   - `ApprovalStepApprovedEvent` — fires after a `pending` step is approved.
     Carries the next pending step (or `null` for the final step).
   - `ApprovalStepRejectedEvent` — fires after a `pending` step is rejected
     (terminates the chain).
   - `ApprovalStepCompletedEvent` — fires ONCE per chain when the final step
     is approved.
   `OCA\DocuDesk\EventListener\ApprovalStepListener` registers `IEventListener`
   on all four; direct post-approve provider calls are not needed. Dispatch
   order spec'd by OR: Approved → (Initiated | Completed), then Rejected.

## Seed Data

No new schemas are introduced in OR. The signing-flow schema (if one existed as a top-level
approval-chain schema in docudesk) is deprecated — not deleted. The `SignRequest` or equivalent
signing-chain schema in docudesk's register is annotated `"deprecated": true`. Existing
signing-request rows remain accessible read-only.

No new OR schemas are registered as part of this migration.

## Related ADRs

- **ADR-022** (primary) — apps consume OR abstractions; approval-chain is the specific
  abstraction this migration delegates to.
- **ADR-031** — schema-declarative business logic; marking the schema deprecated is the
  correct pattern.
- **ADR-008** — testing contract; end-to-end test exercising OR's approval-workflow store
  is required.
- **Umbrella spec** — `hydra/openspec/changes/consume-or-approval-workflow-fleet-wide`
  (policy contract this migration satisfies).
- **OR approval-workflow spec** — `openregister/openspec/specs/approval-workflow/spec.md`
  (the API this migration consumes).
- **Audit migration** — `docudesk/openspec/changes/migrate-signing-audit-to-or-audit`
  (audit events for the signing decisions are covered there).
