# libresign-signing-provider Specification (delta)

---
status: proposed
---

## Purpose

A LibreSign-backed provider plugin in Filinq's existing signing provider
registry (`SigningProviderInterface` / `SigningProviderFactory`, verified at
HEAD), giving Filinq real X.509/OpenSSL certificate-based (AES-class)
signatures without building crypto — absorbing the strongest OSS signing rival
(R4 opportunity #1) that procest already trusts for besluit signing. The
provider is offered only when the LibreSign app is enabled, maps its capability
to signature levels honestly (never advertising a level the configured
certificate cannot back), refuses to produce an unsupported level rather than
let the framework substitute a lower one (the assurance-level-laundering defect
tracked by `signing-trust-rebuild`, GH #304), and round-trips the signed
artifact and audit into OpenRegister. The shared no-silent-fallback fix on the
completion path is owned by `signing-trust-rebuild` (dependency), not
re-specced here.

## ADDED Requirements

### Requirement: Availability-guarded registration in the provider registry (REQ-DDLSP-001)

The `SigningProviderFactory` MUST register a `libresign` provider only when the
LibreSign app is installed and enabled (checked via `OCP\App\IAppManager`).
When LibreSign is absent the provider MUST NOT appear in
`getAvailableProviders()` and MUST NOT be selectable in admin settings, and
`getProvider('libresign')` MUST throw. When `filinq.signing_provider` is set
to `libresign` but LibreSign is not available, provider resolution MUST fail
closed with an explanatory error and MUST NOT silently fall back to the native
provider.

#### Scenario: Provider offered only when LibreSign is enabled

- GIVEN the LibreSign app is enabled
- WHEN the admin opens the signing provider picker
- THEN `libresign` is offered
- AND WHEN LibreSign is disabled, `libresign` is not offered and `getProvider('libresign')` throws
- @e2e tests/e2e/spec-coverage/libresign-signing-provider.spec.ts

#### Scenario: Configured-but-absent fails closed, never native

- GIVEN `filinq.signing_provider` is `libresign` and the LibreSign app is not installed
- WHEN a signing request resolves the active provider
- THEN resolution fails with an explanatory error
- AND the request is NOT silently served by the native provider
- @e2e exclude provider-resolution fail-closed is factory logic — covered by PHPUnit (tests/unit/Service/Signing/SigningProviderFactoryTest.php)

### Requirement: Honest capability mapping and no assurance-level laundering (REQ-DDLSP-002)

`LibreSignProvider::supportsLevel()` MUST return only the signature levels the
configured LibreSign certificate genuinely provides: SES and AdES for a real
X.509 certificate, and QES only when the certificate is qualified / QTSP-backed
(admin-configured via `filinq.libresign_qualified`, default off).
`produceSignedArtifact()` and any delegation MUST re-check `supportsLevel()` and
MUST throw on a level the provider does not support — it MUST NOT emit a
lower-assurance artifact under a higher-level request. The provider MUST NOT
advertise QES on a non-qualified certificate.

#### Scenario: QES is not advertised without a qualified certificate

- GIVEN `libresign_qualified` is false (default)
- WHEN `supportsLevel('QES')` is checked
- THEN it returns false, and `supportsLevel('SES')` and `supportsLevel('AdES')` return true
- @e2e exclude capability matrix is unit logic — covered by PHPUnit (tests/unit/Service/Signing/LibreSignProviderTest.php)

#### Scenario: An unsupported level is refused, not laundered

- GIVEN `libresign_qualified` is false and a request asks for QES
- WHEN `produceSignedArtifact()` is invoked on the LibreSign provider
- THEN it throws rather than producing an AES/SES artifact that claims QES
- @e2e exclude anti-laundering enforcement is provider crypto logic — covered by PHPUnit (tests/unit/Service/Signing/LibreSignProviderTest.php::testUnsupportedLevelThrows)

### Requirement: Signing delegated to LibreSign's API (REQ-DDLSP-003)

The provider MUST delegate the signing flow to LibreSign's signing API —
create a signature request, drive the signer flow, retrieve the signed file,
and validate — through a `LibreSignClient` seam resolved lazily so Filinq
stays loadable without LibreSign. The provider MUST persist the LibreSign
request identifier as the session `externalId` with `provider: libresign` on
the existing `signingSession` object. The concrete LibreSign OCS endpoints MUST
be confirmed against LibreSign's documented API before implementation is
considered complete (recorded as a design Open Question, this instance having
no LibreSign installed).

#### Scenario: A signing request is delegated to LibreSign

- GIVEN LibreSign is enabled and selected as the provider
- WHEN a signing request is initiated for a document
- THEN a LibreSign signature request is created and its identifier is stored as the session `externalId` with `provider: libresign`
- @e2e exclude requires a live LibreSign instance absent from this environment — covered by PHPUnit with a fake LibreSignClient (tests/unit/Service/Signing/LibreSignProviderTest.php)

#### Scenario: Signer flow status is read from LibreSign

- GIVEN an in-progress LibreSign signature request
- WHEN the provider checks status
- THEN it reports LibreSign's current signer state without mutating step state itself
- @e2e exclude live-instance dependency — covered by PHPUnit with a fake LibreSignClient (tests/unit/Service/Signing/LibreSignProviderTest.php)

### Requirement: Signed-artifact and audit round-trip into OpenRegister with an honest-completion gate (REQ-DDLSP-004)

The signed PDF LibreSign produces MUST be stored as a new document version
through Filinq's existing OpenRegister-backed completion path, and every
provider action MUST be recorded through `SigningAuditService` (OpenRegister
hash-chained `AuditTrailMapper`) — Filinq MUST NOT keep a local audit store.
`produceSignedArtifact()` and `downloadSignedDocument()` MUST throw when
LibreSign has not produced a verifiable signed file (request incomplete,
LibreSign unreachable, or validation failed) and MUST NOT return the unsigned
original.

#### Scenario: Completed LibreSign signature is stored and audited

- GIVEN a LibreSign signature request whose signers have all completed
- WHEN the provider retrieves the signed file
- THEN the signed PDF is stored as a new document version via the OR-backed path
- AND an immutable audit entry is recorded through OR's hash-chained audit trail
- @e2e exclude live-instance dependency — covered by PHPUnit with a fake LibreSignClient and a fake audit service (tests/unit/Service/Signing/LibreSignProviderTest.php)

#### Scenario: Incomplete LibreSign result never yields the unsigned original

- GIVEN a LibreSign request that has not produced a signed file
- WHEN `produceSignedArtifact()` or `downloadSignedDocument()` is called
- THEN it throws, and the unsigned original is never returned as the signed document
- @e2e exclude honest-completion gate is provider logic — covered by PHPUnit (tests/unit/Service/Signing/LibreSignProviderTest.php::testHonestCompletionGate)
