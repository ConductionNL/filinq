# Migrate signing to OR task sequences

## Why

OpenRegister PR #3302 (`flow-approval-consolidation`) removes the approval-chain
surface filinq's signing bridge is built on: the four `ApprovalStep*Event`
classes, the `ApprovalChain`/`ApprovalStep` entities and mappers,
`ApprovalService`, and the `/api/approval-chains` and `/api/approval-steps`
routes. Nothing is aliased or re-emitted; the retirement inventory
(`tests/fixtures/approval-consolidation/retired-approval-surface.json` on the
OR branch) marks any app still touching that surface as a broken integration.
filinq is the only consumer. Without this change, filinq's signing event bridge
dies the moment #3302 deploys, and its Signer* event classes reference classes
that no longer exist.

The published replacement mapping is
`docs/development/approval-events-migration.md` on the #3302 branch:

| Retired | Replacement |
|---|---|
| `ApprovalStepInitiatedEvent` | a sequence task transitioning to `enabled` (`TaskTransitionedEvent`) |
| `ApprovalStepApprovedEvent` | `TaskTerminalEvent` (committed) — state `completed`, approving outcome |
| `ApprovalStepRejectedEvent` | `TaskTerminalEvent` (committed) — state `completed`, rejecting outcome |
| `ApprovalStepCompletedEvent` | `TaskSequenceCompletedEvent` |
| `ApprovalService::approveStep` / `rejectStep` | `TaskService::complete()` with an approving or rejecting outcome |

## What changes

- `SigningEventRegistrar` registers one listener (`SigningTaskListener`) for
  the three task events, by FQN **string literal** — never `::class` on an OR
  name, never `class_exists()` at register() time (the bootstrap-order
  invariant `BootstrapOrderIndependenceTest` enforces).
- `ApprovalStepListener` is replaced by `SigningTaskListener`: ownership
  filtering moves from chain register/schema slugs to "the task's (or
  sequence's anchor) object resolves in the configured signingRequest
  register/schema", and the OR event surface is read duck-typed so filinq
  loads with OR both older and newer than #3302.
- `SignerEventTranslator` and the four filinq `Signer*Event` classes drop
  every `ApprovalChain`/`ApprovalStep` type: the events now carry scalars
  (sequence uuid, task uuid, position, actor, comment, object uuid). The
  `nextStep` payload is gone by design: the next position's own `enabled`
  transition is the signal, delivered in the same request as the approving
  decision.
- Docblocks and the register-JSON deprecation notes stop naming the retired
  classes and name the task verbs instead.
- The test stubs drop the retired classes and gain `Task`, `TaskSequence`,
  `TaskState` and the three events; a new load test proves the app's signing
  wiring boots in a process where the retired classes are absent and no OR
  stub is loaded.

## What does NOT change

- filinq ships **no** `x-openregister-approval-chains` declaration, so there
  is no declarative block to migrate (verified against both register JSONs).
- The bespoke `SigningService` write path (signingRequest/signerRecord object
  rows) is untouched. The archived
  `migrate-signing-to-or-approval-workflow` change deferred that rewrite
  (tasks D1.1–D1.3), so filinq has **no live call site** driving
  `approveStep`/`rejectStep` to repoint; the reply path
  (`TaskService::complete()` / `consume()`) is recorded in the spec for the
  deferred write-path rewrite and in the provider docblocks.
- The filinq signing HTTP API is unchanged.

## Impact

- Affected specs: `signing-via-or-approval-with-provider-plugins` (MODIFIED —
  the OR vocabulary moves from chains/steps to sequences/tasks).
- Affected code: `lib/AppInfo/SigningEventRegistrar.php`,
  `lib/EventListener/` (listener replaced, translator rewritten),
  `lib/Event/Signer*.php` (4), provider docblocks, register-JSON notes,
  `tests/stubs/OpenRegisterStubs.php`, unit tests.
- Must merge in the same train as openregister#3302.
