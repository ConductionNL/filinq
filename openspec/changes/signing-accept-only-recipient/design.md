# Design: signing-accept-only-recipient

Kind: code. One property and one status on the recipient, one service
method, one route, one portal row action, two screens.

## Context

Read at development `2088cc1f`.

- `signerRecord` (`lib/Settings/filinq_register.json:2714`) has
  `signingRequestId`, `userId`, `displayName`, `email`, `order`,
  `status`, `signedAt`, `declineReason`, `ipAddress` and `signatureData`.
  `status` is `PENDING`, `SIGNED`, `DECLINED`, `EXPIRED` or `CANCELLED`
  (:2830). There is no role and no lifecycle block. Its
  `x-openregister-notifications` rule `signingRequested` (:2716) tells a
  new recipient "You have a document to sign".
- `lib/Service/SigningService.php`:
  - `createRequest()` (:130) writes one `signerRecord` per entry in
    `signers` with `userId`, `displayName`, `email` and `order`.
  - `sign()` (:383) checks the request is `PENDING` or `IN_PROGRESS`,
    checks the signing mandate for an in-app actor (:406), loads the
    authorised recipient through `SigningActorResolver`, and writes
    `SIGNED` (:428) and an audit entry `SIGNED` (:442).
  - `decline()` (:476) writes `DECLINED` and ends the request.
  - `updateRequestStatus()` (:677) completes the request only when every
    recipient is `SIGNED` (:688), then produces the signed artifact.
- `lib/Controller/SigningController.php:296` `sign()` and `:326`
  `decline()`, routed at `appinfo/routes.php:244` and `:245`.
- `lib/Event/DocumentSigningRequestedEvent.php:82` carries an ordered
  signer list (`userId`, `displayName`, `email`, `order`) from a
  delegating app.
- `lib/Portal/PortalContributionProvider.php` declares on the
  `signerSigningRequests` collection (:344, scoped on the `signerEmail`
  claim) the row actions `sign` and `decline` (:378 to :393) and the same
  two plus `viewDocument` as actions (:401), all at
  `minTrust: substantial`. That is REQ-DDPSS-001 of the open change
  `portal-signing-surface`.
- `lib/Controller/PortalSigningReceiverController.php` implements
  `signDocument()` (:143), `declineDocument()` (:195) and `viewDocument()`
  (:242), routed at `appinfo/routes.php:271` to `:273`, per the open
  change `portal-signing-actions`.
- `src/views/signing/SigningRequestForm.vue` keeps `signers: []` in its
  data (:80) but renders no field to add a recipient, so a request made
  from the form has none.
- `src/views/signing/SigningRequestDetail.vue` shows status, level,
  mode, provider and the audit trail, and no recipient list.
- `src/views/signing/SigningFolder.vue` lists what waits for the current
  user's signature, from `SigningFolderService::folder()`
  (`lib/Service/SigningFolderService.php:93`).
- `lib/Service/Signing/ValidSignProvider.php:79` `initiateSigning()` is a
  stub that returns a made-up package id.

New: `role`, `acceptedAt`, `ACCEPTED`, `SigningService::accept()`, the
accept routes and the portal row action.

## Goals / Non-goals

Goals: a recipient who accepts without signing; completion that counts
acceptances; an acceptance recorded as an acceptance; the same act in the
app and on the portal.

Non-goals: other roles, ValidSign mapping, fields for accept-only
recipients.

## Decisions

### D1. A role on the recipient, sign by default

`signerRecord.role` is `sign` or `accept`, default `sign`. A record
without a role reads as `sign`, so no existing request changes and nothing
is migrated. A request MUST keep at least one `sign` recipient; a request
of only accept-only recipients is refused, because nothing would be
signed.

Alternative considered: a separate recipient list on `signingRequest` for
accepters. Rejected. Order, notifications, the folder, the portal scope and
the audit all read `signerRecord` today; a second list would need all of
them twice.

### D2. Accepting is its own act

`SigningService::accept()` checks the same things as `sign()`: request
state, the authorised recipient, and a recipient still `PENDING`. It then
writes `ACCEPTED`, `acceptedAt` and the route, and an audit entry
`ACCEPTED`. It writes no `signatureData`. `sign()` refuses a recipient
whose role is `accept`, and `accept()` refuses one whose role is `sign`.
`decline()` stays open to both.

Alternative considered: let an accept-only recipient call `sign()` and
store it as a signature with a flag. Rejected. A signature and an
acceptance have different legal weight, and the audit trail must not call
one the other.

### D3. Completion counts both

`updateRequestStatus()` treats a recipient as done when a `sign` recipient
is `SIGNED` or an `accept` recipient is `ACCEPTED`. In sequential mode the
order holds across roles, so an officer placed second accepts after the
tenant signs. The signed artifact lists accept-only recipients as
"accepted" in its evidence and never as signers.

### D4. No signing mandate for accepting

The mandate check in `sign()` (:406) asks whether a person may sign for a
record type. Accepting is not signing, so `accept()` skips it. The
authorised-recipient check still applies.

### D5. The portal gets a third row action

`PortalContributionProvider` declares `accept` ("Accept") beside `sign`
and `decline`, on the same collection, at `minTrust: substantial`,
pointing at `/apps/filinq/api/portal/signing/accept`.
`PortalSigningReceiverController::acceptDocument()` verifies the portal
assertion and the invited-recipient scope the same way as
`signDocument()`, then calls `SigningService::accept()` with the verified
actor. `signerRecord.role` is not on the portal collection today, and the
contribution contract has no way to show a row action only on some rows.
So all three row actions are declared, and the receiver answers the wrong
act for a role with 422 and "This document asks you to accept it, not to
sign it" (or the reverse). The row cannot show the role either: the
collection lists `signingRequest` fields, and the role sits on the
`signerRecord` it is reached through.

Alternative considered: a lower trust level for accepting. Rejected. The
acceptance is attributed to a named person, so the identity behind it must
be as sure as a signer's.

### D6. Where the role is set

The new request form gets recipient rows (name, user or e-mail, role).
`createRequest()` stores `role` from each `signers` entry, and a delegating
app sets it the same way in `DocumentSigningRequestedEvent`'s signer list.
The ValidSign provider receives the role in the signer list it already
takes.

### D7. The notification says what is asked

The `signingRequested` subject becomes "A document is waiting for you" (nl:
"Er wacht een document op je"). The dialect's `created` trigger takes no
filter, so two rules split by role cannot be declared today.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| role and status values | declared enums on `signerRecord` | data, no behaviour |
| completion rule | imperative, in `updateRequestStatus()` | the existing completion gate also produces the artifact; the rule moves with it |
| notification to a new recipient | declared rule, neutral subject | the `created` trigger has no role filter |
| portal row action per role | declared in the contribution manifest | pure data, as REQ-DDPSS-001 requires |

## Seed data

`signerRecord` gains `role` (string, enum `sign`, `accept`, default `sign`)
and `acceptedAt` (date-time), and `status` gains `ACCEPTED`. The seed adds
one request with a signer and an accept-only recipient, the first signed
and the second waiting, so the folder shows an "Accept" row on a demo.
Register version bump.

## Risks / trade-offs

- A consumer that reads `status == SIGNED` for "done" misreads an accepter.
  The artifact and `SigningConcludedEvent` both list acceptances apart
  from signatures, and the docs say so.
- An external accepter sees "Sign", "Decline to sign" and "Accept" on the
  portal and learns from a refusal which one applies. That is a poor first
  try, and the open question below asks portaliq for a better way.
- Until portaliq keeps non-string row actions and passes `signerEmail`,
  the portal half is dark for accepters and signers alike. The in-app half
  works without it.

## Open questions

- Should the accept-only recipient see the document before accepting
  through `viewDocument`, the same way a signer does? The design assumes
  yes; it reuses the existing act.
- Can the portal contract show a row action only on rows where a field
  matches? Then an accepter would never see "Sign". That is a portaliq
  contract question; until then the receiver refuses the wrong act.
