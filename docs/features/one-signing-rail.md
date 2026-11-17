# One signing rail for the fleet

Another Conduction app that needs a document signed asks Filinq to do it. It
does not talk to a signing provider itself. There is one door for that:
`DocumentSigningRequestedEvent`.

## The door

A consumer app dispatches `OCA\Filinq\Event\DocumentSigningRequestedEvent`
through Nextcloud's event dispatcher. Filinq's
`DocumentSigningRequestedListener` turns it into a signing request with
`SigningService::createRequest()`, using whichever provider the administrator
chose. When the signing ends, Filinq dispatches `SigningConcludedEvent`, which
the consumer listens for.

The event carries a `SigningProvenance`:

| Field | Meaning |
| --- | --- |
| `sourceApp` | The app that asks, for example `shillinq` |
| `subjectRegister`, `subjectSchema`, `subjectId` | The record the signature belongs to |
| `externalReference`, `correlationId` | The consumer's own references, returned on the conclusion |

### What the record says

The signing request records which app asked and **what it signs**. The signed
noun (`subjectNoun`) is read from the subject schema the consumer already
supplies: `beschikking` for a dossiq decision, `minutes` for decidiq minutes.
A consumer has to send nothing new for it.

### A request that does not say what it signs is refused

A request from another app must name its subject register, schema and id.
If one is missing, Filinq does not create the request: the event stays
unhandled, `getRefusal()` names the missing fields, and a warning is logged.
A request raised inside Filinq itself names no source app and is not affected.

## Worked example: shillinq

Shillinq is the consumer that already uses the door.
`SigningDelegationService` dispatches the event for an invoice (`finance` /
`invoice` / the invoice id), and `SigningConcludedListener` picks up the
result. The signing request on Filinq's side reads `sourceApp: shillinq`,
`subjectNoun: invoice`.

## Rails to retire

Two apps still sign another way. Their own changes move them onto the door:

- **dossiq**: `LibresignSigningAdapter`, `LibresignApiClient`,
  `LibresignResultAssembler` and `LibresignStatusMapper` call LibreSign
  directly. They are replaced by dispatching the event; signer resolution
  from the mandate matrix stays in dossiq.
- **decidiq**: `EIDASSignatureService` sends signing through two integriq
  Source hops (`docudesk-signing` and `eidas-qes`) that do not exist on a
  standard install. They are replaced by dispatching the event.

Both wait for the LibreSign provider to land, because a decision needs more
than Filinq's built-in simple signature.
