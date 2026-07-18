# Design: portal-signing-actions

## Context

hydra ADR-046 makes **portaliq** the ONE external portal for people without a
Nextcloud account. Contribution contract v2 (portaliq
`openspec/specs/portal-contribution-contract/spec.md`, verified at HEAD) defines
**A6 endpoint bearer-forward actions**: an app declares actions
`{id, label, endpoint, method, minTrust?}` on a manifest; portaliq exposes
`POST /portal/api/actions/{appId}/{actionId}` which authorises against the
SUBJECT's own aggregated manifest, then forwards the call server-to-server
(`OCP\Http\Client\IClientService`) to the declared instance-local endpoint,
attaching a short-lived (~60 s) HS256 `X-Portal-Subject` assertion and NEVER the
client's `Authorization` header, and relays the domain app's JSON status+body.

`portal-contribution` already ships DocuDesk's plain `PortalContributionProvider`
with a `signer` audience, but with `actions: []` (`REQ-DDPORT-006`) — its
design.md explicitly defers sign/decline as "A6 endpoint actions … so the
receiver + `PortalAssertionVerifier` land as a reviewed unit". This change is
that reviewed unit.

### Verified facts (HEAD, docudesk + portaliq)

- **portaliq mints the assertion** in `PortalJwtService::createAssertion()`
  (`lib/Service/PortalJwtService.php:160`): header `{"alg":"HS256","typ":"JWT"}`,
  claims exactly `sub, audience, organisation, trust, jti, use="assertion",
  iat, exp, iss="portaliq"`, `exp-iat = 60`. This is FROZEN
  ("Frozen assertion wire format" requirement) — a receiver may rely on the
  shape byte-for-byte. It is signed with portaliq's `jwt_signing_secret`
  instance secret (`PortalSessionService::issueAssertion` → `createAssertion`).
- **The A6 forward** (`ContributionController::action()`,
  `lib/Controller/ContributionController.php:744`) is `#[PublicPage]` +
  `#[NoCSRFRequired]`, rejects full http(s) URLs (SSRF guard), forwards the
  client body as-is, relays the decoded JSON body + status, and returns `502` on
  transport failure. The receiver must mirror that JSON-only relay shape.
- **`signerRecord`** (`lib/Settings/docudesk_register.json`, register `signing`)
  properties: `signingRequestId, userId, displayName, email, order, status,
  signedAt, declineReason, ipAddress, signatureData`. There is NO external
  contact UUID — the invited `email` is the only stable external identity (same
  reason `portal-contribution` scopes the signer by `signerEmail`).
- **`signingRequest`** properties: `documentFileId, documentName,
  initiatorUserId, signatureLevel, signingMode, status, provider, deadline,
  signerIds`. The document to view lives at `documentFileId`.
- **`SigningService::sign()` / `decline()`** (`lib/Service/SigningService.php`,
  `sign` :352, `decline` :441) derive the actor from
  `$this->userSession->getUser()` and reject the act unless
  `signer['userId'] === $user->getUID()` (the #282 fix) AND
  `signer['signingRequestId'] === $requestId` (the C4 belongs-to-request check).
  An external portal signer has NO Nextcloud user, so these methods CANNOT be
  called unchanged from a `#[PublicPage]` receiver — the central design problem
  below.

## The identity chain (server-derived, never client)

```
portaliq SPA (accountless signer, magic-link/eIDAS session, trust=substantial)
  → POST /portal/api/actions/docudesk/sign     body: {signingRequestId, ...}
  → portaliq authorises against the signer's OWN manifest, re-checks minTrust
  → forwards to /apps/docudesk/api/portal/signing/sign
        header  X-Portal-Subject: <HS256 assertion>   (client bearer dropped)
        body    {signingRequestId, ...}                (client-supplied)
  → DocuDesk receiver:
        1. PortalAssertionVerifier: verify HS256 vs shared secret; alg=HS256 only;
           iss=portaliq; use=assertion; unexpired; frozen claim set  → else 401
        2. audience==signer && trust>=substantial (re-check)          → else 403
        3. signerEmail := verified assertion scope claim (NEVER body) → else 403
        4. target := body.signingRequestId  (opaque id; reject URL/path → SSRF)
        5. signerRecord := OR find email==signerEmail && signingRequestId==target
                                                                       → else 403/404 uniform
        6. SigningService::sign(target, signerRecord, actor=verified-external)
        7. audit: portal email + assertion jti; relay JSON; 502 on downstream fail
```

Steps 1–5 make identity and authorisation entirely server-derived. Step 5 is the
anti-IDOR boundary: the acting signer must be an invited `signerRecord` on the
EXACT target request, so knowing another request's id buys nothing.

## Composition with the two dependencies

- **Extends `portal-contribution`.** This change adds to the SAME
  `PortalContributionProvider` file: the `signer` manifest's `actions` goes from
  `[]` to the three A6 actions. It supersedes `REQ-DDPORT-006` for the `signer`
  audience ONLY (the `data-subject` manifest stays read-only). Because
  `portal-contribution`'s canonical spec is not assigned to this change (wave
  canonical-touch discipline), the relaxation is modelled as this change's own
  `portal-signing-actions` capability and noted here; apply order is
  `portal-contribution` first, then this change layers the actions on.
- **Consumes `signing-trust-rebuild`.** That change makes `decline()` pass the
  terminal-state status machine and binds the assertion MAC to signer identity,
  timestamp, level and method. This change does NOT re-implement signing; it
  calls the honest primitive so an external signature is exactly as trustworthy
  as an in-app one, and a `decline` on a COMPLETED request is rejected the same
  way. The only new seam is the verified-actor entrypoint (below).

### The verified-actor entrypoint (the one signing-service seam)

`sign()`/`decline()` read the actor from `userSession`. An external signer has
none. Rather than duplicate the signing logic in the receiver (which would fork
the honest primitive and re-open #282), this change adds an actor-source seam to
`SigningService`: an overload/optional parameter that accepts an already-resolved
verified `signerRecord` as the actor. When the receiver supplies it, identity is
asserted against `signer['email']` (matching the verified assertion) instead of
`signer['userId']` against a Nextcloud uid; the belongs-to-request C4 check, the
status machine, the audit write and the MAC binding are all UNCHANGED. When no
actor is supplied the method behaves exactly as today (Nextcloud userSession).
This keeps ONE honest signing path for both audiences and is the intended
reading of the brief's "calls the existing `SigningService::sign()/decline()`".

## Declarative vs imperative

- **Declarative (pure data):** the three A6 action declarations on the `signer`
  manifest. Like the rest of `PortalContributionProvider`, they are constants —
  no I/O, no branching on subject data beyond the audience. This keeps the
  provider a plain, dependency-free, duck-typed class (ADR-046 A1) and keeps the
  authorisation contract audit-readable.
- **Imperative (justified external-integration exception):** the
  `PortalSigningReceiverController` and `PortalAssertionVerifier` are genuinely
  imperative — they verify a cryptographic assertion, read the shared secret,
  query OpenRegister for the invited `signerRecord`, and call `SigningService`.
  This is NOT an ADR-022 violation (apps-consume-OR-abstractions) or an
  ADR-008 layering break: it is the **A6 consumer half of a cross-app security
  protocol**. portaliq's own contract states "Receiving-app assertion
  verification (A6 consumer side) is out of scope here by design — tracked per
  app in the ADR-046 rollout waves"; this IS that per-app consumer, and a signed
  server-to-server assertion cannot be validated declaratively. The imperative
  surface is deliberately minimal (verify → resolve → delegate) and every
  business rule (status machine, MAC, audit) stays inside the honest
  `SigningService` primitive, not the receiver.

## Document-view transport

portaliq's A6 forward relays a DECODED JSON body only, so the `viewDocument`
action cannot stream binary. v1 returns `{documentName, mimeType, contentBase64}`
resolved from `signingRequest.documentFileId`, carried inside the single JSON
hop. This is correct and fail-closed but heavy for large PDFs; a streaming /
short-lived signed-URL variant is an Open Question follow-up (below), not a v1
blocker — an external signer must be able to read the modest documents typical of
a signing request before signing.

## Security Considerations

- **Fail closed on every path** (ADR-005): `401` (missing/invalid/expired/
  wrong-`alg`/wrong-`iss`/wrong-`use` assertion, or no shared secret configured),
  `403` (wrong audience, trust below substantial, not an invited signer,
  malformed target), `404`/`403` uniform where an oracle would leak, `502`
  (downstream/OR failure). No path falls open to acting without a verified
  assertion.
- **alg-confusion / `none` defeated:** the verifier accepts ONLY
  `alg == "HS256"`; a token declaring `none` or an asymmetric alg is rejected
  before signature checking.
- **Shared-secret sourcing:** the verifier reads the portaliq-managed instance
  signing secret server-side (the same `jwt_signing_secret` portaliq signs with)
  and fails closed when it is unset — it never accepts an empty-secret or
  unsigned assertion.
- **No cross-signer IDOR:** identity is the assertion's `signerEmail`; the act
  is allowed only when a `signerRecord` exists with that email AND the target
  `signingRequestId`. A body-supplied identity is ignored.
- **SSRF:** the receiver never makes an outbound request; the client
  `signingRequestId` is used only as an OR object id and is rejected if it looks
  like a URL/path. (portaliq additionally rejects full-URL endpoints on the
  forward side.)
- **Token confusion:** the `X-Portal-Subject` assertion carries `use:
  assertion`; portaliq's `resolveFromBearer` already rejects it as a session
  bearer, and the DocuDesk receiver treats it only as an action assertion, never
  a session.
- **Audit:** every act writes a signing-audit entry (via the honest
  `SigningService`/`SigningAuditService` path) recording the portal signer email
  and the assertion `jti`, so the external act is traceable to its portaliq
  session.
- **No client secrets, tokens or endpoints** are introduced by the manifest —
  only relative endpoint paths.

## Seed Data

This change adds NO OpenRegister schemas, registers or objects — it reuses the
`signing` register's `signerRecord` / `signingRequest` schemas verified at HEAD,
and the existing `signingAuditEntry` audit path. There is therefore no register
version bump and no migration.

Unit tests construct the provider directly (no container) and build synthetic
assertions/subjects on the **nil-UUID pattern** so fixtures are self-evidently
fake and can never collide with live data (mirroring `portal-contribution`):

```php
$signerAssertionClaims = [
    'sub'          => '00000000-0000-0000-0000-000000000003',
    'audience'     => 'signer',
    'organisation' => '00000000-0000-0000-0000-000000000002',
    'trust'        => 'substantial',
    'jti'          => '00000000-0000-0000-0000-0000000000aa',
    'use'          => 'assertion',
    'iss'          => 'portaliq',
    // signerEmail scope claim carried per the portaliq A6 amendment (Open Qs)
    'signerEmail'  => 'signer@example.invalid',
];
```

Live seeding (a portalAccount carrying `claims.docudesk.signerEmail`, an invited
`signerRecord` on a PENDING `signingRequest`) belongs to portaliq's + DocuDesk's
shared e2e environment, not to this change.

## Open questions (apply-blocker dependencies)

1. **The A6 assertion does not carry the resolved scope claim.** The FROZEN
   wire format is `sub, audience, organisation, trust, jti, use, iat, exp,
   iss` — there is NO `signerEmail`, and `sub` is portaliq's subjectRef UUID,
   which DocuDesk cannot map to an invited email (DocuDesk owns no portalAccount).
   The receiver's identity step REQUIRES the invited email server-side. Resolution
   options, both keeping identity server-derived:
   - **(a, preferred)** a small portaliq A6 amendment: for a claim-scoped
     action, `createAssertion` also forwards the resolved scope claim (e.g.
     `signerEmail`) it already resolved server-side for the collection read; the
     DocuDesk verifier consumes it. This spec is written to consume that claim.
   - **(b)** the DocuDesk receiver resolves `sub` → invited email via a
     portaliq server-to-server callback.
   This is a **named apply-blocker**: portal-signing-actions cannot go live until
   (a) or (b) lands on the portaliq side. The DocuDesk receiver is authored to
   fail closed (`403`) when no signer-identifying claim is present, so shipping
   it early is safe (it simply refuses every act until the claim arrives).
2. **Large-document `viewDocument`.** base64-in-JSON is fine for typical signing
   documents; a streaming / short-lived signed-URL transport for large PDFs is a
   follow-up.
3. **Shared-secret provisioning.** Confirm DocuDesk reads portaliq's
   `jwt_signing_secret` app value directly (same instance) vs a dedicated
   shared-secret config surface — an install/ops decision to confirm at apply.
