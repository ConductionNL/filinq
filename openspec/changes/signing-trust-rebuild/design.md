# Design: signing-trust-rebuild

## Context

All facts verified at HEAD (`spec/market-gap-wave2-2026-07`, development @
9cc14407), files under `lib/`:

- `Service/SigningService.php` (1021 lines) — request lifecycle;
  `sign()`/`decline()` carry the #282 ownership fix; completion path
  `updateRequestStatus()` → `produceAndStoreSignedArtifact()` (lines 683–811)
  is wired (#304 core). `decline()` (441–512) does NOT call
  `isValidTransition()`.
- `Service/SigningVerificationService.php` — fail-closed HMAC verification
  (#284 first fix). `verifyAssertion()` MACs `HMAC-SHA256(secret,
  sha256(canonical-document))`; `canonicaliseForAssertion()` blanks every
  `/DocuDesk-Signature(...)` payload, so the assertion FIELDS are outside the
  MAC.
- `Service/Signing/NativeSigningProvider.php` — OR-persisted sessions (#287
  fix); `produceSignedArtifact()` (283–314) writes the marker + MAC but never
  checks `supportsLevel()`; `downloadSignedDocument()` (196–219) falls back to
  the original `documentPath`.
- `Service/Signing/ValidSignProvider.php` — `produceSignedArtifact()` always
  throws (honest gate).
- `Service/Signing/SigningProviderFactory.php` — `getProvider()` throws on
  unknown, but `getActiveProvider()` silently returns native for an unknown
  configured name, and `produceAndStoreSignedArtifact()` catches the
  `getProvider()` throw and falls back to `getActiveProvider()`.
- `Service/SigningAuditService.php` — routes through OR `AuditTrailMapper`
  (hash chain); creates entries from `new ObjectEntity()` +
  `setUuid($signingRequestId)` only; `getAuditTrail()` runs
  `findAll(filters: ['action' => <all 8 docudesk.signing.* types>])` and
  filters by objectUuid in PHP.
- `Controller/SigningController.php` — auth + scoping fixed (filinq#100);
  its `errorResponse()` is the oracle-free reference implementation.
- `Controller/ConsentController.php` — ownership guards + whitelist shipped
  (#283 partial); `errorResponse()` (117–126) interpolates
  `$exception->getMessage()` into the 500 body.
- Register `signing` schemas at HEAD: `signingRequest`, `signerRecord`,
  `signingAuditEntry` (deprecated write path), `signingSession` (has
  `markerEmbedded`, `signedDocumentPath`).
- Frontend exists: `src/views/signing/` (SigningRequestList/Detail/Form,
  BulkSigningPanel, SignatureVerification) + `src/store/modules/signing.js` —
  the pipeline is UI-drivable, so e2e coverage is possible.
- Canonical specs `document-signing`, `signing-via-or-approval-with-provider-plugins`,
  `signing-audit-via-or` are all `status: done`; this change ADDS requirements
  and weakens none.

## Goals / Non-Goals

**Goals**

1. Make a Filinq-signed artifact's *identity claims* cryptographically
   bound, not just its content (finish #284/#282 at the evidence layer).
2. Make every completion/termination path honest: correct provider, supported
   level, valid transition, never the unsigned original (finish #304, #287).
3. Make the audit trail provably immutable and object-bound (finish #289).
4. Remove the last #283 info-leak Filinq still owns (consent error oracle).

**Non-Goals**

- Tenancy (sibling `multi-tenant-hardening` owns org-scoping incl. GH #283's
  cross-tenant dimension; coordinated, not duplicated).
- PAdES/CMS chain validation; identity rails; bulk/envelope features (each has
  its own change).
- Any change to the OR approval-chain contracts or the deprecated
  `signingAuditEntry` read path.

## Decisions

### D1 — Assertion v2: MAC over content hash AND assertion fields

Current formula (v1): `mac = HMAC(secret, sha256(canonical(doc)))`. The
assertion payload is blanked during canonicalisation, so `signer`, `timestamp`,
`level`, `method`, `ip` are all outside the MAC — rewriting them keeps the
artifact "valid". v2 formula:

```
payloadCore = canonical-JSON of assertion WITHOUT `mac` (sorted keys, v:2 field)
mac         = HMAC-SHA256(secret, sha256(canonical(doc)) . "\n" . payloadCore)
```

Writer (`produceSignedArtifact`) and verifier (`verifyAssertion`) stay
symmetric: the verifier decodes the payload, removes `mac`, re-canonicalises,
recomputes. Rejected alternative — MAC over the raw base64 payload including
mac — is the self-referential trap the v1 fix already documented as
unworkable. `hash_equals()` for comparison (already the case). The `v` field
discriminates: assertions without `v: 2` (or without `mac`) are **legacy**,
reported with status `unverifiable` and reason `legacy-assertion-v1`, never
`verified` (fail-closed; see Migration Plan).

### D2 — Tri-state verification, backward-compatible response

Per-signature `valid: bool` becomes `status: verified|invalid|unverifiable`
(+ machine-readable `reason`). `valid` is kept as a derived boolean
(`status === 'verified'`) so existing consumers (SignatureVerification.vue,
Newman) don't break. Document-level: `isValid` keeps its strict meaning (all
signatures `verified`, at least one present); a new `verdict` field reports
`verified | tampered | unverifiable | mixed`. An embedded external `/Type /Sig`
without a Filinq marker → `unverifiable` + `reason: external-signature-
unsupported` (today it is reported as if tampered). A v2 marker whose MAC fails
→ `invalid` (that IS tamper evidence).

### D3 — Provider/level honesty enforced at three points

1. **Creation**: `createRequest()` validates the level↔provider pair via a new
   `SigningProviderInterface::supportsLevel()` check against the *requested*
   provider (native+SES only; validsign accepts AdES/QES per its config).
   Invalid pair → 400 before anything persists.
2. **Resolution**: `produceAndStoreSignedArtifact()` drops the
   `catch → getActiveProvider()` fallback; an unknown provider name on the
   request throws (honest-completion gate). `getActiveProvider()`'s
   unknown-config fallback-to-native is retained ONLY for reads/UI defaults,
   never on the artifact path.
3. **Production**: every `produceSignedArtifact()` implementation guards
   `supportsLevel($context['level'])` and throws on mismatch — defence in
   depth so a future call site cannot re-introduce silent substitution.

### D4 — Terminal transitions all pass the status machine

`decline()` gains the same `isValidTransition(current, 'DECLINED')` gate that
`cancelRequest()` already has; DECLINED is reachable only from
PENDING/IN_PROGRESS (per the existing STATUS_TRANSITIONS map). Terminal states
(COMPLETED, DECLINED, EXPIRED, CANCELLED) accept no further transition. The
signer-record write happens only after the request-level gate passes (today the
signer row is mutated before the request status is even loaded).

### D5 — Session download fail-closed

`downloadSignedDocument()` returns a path ONLY when the session is `completed`
AND `signedDocumentPath` is non-empty AND `markerEmbedded === true`; otherwise
it throws (same posture as the honest-completion gate). The
"fall back to `documentPath`" branch and its info-log are removed. The session
flow remains a pluggable extension seam (per
`signing-via-or-approval-with-provider-plugins`, unchanged); the fix only
guarantees the seam can never serve unsigned bytes as signed.

### D6 — Audit entries bind to the real OR object; retrieval is bounded

`SigningAuditService::logEvent()` resolves the actual signing-request
`ObjectEntity` via ObjectService (register `signing`, schema `signingRequest`)
instead of fabricating a uuid-only stub, so the OR audit entry carries real
register/schema/object linkage and the hash chain anchors to a real row.
Fail-soft: if resolution fails (request vanished mid-flight), fall back to the
uuid-stub WITH a warning log — an unlinked audit entry is better than none
(never throw away audit). `getAuditTrail()` switches to an objectUuid-scoped
query (AuditTrailMapper `findAll(filters: ['object' => …])` — the exact filter
key verified during apply against OR HEAD; if OR exposes none, add the filter
OR-side rather than scanning fleet-wide in PHP). Immutability is OR's control
(ADR-022): this change does not re-implement it app-side, it **proves** it —
a live test attempts PUT/DELETE on a signing audit entry through OR's API and
asserts rejection, closing the #289 "guard methods are dead code / control
unverified" finding. Retention ≥ 3650 days on the signing register is asserted
by the same deploy check documented in `signing-audit-via-or`.

### D7 — Consent error oracle: copy the proven fix, don't invent

`ConsentController::errorResponse()` adopts the `SigningController` pattern
verbatim: generic translated message only, full detail to the logger, status
code honoured from the exception code when 4xx/5xx. Not-found and
access-denied continue to collapse to a single 404 (already the case via
`canAccessConsent`). Everything else on #283 is `multi-tenant-hardening`'s.

## OpenRegister usage (ADR-001)

No new registers or schemas. Touched OR surfaces: ObjectService
`find`/`saveObject`/`findAll` (existing call shapes), AuditTrailMapper
`createAuditTrailEntry`/`findAll`. `signedDocumentRef` on `signingRequest` is
being added by the in-flight `filinq-signing-events` change — this change
reuses it, no duplicate schema edit. The assertion `v` field lives inside the
PDF marker payload, not in any schema.

## Seed Data

No new object types, so seed data is test-fixture only, on the nil-UUID
pattern (self-evidently fake, collision-free):

- A `signingRequest` fixture `00000000-0000-0000-0000-00000000d001`
  (Demostad, level SES, provider native, two signers
  `…d002`/`…d003`) used by the transition and completion tests.
- Mutation fixtures generated at runtime: a v2-signed PDF produced by the test
  itself with secret `test-secret-not-production` (set via IAppConfig in the
  test container), then byte-flipped / payload-rewritten / marker-stripped.
  No signed PDF fixture is committed — artifacts are runtime-generated so the
  writer and verifier are always exercised as a pair.

## Security Considerations

- Fail-closed everywhere: legacy v1 assertions, missing secret, unknown
  provider, unsupported level, incomplete session, unverifiable external
  signature — none may yield `verified`/COMPLETED/signed bytes.
- The MAC secret (`signing_verification_secret`) remains server-held app
  config; it is never logged, never in a schema (ADR-064 posture), and the v2
  formula gives it identity-binding force.
- Oracle parity: consent + signing error bodies are generic and identical
  across failure classes; 404-vs-403 never split (filinq#100 invariant kept).
- Audit entries gain real object linkage — IP + identity data in the `changed`
  payload stays restricted by the existing initiator/signer/admin read gate on
  `SigningController::getAudit`.

## Risks / Trade-offs

- **v1 artifacts stop verifying** — accepted and explicit (Migration Plan);
  reporting them `verified` would preserve the #284 forgery on old artifacts.
- **Provider-fallback removal** could break deployments whose requests carry a
  stale provider name — that break is the point (silent native substitution is
  the defect); the error message names the misconfigured provider.
- **AuditTrailMapper filter key drift** — the objectUuid filter key must be
  verified against OR HEAD during apply (task 3.1); test-fake drift is the
  known trap (check the receiver's REAL class, not a fake).
- Coordination risk with `multi-tenant-hardening` on ConsentService: this
  change touches ONLY `ConsentController::errorResponse()`; the sibling owns
  the `_rbac` flags — file-level overlap is nil, merge-order free.

## Migration Plan

1. Code lands with assertion v2 writer + verifier in one PR (writer/verifier
   symmetry is a single reviewed unit).
2. Existing v1 artifacts: reported `unverifiable`/`legacy-assertion-v1`.
   Documented operator guidance: re-run signing (new request) for any document
   whose verification evidence must remain load-bearing. No bulk rewrite —
   mutating stored signed artifacts would itself destroy evidence.
3. `signingAuditEntry` legacy rows: unchanged, read-only (per
   `signing-audit-via-or`).
4. No register version bump needed (no schema change in this change).

## Open Questions

- Real PAdES/CMS validation of external signatures (openssl_cms_verify chain +
  revocation): follow-up candidate once `document-waarmerk-certification`
  lands its CMS toolchain — reuse, don't duplicate (ADR-011).
- Should `verdict: unverifiable` documents be flagged in My Documents UI? UX
  call deferred to apply-time review with PO.
