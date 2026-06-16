# docudesk-signing-events Specification (delta)

---
status: proposed
---

## Purpose

Define the in-process **event contract** through which fleet consumer apps delegate document
signing to DocuDesk when both apps are installed, replacing the broken
`$registry->call('docudesk','createSigningRequest',…)` delegation path. Consumers dispatch a
`DocumentSigningRequestedEvent` to raise a signing request and listen for a `SigningConcludedEvent`
to consume the outcome. DocuDesk builds its side of the contract entirely on top of the existing
`OCA\DocuDesk\Service\SigningService` — no new signing engine, no new state machine, no schema
change. Mirrors decidesk's `decidesk-decision-events`.

## Cross-app contract — verbatim FQCNs and payloads

Consumers MUST reference these exact fully-qualified class names (autoloaded whenever DocuDesk is
installed):

- **`OCA\DocuDesk\Event\DocumentSigningRequestedEvent`** — request fields (immutable getters):
  `sourceApp` (string), `subjectRegister` (string), `subjectSchema` (string), `subjectId`
  (string), `subjectLabel` (string), `documentReference` (string — NC Files file id / path or
  document content reference), `signers` (array), `signatureLevel` (string, e.g. SES|AdES|QES),
  `signingMode` (string, sequential|parallel), `externalReference` (string), `correlationId`
  (string). Result slot (set by DocuDesk during synchronous dispatch):
  `signingRequestId` (?string via `setSigningRequestId(string)` / `getSigningRequestId(): ?string`),
  `handled` (bool via `setHandled(bool)` / `isHandled(): bool`).
- **`OCA\DocuDesk\Event\SigningConcludedEvent`** — immutable fields: provenance
  (`sourceApp`, `subjectRegister`, `subjectSchema`, `subjectId`, `externalReference`,
  `correlationId`) plus the outcome envelope (`signingRequestId`, `status` —
  `signed|declined|expired|cancelled`, `signedDocumentRef` (?string), `signers` (array),
  `signedAt` (?string)).

The listener is **`OCA\DocuDesk\EventListener\DocumentSigningRequestedListener`**.

## ADDED Requirements

### Requirement: Public DocumentSigningRequestedEvent contract class

The system SHALL provide an autoloaded public event class
`OCA\DocuDesk\Event\DocumentSigningRequestedEvent` extending `OCP\EventDispatcher\Event`,
available whenever DocuDesk is installed, that a consumer app dispatches to ask DocuDesk to raise
a document signing request for one of its objects. The event SHALL expose immutable getters for
`sourceApp`, `subjectRegister`, `subjectSchema`, `subjectId`, `subjectLabel`, `documentReference`,
`signers` (array), `signatureLevel`, `signingMode`, `externalReference`, and `correlationId`, all
supplied at construction. Because Nextcloud typed dispatch is synchronous, the event SHALL carry a
single writable result slot — `setSigningRequestId(string)` / `getSigningRequestId(): ?string` and
`setHandled(bool)` / `isHandled(): bool` — that DocuDesk's listener writes so the dispatching
producer can read the resolved `signingRequestId` back off the same instance. The request getters
SHALL NOT be mutable.

#### Scenario: A consumer dispatches a request and reads back the signing-request id

- GIVEN shillinq holds an invoice/contract object requiring a signature
- WHEN shillinq constructs a `DocumentSigningRequestedEvent` with `sourceApp=shillinq`, the
  subject reference, a `documentReference`, a `signers` list, a `signatureLevel`, and an
  `externalReference`, and dispatches it via `IEventDispatcher`
- THEN after dispatch the event's `getSigningRequestId()` returns the id of the signing request
  DocuDesk created and `isHandled()` is true

#### Scenario: Request getters are immutable

- GIVEN a constructed `DocumentSigningRequestedEvent`
- WHEN its request fields are read through the getters
- THEN the values equal those supplied at construction and the class exposes no setter for any
  request field (only the `signingRequestId` / `handled` result slot is writable)

### Requirement: Public SigningConcludedEvent contract class

The system SHALL provide an autoloaded public event class `OCA\DocuDesk\Event\SigningConcludedEvent`
extending `OCP\EventDispatcher\Event` that DocuDesk dispatches when a delegated signing request
reaches a terminal outcome. The event SHALL carry, all immutable, the subject/provenance reference
(`sourceApp`, `subjectRegister`, `subjectSchema`, `subjectId`, `externalReference`,
`correlationId`) and the outcome envelope (`signingRequestId`, `status` —
`signed|declined|expired|cancelled`, `signedDocumentRef`, `signers`, `signedAt`). Consumers SHALL
listen for this event to perform their own downstream side effects.

#### Scenario: Concluded event exposes the outcome envelope to consumers

- GIVEN DocuDesk dispatches a `SigningConcludedEvent` for a concluded delegated signing request
- WHEN a consumer's listener reads the event
- THEN it can read the subject reference and the full outcome envelope (`signingRequestId`,
  `status`, `signedDocumentRef`, `signers`, `signedAt`) without any further query to DocuDesk

### Requirement: Listener maps a requested event to SigningService::createRequest

The system SHALL register a listener `OCA\DocuDesk\EventListener\DocumentSigningRequestedListener`
(implementing `OCP\EventDispatcher\IEventListener`) bound to `DocumentSigningRequestedEvent` via
`registerEventListener(DocumentSigningRequestedEvent::class, DocumentSigningRequestedListener::class)`
in `lib/AppInfo/Application.php`. On handling, the listener SHALL build the request-data array from
the event (document reference, signers, signature level, signing mode, plus the provenance fields
`sourceApp`, `subjectRegister`, `subjectSchema`, `subjectId`, `subjectLabel`, `externalReference`,
`correlationId`) and SHALL call `OCA\DocuDesk\Service\SigningService::createRequest($data)` with
**positional** arguments — reusing the existing create logic (ADR-022, no parallel CRUD). The
listener SHALL ensure the provenance is persisted on the created signing request so a later
conclusion can correlate back to the consumer. On a successful result the listener SHALL write the
returned signing-request id and a `handled=true` flag back onto the event; on a failure it SHALL
log and leave the event unhandled, and no exception SHALL escape into the dispatcher.

#### Scenario: Requested event creates a provenance-carrying signing request

- GIVEN DocuDesk is installed and a consumer dispatches a `DocumentSigningRequestedEvent` with a
  complete subject reference and provenance
- WHEN the listener handles it
- THEN `SigningService::createRequest` persists a signing request with the provenance fields set
  and the event's `signingRequestId` result slot holds the created id

#### Scenario: Service failure does not throw into the dispatcher

- GIVEN `createRequest` raises (e.g. invalid signature level / missing document reference)
- WHEN the listener handles the event
- THEN the event is left unhandled (`isHandled()` false, `getSigningRequestId()` null) and no
  exception propagates out of the listener

### Requirement: Persist provenance on the signing request

The system SHALL persist the consumer provenance (`sourceApp`, `subjectRegister`, `subjectSchema`,
`subjectId`, `subjectLabel`, `externalReference`, `correlationId`) onto the signing-request object
created by `SigningService::createRequest()` when those fields are supplied. These fields SHALL be
optional and additive: a signing request created without provenance (an internal DocuDesk request)
SHALL be unaffected and SHALL carry no `sourceApp`. The persisted provenance SHALL be the source
from which the terminal `SigningConcludedEvent` is built.

#### Scenario: Internal request carries no provenance

- GIVEN DocuDesk's own UI creates a signing request without provenance fields
- WHEN the request is persisted
- THEN it carries no `sourceApp` and is indistinguishable in behaviour from the pre-change
  internal flow

### Requirement: Emit SigningConcludedEvent on a delegated terminal outcome

The system SHALL dispatch a `SigningConcludedEvent` from `SigningService` when a signing request
that carries provenance (`sourceApp` set and non-empty) reaches a terminal outcome — COMPLETED
(all signers signed), DECLINED, CANCELLED, or EXPIRED. The event status SHALL be normalised to the
contract vocabulary (`signed | declined | expired | cancelled`), and the envelope (signing-request
id, signers, signed document reference, signed-at) and provenance SHALL be built from the persisted
request fields, then dispatched via the injected `OCP\EventDispatcher\IEventDispatcher`. The system
SHALL NOT emit the event for internal signing requests that carry no provenance. The dispatch SHALL
be fail-soft: a dispatch failure SHALL be logged and SHALL NOT roll back the already-persisted
terminal transition.

#### Scenario: A completed delegated request emits the event

- GIVEN a signing request raised by a consumer (with `sourceApp` set) has all its signers sign
- WHEN the request transitions to COMPLETED
- THEN DocuDesk dispatches a `SigningConcludedEvent` with `status=signed` carrying the provenance
  and the outcome envelope

#### Scenario: A declined or cancelled delegated request emits the event

- GIVEN a signing request raised by a consumer (with `sourceApp` set)
- WHEN a signer declines it (DECLINED) or the initiator cancels it (CANCELLED)
- THEN DocuDesk dispatches a `SigningConcludedEvent` with `status=declined` respectively
  `status=cancelled` carrying the provenance

#### Scenario: An internal request does not emit

- GIVEN an internal signing request with no `sourceApp`
- WHEN it reaches a terminal outcome (COMPLETED / DECLINED / CANCELLED / EXPIRED)
- THEN no `SigningConcludedEvent` is dispatched

#### Scenario: Emission failure does not roll back the terminal transition

@e2e exclude backend integration contract — fail-soft emission path is covered by PHPUnit, not a UI flow

- GIVEN a delegated signing request reaches a terminal outcome and the event dispatch raises
- WHEN the failure occurs after the terminal status has persisted
- THEN the terminal status remains persisted, the failure is logged, and the calling operation
  still completes successfully
