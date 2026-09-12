## Context

Verified on `development` across four repos.

**Filinq.** `SigningService` has `createRequest`, `sign`, `decline`,
`cancelRequest` and `bulkSign`. A provider registry selects the active
provider from `filinq.signing_provider`, with `NativeSigningProvider` and
`ValidSignProvider` shipped. Signing mode is sequential or parallel. eIDAS
level is a first-class parameter. There are audit, verification, expiry and
cancellation services, five signing events, and `signingRequest`,
`signingSession`, `signerRecord` and `signingAuditEntry` schemas. The
delegated contract is implemented: `DocumentSigningRequestedEvent`,
`SigningProvenance`, `DocumentSigningRequestedListener`, `SigningConcludedEvent`.
`createRequest()` already accepts an ordered `signers` list from its caller.

**Shillinq.** Uses the contract. `SigningDelegationService` dispatches,
`SigningConcludedListener` receives.

**Dossiq.** `LibresignSigningAdapter` plus three helper classes. LibreSign
only. One signer, resolved from the mandaat matrix. Synchronous against an
asynchronous process, by its own design document.

**Decidiq.** `EIDASSignatureService::initializeSigningRequest()` prefers a
document signature through filinq, then falls back to integriq. Both branches
go through integriq Sources. The preferred branch looks up slug
`docudesk-signing` and posts `/signing-requests`; filinq's route is
`api/signing/requests`. The fallback looks up slug `eidas-qes`. Neither Source
exists and integriq ships no e-sign adapter, source template, controller or
route.

## Goals / Non-Goals

**Goals:**

- One documented way for a fleet app to ask filinq to sign a document.
- A signing request that says, on the record, which app asked and what noun
  was signed.
- The ownership question recorded with its evidence, so the next person
  argues from the same facts.

**Non-Goals:**

- Picking the winner of the ownership question. See D1.
- The LibreSign provider. `libresign-signing-provider` owns it.
- The no-silent-downgrade fix on the completion path. `signing-trust-rebuild`
  owns it.
- Signer identity rails, DigiD and eHerkenning. `signer-identity-rails` owns
  them.
- Moving signer resolution out of dossiq. Who is legally competent to sign a
  beschikking comes from the mandaat matrix, which models bevoegdheid. That is
  a case-domain rule and it stays where it is under either answer to D1.
- The consumer-side edits in dossiq and decidiq. Each is a change in its own
  repo.

## Decisions

**D1. The ownership question is recorded as open, not answered here.**

Ruben's position: signing belongs in decidiq.

*What it rests on.* A signature is the moment a bevoegd gezag commits. Decidiq
owns the decision object, the approval chain and the governance body. It
already has `IEIDASSignatureService`, an approval-route service family, and
the concept of who may resolve. Putting the signature next to the decision
keeps the legal act and its authority in one app.

The leaf register's position: signing belongs in filinq.

*What it rests on.* The thing physically signed is a file, and filinq owns
files. Filinq has the provider registry, the sequential and parallel modes,
the eIDAS levels, the audit, the expiry, the cancellation, the four schemas
and the implemented cross-app contract. Decidiq's own code already prefers
filinq for a document signature.

*Why it does not resolve.* The two positions are about different nouns.
Decidiq signs a besluit. Filinq signs a PDF. A beschikking is a besluit that
exists as a PDF, which is why dossiq's own signing sits in the middle and why
both answers look right from where their advocate stands.

*What would settle it.* Somebody has to say whether the fleet's signature is
an act on a decision or an operation on a file. That is a product call, not a
code one. This change makes the answer cheap either way: with D2 in place,
every signing request already records the noun, so a later decision to route
besluiten to decidiq and documents to filinq can be made per request rather
than per fleet, and the existing records can be counted first.

**D2. The noun goes on the provenance, derived from what consumers already
send.** `DocumentSigningRequestedEvent` already carries the subject register,
schema and id. The noun is the subject schema. Nothing new is asked of a
consumer, and the record gains the field the ownership argument turns on.

**D3. Refuse an unattributed delegated request.** A signing request that
cannot say what it signs is exactly the record that makes D1 unanswerable.
Internal filinq requests carry no provenance and are untouched, so the refusal
applies only where a consumer app asked.

**D4. One door, stated in the spec rather than assumed.** The contract has
been implemented and unused by two of its three intended consumers for long
enough that both built alternatives. Writing the rule down is the cheap half
of fixing that. The expensive half is the two consumer changes, and those are
sequenced behind `libresign-signing-provider`, because filinq cannot yet
produce a signature a beschikking can carry.

**D5. Nothing in this change signs anything differently.** No provider, no
level, no artifact, no state machine. This is the rail and the record.

## Risks / Trade-offs

**Recording a question instead of answering it can read as avoidance.** The
alternative is worse: whichever app a single agent picked would become the
fleet's answer by accident, and the two consumers would migrate onto it before
anybody with the authority to decide had seen the argument. D2 makes the
decision reversible per request, which is the property that makes recording it
safe.

**The single-door rule bites before the door is worth walking through.**
Filinq's only working signature today is a native HMAC SES marker. A
beschikking needs a certificate behind it. So the rule ships now and the
consumer migrations wait on `libresign-signing-provider`. That ordering is
deliberate and it is written into the task list rather than left to whoever
picks this up.

**Decidiq's dark path may be load-bearing somewhere.** Its filinq branch fails
closed and its integriq branch fails to an audit-log stub, so nothing depends
on it working. It should still be confirmed against a live instance before the
decidiq change removes it.
