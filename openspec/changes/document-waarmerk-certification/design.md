# Design: document-waarmerk-certification

## Context

Verified current state (HEAD of this worktree):

- `SigningVerificationService::verifyDocument(fileId, userId)` verifies
  embedded `/DocuDesk-Signature(...)` assertions with an HMAC-SHA256 over a
  canonicalised document using a server-held secret
  (`docudesk.signing_verification_secret`), **fail-closed** after security
  finding #284 (self-asserted blobs report `valid => false`). Real PAdES/CMS
  validation is not implemented. Route: `GET api/signing/verify/{fileId}`
  (authenticated, user-folder scoped).
- The `signing` register (v1.1.0) holds `signingRequest`, `signerRecord`,
  `signingAuditEntry`, `signingSession` — all *personal-signature* flow
  records. Providers: `NativeSigningProvider`, `ValidSignProvider`
  (`lib/Service/Signing/`).
- Every successful anonymization writes an `anonymizationLink` object
  (register `document`) with verified fields: `sourceFileId/Name/Path`,
  `anonymizedFileId/Name/Path`, `outputFormat`, `status`,
  `replacementCount` (derived from real replacements since GH #286),
  `runCount`, `anonymizedAt`, `anonymizedBy`. Batch flows additionally
  produce a CSV audit report (`BatchReportService::generateReport`).
- `PdfService::renderPdf()` renders Twig→HTML→mPDF (PDF/A-3B capable via
  `pdfa` option); `PdfConversionService` converts arbitrary files to PDF.
- No credential/crypto custody code exists in DocuDesk today (verified: no
  `credentialRef`/`ICrypto` usage under `lib/`).

Fleet context: ADR-064 (hydra#107) fixes custody — secrets are never stored
in a register schema; objects carry a `credentialRef` and the secret lives
with the credential broker (`mint()` in OpenRegister, or#440). GH #289
(audit immutability) informs the record design: waarmerk records are
write-once evidence.

## Goals / Non-Goals

**Goals:**

- An organisation seals an anonymized/published PDF with its own
  certificate; anyone holding the document can verify authenticity and
  integrity without a Nextcloud account.
- The seal is honest: it claims exactly what it can prove (integrity +
  origin under the configured org certificate), fails closed, and never
  reports "valid" for anything unverifiable — the #282/#284 signing lessons
  applied from day one.
- Anonymization runs can be certified with a data-minimised evidence PDF.
- Private key custody follows ADR-064 — no key material in schemas, app
  config, or logs.

**Non-Goals:**

- Not a *qualified* seal (eIDAS QSeal requires a QTSP-managed QSCD; the
  gap-shortlist explicitly defers QTSP partnerships as a business decision).
  The waarmerk is an **advanced-level electronic seal at most**, and the UI/
  docs MUST say so.
- No change to the personal-signature flow (`document-signing` capability),
  its providers, or `signing#verify`'s contract.
- No embedded PAdES/ISO 32000 incremental-update signature inside the PDF
  structure this wave (see D2).
- No timestamping authority (RFC 3161) integration this wave — `sealedAt`
  is server time, recorded in the sealed record.

## Decisions

### D1 — The waarmerk is an organisation seal, not a signature

Terminology and data model separate it from `signingRequest`: a new
`certification` register with a `waarmerk` schema. Rationale: the signing
register models a *workflow between persons* (signers, order, decline);
sealing is a single organisational act on an artifact. Mixing them would
overload `signingRequest` semantics and its portal exposure
(portal-contribution reads `signing`). eIDAS grounding: Art. 3(25) electronic
seal = data attached by a *legal person* to ensure origin and integrity.

### D2 — Seal format: detached CMS (PKCS#7) + visible stamp page, not embedded PAdES

**Chosen:** compute `sha256` over the **sealed payload** (original PDF bytes
+ appended stamp page, with the verification-code field canonicalised the
same way `SigningVerificationService::canonicaliseForAssertion()` blanks
marker payloads); sign the hash as a detached CMS structure with
`openssl_cms_sign()` (fallback `openssl_pkcs7_sign()` on older OpenSSL) using
the org certificate; store the CMS blob and hash on the `waarmerk` record;
append a visible stamp page to the output PDF carrying the waarmerk mark,
the organisation name, `sealedAt`, the verification code, and a QR encoding
the public verification URL.

**Rejected alternatives:**

- *True embedded PAdES signature*: requires incremental-update writing with
  `/ByteRange` placeholder arithmetic; pure-PHP support means adopting
  SetaPDF (commercial) or TCPDF's signing (would replace mPDF for this
  path). Cost/risk is out of proportion for a v1 whose verification surface
  is DocuDesk's own page — and Adobe-validatable embedded seals can be added
  later without breaking the record model (the CMS + hash stay valid).
- *Reusing the HMAC `/DocuDesk-Signature` marker*: an HMAC proves only "this
  server knew its own secret" — it is not attributable to an organisation
  certificate and cannot be verified by a third party even in principle.
  Asymmetric CMS is the honest primitive for a waarmerk.

Trade-off accepted: PDF viewers will not show a signature panel; the
verification story is the QR/verification page. This matches how Redactable
certificates and Zynyo deed-of-signing PDFs are consumed in practice.

### D3 — Key custody: `credentialRef` per ADR-064, fail-closed

Admin settings store: the org **certificate** (public, PEM — safe in app
config), its fingerprint, and a **`credentialRef`** string pointing at the
private key held by the fleet credential broker (ADR-064; broker `mint()`
contract from or#440). At seal time `WaarmerkService` resolves the key
material through the broker, uses it in-memory, and never persists or logs
it. If the ref cannot be resolved (broker absent, ref revoked), sealing MUST
fail with a clear 503-style error — **never** fall back to an unsealed "best
effort" artifact.

**Documented uncertainty:** the broker resolution API surface available to a
leaf app at implementation time must be verified against the then-current
OpenRegister release; as an interim custody fallback the design allows the
key encrypted-at-rest via Nextcloud `ICrypto` in app config **only** behind
the same resolver interface, flagged in admin UI as "local custody
(interim)". The register schema carries only `credentialRef`-shaped
references either way — never key material (ADR-064 hard rule).

### D4 — Verification: public, code-scoped, fail-closed, no oracles

Two public surfaces (`#[PublicPage]`, brute-force rate-limited via
`AnonRateThrottle`):

- `GET /verify/{code}` — human page: shows waarmerk status (valid / revoked
  / unknown), organisation, sealedAt, document *name only* — no content, no
  file download (the citizen already holds the document).
- `POST /api/waarmerk/verify` — accepts `{code, sha256}` (the verifier's
  locally computed document hash — the UI computes it client-side from a
  user-selected file via WebCrypto so the **document bytes never leave the
  verifier's machine**); the server compares against the recorded hash and
  validates the stored CMS against the stored certificate chain
  (`openssl_cms_verify`), returning `valid|hash-mismatch|revoked|unknown`.

Verification codes are high-entropy (UUIDv4 + check-part), so the endpoint
is not enumerable (CB #100 existence-oracle lesson: `unknown` and
`revoked-but-wrong-hash` responses are indistinguishable in timing and
identical in shape for non-matching codes). NC user-folder access is not
involved — the record lookup is by code over the `certification` register
via ObjectService with a system-operation context.

### D5 — Stamp page + QR composition

The stamp page is rendered with the existing `PdfService` (Twig template in
the templates register, namespace `docudesk`, so the mark is
huisstijl-brandable) and appended to the source PDF; the QR is generated
locally as an SVG (small pure-PHP QR encoder vendored under `lib/`, or —
ADR-011 reuse check — OpenRegister's existing QR utility if one exists at
implementation time; verified: DocuDesk has none today). No external QR API
(local-only rule).

### D6 — Processing certificate grounded in `anonymizationLink`

`ProcessingCertificateService` builds the anonymization certificate PDF from
**already-recorded** data only: the `anonymizationLink` fields listed in
Context, per-entity-type counts from the run result (types + counts, never
values), the configured detection backend identifier (from the OR
anonymizer-backend state DocuDesk already reads for its admin warning), and
the acting user (`anonymizedBy`). The certificate PDF is then sealed via the
same `WaarmerkService` path (`sealType: processing-certificate`,
`anonymizationLinkId` on the record). Nothing is re-derived from document
content at certificate time — the certificate attests the *record*, which is
the honest claim (Redactable's model). GDPR: AVG Art. 5(1)(c)
data-minimisation, Art. 5(2) accountability — the certificate is
accountability evidence, and contains no personal data beyond the acting
user id.

### D7 — Declarative vs imperative (ADR-031)

Imperative, with justification: cryptographic sealing, CMS/OpenSSL calls,
PDF composition, and broker resolution are external-integration/
document-generation work — listed valid exceptions. Declarative parts stay
declarative: the `certification` register + `waarmerk` schema are pure
`docudesk_register.json` additions. **No lifecycle annotation** on
`waarmerk.status`: the only transition is `active → revoked` and it needs an
authorization guard (admin or sealer) — a lifecycle-guard imperative
exception; the guard lives in the controller/service, and the record is
otherwise write-once (GH #289 immutability: sealed fields are never mutated
after creation; revocation only sets `status`, `revokedAt`, `revokedBy`,
`revocationReason`).

### D8 — Frontend (ADR-012)

- Document surfaces (My Documents / anonymization results): "Waarmerk
  toevoegen" action + waarmerk badge, via existing view integration points.
- Public verification page: minimal standalone Vue entry (no app shell),
  NL Design System tokens, WCAG AA (it is citizen-facing).
- Admin settings: certificate panel in the existing DocuDesk admin settings
  (`lib/Settings/DocuDeskAdmin.php` section) — upload PEM, show fingerprint/
  expiry, set `credentialRef`, test-seal button. Registered via the settings
  framework, NOT the vue-router (hydra-gate-admin-router rule).
- Dialogs as separate components in `src/modals/` (modal-isolation).

## OpenRegister usage (ADR-001)

| Operation | OR service |
|---|---|
| `waarmerk` record CRUD (create/find/list/revoke-update) | `ObjectService` via the same resolver pattern as existing services |
| Public verification lookup by code | `ObjectService` search with system-operation context (no user folder) |
| Evidence source | existing `anonymizationLink` objects (read-only) |
| Register/schema definition | `certification` register in `docudesk_register.json`, imported on boot via `ConfigurationService::importFromApp()` |

No custom tables. `waarmerk` schema (all evidence fields write-once):
`documentFileId`, `documentName`, `documentHash` (sha256 hex),
`sealType` (`waarmerk` | `processing-certificate`), `cmsSignature` (base64
detached CMS), `certificateFingerprint`, `certificateSubject`,
`verificationCode`, `sealedAt`, `sealedBy`, `status`
(`active` | `revoked`), `revokedAt`, `revokedBy`, `revocationReason`,
`anonymizationLinkId` (optional relation, UUID).

## Seed Data

Demo objects (nil-UUID pattern, Demostad flavour; no realistic secrets —
placeholders only):

```json
{
  "waarmerk": {
    "id": "00000000-0000-0000-0000-000000000201",
    "documentFileId": 0,
    "documentName": "woo-besluit-2025-017-geanonimiseerd.pdf",
    "documentHash": "0000000000000000000000000000000000000000000000000000000000000000",
    "sealType": "waarmerk",
    "cmsSignature": "PLACEHOLDER_BASE64_CMS",
    "certificateFingerprint": "00:11:22:33:44:55:66:77:88:99:AA:BB:CC:DD:EE:FF",
    "certificateSubject": "O=Gemeente Demostad, C=NL",
    "verificationCode": "00000000-0000-0000-0000-000000000202",
    "sealedAt": "2026-07-16T12:00:00+02:00",
    "sealedBy": "demo-woo-officer",
    "status": "active",
    "anonymizationLinkId": "00000000-0000-0000-0000-000000000203"
  }
}
```

Unit tests generate a **throwaway self-signed org certificate at test
runtime** (openssl in the test bootstrap) — no PEM/key fixture is ever
committed (gitleaks). Admin-settings seed uses `credentialRef:
"doriath://org/demostad/waarmerk-seal"`-style opaque refs with placeholder
ids.

## Security Considerations

- Private key: resolved per D3, held only in memory during
  `openssl_cms_sign`, zeroised reference after; never logged (log the
  fingerprint, never the PEM).
- Fail-closed everywhere: unresolvable key → seal refused; unknown code /
  wrong hash / revoked / broken CMS → never `valid` (finding #284 posture).
- Public endpoints: `#[PublicPage]` + `#[NoCSRFRequired]` on the POST verify
  (stateless, no session mutation), brute-force protection, response-shape
  parity to avoid oracles (CB #100), no document content served.
- Sealing authorization: authenticated users with access to the file;
  revocation restricted to admins or the sealer (guard in method body —
  no-admin-idor gate).
- Certificate expiry: sealing with an expired certificate is refused;
  verification of seals made while the certificate was valid reports valid
  with an `certificateExpired: true` advisory (standard long-term-validation
  compromise, documented on the verification page).

## Risks / Trade-offs

- [No embedded PAdES → Adobe shows no signature panel] → QR/verification
  page is the product story (D2); record model is forward-compatible with a
  later embedded-seal upgrade.
- [ADR-064 broker API surface for leaf apps may shift] → resolver interface
  isolates it; interim ICrypto custody path behind the same interface,
  admin-visible (D3). Open question logged.
- [Stamp page changes document pagination/layout] → stamp is appended as a
  final page only, never overlaid; the *hash covers the sealed artifact*, so
  what is verified is exactly what circulates.
- [Clock trust for `sealedAt`] → documented limitation (no RFC 3161 TSA this
  wave); `sealedAt` additionally covered by the CMS signed attributes.
- [Public endpoint abuse] → rate limiting + high-entropy codes; no
  enumeration, no content exposure.

## Migration Plan

1. Register addition ships first (additive; new register import on boot).
2. Backend + admin settings; sealing usable via API.
3. Public verification page + document-view actions.
4. Rollback = revert release; existing waarmerk records remain readable
   (they are plain OR objects), verification page disappears with the app.

## Open Questions

- Exact broker-resolution call for leaf apps under ADR-064 at build time
  (or#440 `mint()` is sessionless — confirm leaf-app read path); interim
  ICrypto custody acceptable? (Provisional: yes, behind the resolver
  interface, flagged in admin UI.)
- Should batch anonymization auto-seal every output (per-dossier toggle)?
  (Provisional: manual + per-batch opt-in checkbox; auto-seal default off to
  keep seals meaningful.)
