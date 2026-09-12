---
kind: code
depends_on: [libresign-signing-provider, signing-trust-rebuild]
---

# Proposal: one signing rail for the fleet

## Why

Three fleet apps sign things. Each built its own way to do it, and two of the
three reach filinq through a route that does not work.

**Filinq already ships the contract they need, and it is implemented.**
`filinq-signing-events` is 9 of 9 tasks done: `DocumentSigningRequestedEvent`
takes a subject register, schema and id, an ordered signer list, a signature
level, a signing mode, a document reference and a correlation id, and hands
back the new signing request id on the same event instance. `SigningProvenance`
persists who asked and for which object. `SigningConcludedEvent` fires on
every terminal outcome of a request that carries provenance. Shillinq uses it
today, through `SigningDelegationService` and `SigningConcludedListener`.

**Dossiq does not use it.** It signs beschikkingen through its own
`LibresignSigningAdapter`, `LibresignApiClient`, `LibresignResultAssembler`
and `LibresignStatusMapper`. LibreSign only, one signer, and its own design
document records that it runs synchronously against an asynchronous process.

**Decidiq does not use it either, and its filinq path is dark twice over.**
`EIDASSignatureService::initializeSigningRequest()` prefers filinq for a
document signature, which is the right instinct. It reaches it by looking up
an integriq Source with the slug `docudesk-signing` and posting
`/signing-requests` through integriq's `CallService`. No such Source exists,
and filinq's route is `api/signing/requests`, not `/signing-requests`. The
fallback is an integriq Source with the slug `eidas-qes`, and that does not
exist either. Decidiq's signing has never reached anything.

So the fleet has one working contract, one working consumer, and two apps
that each built a private road to the same destination.

**And there is an open question underneath this that nobody has settled.**
Ruben's position is that signing belongs in decidiq. The leaf register's
position is that it belongs in filinq. Both are defensible and they are not
actually about the same thing, which is why the argument does not resolve
itself: decidiq signs a *decision*, filinq signs a *document*, and a
beschikking is both. This proposal does not pick a winner. It records the
question, states what each side rests on, and makes the answer readable off
every signing request instead of being a matter of opinion.

## What Changes

- The open ownership question is written down, with both positions and what
  would settle each. It sits in this change's design as a decision that is
  explicitly not taken, so the next person reads the argument rather than
  guessing which app to call.
- Filinq's signing capability declares that a fleet app reaches it through
  `DocumentSigningRequestedEvent`, and that a consumer does not ship a signing
  provider adapter of its own. The contract exists. This says it is the only
  door.
- `SigningProvenance` gains a required statement of what is being signed: the
  noun, taken from the subject schema the consumer already supplies. A signing
  request can then be read as "decidiq asked filinq to sign a besluit" or
  "dossiq asked filinq to sign a beschikking", which is exactly the
  distinction the open question turns on.
- Filinq refuses a delegated request whose subject it cannot identify, rather
  than accepting an unattributed signature.
- Filinq's signing settings surface names the consumers that have signing
  requests on the instance, so an admin can see the rail is one rail.

## Capabilities

### New Capabilities

- `signing-rail-consolidation`: one delegated-signing door for the fleet, a
  signing request that records the noun it signs, and the ownership question
  recorded as open rather than assumed.

### Modified Capabilities

<!-- None. The delegated-signing contract in `filinq-signing-events` and the
     document-signing requirements are unchanged. This capability adds the
     single-door rule and the subject-noun record on top of them. -->

## Impact

- `lib/Event/SigningProvenance.php`,
  `lib/Event/DocumentSigningRequestedEvent.php`,
  `lib/EventListener/DocumentSigningRequestedListener.php`,
  `lib/Service/SigningService.php` and the `signingRequest` schema.
- Counterpart changes in two other repos, each raised there and not here:
  dossiq retires `LibresignSigningAdapter` and dispatches the event, keeping
  signer resolution from the mandaat matrix, which is a case-domain rule about
  legal competence and does not move. Decidiq retires the integriq Source hop
  and dispatches the same event for a besluit.
- Depends on `libresign-signing-provider`, because filinq's only working
  signature today is native HMAC SES and a beschikking needs a certificate
  behind it. Dossiq's LibreSign integration is the thing that provider absorbs.
- Depends on `signing-trust-rebuild`, because a consumer that hands over its
  own signing must be able to trust that the level it asked for is the level
  it gets.
- Does not restate `signer-identity-rails`. Who the signer proves to be is a
  separate and larger question.
