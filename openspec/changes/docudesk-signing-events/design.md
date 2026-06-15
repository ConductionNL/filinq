# Design: docudesk-signing-events

## Context

The fleet replaced the broken `$registry->call('docudesk','createSigningRequest',…)` HTTP-style
delegation with in-process `OCP\EventDispatcher` events, mirroring the just-merged decidesk
`decidesk-decision-events` contract. DocuDesk already owns the signing lifecycle in
`OCA\DocuDesk\Service\SigningService`. This change adds only the event surface on top of it.

## Decisions

### D1 — Mirror the decidesk-decision-events shape exactly

The request event carries immutable provenance + request getters and one writable result slot
(signingRequestId + handled). Nextcloud typed dispatch (`dispatchTyped()`) is synchronous, so the
listener writes the result slot and the producer reads it back off the same instance — the
standard NC request/response-over-the-bus pattern. The conclusion event is fully immutable.

### D2 — Namespacing follows the existing app, not the brief's literal casing

The app's PHP namespace is `OCA\DocuDesk` (capital D — see `lib/Event/SignerChainCompletedEvent.php`,
`composer.json` PSR-4). The brief wrote `OCA\Docudesk`; the real, autoloadable FQCNs are:

- `OCA\DocuDesk\Event\DocumentSigningRequestedEvent`
- `OCA\DocuDesk\Event\SigningConcludedEvent`
- `OCA\DocuDesk\EventListener\DocumentSigningRequestedListener`

The listener lives in `lib/EventListener/` (the app's existing listener directory; e.g.
`ApprovalStepListener`, `DocuDeskEventListener`), not `lib/Listener/`. The shillinq consumer fix
MUST use these exact FQCNs.

### D3 — Reuse SigningService::createRequest() positionally, persist provenance

The listener builds the `$data` array `createRequest()` already accepts (`documentFileId`,
`documentName`, `signatureLevel`, `signingMode`, `provider`, `deadline`, `signers`) from the
event, and additionally threads provenance fields (`sourceApp`, `subjectRegister`,
`subjectSchema`, `subjectId`, `subjectLabel`, `externalReference`, `correlationId`) into the
persisted signing-request object so the conclusion can correlate back to the consumer. `createRequest()`
is extended to copy any present provenance keys onto the stored request (additive, optional — no
behaviour change for internal callers that omit them). The call is positional:
`createRequest($data)`.

### D4 — Emit the conclusion only for provenance-carrying requests, at terminal points

`SigningService` gains an injected `OCP\EventDispatcher\IEventDispatcher` and a private
`emitConclusionIfDelegated(array $request, string $status, ?string $signedDocumentRef)` helper.
It fires only when the stored request has a non-empty `sourceApp`, and only at terminal states:

- **COMPLETED** — reached inside `updateRequestStatus()` when all signers have signed (called from
  `sign()`). Emission happens after the request is persisted COMPLETED.
- **DECLINED** — in `decline()` after the request is persisted DECLINED.
- **CANCELLED** — in `cancelRequest()` after the request is persisted CANCELLED.
- **EXPIRED** — when a request transitions to EXPIRED through the service (the helper is called
  with `status='expired'`); no new expiry sweeper is introduced here.

The status value carried on the event is normalised to the contract vocabulary
(`signed | declined | expired | cancelled`). Emission is fail-soft: any dispatch error is logged
and never rolls back the already-persisted terminal transition (matches the decidesk
`emitConclusionIfDelegated` fail-soft contract).

### D5 — Do NOT repurpose the SignerChain* events

`SignerChainCompletedEvent`, `SignerStepApprovedEvent`, `SignerStepPendingEvent`, and
`SignerStepRejectedEvent` are internal OR-approval-chain step signals with no cross-app
provenance. The cross-app contract is a new, separate pair of events.

## Risks / Trade-offs

- **OR object shape:** provenance fields are persisted onto the signing-request OR object. They
  are additive optional properties; OpenRegister tolerates extra properties. If the signingRequest
  schema is strict, a follow-up may add them declaratively — out of scope here (the event contract
  itself does not depend on schema validation).
- **Stale OCP stub:** local Psalm/PHPStan may emit phantom errors about `IEventDispatcher` /
  `dispatchTyped`; rely on CI for the deep pass (known fleet gotcha). `php -l` is authoritative
  locally.
- **Synchronous dispatch:** if no listener is registered (DocuDesk degraded install) the producer
  simply reads `isHandled() === false`; nothing throws.
