# Tasks: migrate-signing-to-or-tasks

## 1. Registrar and listener

- [x] 1.1 Rewrite `SigningEventRegistrar` to register `SigningTaskListener`
      for `TaskTransitionedEvent`, `TaskTerminalEvent` and
      `TaskSequenceCompletedEvent` by FQN string literal; drop the four
      retired registrations and the retired imports.
      **Acceptance:** no retired name in the file;
      `BootstrapOrderIndependenceTest` still passes.
- [x] 1.2 Replace `ApprovalStepListener` with `SigningTaskListener`:
      class-string routing, committed/state/sequence pre-filters, anchored
      object ownership check via the configured signingRequest binding,
      duck-typed extraction to scalars, rejecting-outcome classification with
      OR delegation + literal fallback.
      **Acceptance:** `SigningTaskListenerTest` covers every filter branch;
      manual mutant flips on the ownership guard each fail a test.
- [x] 1.3 Rewrite `SignerEventTranslator` to the scalar surface
      (onPositionEnabled / onTaskDecided / onSequenceCompleted) and keep the
      provider invocation on position-enabled.
      **Acceptance:** translator references no OR type.

## 2. filinq event classes

- [x] 2.1 Rewrite the four `Signer*Event` classes to scalar payloads
      (sequence uuid, task uuid, position, actor, comment, object uuid;
      completed adds statusOnApprove). Drop `nextStep` per the mapping.
      **Acceptance:** no OR import in `lib/Event/Signer*.php`.

## 3. Prose and stubs

- [x] 3.1 Repoint the provider docblocks (`NativeSigningProvider`,
      `ValidSignProvider`, `SigningProviderInterface`) and the register-JSON
      deprecation notes from the retired names to the task verbs.
- [x] 3.2 Drop the retired stubs from `tests/stubs/OpenRegisterStubs.php`;
      add truthful `Task`, `TaskSequence`, `TaskState` and the three event
      stubs mirroring the #3302 signatures.

## 4. Tests and proof

- [x] 4.1 Replace `ApprovalStepListenerTest` with `SigningTaskListenerTest`
      (filters, mapping, provider invocation, error swallowing) and add
      `SignerEventTranslatorTest` for the scalar surface.
- [x] 4.2 Add `RetiredApprovalSurfaceTest`: lib/ + register-JSON sweep
      against the retirement inventory, and the separate-process boot proof
      (`tests/scripts/boot-without-openregister.php`).
- [x] 4.3 Full quality pass: phpcs, phpmd, psalm, phpstan, PHPUnit, hydra
      gates scoped to the diff — 0 FAIL.

Verified 2026-11-13 on development: `SigningEventRegistrar` registers the three task events by FQN string; `SigningTaskListener`, `SignerEventTranslator` (onPositionEnabled / onTaskDecided / onSequenceCompleted) and the `Signer*Event` classes carry no retired name or OR import; the stubs mirror `Task`, `TaskSequence` and the three events; `SigningTaskListenerTest`, `SignerEventTranslatorTest`, `RetiredApprovalSurfaceTest`, `tests/scripts/boot-without-openregister.php` exist. The registrar, listener, translator, retirement, bootstrap-order and decision tests pass (91 tests), the full unit suite passes (6443, 0 failures) and phpstan is clean on the three classes.

## 5. Approving vocabulary

- [ ] 5.1 When the deferred write path provisions a signing sequence, its
      template declares `approvingOutcomes: ["approved"]` (OpenRegister
      `a-sequence-advances-only-on-an-approving-outcome`), so a signer who
      only ticks a task `done` has not signed and the next signer is not asked.
      **Acceptance:** a test provisions a two-signer sequence and asserts the
      template carries the key; completing position 1 with `done` leaves
      position 2 not enabled.
      **Not built:** filinq has no path that provisions a signing sequence yet (design: deferred), so there is no template to put the key on.
