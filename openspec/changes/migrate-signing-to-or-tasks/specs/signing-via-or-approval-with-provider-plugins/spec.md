# signing-via-or-approval-with-provider-plugins — delta for migrate-signing-to-or-tasks

OpenRegister's `flow-approval-consolidation` (openregister#3302) retires the
approval-chain surface this spec was written against. The chain/step
vocabulary is replaced by ordered task sequences: a chain is a
`TaskSequence`, a step is a `Task` at a `sequencePosition`, step decisions
are `TaskService::complete()` outcomes, and the four `ApprovalStep*Event`
classes map to `TaskTransitionedEvent` (position enabled),
`TaskTerminalEvent` (decision, committed) and `TaskSequenceCompletedEvent`
(final approval), per the normative mapping in OR's
`docs/development/approval-events-migration.md`.

## RENAMED Requirements

- FROM: `### Requirement: Sign-Request Creation SHALL Create an OR ApprovalChain with One Step per Signer`
- TO: `### Requirement: Sign-Request Creation SHALL Provision an OR Task Sequence with One Position per Signer`

- FROM: `### Requirement: Signer Approval and Decline MUST Emit Via OR's Approval-Workflow API`
- TO: `### Requirement: Signer Approval and Decline MUST Emit Via OR's Task Verbs`

- FROM: `### Requirement: Signing Providers SHALL Execute on OR ApprovalStep Pending Transition (Event-Driven)`
- TO: `### Requirement: Signing Providers SHALL Execute When a Sequence Position Becomes Enabled (Event-Driven)`

## MODIFIED Requirements

### Requirement: Sign-Request Creation SHALL Provision an OR Task Sequence with One Position per Signer

SHALL be the primary requirement that when a signing request is initiated on a
document, filinq provisions an OR task sequence with one ordered position per
signer. A position's `candidateGroups` carries the signer's NC group. No new
signing-chain rows are written to any filinq-local approval schema.

The write-path rewrite this requirement describes was deferred by the archived
`migrate-signing-to-or-approval-workflow` change (tasks D1.1–D1.3) and stays
deferred here: `SigningService` still records the bespoke
signingRequest/signerRecord rows. This requirement binds that future rewrite
to the surviving surface so it is never re-attempted against the retired one.

#### Scenario: Sign request with two signers provisions a two-position sequence

@e2e exclude deferred write path — sign-request creation still uses the bespoke object rows (archived change D1.x); this scenario binds the future rewrite to the task-sequence surface and has no implementation to drive yet

- GIVEN a document with UUID `doc-xyz` stored in a filinq OR register
- AND a sign request is initiated with signers in order: `signer-a`, `signer-b`
- WHEN the POST to filinq's sign-request endpoint is called
- THEN a `TaskSequence` SHALL be provisioned in OR with two positions (1, 2)
- AND position 1's task SHALL be `enabled` with `signer-a`'s NC group in `candidateGroups`
- AND position 2's task SHALL be waiting (not yet enabled)

#### Scenario: Single-signer sign request provisions a one-position sequence

@e2e exclude deferred write path — same deferral as the two-signer scenario above

- GIVEN a sign request with a single signer `signer-only`
- WHEN the sign-request endpoint is called
- THEN a `TaskSequence` SHALL be provisioned in OR with one position, its task `enabled`

### Requirement: Signer Approval and Decline MUST Emit Via OR's Task Verbs

MUST be the requirement that all signer decisions (sign or decline) on an
OR-backed sequence are emitted through `TaskService::complete()` — an
approving outcome for sign, a rejecting outcome (with the mandatory comment)
for decline — or through OR's task HTTP routes. Where an approval authorizes
exactly one subsequent action, that action MUST record it via
`TaskService::consume()` so the approval cannot silently re-authorize a later
run. filinq MUST NOT update task or sequence state in any local storage path
in parallel with or instead of the task verbs, and MUST NOT reference the
retired `ApprovalService`, its events, or the `/api/approval-chains` and
`/api/approval-steps` routes.

#### Scenario: Signer signs — the task is completed with an approving outcome

@e2e exclude deferred write path — no live filinq call site drives task decisions yet (archived change D1.x); the reply-path contract is pinned here for the rewrite

- GIVEN an enabled sequence task at position 1 for `doc-xyz`
- AND the requesting user is in the position's candidate group
- WHEN the signer completes the signing flow
- THEN filinq SHALL call `TaskService::complete()` with an approving outcome
- AND OR SHALL enable position 2 in the same request

#### Scenario: Signer declines — the task is completed with a rejecting outcome

@e2e exclude deferred write path — same deferral; the mandatory-comment refusal is OR's own contract, tested upstream

- GIVEN an enabled sequence task at position 1 for `doc-xyz`
- WHEN the signer declines with reason "Niet akkoord met de inhoud"
- THEN filinq SHALL call `TaskService::complete()` with a rejecting outcome and the reason as `comment`
- AND OR SHALL close the sequence; no further position is enabled

### Requirement: Signing Providers SHALL Execute When a Sequence Position Becomes Enabled (Event-Driven)

SHALL be the requirement that signing providers (NativeSigningProvider and
external provider adapters) are invoked in response to a sequence position's
task transitioning to `enabled`, not by an app-local step cursor. filinq
learns of the transition from `TaskTransitionedEvent` (committed, state
`enabled`); the next position after an approval is enabled by OR in the same
request as the approving decision, so no `nextStep` payload exists or is
needed. Providers capture the signature and return a result without mutating
task state themselves.

#### Scenario: Provider invoked when a position's task becomes enabled

@e2e exclude event bridge — requires OR to dispatch task lifecycle events for a provisioned sequence; the listener→translator→provider path is covered by PHPUnit (SigningTaskListenerTest, SignerEventTranslatorTest)

- GIVEN a sequence task for `doc-xyz` owned by filinq's signingRequest schema
- WHEN OR dispatches a committed `TaskTransitionedEvent` with state `enabled`
- THEN `SignerStepPendingEvent` SHALL be re-dispatched with the sequence uuid, task uuid, position, role and object uuid
- AND the active `SigningProviderInterface` SHALL be invoked for that position
- AND the provider SHALL NOT update task state itself

#### Scenario: External signing provider invoked on position enabled

@e2e exclude event bridge — same PHPUnit coverage as the native-provider scenario; the external callback's task-verb reply is part of the deferred write path

- GIVEN a sign request configured with an external signing provider
- AND the sequence task at position 1 becomes `enabled`
- WHEN OR dispatches the committed `TaskTransitionedEvent`
- THEN filinq SHALL delegate to the configured `SigningProviderInterface` implementation
- AND on callback/completion, filinq SHALL reply through `TaskService::complete()` with the provider's result

### Requirement: Signing API Surface for Clients SHALL Be Preserved

SHALL be the requirement that all existing filinq signing endpoints (initiate
sign request, get sign status, cancel sign request) retain their current
request parameters and response shapes. Callers require no changes when
filinq migrates signing-chain state to OR.

#### Scenario: Existing sign-request endpoint behaves identically after migration

@e2e exclude API-shape contract — covered by Newman on /api/signing/* and PHPUnit on SigningController; no navigable UI change

- GIVEN a client calls `POST /api/signing/requests` with the same payload as before migration
- WHEN the request is processed
- THEN the response shape SHALL be identical to the pre-migration response
- AND once the deferred write path lands, the sign request SHALL be backed by an OR task sequence internally

#### Scenario: Sign status endpoint returns correct state from OR

@e2e exclude API-shape contract — same Newman/PHPUnit coverage as above

- GIVEN a sign request backed by an OR task sequence with two positions, one completed and one enabled
- WHEN the client calls `GET /api/signing/requests/{id}`
- THEN the response SHALL indicate one step complete and one step pending, in the same format as the pre-migration response

### Requirement: MUST NOT Write to Deprecated Signing-Chain Schema

MUST NOT be violated: after this migration ships, no code path in filinq
creates or updates objects in any app-local signing-chain approval schema.
All new signing chains are OR task sequences. Existing pre-migration
signing-chain rows remain accessible read-only.

#### Scenario: Migration does not write new rows to deprecated schema

@e2e exclude backend guard — negative storage assertion, covered by PHPUnit on the signing service write path; no UI surface

- GIVEN the migration is deployed
- WHEN any filinq endpoint initiates or advances a signing flow
- THEN no object of any deprecated filinq signing-chain schema type SHALL be created
- AND the deprecated schema's object store SHALL contain only pre-migration rows

### Requirement: Provider Async-Flow Methods Are a Pluggable Extension Seam, Not Authorization Guards

The `SigningProviderInterface` async-flow methods SHALL be classified as a
pluggable extension seam implemented by external signing providers, namely
`initiateSigning`, `checkStatus`, `downloadSignedDocument` and `cancelSigning`. These
methods SHALL NOT be treated as authorization guards: none makes an access
decision, and `checkStatus` in particular is a status **read** returning
`status`/`signers`/`completedAt`. The current app signing path is synchronous
and drives only `produceSignedArtifact` (plus `supportsLevel`/`getIdentifier`);
the async-flow methods have no native caller by design and are invoked only by
external-provider plugins. The live "get sign status" surface for clients SHALL
remain the signing request read via the authenticated, per-UID-authorized
`SigningController::showRequest` (backed by an OR task sequence once the
deferred write path lands), never `provider->checkStatus`.

#### Scenario: checkStatus is a status read, not an authorization guard

@e2e exclude backend classification — unchanged behaviour, PHPUnit-covered; only the OR vocabulary in the prose moved

- GIVEN a signing request handled by `NativeSigningProvider`
- WHEN `checkStatus` is invoked with the request's external identifier
- THEN it SHALL return the persisted `status`, `signers`, and `completedAt`
- AND it SHALL make no authorization decision and reject no actor
- AND it SHALL be reached only through the pluggable-provider extension flow,
  not the app's live status endpoint

#### Scenario: Live sign-status surface is the authenticated controller path

@e2e exclude backend auth contract — 401/403 paths covered by PHPUnit on SigningController; unchanged behaviour

- GIVEN a client requests the status of sign request `{id}`
- WHEN the client calls `GET /api/signing/requests/{id}`
- THEN the request SHALL be served by `SigningController::showRequest`
- AND the caller SHALL be authenticated (`401` when no user session)
- AND the caller SHALL be authorized per-UID against the request owner
  (`403` on mismatch)
- AND `provider->checkStatus` SHALL NOT be on this live path

## ADDED Requirements

### Requirement: Signer Decisions and Sequence Completion SHALL Be Consumed from the Task Events

SHALL be the requirement that filinq's signing bridge consumes exactly three
OR events, registered by FQN string literal with no `class_exists()` probe at
register() time: a committed `TaskTransitionedEvent` to `enabled` (position
pending), a committed `TaskTerminalEvent` with state `completed` (approved
when the outcome is not in the rejecting vocabulary, rejected when it is),
and `TaskSequenceCompletedEvent` (final approval). Uncommitted dispatches and
terminal-but-not-completed states (cancel, moot, run termination) SHALL be
ignored. An event belongs to filinq when its anchor object resolves in the
configured signingRequest register/schema; with the binding unconfigured,
every event is foreign.

#### Scenario: Approving completion re-dispatches SignerStepApprovedEvent

@e2e exclude event bridge — OR-dispatched events cannot be raised from filinq's Playwright surface; PHPUnit covers the mapping (SigningTaskListenerTest)

- GIVEN a committed `TaskTerminalEvent` for a sequence task anchored on a filinq signing request
- AND the task's state is `completed` with outcome `approved`
- WHEN the listener handles the event
- THEN `SignerStepApprovedEvent` SHALL be dispatched carrying the sequence uuid, task uuid, position, completing user, comment and object uuid

#### Scenario: Rejecting completion re-dispatches SignerStepRejectedEvent

@e2e exclude event bridge — same PHPUnit coverage

- GIVEN a committed `TaskTerminalEvent` for an owned sequence task
- AND the task's state is `completed` with outcome `rejected`
- WHEN the listener handles the event
- THEN `SignerStepRejectedEvent` SHALL be dispatched with the rejection comment

#### Scenario: Sequence completion re-dispatches SignerChainCompletedEvent

@e2e exclude event bridge — same PHPUnit coverage

- GIVEN a `TaskSequenceCompletedEvent` whose sequence anchors on a filinq signing request
- WHEN the listener handles the event
- THEN `SignerChainCompletedEvent` SHALL be dispatched carrying the sequence uuid, final task uuid, decider, resolved approving status and object uuid

#### Scenario: Foreign, uncommitted and non-completed events are ignored

@e2e exclude event bridge — negative filtering, PHPUnit-covered

- GIVEN a task event that is uncommitted, OR carries no sequence uuid, OR anchors on an object outside the configured signingRequest register/schema, OR is terminal without state `completed`
- WHEN the listener handles the event
- THEN no `Signer*Event` SHALL be dispatched and no provider SHALL be invoked

### Requirement: The App MUST Load with the Retired Approval Surface Absent

MUST be the requirement that filinq references none of the retired classes
(`ApprovalChain`, `ApprovalStep`, their mappers, `ApprovalService`,
`ApprovalController`, the four `ApprovalStep*Event` classes) or retired
routes anywhere in `lib/`, and that the signing wiring loads in a PHP
process where those classes are absent and no OpenRegister stub is loaded.
This is what lets filinq deploy on either side of openregister#3302: the
fleet trains are close but not atomic.

#### Scenario: Signing wiring boots without OpenRegister

@e2e exclude load-safety proof — a separate-process autoloader experiment (tests/scripts/boot-without-openregister.php), not a browser flow; asserted by RetiredApprovalSurfaceTest

- GIVEN a PHP process whose autoloader serves filinq's `lib/` and the Nextcloud stubs but no OpenRegister class
- WHEN every signing-surface class is force-linked and `SigningEventRegistrar::register()` runs
- THEN the process SHALL exit cleanly
- AND the retired classes SHALL be unresolvable in that process
- AND the registered event names SHALL contain no retired event class

#### Scenario: No retired reference survives in lib/

@e2e exclude static sweep — a source scan in PHPUnit (RetiredApprovalSurfaceTest), no runtime surface

- GIVEN the retirement inventory's class and route lists
- WHEN every PHP file under `lib/` and both register JSONs are scanned
- THEN no retired class FQCN and no retired route SHALL appear
