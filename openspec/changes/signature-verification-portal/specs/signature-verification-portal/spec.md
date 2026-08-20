# signature-verification-portal Specification (delta)

---
status: proposed
---

## Purpose

A public, account-free verification page — reached by scanning a QR stamped on
a signed or waarmerked DocuDesk PDF — that states what a document's signature
actually guarantees. Because the native signing MAC covers only the content
hash and **excludes the signer-identity assertion fields** (verified at HEAD in
`NativeSigningProvider::produceSignedArtifact()`; the forgeable-signer defect
tracked by the active `signing-trust-rebuild` change), the portal presents
*content integrity* and *signer identity* as distinct guarantees and never
asserts an identity the cryptography does not cover. The page is backed by a
non-enumerable verification token (so an anonymous verifier needs no file-read
access), consumes the `document-waarmerk-certification` primitive to show seal
status, and is served fail-closed and rate-limited. This change owns the public
portal + QR only; the internal operator verify page is owned by the parallel
`orphaned-surface-restoration` change, and the identity-bound MAC by
`signing-trust-rebuild`.

## ADDED Requirements

### Requirement: Public verification portal page (REQ-DDSVP-001)

The app MUST provide a `#[PublicPage]` route `verify/{token}` that renders,
with no Nextcloud account and no file-read access, the verification outcome of
one signed or waarmerked document: an overall verdict, per-signature status,
signature level, the signer as asserted, an audit summary, and — when the
document carries a seal — its waarmerk status. The page MUST serve no document
bytes, MUST offer no download, and MUST expose the file name only (never a path
or content). The page MAY offer an optional client-side WebCrypto re-hash of a
locally selected copy so a verifier can confirm their file matches the recorded
`contentHash` without uploading it. When the token is unknown the page MUST
render a neutral "unknown" outcome, never a 404 or any response that reveals
whether a token exists.

#### Scenario: Anonymous visitor verifies a signed document via its QR

- GIVEN a signed DocuDesk PDF whose QR encodes `verify/{token}`
- AND a visitor with no Nextcloud session
- WHEN they open `verify/{token}`
- THEN the page renders the verification outcome including signature level and an audit summary
- AND no document bytes are served and no download is offered
- @e2e tests/e2e/spec-coverage/signature-verification-portal.spec.ts

#### Scenario: Unknown token is not an existence oracle

- GIVEN a syntactically plausible token that matches no record
- WHEN `verify/{token}` (or `GET /api/verify/{token}`) is requested
- THEN the response is a neutral `unknown` outcome with a shape identical to a valid verdict, not a 404
- @e2e exclude oracle/shape-parity is asserted at the API layer — covered by PHPUnit (tests/unit/Controller/PublicVerificationControllerTest.php)

### Requirement: Content integrity and signer identity are presented as distinct guarantees (REQ-DDSVP-002)

The portal MUST distinguish "content integrity verified" from "signer identity
verified" and MUST NOT assert signer identity that the signature MAC does not
cover. While the native signing MAC excludes the assertion fields (`signer`,
`timestamp`, `level`, `ip`) — the state at HEAD until the active
`signing-trust-rebuild` change lands — each signature's `identityBound` flag
MUST be false and the page MUST show the signer as *asserted, not
cryptographically bound*, never as an unqualified "signed by X". Per-signature
status MUST be tri-state: `verified` (content integrity cryptographically
confirmed), `unverifiable` (an external `/Type /Sig` signature DocuDesk cannot
yet validate — reported as such, never as tamper), or `invalid` (a DocuDesk
marker whose MAC does not match). When `signing-trust-rebuild` ships the
identity-bound MAC and the mint step records `identityBound: true`, the same
signer field MUST reflect the bound verdict without further portal changes.

#### Scenario: Signer identity shown as asserted while the MAC excludes it

- GIVEN a natively signed document whose verification record has `identityBound: false`
- WHEN the portal renders it
- THEN content integrity is shown as verified AND the signer name is labelled "as asserted (not cryptographically bound)"
- AND the page never renders an unqualified "signer identity verified"
- @e2e tests/e2e/spec-coverage/signature-verification-portal.spec.ts

#### Scenario: A rewritten signer field is not presented as verified identity

- GIVEN a validly signed artifact whose assertion `signer` field was rewritten while the content-hash MAC still matches
- WHEN the portal verifies it
- THEN content integrity may report verified but the signer identity is never presented as cryptographically verified
- @e2e exclude tamper-mutation of the assertion blob is backend crypto logic — covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php::testRewrittenSignerNotVerifiedIdentity)

#### Scenario: External CMS signature reports unverifiable, not invalid

- GIVEN a document carrying a genuine `/Type /Sig` CMS signature with no DocuDesk marker
- WHEN the portal verifies it
- THEN the signature status is `unverifiable`, not `invalid`
- @e2e exclude external-signature classification is backend logic — covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php)

### Requirement: Non-enumerable verification token backs anonymous verification (REQ-DDSVP-003)

Signing completion and waarmerk sealing MUST mint a `signatureVerification`
object (`document` register) keyed by a high-entropy (≥ 128-bit),
non-enumerable `token`, carrying the captured signature summary, `contentHash`,
optional `waarmerkRef`, and lifecycle flags — and MUST NOT store the signing
secret or the document bytes in that record. The public verify endpoints MUST
resolve outcomes from this record so that no file-read access is required of
the verifier. The `signatureVerification` schema MUST be added to the register
with a register version bump and register-i18n tags on user-facing string
fields.

#### Scenario: Signing completion mints a verification record

- GIVEN a signing request that completes and produces a signed artifact
- WHEN completion is processed
- THEN a `signatureVerification` object exists with a non-enumerable token, the artifact `contentHash`, and the captured signature summary
- AND the record contains neither the signing secret nor the document bytes
- @e2e tests/e2e/spec-coverage/signature-verification-portal.spec.ts

#### Scenario: Tokens are non-enumerable

- GIVEN two verification records minted in sequence
- WHEN their tokens are compared
- THEN neither is derivable from the other and both are ≥ 128-bit random
- @e2e exclude entropy/non-derivability is a unit property — covered by PHPUnit (tests/unit/Service/SignatureVerificationLinkServiceTest.php)

### Requirement: Public endpoints are fail-closed and rate-limited (REQ-DDSVP-004)

The public routes `verify/{token}` and `GET /api/verify/{token}` MUST be
`#[PublicPage]`, MUST carry Nextcloud brute-force/rate-limit protection keyed by
token and client IP, and MUST fail closed — any unverifiable state (unknown
token, malformed token, verification failure) MUST NOT report a valid outcome.
The application MUST NOT be group-restricted in `appinfo/info.xml`, because a
group restriction makes public pages unreachable to anonymous visitors (the
app-group-vs-PublicPage constraint). No public endpoint may act as an existence
oracle.

#### Scenario: Repeated verification attempts are throttled

- GIVEN many verification requests for varying tokens from one client
- WHEN the rate threshold is exceeded
- THEN further requests are throttled by Nextcloud brute-force protection
- @e2e exclude throttling is not reliably browser-testable — covered by PHPUnit (tests/unit/Controller/PublicVerificationControllerTest.php)

#### Scenario: Public page is reachable logged out and the app is not group-restricted

- GIVEN a logged-out browser
- WHEN `verify/{token}` is requested
- THEN the page loads (not redirected to login) AND `appinfo/info.xml` declares no group restriction
- @e2e tests/e2e/spec-coverage/signature-verification-portal.spec.ts

### Requirement: Signed and waarmerked PDFs carry a QR to the portal (REQ-DDSVP-005)

Signed and waarmerked PDFs MUST carry a visible QR code encoding the absolute
`verify/{token}` URL, composed into the produced PDF bytes so a printed or
downloaded copy remains verifiable. When a document carries both a signature
and a waarmerk, a single QR/token MUST resolve to the unified portal outcome
(signature + seal status). QR rendering MUST NOT introduce a new heavy
dependency (reusing the dependency-free approach of the
`document-waarmerk-certification` change). The internal, logged-in operator
verify page is out of scope here and is owned by the parallel
`orphaned-surface-restoration` change.

#### Scenario: A downloaded signed PDF is verifiable from its stamped QR

- GIVEN a completed signed PDF downloaded to a device
- WHEN its stamped QR is scanned
- THEN it opens the portal `verify/{token}` for that document
- @e2e tests/e2e/spec-coverage/signature-verification-portal.spec.ts

#### Scenario: One QR resolves both signature and seal status

- GIVEN a document that is both signed and waarmerked
- WHEN its QR is opened
- THEN the portal shows the signature verdict and the waarmerk seal status on one page
- @e2e exclude requires a live waarmerk seal fixture — covered by PHPUnit presence-gated integration (tests/unit/Service/SignatureVerificationLinkServiceTest.php)
