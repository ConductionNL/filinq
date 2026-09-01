# Design: migrate-signing-to-or-tasks

## D-1 One listener, three events, string-literal registration

The retired bridge registered one listener for four event classes via
`::class` constants. The replacement registers one listener
(`SigningTaskListener`) for three events — `TaskTransitionedEvent`,
`TaskTerminalEvent`, `TaskSequenceCompletedEvent` — by FQN string literal.

Why strings and not `::class`: `Foo::class` on an imported name is a
compile-time string and never autoloads, but a literal keeps that true even if
someone later re-adds the import (`BootstrapOrderIndependenceTest` pins the
same rule for the MetricsEngine key). Why no `class_exists()` guard at
register() time: filinq sorts before openregister in the app-loading loop, so
the probe is ALWAYS false during register() — dossiq's guarded pattern is
correct only in boot(). Registering a listener for an event class that never
exists is harmless: the dispatcher keys listeners by event name and the name
is simply never dispatched.

Version window this buys: on OR **older** than #3302 (current development),
`TaskTransitionedEvent` and `TaskTerminalEvent` already exist and fire for
plain tasks; `TaskSequenceCompletedEvent` never fires; no retired class is
referenced anywhere in filinq, so the app loads. On OR **newer** (post-#3302),
all three fire. On OR absent, none fire. Same listener, no shim.

## D-2 Ownership moves from chain slugs to the anchored object

The retired filter compared `chain.registerSlug`/`schemaSlug` against the
configured `signingRequest_register`/`_schema`. Task and TaskSequence expose
numeric register/schema IDs, not slugs, and filinq's config stores what the
installer wrote (slug-shaped strings). Rather than resolving slugs to IDs
through more OR surface, ownership is now: **the event's anchor object
(`task.objectUuid`, or `sequence.anchorObjectUuid`) resolves via
`ObjectService::find()` in the configured signingRequest register/schema.**
`find()` already accepts the binding values in whatever form the config holds
— it is the exact call `SigningService` makes with the same values.

Cost control: the object lookup runs only after two free pre-filters — the
binding must be configured (unconfigured ⇒ foreign, exactly the retired
behaviour) and the task must carry a `sequenceUuid` (plain workflow tasks,
the overwhelming majority of task traffic, never reach the lookup).

## D-3 The event mapping, applied

| Signal | Filter | Emits |
|---|---|---|
| `TaskTransitionedEvent` | state `enabled`, previous state not `enabled`, sequence task, ours | `SignerStepPendingEvent` + provider invocation |
| `TaskTerminalEvent` | `isCommitted()`, state `completed`, sequence task, ours | `SignerStepApprovedEvent` or `SignerStepRejectedEvent` by outcome |
| `TaskSequenceCompletedEvent` | anchor object ours | `SignerChainCompletedEvent` |

- The committed flag: the mapper also dispatches `TaskTerminalEvent` INSIDE
  the verb's transaction (`committed: false`) for timer cancellation. filinq
  consumes only the after-commit dispatch, per the migration doc.
- Terminal-but-not-completed states (cancel, moot, run termination) emit
  nothing: the retired surface had no equivalent event, and inventing one is
  not this change's job.
- `nextStep` is not carried anywhere: OR enables the next position in the
  same request as the approving decision, so the provider invocation for the
  next signer rides that position's own `enabled` transition.
- Approving vs rejecting: delegated to
  `OCA\OpenRegister\Service\Task\TaskState::isRejectingOutcome()` when the
  class resolves (it always does on the code path — OR just dispatched the
  event), with the published vocabulary
  (`rejected`, `returned`, `declined`, `denied`) as a literal fallback so the
  classification is testable without OR. The `class_exists()` here runs at
  event time, never at register() time.

## D-4 Duck-typing discipline

`SigningTaskListener` is the ONLY file that touches OR types, and only inside
`handle()`: it routes on `$event::class` string comparison, guards the
event's real accessors with `method_exists()`, and reads Task/TaskSequence
fields through their magic getters (NC `Entity::__call` — which is exactly why
`method_exists()` is NOT used on the entity objects: it answers false for
magic methods). Everything extracted is a scalar before it leaves the
listener. `SignerEventTranslator` and the four `Signer*Event` classes are
pure filinq: scalars in, filinq events out. That is what makes the
boot-without-OR proof meaningful.

## D-5 The load proof is a separate process, not a stub

`tests/scripts/boot-without-openregister.php` builds its own autoloader
(filinq `lib/` + the Nextcloud stubs), loads NO OpenRegister stub, asserts
the retired classes are genuinely unresolvable, then force-links every
signing-surface class and runs `SigningEventRegistrar::register()` against a
minimal context. The PHPUnit wrapper execs it and asserts on exit code and
output. In-process tests cannot prove this: the unit bootstrap loads OR stubs
for every other test, and a stub that exists is exactly what the proof must
exclude.

## D-6 What is deliberately out of scope

The bespoke write path (`SigningService` object rows) still does not
provision OR sequences; the archived change deferred that (D1.x) and this
change does not smuggle it in. The reply path for that future rewrite is
`TaskService::complete()` with an approving/rejecting outcome (plus
`TaskService::consume()` where an approval authorizes exactly one action);
the spec delta and provider docblocks now say so, so no future reader
re-implements against the retired names.
