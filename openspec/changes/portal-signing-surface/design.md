# Design: portal-signing-surface

## Context

hydra ADR-046 makes **portaliq** the ONE external portal for people without a
Nextcloud account. Contribution contract v2.2 now expresses, beyond read
collections: per-collection `rowActions` (an action id rendered as a per-row
control), `type: update` actions with server-stamped `set`, and A6
`endpoint-forward` actions. `portal-contribution`'s `signer` manifest deferred
all of these (`actions: []`, `REQ-DDPORT-006`). Two sibling changes now supply
the plumbing; this change supplies the SURFACE and the portal-identity evidence
binding.

### Verified facts (HEAD, docudesk)

- **The signer manifest** (`lib/Portal/PortalContributionProvider.php`,
  `signerContribution()`) exposes `signerRecords` (schema `signerRecord`, scope
  `email` via `signerEmail` claim) and `signerSigningRequests` (schema
  `signingRequest`, reached by a one-hop `via` join over the subject's own
  `signerRecord` rows, `targetField: signingRequestId`, `minTrust: substantial`).
  Both ship `actions: []` today.
- **`SigningService::sign(string $requestId, string $signerId): array`**
  (`lib/Service/SigningService.php:352`) and
  **`decline(string $requestId, string $signerId, string $reason): array`**
  (`:441`) are the honest signing primitives. Per `signing-trust-rebuild` they
  route terminal transitions through the status machine and emit the `v: 2`
  identity-bound assertion.
- **`signerRecord`** properties (register `signing`): `signingRequestId, userId,
  displayName, email, order, status, signedAt, declineReason, ipAddress,
  signatureData`. `signatureData` (schema `visible:false`) is where a drawn
  signature payload lands; the invited `email` is the only stable external
  identity (no external contact UUID).
- **`signingRequest`** properties: `documentFileId, documentName,
  initiatorUserId, signatureLevel, signingMode, status, provider, deadline,
  signerIds`.
- **`portal-signing-actions`** (sibling change) supplies the
  `PortalSigningReceiverController`, `PortalAssertionVerifier`, the server-side
  invited-signer resolution (`signerRecord.email == assertion signerEmail AND
  signingRequestId == target`), and the `SigningService` verified-actor
  entrypoint. This change extends that receiver rather than adding a new one.
- **`signing-trust-rebuild`** (`REQ-DDSTR-001`) defines the `v: 2` assertion MAC
  over `sha256(canonical-document) . "\n" . canonical-JSON(assertion-minus-mac)`
  with sorted keys, binding `signer`, `timestamp`, `level`. This change adds the
  portal subject claims to that same canonical-JSON so they are MAC-covered.

## The two additive seams

### 1. rowActions on the collection (declarative, pure data)

`portal-signing-actions` declared `sign`/`decline`/`viewDocument` on the
manifest's top-level `actions[]`. The contract-v2.2 way to render a per-document
control is a `rowActions` reference on the collection that owns the rows. This
change adds `rowActions: [sign, decline]` to the `signerSigningRequests`
collection (the documents-awaiting-me rows), each `minTrust: substantial`
(eIDAS-aligned: an AES-grade act needs a substantial-assurance session). This is
constant data — no I/O — keeping the provider plain and duck-typed (ADR-046 A1).
A row already in a terminal state (`signed`/`declined`) MUST NOT offer the
actions; the manifest declares the actions and portaliq + the receiver enforce
state (the receiver's terminal-state guard is authoritative).

### 2. Portal-subject evidence binding (imperative, the portaliq#3 fix)

The forgeable-signer bug class (portaliq#3) is: a signature evidence/MAC that
does not cover the signer's identity can be re-attributed to a different signer
while still validating. `REQ-DDSTR-001` fixes this for the in-app signer's
name/level/timestamp. For a PORTAL-originated signature the true signer identity
is the portaliq subject — `subjectRef`/`identityRef`, the eIDAS trust level, and
the assertion `jti` (the one-time act id) — resolved server-side by the
`portal-signing-actions` receiver from the verified assertion. This change
requires those claims to be added to the `v: 2` assertion's canonical-JSON
BEFORE the MAC is computed, so the MAC covers them:

```
mac = HMAC-SHA256(
        secret,
        sha256(canonical-document) . "\n" .
        canonical-JSON({ ...signer, timestamp, level,
                         portalSubjectRef, portalIdentityRef,
                         portalTrust, portalJti } minus mac))
```

Verification (the `signing-trust-rebuild` verifier) already recomputes and
`hash_equals()`-compares the MAC over the canonical assertion; because the
portal identity fields are now part of that canonical JSON, rewriting any of
them (e.g. swapping `portalSubjectRef` to another subject) changes the recomputed
MAC and the evidence reports `invalid`. No new verifier is needed — the binding
is achieved by INCLUDING the fields in the MAC input. An evidence record that
validates while OMITTING the portal signer identity for a portal-originated
signature is, by this spec, a violation.

## Consent + drawn signature

`signDocument`'s body carries a `consent` confirmation (the signer's explicit
"I agree to sign" — recorded as evidence of intent, an eIDAS SES/AES
requirement) and an OPTIONAL drawn-signature payload that lands in the
`signerRecord.signatureData` field (already `visible:false`, never projected to
the read manifest). Neither is trusted for identity — identity is the
server-derived assertion subject; the drawn signature is decorative/evidentiary
only. `declineDocument` carries a `reason` recorded in `signerRecord.declineReason`.

## eIDAS levels

This surface delivers **SES** (simple electronic signature — consent + audit
trail) and, when the portal session is substantial-assurance (the
`minTrust: substantial` gate), **AES**-grade evidence (identity-bound,
tamper-evident via the MAC binding above). **QES** (qualified, certificate-backed
via an eIDAS QTSP, Article 3(12)) is explicitly delegated to an external QTSP and
out of scope — the manifest MUST NOT claim QES assurance for a portal signature,
and the exposed assurance level MUST NOT exceed the session trust.

## Security Considerations

- **Fail closed** inherits from `portal-signing-actions` (401 invalid assertion,
  403 wrong audience / not an invited signer, uniform not-authorised with no
  existence oracle, 502 downstream). This change adds no new fall-open path.
- **Identity is server-derived**: the bound portal claims come ONLY from the
  verified assertion, never the request body. A body-supplied `subjectRef` /
  `email` / `identityRef` never influences the evidence.
- **Terminal-state**: `SigningService`'s status machine still rejects a
  sign/decline on an already-terminal request; the rowAction manifest does not
  weaken it.
- **portaliq#3 regression**: a unit test MUST rewrite the portal signer identity
  in a stored evidence record and assert verification reports `invalid`.
- **No QES over-claim**: the assurance exposed on the evidence MUST NOT exceed
  the session trust; QES is refused.

## Seed Data

No new OpenRegister schema or register — reuses `signerRecord` /
`signingRequest` verified at HEAD (the drawn signature reuses the existing
`signatureData` field). The `v: 2` assertion gains the four portal-identity keys
inside its existing evidence JSON (no schema change; the assertion is an
embedded artifact field). Unit tests construct the provider/receiver directly
and build synthetic assertions on the nil-UUID pattern so fixtures are
self-evidently fake.

## Open questions (apply-time)

1. **Scope-claim forwarding** (shared with `portal-signing-actions`): the A6
   assertion must carry the resolved `signerEmail` scope claim + the portal
   subject claims server-side; the receiver fails closed without them, so
   shipping early is safe.
2. **Assertion field names**: confirm the exact key names for the portal subject
   claims inside the `v: 2` canonical JSON at apply, coordinated with
   `signing-trust-rebuild`'s writer.
