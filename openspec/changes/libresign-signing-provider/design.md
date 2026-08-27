# Design: libresign-signing-provider

## Context

Verified at HEAD (Filinq `development`, branch
`spec/market-gap-wave3-2026-07`):

- **The provider registry is a real extension seam.**
  `SigningProviderInterface` defines `getIdentifier()`, `initiateSigning()`,
  `checkStatus()`, `downloadSignedDocument()`, `cancelSigning()`,
  `supportsLevel()`, and `produceSignedArtifact()`.
  `SigningProviderFactory::__construct` wires
  `['native' => NativeSigningProvider, 'validsign' => ValidSignProvider]` and
  `getActiveProvider()` reads `filinq.signing_provider` (default `native`),
  **falling back to `native` on an unknown name**. `getAvailableProviders()`
  returns `array_keys($this->providers)`.
- **`supportsLevel` honesty already varies by provider.**
  `NativeSigningProvider::supportsLevel()` returns `$level === 'SES'` and its
  `produceSignedArtifact()` **does not** re-check the level;
  `ValidSignProvider::supportsLevel()` returns true for SES/AdES/QES but its
  `produceSignedArtifact()` throws (honest-completion gate, unimplemented).
- **The completion path can launder levels.** Per `signing-trust-rebuild`
  (GH #304, verified open): `produceAndStoreSignedArtifact` falls back to
  `getActiveProvider()` on an unknown provider and native never checks
  `supportsLevel()`, so a QES request can complete with an SES artifact
  claiming QES. That fix is owned by `signing-trust-rebuild`; this change
  depends on it and additionally refuses unsupported levels inside
  `LibreSignProvider`.
- **procest already integrates LibreSign** for besluit signing
  (`apps-extra/procest/openspec/specs/libresign-besluit-signing/spec.md`), so
  the fleet's certificate-signing engine is settled.
- **LibreSign is not installed on this instance** — no `libresign` app dir
  under `apps/` or `apps-extra/`. Exact API endpoints are therefore specced
  against LibreSign's documented OCS API and marked as Open Questions (D3).

## Goals / Non-Goals

**Goals:**

- Offer LibreSign's X.509/OpenSSL certificate signing as a first-class provider
  in the existing registry.
- Be **honest about assurance level** — advertise only what the configured
  LibreSign certificate delivers, and refuse to produce a level not supported,
  so no fallback can launder SES into a QES claim.
- Only present the provider when LibreSign is actually available; fail closed
  otherwise.
- Round-trip the signed artifact and audit into OpenRegister via existing
  Filinq paths (ADR-022: consume OR's store + audit, no local stores).

**Non-Goals:**

- No new signing crypto in Filinq — LibreSign owns the certificate/key.
- No change to `SigningProviderInterface`/`SigningProviderFactory` contracts
  beyond adding a provider and the availability gate.
- No fix to the shared completion-path fallback — that is
  `signing-trust-rebuild` (dependency).
- No signer-chain create UI / field placement / bulk send (owned by
  `orphaned-surface-restoration`, `bulk-signing-field-builder`).
- No EUDI-wallet / DigiD identity rails (owned by `signer-identity-rails`).

## Decisions

### D1 — Availability-guarded registration

`SigningProviderFactory` registers `libresign` **only when the LibreSign app is
enabled**, checked via `OCP\App\IAppManager` (`isEnabledForUser()` /
`isInstalled('libresign')`) resolved in the factory. Behaviour:

- LibreSign absent → `libresign` is **not** in `getAvailableProviders()`; the
  admin picker does not offer it; `getProvider('libresign')` throws the existing
  `RuntimeException` ("Signing provider not available").
- LibreSign present but the caller selected another provider → unaffected.
- `filinq.signing_provider = libresign` but LibreSign later removed →
  `getActiveProvider()` MUST fail closed with an explanatory error, **not**
  silently return native. (This tightens the current unknown-name→native
  fallback for the specific configured-but-absent case; the general
  no-silent-fallback rule is `signing-trust-rebuild`'s.)

### D2 — Honest capability mapping (`supportsLevel`)

LibreSign signs with an X.509 certificate via OpenSSL, producing PAdES-class
signatures. The mapping to Filinq's `SES|AdES|QES` levels is
**certificate-dependent**, so it is config-driven, not hardcoded true:

| Filinq level | LibreSign support | Default |
|---|---|---|
| `SES` | always (any certificate) | on |
| `AdES` | when signing with a real X.509 certificate (LibreSign's normal mode) | on |
| `QES` | only when the configured certificate is qualified / backed by a QTSP | **off** (admin opt-in) |

`supportsLevel($level)` reads the admin config
(`filinq.libresign_qualified` boolean, default false) and returns true for
`SES`/`AdES` and for `QES` only when qualified. `produceSignedArtifact()` /
delegation MUST re-check `supportsLevel()` and **throw** on an unsupported
level — never emit a lower-assurance artifact under a higher-level request.
This is the anti-laundering core of the change.

### D3 — Delegation to LibreSign's API (endpoints = Open Question)

LibreSign exposes an OCS/REST API under
`/ocs/v2.php/apps/libresign/api/v1/...`. The delegation flow Filinq needs:

1. **Create a signature request** — register the file + signer(s) with
   LibreSign, receiving a request/file uuid. *Documented endpoint (verify):*
   `POST /api/v1/request-signature` (file + users).
2. **Drive the signer flow** — LibreSign presents its own signing UI/link;
   Filinq stores the returned uuid on the `signingSession`.
3. **Retrieve the signed file** — after signers complete, fetch the signed
   PDF. *Documented endpoint (verify):* the file-download / signed-file
   retrieval by uuid.
4. **Validate** — LibreSign's own validation. *Documented endpoint (verify):*
   `GET /api/v1/file/validate/uuid/{uuid}` (or `.../file_id/{fileId}`).
5. **Completion signal** — whether LibreSign pushes a webhook/notification or
   Filinq polls `checkStatus()` is unresolved without a live instance.

**Open Question (must resolve before apply):** confirm the exact request/sign/
retrieve/validate endpoint paths and payloads against the LibreSign version
targeted (its OCS API docs / an installed instance), and whether completion is
push or poll. Until confirmed, `LibreSignProvider` is coded against an
`interface`-level `LibreSignClient` seam so the concrete endpoints are swapped
in one place. The client is resolved lazily (either an openconnector source or
a thin `IClientService` HTTP client) so Filinq stays loadable without
LibreSign.

### D4 — Artifact + audit round-trip into OR (ADR-022)

- **Artifact**: the signed PDF LibreSign returns is stored as a new document
  version through Filinq's existing OR-backed completion path
  (`produceAndStoreSignedArtifact` → new file version) — the same path native
  uses. `LibreSignProvider::produceSignedArtifact()` returns the LibreSign
  signed bytes (or throws per the honest-completion gate); it does not write
  files itself.
- **Audit**: every provider action (request created, signer completed, signed
  file retrieved, validation result) is recorded via the existing
  `SigningAuditService`, which routes to OR's hash-chained `AuditTrailMapper`
  (`signing-audit-via-or`, status done). No Filinq-local audit store.
- **Session**: reuse the `signingSession` schema; store `provider: libresign`
  and the LibreSign `externalId` (request uuid). No new schema for MVP.

### D5 — Honest-completion gate

`produceSignedArtifact()` and `downloadSignedDocument()` MUST throw a
descriptive exception when LibreSign has not produced a verifiable signed file
(request incomplete, LibreSign unreachable, validation failed) — never return
the unsigned original. This mirrors `ValidSignProvider`'s gate at HEAD and the
interface docblock's honest-completion contract.

## OpenRegister service usage (ADR-001)

| Operation | Service |
|---|---|
| Signed-file storage | existing OR-backed completion path (new document version) |
| Audit | `SigningAuditService` → OR `AuditTrailMapper` (hash-chained) |
| Session persistence | OR ObjectService on `signingSession` (provider + externalId) |
| LibreSign availability | `OCP\App\IAppManager` |
| LibreSign API | lazy `LibreSignClient` seam (openconnector source or `IClientService`) |

## Declarative vs imperative

- **Declarative**: admin config keys (`signing_provider`,
  `libresign_qualified`, LibreSign endpoint/source config); the provider picker
  entry.
- **Imperative (justified)**: availability gate, capability enforcement, API
  delegation, honest-completion throw, audit writes.

## Seed Data

None — no new schema; signing sessions are created by real signing runs. Unit
tests use a fake `LibreSignClient` and a fake `IAppManager`.

## Security Considerations

- **No assurance-level laundering**: `supportsLevel` config-driven; unsupported
  level → throw, never a silent lower-assurance artifact. This is the change's
  central security property and directly addresses the HEAD level-honesty
  defect.
- **Fail closed when configured-but-absent** (D1) — never fall through to
  native for `signing_provider = libresign`.
- **No keys in Filinq** — LibreSign holds the certificate/private key;
  Filinq never stores signing keys.
- **Immutable audit** via OR's hash chain.
- API calls to LibreSign stay in-cluster (local NC app); no third-party cloud.

## Risks / Trade-offs

- [Endpoints unverified without a live LibreSign] → mitigated by the
  `LibreSignClient` seam + an explicit Open Question gating apply; concrete
  paths land in one file.
- [Depends on `signing-trust-rebuild` for the shared no-fallback rule] →
  accepted; `LibreSignProvider` refuses unsupported levels in its own methods
  regardless, so it is honest even before that change lands.
- [QES claim requires a qualified certificate] → default-off opt-in; the
  provider never advertises QES on a non-qualified certificate.
- [Completion push-vs-poll unknown] → seam supports both; poll via
  `checkStatus()` is the safe default until a webhook is confirmed.

## Migration Plan

Additive: one provider class + factory registration behind an availability
gate + admin config. No schema change, no route change for MVP. Rollback =
remove the provider from the factory; existing native/validsign paths
unaffected.

## Open Questions

- **Exact LibreSign OCS endpoints + payloads** for create-request / sign /
  retrieve-signed / validate, and **completion push vs poll** — resolve against
  LibreSign's documented API or an installed instance before apply (D3).
- Whether a LibreSign **completion callback route** is needed (push model) or
  polling suffices — provisional: poll via `checkStatus()`.
- Whether provider-specific session metadata warrants extending
  `signingSession` — provisional: no; reuse `provider` + `externalId`.
- **QES qualification**: which qualified-certificate/QTSP configuration
  LibreSign exposes to mark a signature QES — provisional: admin boolean
  `libresign_qualified`, refined when the QTSP path is confirmed.
