---
kind: code
---

# Proposal: signing-trust-rebuild

## Why

Filinq's signing stack carries an open security wave (GH #282–#304, all six
issues verified OPEN on 2026-07-17) that blocks any honest go-to-market for
signing: the intelligence insight "Filinq signing stack currently fails
honest-function: security wave must land before signing can be sold" and the
market-gap feature `mg2026-signing-trust-rebuild` (evidence: GH #282–289/#304,
ValidSign/Zynyo feature parity) both mark this as the must-have precondition
for every other signing investment (signer-identity-rails and
bulk-signing-field-builder both declare `depends_on` on this change).

**Verified HEAD status per issue** (branch `spec/market-gap-wave2-2026-07`,
based on development @ 9cc14407) — substantial parts of the wave are already
fixed in code; this change specs ONLY what is still broken or missing:

| Issue | HEAD status | Still broken / missing (spec'd here) |
|---|---|---|
| #282 sign-as-anyone | **Largely fixed**: `SigningService::sign()`/`decline()` assert `signer.userId === auth uid` (lines 397–402, 470–474) and bind the signer record to the request (C4 check) | `decline()` skips the status machine entirely — a COMPLETED/CANCELLED/EXPIRED request can still be flipped to DECLINED (no `isValidTransition` call, `SigningService.php:441–512`) |
| #284 verification-always-valid | **Partially fixed**: fail-closed HMAC verification shipped (`SigningVerificationService::verifyAssertion`) | The MAC covers ONLY the canonicalised content hash — the assertion fields (`signer`, `timestamp`, `level`, `ip`) are blanked out of the MAC input, so anyone holding a validly signed artifact can rewrite the signer identity and still verify `valid: true`. Genuinely signed external PDFs (`/Type /Sig` without a Filinq marker) are all reported `valid: false`, indistinguishable from tampering |
| #287 in-memory sessions | **Fixed**: sessions persist as OR `signingSession` objects | `downloadSignedDocument()` still falls back to the ORIGINAL unsigned `documentPath` when `signedDocumentPath` is empty (`NativeSigningProvider.php:196–219`, docblock marks it a follow-up) |
| #289 audit immutability | **Largely fixed**: `SigningAuditService` routes through OR's hash-chained `AuditTrailMapper` (per `signing-audit-via-or`, status done) | Entries are created from a uuid-only `ObjectEntity` stub (no register/schema/object-id binding); `getAuditTrail()` fetches ALL `filinq.signing.*` entries fleet-wide and filters in PHP (unbounded); no test proves the OR API actually rejects update/delete of these entries |
| #283 consent forgeable cross-tenant | **Partially fixed**: per-object ownership guards, mutable-field whitelist, server-controlled-field gate, CSRF annotation removed from mutations | Tenant/organisation binding is NOT owned here — the sibling wave-2 change `multi-tenant-hardening` (its proposal explicitly claims GH #283 org-scoping incl. consents and signing requests) delivers it. Still uncovered anywhere: `ConsentController::errorResponse()` echoes raw exception text into the 500 body (`ConsentController.php:117–126`) — the exact existence-probing oracle already fixed on `SigningController` (filinq#100 / Wilco #6) |
| #304 pipeline non-functional | **Largely fixed**: completion is wired (`updateRequestStatus` → `produceAndStoreSignedArtifact` → provider `produceSignedArtifact` → new file version); native + ValidSign both carry honest-completion gates | Provider/level honesty is NOT enforced: `produceAndStoreSignedArtifact` silently falls back to `getActiveProvider()` on an unknown provider (and `getActiveProvider()` itself falls back to native), and `NativeSigningProvider::produceSignedArtifact()` never checks `supportsLevel()` — a QES request routed to native completes with an SES-mechanism artifact whose assertion *claims* QES, violating the existing "SES is the only locally-produced level" requirement |

This change turns those precisely-scoped residuals into an apply-ready spec so
signing reaches honest function and the six issues can be closed with evidence.

## What Changes

- **Identity-bound signature assertions (v2)**: the native SES MAC covers the
  assertion fields (signer, timestamp, level, method, ip) in addition to the
  canonicalised content hash, so a payload-rewritten artifact verifies invalid;
  v1 artifacts (content-hash-only MAC) are reported as a distinct legacy state,
  never `valid`.
- **Honest tri-state verification**: per-signature status becomes
  `verified | invalid | unverifiable`; external `/Type /Sig` CMS signatures
  Filinq cannot yet validate report `unverifiable`, not `invalid`; the
  document-level verdict distinguishes tampering from inability to verify.
- **Provider/level honesty**: no silent provider fallback anywhere on the
  completion path; every provider refuses `produceSignedArtifact` for a level
  it does not support; level↔provider capability is validated at request
  creation.
- **Status machine everywhere**: `decline()` (and every terminal transition)
  goes through `isValidTransition`; terminal states are immutable.
- **Session download fail-closed**: a signing-session download without a
  produced, marker-embedded artifact fails loudly instead of serving the
  unsigned original.
- **Audit binding + bounded retrieval + proven immutability**: signing audit
  entries bind to the real OR signing-request object (register/schema/id, not
  a uuid-only stub); `getAuditTrail` uses a bounded object-scoped query; tests
  prove OR's API rejects update/delete of signing audit entries; retention
  ≥ 3650 days asserted (Archiefwet 1995).
- **Consent endpoint oracle hardening**: consent error responses carry only
  generic translated messages (parity with the SigningController fix).
- Close GH #282, #283 (Filinq-side residual), #284, #287, #289, #304 with
  live-verified evidence once applied.

## Capabilities

### Modified Capabilities

- `document-signing`: adds identity-bound assertion MAC, tri-state
  verification, provider/level honesty, decline-through-status-machine, and
  fail-closed session download (ADDED requirements; no existing requirement
  is weakened).
- `signing-audit-via-or`: adds real-object audit binding, bounded retrieval,
  and API-level immutability proof (ADDED requirements).

### New Capabilities

- `consent-endpoint-hardening`: consent API error responses are oracle-free
  (generic bodies, detail only in the log) — the #283 residual not covered by
  `multi-tenant-hardening`.

## Out of Scope

- Tenant/organisation binding of consent records, signing requests and the
  other object families, and removal of the fleet-wide `_rbac:false` /
  `_multitenancy:false` bypasses — owned by the sibling wave-2 change
  `multi-tenant-hardening` (its proposal claims GH #283 org-scoping
  explicitly). This change must not author a parallel tenancy model.
- Full PAdES/CMS chain validation (certificate chains, OCSP/CRL) of external
  signatures — this change only stops mislabelling them `invalid`; real
  validation is a candidate follow-up (design.md Open Questions).
- Deed-of-signing / evidence-summary artifacts (Zynyo/ValidSign parity) —
  the organisation-seal side is covered by wave-1
  `document-waarmerk-certification`; a signing-specific evidence PDF is a
  follow-up once this rebuild lands.
- NL identity rails (DigiD/eHerkenning/iDIN, EUDI wallet) — the dependent
  change `signer-identity-rails`.
- Bulk send, field placement, envelopes — the dependent change
  `bulk-signing-field-builder`.
- The OR approval-chain migration (`signing-via-or-approval-with-provider-plugins`,
  status done) — its contracts are untouched here.

## Success Criteria

- `openspec validate signing-trust-rebuild --strict` exits 0.
- A validly signed artifact whose assertion payload is rewritten (signer name
  swapped, MAC kept) verifies `invalid` — proven by a mutation test.
- A QES/AdES request can never complete with a native SES artifact; an unknown
  provider on a request fails the completion loudly (no fallback).
- `decline()` on a COMPLETED request is rejected; the stored status is
  unchanged.
- A completed-session download without an embedded marker returns an error,
  never the unsigned original bytes.
- OR API update/delete attempts on a `filinq.signing.*` audit entry are
  rejected — proven by a test against the live OR instance (Postgres 8080).
- Consent endpoint error bodies are byte-identical for not-found vs internal
  failure classes (no exception text).
- GH #282, #284, #287, #289, #304 closable with linked evidence; #283 closable
  jointly with `multi-tenant-hardening`.
