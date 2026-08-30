# Design: signature-verification-portal

## Context

Verified at HEAD (Filinq `development`, branch
`spec/market-gap-wave3-2026-07`):

- **The verify primitive is authenticated + file-scoped.**
  `SigningVerificationService::verifyDocument(int $fileId, string $userId)`
  loads the file via `IRootFolder->getUserFolder($userId)->getById($fileId)`
  and calls `extractSignatures()`. Both a UID and file-read access are
  required, so this cannot serve an anonymous verifier. `extractSignatures()`
  is fail-closed (finding #284): a `/DocuDesk-Signature(...)` blob is
  `valid:false` unless `verifyAssertion()` confirms an HMAC over the
  canonicalised content; external `/Type /Sig` entries are reported
  `valid:false` today.
- **The MAC excludes signer identity (open defect).**
  `NativeSigningProvider::produceSignedArtifact()` computes
  `$mac = hash_hmac('sha256', hash('sha256', $canonical), $secret)` where
  `$canonical` is the document bytes with the marker payload blanked. The
  assertion's `signer`/`timestamp`/`level`/`ip` are **not** in the MAC input.
  `verifyAssertion()` recomputes the identical value. Result: a validly signed
  artifact can have its signer field rewritten and still verify — forgeable
  signer identity (tracked by `signing-trust-rebuild`, GH #284).
- **The verify UI is orphaned.** `src/views/signing/SignatureVerification.vue`
  is imported only by the dead `MainMenu.vue`; it is not in `registry.js` or
  the manifest shell (R1 §4 item 3).
- **The waarmerk change owns a public seal-verification surface.**
  `document-waarmerk-certification` REQ-DDWMK-004 specs `GET /verify/{code}`
  (human page) + `POST /api/waarmerk/verify` (`{code, sha256}`,
  WebCrypto-hashed client-side, fail-closed, rate-limited, non-enumerable
  codes, no oracle). That change owns seal verification; this portal must not
  re-implement it.
- **No group restriction** in `appinfo/info.xml` (version 0.0.37) — a
  precondition for `#[PublicPage]` reachability (the app-group-vs-PublicPage
  gotcha).

## Goals / Non-Goals

**Goals:**

- One public, account-free page that verifies a signed/waarmerked Filinq
  document reached by scanning a QR.
- Present **only guarantees the cryptography actually provides** — separate
  "content integrity verified" from "signer identity verified", and never
  claim identity while the MAC excludes identity fields.
- Reach the anonymous verifier without granting file-read: verify from a
  stored record keyed by a non-enumerable token, not from a live file handle.
- Surface waarmerk seal status by consuming the waarmerk primitive, not
  rebuilding it.

**Non-Goals:**

- No change to the signing crypto or the MAC — the identity-bound assertion is
  `signing-trust-rebuild`'s job (dependency).
- No re-implementation of waarmerk seal verification (consumed).
- No re-registration of the internal operator verify page
  (`orphaned-surface-restoration` owns it).
- No full PAdES/CMS chain validation of external signatures (they render
  `unverifiable`, per the tri-state model `signing-trust-rebuild` introduces).
- No document bytes served publicly; no file download; no upload of the
  document to the server.

## Decisions

### D1 — A verification record + non-enumerable token, not a live file handle

Anonymous verification cannot use `verifyDocument(fileId, userId)`. Instead
signing completion (and waarmerk sealing) mints a `signatureVerification`
object in the `document` register carrying **the outcome, not the document**:

| Field | Meaning |
|---|---|
| `token` | high-entropy (≥ 128-bit, `bin2hex(random_bytes(16))`) non-enumerable public handle |
| `fileRef` | OR reference to the source object (for authenticated re-checks; never exposed publicly as a path) |
| `contentHash` | sha256 of the canonical signed bytes at completion time |
| `signatures[]` | captured summary per signature: `level`, `method`, `signerAsserted`, `integrityVerified` (bool), `identityBound` (bool) |
| `waarmerkRef` | optional pointer to a `waarmerk` record (seal status shown via the waarmerk primitive) |
| `createdAt`, `revoked` | lifecycle |

The public endpoint resolves the record by `token` and renders it. The raw
signing secret is never stored in the record; the record stores the *result*
of verification computed server-side at mint time and re-confirmable on demand.
`SignatureVerificationLinkService` owns mint/lookup; `SigningVerificationService`
gains a record-based verify path (`verifyByRecord(array $record): array`) that
does not require a `userId`.

### D2 — Honest trust model (the MAC defect governs what the page may claim)

The page distinguishes two guarantees and labels them separately:

- **Content integrity** — `integrityVerified: true` iff the stored
  `verifyAssertion()` MAC matched at mint time (and re-matches on demand). This
  is what the current MAC actually proves.
- **Signer identity** — shown as **asserted** with an explicit
  `identityBound: false` badge while the MAC excludes identity fields. The page
  copy reads e.g. *"Document content verified unchanged. Signer name as
  asserted by the signer (not yet cryptographically bound to the signature)."*
  The page MUST NOT render an unqualified "Verified: signed by X".

Per-signature status is tri-state, matching the tri-state
`signing-trust-rebuild` introduces:

- `verified` — content integrity cryptographically confirmed.
- `unverifiable` — an external `/Type /Sig` CMS signature Filinq cannot yet
  validate (not tampering; honestly reported as such, not `invalid`).
- `invalid` — a Filinq marker whose MAC does not match (tamper).

**Dependency wiring:** once `signing-trust-rebuild` ships the identity-bound
MAC, the mint step sets `identityBound: true` and the page promotes the signer
field to a bound verdict — *no portal code change beyond reading the flag*.
Until then the flag is false and the page is honest by construction.

### D3 — Public endpoints: fail-closed, no oracle, rate-limited

- `GET /verify/{token}` — `#[PublicPage] #[NoCSRFRequired]`, renders the portal
  page (SPA entry; the page then calls the JSON endpoint).
- `GET /api/verify/{token}` — `#[PublicPage] #[NoCSRFRequired]`, returns the
  verdict JSON.
- Unknown/malformed tokens return a verdict of shape identical to a known
  token with a `status: unknown` body — **never a 404 that distinguishes
  existence** (no enumeration oracle), mirroring the waarmerk change's
  non-oracle rule.
- Both public routes carry NC brute-force protection
  (`OCP\IRequest` throttling / `BruteForceProtection` annotation on the
  controller) keyed by token+IP.
- The page serves no document bytes and offers no download; it shows file
  *name* only (already public-safe metadata on a shared/published doc), never
  path or content.

### D4 — QR stamp linking to the portal

Signed and waarmerked PDFs get a visible QR encoding the absolute
`verify/{token}` URL (built from the instance base URL). QR rendering reuses
the dependency-free approach the waarmerk change adopted (design.md D5 there —
no new heavy dependency). Placement: a footer stamp on the completion artifact,
composed by the same writer that assembles the signed bytes, so the QR travels
with the file (paper or download). When both a signature and a waarmerk exist
on one file, a single QR points at the unified portal token (D5).

### D5 — Route unification with the waarmerk public surface (coordination)

Two public `/verify/*` surfaces would confuse verifiers and risk a literal
route collision (`verify/{code}` vs `verify/{token}`). Decision: **this portal
is the intended single public verification home.** The portal token resolves a
`signatureVerification` record which, when the document also carries a seal,
links a `waarmerkRef` and the page renders seal status by calling the waarmerk
verification primitive (`POST /api/waarmerk/verify` / `WaarmerkService`).
The waarmerk change's `GET /verify/{code}` remains valid for seal-only
documents; converging both under one `verify/{token}` surface is a
**coordination follow-up** recorded here — the waarmerk change's files are not
assigned to this change, so no edit is made to them. Namespacing note: to avoid
a router clash before convergence, this portal registers `verify/{token}` and
the JSON `api/verify/{token}`; the token namespace is disjoint from waarmerk
codes by construction (different length/charset), and the resolver treats an
unrecognised token as `unknown` (D3), so the surfaces coexist safely.

## OpenRegister service usage (ADR-001)

| Operation | Service |
|---|---|
| Mint / look up verification record | OR ObjectService `saveObject()` / `findAll()` on `signatureVerification` (`document` register) |
| Seal status | consume `document-waarmerk-certification` verification primitive (presence-gated) |
| Signature verdict | `SigningVerificationService` record-based path (D1) |
| Audit summary | OR `AuditTrailMapper` read via existing `SigningAuditService` (bounded, object-scoped) |

No custom tables; ADR-011: sha256/HMAC via PHP `hash()`/`hash_hmac()`, no
re-implemented crypto.

## Declarative vs imperative

- **Declarative**: the `signatureVerification` schema + register-i18n tags; the
  manifest portal page.
- **Imperative (justified)**: token mint on signing/seal completion (must be
  atomic with the artifact), the public fail-closed resolver + rate-limit, QR
  composition into the PDF bytes.

## Seed Data

One nil-token demo verification record so the (dev-only) authenticated preview
renders non-empty; the public portal for the nil token returns `unknown` by
construction (nil token is not a valid public handle):

```json
{
  "@self": {"register": "document", "schema": "signatureVerification", "slug": "seed-signature-verification-001"},
  "token": "00000000000000000000000000000000",
  "contentHash": "0000000000000000000000000000000000000000000000000000000000000000",
  "signatures": [{"level": "SES", "method": "native", "signerAsserted": "demo-signer", "integrityVerified": false, "identityBound": false}],
  "revoked": false,
  "createdAt": "2026-07-01T09:00:00+00:00"
}
```

## Security Considerations

- `#[PublicPage]` reachable only because the app is not group-restricted; the
  change asserts this precondition and must not add a restriction.
- Tokens high-entropy, non-enumerable; unknown tokens are non-oracle.
- Public endpoints rate-limited (brute-force protection) keyed by token+IP.
- Page serves no document bytes, no download, no path.
- **Honest trust boundary**: identity claims are never asserted while the MAC
  excludes identity fields (D2); the portal cannot be used to lend a forgeable
  claim the appearance of proof — the single most important security property
  of this change.
- The stored record holds outcomes, never the signing secret and never
  document bytes.

## Risks / Trade-offs

- [Depending on `signing-trust-rebuild` for real identity binding] → accepted:
  the portal is honest with or without it (identity shown as asserted-not-bound
  until the flag flips); shipping the portal first is safe and adds pressure to
  land the fix.
- [Two public verify surfaces until convergence] → mitigated by disjoint token
  namespaces + non-oracle unknown handling (D5); convergence recorded as
  coordination.
- [A public page invites brute force / scraping] → rate-limited, non-
  enumerable, no oracle, no content served.
- [QR adds a footer to the artifact] → cosmetic, opt-outable per signing
  request; does not alter the signed content hash (stamp is part of the
  produced bytes whose hash is the recorded `contentHash`).

## Migration Plan

Additive: one schema + seed + version bump; new controller/service/routes/
portal page; a mint hook on signing/seal completion. No existing schema
changes. Rollback = remove the public routes + page; records remain readable.

## Open Questions

- **Route convergence** of `verify/{code}` (waarmerk) and `verify/{token}`
  (portal) into one public surface — coordination with
  `document-waarmerk-certification`; provisional decision: portal is the home,
  waarmerk seal status is consumed (D5).
- Whether the audit summary shown publicly should be the full chain or a
  redacted "N steps, all approved, on <date>" rollup — provisional: redacted
  rollup only (no signer emails/UIDs publicly), pending privacy review.
- Whether to offer an optional WebCrypto client-side re-hash of a locally
  selected file (as the waarmerk page does) to prove the held copy matches the
  recorded `contentHash` — provisional: yes, reusing the waarmerk page's
  no-upload pattern (kept as a should within REQ-DDSVP-001).
