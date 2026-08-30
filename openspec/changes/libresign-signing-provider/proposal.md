---
kind: code
---

# Proposal: libresign-signing-provider

## Why

Filinq's only working signature today is its **native SES** — an HMAC marker
over a document hash. Everything above SES (AdES/QES) needs certificate-based
X.509/OpenSSL crypto Filinq does not own and should not build. **LibreSign**
already ships exactly that, in-cluster, open-source:

- LibreSign is *"the single most credible OSS rival to Filinq's signing rail
  … owns the OpenSSL/X.509 crypto Filinq's native SES lacks"* (R4 A, R4 C
  "Signing crypto / verification"). The strategic move is **R4 opportunity #1**:
  absorb it as a provider plugin rather than out-build its certificate stack —
  *"neutralises the strongest OSS rival by absorbing it, and gives real
  cert-based signing without building crypto."*
- **procest already trusts LibreSign for besluit signing** — verified at HEAD:
  `apps-extra/procest/openspec/specs/libresign-besluit-signing/spec.md` (the
  change archived 2026-07-13). The fleet has already committed to LibreSign as
  the certificate-signing engine; Filinq consuming the same engine is
  consistency, not a new bet.
- Filinq's provider registry is **built for this**. Verified at HEAD:
  `SigningProviderFactory` holds `['native' => NativeSigningProvider,
  'validsign' => ValidSignProvider]` and resolves the active provider from the
  `filinq.signing_provider` app-config; `SigningProviderInterface` already
  defines `getIdentifier()`, `supportsLevel()`, `produceSignedArtifact()` and
  the async-flow seam. Adding a `LibreSignProvider` is a first-class extension,
  not a refactor.

But the registry has an **honesty defect this change must not inherit**.
Verified at HEAD: `SigningProviderFactory::getActiveProvider()` falls back to
`native` for any unknown provider name, and (per the active
`signing-trust-rebuild` change, GH #304) the completion path
`produceAndStoreSignedArtifact` silently falls back to `getActiveProvider()`
while `NativeSigningProvider::produceSignedArtifact()` never checks
`supportsLevel()` — so **a QES request can complete with a native SES artifact
whose assertion claims QES**. A LibreSign provider that advertised AES/QES while
the framework could silently substitute native would launder a lower assurance
level into a higher claim. This change therefore ships an **honest capability
contract**: `LibreSignProvider::supportsLevel()` returns only what LibreSign's
configured certificate actually delivers, and `produceSignedArtifact()` refuses
a level it does not support rather than let a fallback fill in — and it declares
`signing-trust-rebuild` (the no-silent-fallback fix) as a dependency.

## What Changes

- **`LibreSignProvider`** implementing `SigningProviderInterface`, registered in
  `SigningProviderFactory` under identifier `libresign`.
- **Availability-guarded registration**: the provider is offered (appears in
  `getAvailableProviders()` / the admin provider picker) **only when the
  LibreSign app is installed and enabled** (`IAppManager::isEnabledForUser` /
  `isInstalled`). When LibreSign is absent the provider is not selectable and,
  if configured-but-absent, fails closed with an explanatory error — never a
  silent downgrade to native.
- **Honest capability mapping**: `supportsLevel()` maps LibreSign's X.509/
  OpenSSL certificate-based capability to the AES-class levels it genuinely
  provides (SES + AdES; QES only when backed by a qualified certificate/QTSP —
  configurable, default off). It never claims a level the configured
  certificate cannot back.
- **Request delegation to LibreSign**: signing requests are delegated to
  LibreSign's API (create signature request, drive signer flow, retrieve the
  signed file, validate). The exact OCS endpoints are pinned as an Open
  Question against LibreSign's documented API (this instance has no LibreSign
  installed — see design.md D3).
- **Artifact + audit round-trip into OR**: the signed file LibreSign produces
  is stored as a new document version via Filinq's existing OR-backed path,
  and every provider action is recorded through `SigningAuditService` (OR
  hash-chained `AuditTrailMapper`) — no Filinq-local audit store.
- **Honest-completion enforcement**: `produceSignedArtifact()` / download throw
  (never return the unsigned original) when LibreSign has not produced a signed
  artifact, matching the `ValidSignProvider` honest-completion gate already at
  HEAD.

## Capabilities

### New Capabilities

- `libresign-signing-provider`: a LibreSign-backed plugin in Filinq's signing
  provider registry — availability-guarded registration, honest X.509/AES-class
  `supportsLevel` with no silent lower-level substitution, delegation to
  LibreSign's signing API, and signed-artifact + hash-chained-audit round-trip
  into OpenRegister.

### Modified Capabilities

<!-- none — SigningProviderInterface and SigningProviderFactory are extended by
     adding a provider, not by changing their contracts. The no-silent-fallback
     enforcement on the shared completion path is delivered by the active
     signing-trust-rebuild change (dependency), referenced not re-specced. -->

## Impact

- **Backend**: new `lib/Service/Signing/LibreSignProvider.php`
  (implements `SigningProviderInterface`); `SigningProviderFactory` constructor
  gains the provider and registers it **only when LibreSign is enabled**;
  admin settings gain a LibreSign section (certificate/QTSP config, default-QES
  off). LibreSign's API is called via its OCS endpoints (openconnector source
  or a thin HTTP client resolved lazily — design.md D3).
- **Register**: no new schema required for MVP — signing sessions reuse the
  existing `signingSession` schema; a `provider: libresign` and
  `externalId` (LibreSign request uuid) are stored on the existing session
  object. If provider-specific metadata proves necessary it is added additively
  with a version bump (design.md Open Questions).
- **Routes**: none new for MVP — the provider plugs into the existing
  `signing#*` routes; a LibreSign completion callback endpoint is an Open
  Question (design.md D3) depending on whether LibreSign pushes or Filinq
  polls.
- **Frontend**: the existing signing provider picker gains `libresign` when
  available; no new page. (The orphaned signer-chain create UI is owned by
  `orphaned-surface-restoration` / `bulk-signing-field-builder`, not this
  change.)
- **Depends on**:
  - `signing-trust-rebuild` (active) — removes the silent provider/level
    fallback on the completion path (GH #304). This change relies on it so a
    QES request can never be quietly served by native; until it lands,
    `LibreSignProvider` still refuses unsupported levels in its own methods.
  - LibreSign app installed + enabled (runtime availability gate).
- **Security**: honest `supportsLevel` (no assurance-level laundering);
  fail-closed when configured-but-absent; audit through OR's immutable chain;
  no signing keys stored by Filinq (LibreSign holds the certificate/key).
- **Evidence**: R4 A/C + opportunity #1 (absorb LibreSign, don't out-build);
  procest `libresign-besluit-signing` spec (fleet already trusts LibreSign);
  MAC/level-honesty defect verified at HEAD (`signing-trust-rebuild` GH #304);
  registry structure verified at HEAD (`SigningProviderFactory`,
  `SigningProviderInterface`).
