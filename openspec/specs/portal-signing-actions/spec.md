---
status: done
---

# portal-signing-actions Specification

## Purpose
DocuDesk gives an external **signer** (a person WITHOUT a Nextcloud account) a real outside signing frontend through **portaliq**, the shared external portal (hydra ADR-046, contribution contract v2). `portal-contribution` exposes a READ-ONLY signer surface (`REQ-DDPORT-006` mandates the signer manifest's `actions` be empty). This capability closes the write path: it declares the contract-v2 A6 `endpoint`-type actions `sign`, `decline` and `viewDocument` on the signer manifest, and adds a bearer-guarded DocuDesk **receiver** plus a `PortalAssertionVerifier` that validates portaliq's frozen `X-Portal-Subject` HS256 assertion, derives the signer identity SERVER-SIDE from that assertion (never from client input), verifies the signer is genuinely invited on the target request, and calls the honest `SigningService::sign()` / `decline()` primitive delivered by `signing-trust-rebuild`. Every path fails closed (401/403/502) and every act is written to the signing audit.

**Go-live note**: the receiver fails closed `403` on every act until portaliq's A6 wire format forwards a resolved `signerEmail` scope claim (design.md Open Question 1) — this is a go-live gate, not an authoring gap; the receiver, verifier and entrypoint are fully implemented and unit-tested against the frozen contract as specified.

@e2e exclude Backend server-to-server A6 receiver consumed by portaliq; no DocuDesk UI surface (the signing frontend is portaliq's SPA). Covered by PHPUnit (tests/unit/Portal/PortalAssertionVerifierTest.php, tests/unit/Controller/PortalSigningReceiverControllerTest.php, tests/unit/Portal/PortalContributionProviderTest.php). Newman contract collection not yet authored.
## Requirements
### Requirement: Signer manifest declares three substantial-gated endpoint actions (REQ-DDPSA-001)
`OCA\DocuDesk\Portal\PortalContributionProvider`'s `signer` manifest MUST declare an `actions` array containing exactly three contract-v2 A6 endpoint actions, each shaped `{id, label, endpoint, method, minTrust}`:

- `sign` — `method: POST`, `minTrust: substantial`.
- `decline` — `method: POST`, `minTrust: substantial` (the client supplies a decline `reason` in the request body).
- `viewDocument` — `method: GET`, `minTrust: substantial`.

Every `endpoint` MUST be an instance-local RELATIVE path under `/apps/docudesk/api/portal/signing/…` (leading slash, no scheme, no host, no `..`), so portaliq's SSRF guard and its `urlGenerator->getAbsoluteURL()` forward accept it. This requirement RELAXES `portal-contribution`'s read-only signer constraint for the `signer` audience only; the `data-subject` manifest's `actions` MUST remain empty. The provider stays a plain, dependency-free class (no portaliq import, no `implements`, no constructor) — it only adds pure-data action declarations.

#### Scenario: The signer manifest carries exactly the three endpoint actions
- **GIVEN** a constructed `PortalContributionProvider` and a subject with `audience: 'signer'`
- **WHEN** `getContribution($subject)` is called
- **THEN** the returned manifest's `actions` contains exactly `sign`, `decline` and `viewDocument`
- **AND** each action declares an instance-local relative `endpoint` under `/apps/docudesk/api/portal/signing/`, a `method` of `POST`/`POST`/`GET` respectively, and `minTrust: substantial`
- **AND** the `data-subject` manifest's `actions` is still empty
- @e2e exclude backend contract-data declaration consumed by portaliq, no DocuDesk UI surface — covered by PHPUnit (tests/unit/Portal/PortalContributionProviderTest.php)

### Requirement: Bearer-guarded receiver validates the frozen portaliq assertion (REQ-DDPSA-002)
DocuDesk MUST ship a receiver controller for the three action endpoints, each route declaring `#[PublicPage]` + `#[NoCSRFRequired]` (portaliq forwards server-to-server without a Nextcloud session or CSRF token). A `PortalAssertionVerifier` MUST validate the inbound `X-Portal-Subject` header as portaliq's frozen A6 assertion BEFORE any other work: it MUST verify the HS256 signature against the portaliq-managed shared signing secret; reject any token whose header `alg` is not exactly `HS256` (defeating `none` / alg-confusion); require `iss` = `portaliq`, `use` = `assertion`, and a present, unexpired `exp`; and require the claim set `sub`, `use`, `iat`, `exp`, `iss`. A missing, malformed, wrongly-signed, expired or wrong-`use` assertion MUST fail closed with `401`. The verifier MUST source the shared secret server-side (the portaliq-managed instance secret) and MUST fail closed with `401` when no shared secret is configured — it MUST NEVER accept an unsigned or empty-secret assertion.

#### Scenario: An invalid or missing assertion is rejected 401
- **GIVEN** a POST to a portal signing receiver endpoint
- **WHEN** the `X-Portal-Subject` header is absent, has `alg` other than `HS256`, has `iss` other than `portaliq`, has `use` other than `assertion`, is expired, or its signature does not match the shared secret
- **THEN** the receiver responds `401` and performs no OpenRegister read or write and no `SigningService` call
- **AND** when no shared signing secret is configured the receiver also responds `401`
- @e2e exclude server-to-server verifier contract with no DocuDesk UI surface — covered by PHPUnit (tests/unit/Portal/PortalAssertionVerifierTest.php, tests/unit/Controller/PortalSigningReceiverControllerTest.php)

### Requirement: Signer identity is derived server-side, never from the client (REQ-DDPSA-003)
After signature validation the receiver MUST derive the acting signer's identity ONLY from the verified assertion, NEVER from the request body, query, or `Authorization` header. It MUST require `audience` = `signer` and re-check `trust` server-side to be at least `substantial` (defence in depth — portaliq already gates the action, but the receiver MUST NOT rely on that); any other audience or a lower trust MUST fail closed with `403`. The signer's scoping identity is the invited email carried as the assertion's resolved `signerEmail` scope claim; the receiver MUST take that email from the verified assertion only and MUST fail closed with `403` when no signer-identifying claim is present. A client-supplied email, `userId`, `subjectRef` or `claims` value MUST NEVER influence the resolved identity.

#### Scenario: Identity comes from the assertion and client-supplied identity is ignored
- **GIVEN** a valid assertion whose `audience` is `signer`, `trust` is `substantial`, and which carries the resolved `signerEmail` scope claim
- **AND** a request body that also contains an `email` and a `userId` naming a DIFFERENT signer
- **WHEN** the receiver resolves the acting signer
- **THEN** the identity used is the assertion's `signerEmail`, and the body's `email`/`userId` are ignored entirely
- **AND** an assertion with `audience` other than `signer`, with `trust` below `substantial`, or with no signer-identifying claim is rejected `403`
- @e2e exclude server-derived-identity contract with no DocuDesk UI surface — covered by PHPUnit (tests/unit/Controller/PortalSigningReceiverControllerTest.php)

### Requirement: No cross-signer IDOR — the actor must be an invited signer on the target request (REQ-DDPSA-004)
The receiver MUST treat the client-supplied `signingRequestId` (carried in the request body) ONLY as an opaque object reference (an id), and MUST reject any value that is a full URL or contains a path/scheme/host (SSRF hardening), and MUST NEVER use it to build an outbound request. Before any signing act the receiver MUST resolve, via OpenRegister, the `signerRecord` whose `email` equals the assertion-derived `signerEmail` AND whose `signingRequestId` equals the target — i.e. the acting signer MUST be a genuinely invited signer on that exact request. When no such `signerRecord` exists (wrong email, request the signer was not invited to, or a non-existent request id) the receiver MUST fail closed with a single uniform not-authorised result and MUST NOT reveal whether the request exists (no existence oracle) and MUST NOT touch `SigningService`.

#### Scenario: A signer cannot act on a request they were not invited to
- **GIVEN** a valid `signer` assertion resolving to email `A`
- **AND** a `signingRequestId` in the body whose `signerRecord` set does NOT include a row with `email == A` and that `signingRequestId`
- **WHEN** the receiver processes a `sign`, `decline` or `viewDocument` call
- **THEN** the receiver fails closed with the identical not-authorised response for a foreign request, a not-invited signer, and a non-existent request id (no existence oracle)
- **AND** `SigningService::sign()` / `decline()` is never called
- **AND** a `signingRequestId` value that is a full URL or contains a path is rejected before any lookup
- @e2e exclude IDOR/SSRF security invariant on a backend-only receiver — covered by PHPUnit (tests/unit/Controller/PortalSigningReceiverControllerTest.php)

### Requirement: Sign and decline call the honest SigningService primitive with the verified external actor (REQ-DDPSA-005)
On a verified, authorised request the receiver MUST perform the signing act through the existing `OCA\DocuDesk\Service\SigningService::sign()` / `decline()` primitive (the honest, status-machine-gated, identity-bound version delivered by `signing-trust-rebuild`), acting as the resolved verified external signer rather than a Nextcloud user session. Because those methods derive the actor from the Nextcloud `userSession` (an external signer has no Nextcloud account), this capability provides a verified-actor entrypoint so the SAME honest primitive serves both an in-app Nextcloud signer and an external portal signer: the receiver passes the OpenRegister-resolved `signerRecord` (identified above) as the verified actor, the `signerRecord.email` (not a Nextcloud uid) is the identity the act is bound to, and every existing invariant — the signer-belongs-to-request C4 check, the terminal-state status machine, and identity-bound assertion MAC — MUST still hold. `decline` MUST carry the client-supplied `reason`. Each act MUST be written to the signing audit with the acting portal signer (email) and the assertion `jti` recorded so the portal act is traceable to its originating portaliq session. The receiver MUST relay the primitive's outcome as JSON and MUST return `502` on a downstream or OpenRegister failure (never leaking transport or exception internals).

#### Scenario: A verified portal signer signs through the honest primitive and is audited
- **GIVEN** a valid `signer` assertion resolving to email `A` and a `signingRequestId` on which `A` is an invited, still-PENDING signer
- **WHEN** the receiver processes the `sign` action
- **THEN** it calls `SigningService::sign()` acting as the resolved `signerRecord` for `A` (no Nextcloud user session required)
- **AND** the signer-belongs-to-request and terminal-state guards still apply, so a `decline` on a COMPLETED request is rejected unchanged
- **AND** a signing-audit entry is written recording the portal signer email and the assertion `jti`
- **AND** a downstream/OpenRegister failure is relayed as `502` with no internal detail
- @e2e exclude backend receiver -> service contract with no DocuDesk UI surface (the signing UI is portaliq's SPA) — covered by PHPUnit (tests/unit/Controller/PortalSigningReceiverControllerTest.php, tests/unit/Service/SigningServiceTest.php)

### Requirement: Document view returns the target document to the verified signer only (REQ-DDPSA-006)
The `viewDocument` action MUST let the verified invited signer READ the target document before signing, scoped by the IDENTICAL invited-signer guard as `sign`/`decline` (REQ-DDPSA-004). The receiver MUST resolve the document from the target `signingRequest.documentFileId` server-side and return it to the signer within the A6 JSON relay as document metadata (`documentName`, MIME type) plus the document content (base64), so the single JSON hop portaliq forwards can carry it. A signer who is not invited on the target request MUST receive the same uniform not-authorised result as for `sign`/`decline` (no existence oracle), and a missing or unreadable document MUST fail closed (`404`/`502`), never returning another request's document.

#### Scenario: An invited signer reads the document; a non-invited one cannot
- **GIVEN** a valid `signer` assertion resolving to email `A`
- **WHEN** the receiver processes `viewDocument` for a `signingRequestId` on which `A` is invited
- **THEN** it returns the document metadata and base64 content resolved from that request's `documentFileId`
- **AND** when `A` is NOT invited on the target the receiver returns the identical not-authorised result as `sign`/`decline`, and a missing/unreadable document fails closed
- @e2e exclude backend document-read receiver with no DocuDesk UI surface — covered by PHPUnit (tests/unit/Controller/PortalSigningReceiverControllerTest.php)

### Requirement: Assertion is never a portal session and every path fails closed (REQ-DDPSA-007)
The receiver MUST NOT accept the `X-Portal-Subject` assertion (or any token carrying `use: assertion`) as a Nextcloud/portal session bearer — a relayed or leaked assertion can never be replayed to authenticate anything other than the one action forward it was minted for. All receiver failure modes MUST be fail-closed and MUST NOT leak internals: `401` for a missing/invalid assertion or unconfigured secret, `403` for wrong audience / insufficient trust / not-an-invited-signer / malformed target reference, `404`/`403` uniformly where an existence oracle would otherwise leak, and `502` for a downstream or OpenRegister failure. No receiver path may fall open to acting without a verified assertion, and no receiver response may echo raw exception text.

#### Scenario: The receiver never falls open and never leaks internals
- **GIVEN** any receiver call whose assertion is absent/invalid, whose audience/trust is insufficient, whose signer is not invited, or whose downstream fails
- **WHEN** the receiver handles it
- **THEN** it returns `401`/`403`/`404`/`502` per the mode above, performs no signing act on any non-authorised path, and returns no raw exception text
- **AND** an `X-Portal-Subject` assertion presented as an `Authorization: Bearer` to any DocuDesk endpoint is never treated as a valid session
- @e2e exclude fail-closed security invariant on a backend-only receiver — covered by PHPUnit fail-closed matrix (tests/unit/Controller/PortalSigningReceiverControllerTest.php, tests/unit/Portal/PortalAssertionVerifierTest.php)
