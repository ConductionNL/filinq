# Proposal: filinq-signing-events

## Why

Filinq is the fleet's document e-signature hub. Consumer fleet apps (e.g. shillinq) must be
able to delegate document signing to it when both apps are installed. The previously-shipped
delegation path used a phantom mechanism — `$registry->call('filinq','createSigningRequest',…)`
— for which no such registry method exists. The call therefore always failed closed and the
request never reached Filinq: delegated signing was silently broken.

The fleet has since standardised cross-app delegation on **`OCP\EventDispatcher` events**
(in-process, autoloaded whenever the producer app is installed), exactly as decidesk shipped in
`decidesk-decision-events` (`DecisionRequestedEvent` / `DecisionConcludedEvent` +
`DecisionRequestedListener` + `DecisionLifecycleService` emission). Filinq needs the same
contract for signing so a consumer can:

1. dispatch a request to raise a signing request and read the resulting signing-request id back
   synchronously off the same event instance, and
2. listen for a conclusion event when that signing request reaches a terminal outcome
   (signed / declined / expired / cancelled) to run its own downstream side effects.

Filinq already has `OCA\Filinq\Service\SigningService` with `createRequest()`, `sign()`,
`decline()`, and `cancelRequest()`. This change builds the event contract entirely on top of
that existing service — no new signing engine, no new state machine, no schema change. The
existing approval-chain `Signer*Event` classes are NOT repurposed: they are internal
approval-step signals with no cross-app provenance.

## What

Add the in-process **event contract** for delegated document signing:

1. **`OCA\Filinq\Event\DocumentSigningRequestedEvent`** — a consumer dispatches this to ask
   Filinq to raise a signing request for one of its objects. Immutable provenance + request
   getters, plus a synchronous result slot (`setSigningRequestId()` / `getSigningRequestId()`,
   `setHandled()` / `isHandled()`) the listener writes so the producer reads the new
   signing-request id back off the same instance.
2. **`OCA\Filinq\Event\SigningConcludedEvent`** — Filinq dispatches this when a delegated
   (provenance-carrying) signing request reaches a terminal outcome. Carries the
   subject/provenance reference plus the outcome envelope (signingRequestId, status,
   signedDocumentRef, signers, signedAt).
3. **`OCA\Filinq\EventListener\DocumentSigningRequestedListener`** (implements
   `OCP\EventDispatcher\IEventListener`) — maps the request event onto
   `SigningService::createRequest()` (positional), persists the provenance
   (sourceApp / subject reference / externalReference / correlationId) on the signing request so
   the conclusion can correlate back, and writes the result slot. Registered via
   `registerEventListener()` in `lib/AppInfo/Application.php`.
4. **Conclusion emission** from `SigningService` at its terminal points — request COMPLETED (all
   signers signed), DECLINED, CANCELLED, and EXPIRED — dispatched via the injected
   `OCP\EventDispatcher\IEventDispatcher`, **only** for requests that carry provenance
   (`sourceApp` set). Internal / no-provenance signing requests emit nothing.

The event FQCNs and payload fields are documented verbatim so the consumer-side fix (shillinq)
can reference them exactly.

## Capabilities

### Added Capabilities

- `filinq-signing-events`: the cross-app event contract (request + conclusion events,
  listener, provenance persistence, terminal emission) through which fleet apps delegate
  document signing to Filinq.

## Affected Projects

- [x] Project: `filinq` — all implementation work is in this repo
- Reference: `decidesk/openspec/changes/decidesk-decision-events/` (the mirrored pattern)
- Reference: `hydra/openspec/architecture/adr-022-apps-consume-or-abstractions.md` (reuse
  SigningService, no parallel CRUD)
- Counterpart: `shillinq` delegated-signing consumer fix (dispatches / listens on the classes
  defined here), to be done separately.

## Out of Scope

- Any change to the actual signing mechanics (`SigningService` create/sign/decline/cancel
  behaviour, provider plugins, approval-chain `Signer*Event` flow) beyond persisting provenance
  and emitting the conclusion event.
- A signing **expiry** background job: the contract emits a conclusion when an EXPIRED terminal
  state is reached via the service; introducing a new expiry sweeper is a separate change.
- The shillinq consumer implementation (separate per-app change).
- Any new user-facing UI or translatable string (the contract is backend-only).

## Success Criteria

- `openspec validate filinq-signing-events --strict` exits 0.
- A consumer that dispatches `DocumentSigningRequestedEvent` (with a complete subject reference
  and provenance) gets `getSigningRequestId()` populated and `isHandled() === true` after
  dispatch, and a signing request is created via `SigningService::createRequest()` carrying the
  provenance.
- When that signing request reaches COMPLETED / DECLINED / CANCELLED / EXPIRED, Filinq
  dispatches a `SigningConcludedEvent` carrying the provenance and outcome envelope; an internal
  (no-provenance) request emits nothing.
- `php -l` passes on every new/changed PHP file; no new user-facing English l10n key is added.
