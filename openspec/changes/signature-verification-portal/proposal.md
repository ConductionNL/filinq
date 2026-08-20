---
kind: code
---

# Proposal: signature-verification-portal

## Why

A signature is only worth as much as a third party's ability to *check* it.
DocuDesk can produce signed and waarmerked PDFs, but a citizen, a journalist
or a counterparty who holds one has **no way to verify it** without a
Nextcloud account and file-read access — the exact eIDAS "trust loop" the
market is now closing:

- **LibreSign#2617** ("present validation info with QR code on a signature
  information page", research-user-wishes.md B) is open demand for precisely
  this surface: scan a QR on the document, land on a public page that states
  what the signature guarantees.
- **Collabora Online + eID Easy** now ship in-editor eIDAS QES (R2 A6/B,
  announced 2025-06-26) and every serious signer (ValidSign "evidence
  summary", Zynyo "Deed of Signing") pairs signing with a *public
  verification* artifact. A signer without a verifier is not competitive.
- The **European Accessibility Act deadline (2025-06-28)** and the eIDAS
  trust framework (R2 C4/C7) make a stored, checkable verdict — not "we hope
  it's valid" — a procurement expectation.

The pieces already exist but are **orphaned or inward-facing**, verified at
HEAD:

- `SigningVerificationService::verifyDocument(int $fileId, string $userId)`
  extracts `/DocuDesk-Signature(...)` markers and returns a per-signature
  `valid` verdict — but it requires an authenticated `userId` *and* file-read
  access (`IRootFolder->getUserFolder($userId)->getById($fileId)`), so it can
  never serve an anonymous verifier.
- The bespoke verify UI (`src/views/signing/SignatureVerification.vue`) is
  **orphaned** — imported only by the dead `MainMenu.vue`, absent from
  `registry.js`/the manifest shell (R1 §4 item 3). The verify capability is
  built and unreachable.
- `signing#verify/{fileId}` is `@NoAdminRequired` (account-gated), not a
  `#[PublicPage]`.

Critically, the trust the page may claim is **bounded by a known open
defect**. Verified at HEAD in `NativeSigningProvider::produceSignedArtifact()`
and `SigningVerificationService::verifyAssertion()`: the HMAC covers only the
canonicalised **content hash** (`hash_hmac('sha256', $contentHash, $secret)`);
the assertion fields — `signer`, `timestamp`, `level`, `ip` — are **excluded
from the MAC input**. Anyone holding a validly signed artifact can rewrite the
signer identity or claimed level and the MAC still validates (tracked by the
active `signing-trust-rebuild` change, GH #284). A public page that printed
"signed by X" from that blob would be **publishing a forgeable claim as fact**.
This portal therefore treats *content integrity* and *signer identity* as
distinct guarantees and refuses to assert identity the cryptography does not
yet cover — it depends on `signing-trust-rebuild` to make identity-bound
assertions real, and until then presents identity as *asserted, not
cryptographically bound*.

## What Changes

- **Public verification portal** (`#[PublicPage]`, no NC account): a route
  `verify/{token}` rendering one signed/waarmerked document's verification
  outcome — content-integrity verdict, signature level, signer assertions
  (honestly qualified, see below), an audit summary, and waarmerk seal status.
- **Verification record + token**: signing completion mints a `document`-
  register `signatureVerification` object keyed by a high-entropy,
  non-enumerable `token`, carrying the captured signature summary (never the
  document bytes, never the raw signing secret) so an anonymous verifier gets
  a verdict without file-read access. Non-matching tokens return a verdict
  shape identical to any other, so the endpoint is not an existence oracle.
- **Honest trust model**: per-signature status is presented as `verified`
  (content integrity cryptographically confirmed) vs `unverifiable` (external
  `/Type /Sig` we cannot yet validate) vs `invalid` (tamper detected); signer
  identity is shown as **asserted** and flagged *not cryptographically bound*
  until `signing-trust-rebuild` lands, after which the same field reflects the
  identity-bound MAC. The page never renders "signer identity verified" while
  the MAC excludes those fields.
- **QR stamp**: signed and waarmerked PDFs carry a visible QR encoding the
  portal `verify/{token}` URL, so a paper or downloaded copy is checkable.
- **Manifest re-registration**: the (public) portal page is registered in the
  V2 manifest shell; the orphaned *internal* `SignatureVerification.vue`
  re-registration for logged-in operators is owned by the parallel
  `orphaned-surface-restoration` change — this change references it and does
  not double-spec the internal page.

## Capabilities

### New Capabilities

- `signature-verification-portal`: a public, account-free QR-linked page that
  verifies a signed/waarmerked DocuDesk document, presenting content-integrity
  and signer-identity as distinct (honestly-bounded) guarantees plus waarmerk
  seal status, backed by a non-enumerable verification token and rate-limited
  fail-closed endpoints.

### Modified Capabilities

<!-- none — SigningVerificationService's verify logic and the waarmerk
     verification primitive are consumed, not modified. The honest
     identity-bound MAC is delivered by the active signing-trust-rebuild
     change (dependency, not re-specced here). -->

## Impact

- **Backend**: new `PublicVerificationController` (`#[PublicPage]`,
  `#[NoCSRFRequired]` on the GET page + verify API, rate-limited) resolving a
  `signatureVerification` record by token and calling
  `SigningVerificationService` (extended with a token/record-based path that
  does not require a `userId`/file-read); new `SignatureVerificationLinkService`
  minting/looking up tokens; QR generation reusing the waarmerk change's
  dependency-free QR approach (no new heavy dep).
- **Register**: new `signatureVerification` schema in the `document` register
  of `lib/Settings/docudesk_register.json` (token, fileRef, contentHash,
  captured signature summary, level, waarmerkRef?, createdAt, revoked) with
  register-i18n tags and a register **version bump**; one nil-token seed.
- **Signing path**: signing completion (`SigningService`) and the waarmerk
  seal path mint the verification record + stamp the QR — additive hook, no
  change to the OR ApprovalChain contract.
- **Frontend**: public portal page registered in `src/manifest.json` +
  `src/registry.js` (manifest V2 shell, NOT the dead `src/router/index.js`);
  ADR-012 Cn components, NL Design tokens.
- **Routes**: `verify/{token}` (page) + `api/verify/{token}` (JSON) as
  `#[PublicPage]` in `appinfo/routes.php`.
- **Depends on**:
  - `signing-trust-rebuild` (active) — identity-bound assertion MAC; until it
    lands the portal shows identity as asserted-not-bound (declared, not
    re-specced).
  - `document-waarmerk-certification` (active) — owns the `waarmerk` record and
    its own `GET /verify/{code}` seal-verification surface; this portal
    **consumes** the waarmerk verification primitive to show seal status and
    is the intended single public `/verify` home. Route unification of the two
    public surfaces is a coordination point recorded in design.md, not an edit
    to the waarmerk change's files.
  - `orphaned-surface-restoration` (parallel) — owns re-registering the
    internal, logged-in `SignatureVerification.vue` operator page; this change
    owns only the public portal + QR.
- **Security**: `#[PublicPage]` + NC rate-limiting; app MUST NOT be group-
  restricted in `info.xml` (the app-group-vs-PublicPage gotcha: a group
  restriction makes public pages unreachable to anonymous visitors); tokens
  high-entropy/non-enumerable; no existence oracle; page serves no document
  bytes and offers no download.
- **Evidence**: LibreSign#2617 QR verification (R3 B); eIDAS trust loop,
  Collabora+eID Easy encroachment, EAA live (R2 A6/C4/C7); MAC-excludes-
  identity defect verified at HEAD (`signing-trust-rebuild` GH #284); orphaned
  verify UI (R1 §4 item 3).
