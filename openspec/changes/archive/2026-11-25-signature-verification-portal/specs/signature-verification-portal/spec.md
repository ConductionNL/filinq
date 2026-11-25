# signature-verification-portal Specification (delta)

## Purpose

A public, account-free verification page — reached by scanning a QR stamped on
a signed or waarmerked Filinq PDF — that states what a document's signature
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
with no account and no file-read access, the verification outcome of one
signed or waarmerked document. The page MUST serve no document bytes, offer
no download and expose the file name only. An unknown token MUST render a
neutral "unknown" outcome, never a 404 or anything that reveals whether a
token exists. What the page shows is set out in the scenarios below.

#### Scenario: Anonymous visitor verifies a signed document via its QR

- GIVEN a signed Filinq PDF whose QR encodes `verify/{token}`
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

#### Scenario: What the page shows
- GIVEN a token that names a record
- WHEN the page renders
- THEN it shows an overall verdict, per-signature status, signature level, the signer as asserted, an audit summary and, when the document carries a seal, its waarmerk status
- AND it MAY offer a client-side WebCrypto re-hash of a locally selected copy against the recorded `contentHash`, without uploading the file
- @e2e exclude page content detail; covered by vitest (src/views/verify/PublicSignatureVerification.spec.js)

### Requirement: Content integrity and signer identity are presented as distinct guarantees (REQ-DDSVP-002)

The portal MUST distinguish "content integrity verified" from "signer
identity verified" and MUST NOT assert a signer identity the signature MAC
does not cover: an unbound signer is shown as *asserted, not
cryptographically bound*, never as an unqualified "signed by X".
Per-signature status MUST be tri-state (`verified`, `unverifiable`,
`invalid`), as the scenarios below define.

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

- GIVEN a document carrying a genuine `/Type /Sig` CMS signature with no Filinq marker
- WHEN the portal verifies it
- THEN the signature status is `unverifiable`, not `invalid`
- @e2e exclude external-signature classification is backend logic — covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php)

#### Scenario: The three statuses
- GIVEN a signature on a verified document
- WHEN its status is reported
- THEN `verified` means content integrity is cryptographically confirmed, `unverifiable` means an external `/Type /Sig` signature Filinq cannot validate yet (never reported as tamper), and `invalid` means a Filinq marker whose MAC does not match
- @e2e exclude status semantics; covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php::testComputeVerdictDistinguishesStates, ::testExternalSignatureIsUnverifiableNotInvalid)

#### Scenario: Identity follows what the MAC covers
- GIVEN a signature whose MAC excludes the assertion fields (`signer`, `timestamp`, `level`, `ip`)
- WHEN it is summarised
- THEN its `identityBound` flag is false and the page shows the signer as asserted
- AND once the identity-bound MAC of `signing-trust-rebuild` records `identityBound: true`, the same field shows the bound verdict without portal changes
- @e2e exclude binding rule; covered by PHPUnit (tests/unit/Service/SigningVerificationServiceTest.php::testAGenuineSignatureIsSummarisedAsBound, ::testRewrittenSignerNotVerifiedIdentity)

### Requirement: Non-enumerable verification token backs anonymous verification (REQ-DDSVP-003)

Signing completion and waarmerk sealing MUST mint a `signatureVerification`
record keyed by a high-entropy (≥ 128-bit), non-enumerable `token`, and MUST
NOT store the signing secret or the document bytes in it. The public verify
endpoints MUST resolve outcomes from this record, so the verifier needs no
file-read access. The record's contents and schema are set out in the
scenarios below.

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

#### Scenario: What the record holds and where it lives
- GIVEN a minted record
- WHEN it is read
- THEN it carries the captured signature summary, `contentHash`, an optional `waarmerkRef` and lifecycle flags
- AND the `signatureVerification` schema is in the register, added with a register version bump and register-i18n tags on its user-facing string fields
- @e2e exclude record shape; covered by PHPUnit (tests/unit/Service/SignatureVerificationLinkServiceTest.php::testMintStoresTheOutcomeNotTheDocument, ::testMintKeepsASealReference)

### Requirement: Public endpoints are fail-closed and rate-limited (REQ-DDSVP-004)

The public routes `verify/{token}` and `GET /api/verify/{token}` MUST be
`#[PublicPage]`, carry brute-force/rate-limit protection keyed by token and
client IP, and fail closed: an unknown or malformed token or a failed
verification MUST NOT report a valid outcome. The app MUST NOT be
group-restricted in `appinfo/info.xml`, which would make public pages
unreachable. No public endpoint may act as an existence oracle.

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

#### Scenario: The token lookup reaches the record for a visitor without an organisation
- GIVEN a record minted in an organisation and a visitor with no account
- WHEN the visitor opens the token's page or endpoint
- THEN the one exact-token lookup runs as the system through `SystemContextSeam` with a logged reason, so OpenRegister's organisation filter does not hide the record
- @e2e exclude lookup path; covered by PHPUnit (tests/unit/Service/SignatureVerificationLinkServiceTest.php::testTheTokenLookupRunsThroughTheSeam)

### Requirement: Signed and waarmerked PDFs carry a QR to the portal (REQ-DDSVP-005)

Signed and waarmerked PDFs MUST carry a visible QR code encoding the absolute
`verify/{token}` URL, composed into the PDF bytes so a printed or downloaded
copy stays verifiable. A document with both a signature and a waarmerk MUST
resolve through one QR/token to the unified outcome. QR rendering MUST NOT
add a heavy dependency (reuse the waarmerk change's dependency-free
approach). The logged-in operator verify page is out of scope.

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

#### Scenario: The QR address opens the signature page, not the waarmerk page
- GIVEN the waarmerk page also lives under `verify/...`
- WHEN the `verify/{token}` address of a signature token is opened
- THEN the signature page answers, and a waarmerk code under the same prefix still opens the waarmerk page
- @e2e exclude routing; covered by PHPUnit (tests/unit/AppInfo/VerifyPageRoutingTest.php)

