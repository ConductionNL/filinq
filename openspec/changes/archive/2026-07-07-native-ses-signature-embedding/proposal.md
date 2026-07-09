# Proposal: native-ses-signature-embedding

## Why

DocuDesk documents digital signing as a shipped, "legally meaningful, auditable signature
process" (`document-signing` spec Purpose; `docs/features.json`) and the government feature sheet
marks **F-13 "Digitale ondertekening" as "Beschikbaar"** (`docs/GOVERNMENT-FEATURES.md`). Verified
against HEAD, the signing **workflow and audit trail** are real — `SigningService::createRequest()`
creates the request + signer records + an OR `ApprovalChain`, `sign()`/`decline()` enforce
authorization and record an OR `AuditTrail` entry, and the status machine advances correctly. But
the **signature is never applied to the document**:

- `SigningService` injects `SigningProviderFactory` yet **never invokes a provider** on the
  signing path. `sign()` flips the signer record to `SIGNED` and `updateRequestStatus()` flips the
  request to `COMPLETED` — nothing embeds a signature or produces a signed file.
- `NativeSigningProvider::initiateSigning()` and `downloadSignedDocument()` **throw immediately**
  ("Native signing pipeline is not yet integrated — see docudesk#304").
- `ValidSignProvider` is a **stub** — `downloadSignedDocument()` "always throws — not yet
  implemented".
- On `COMPLETED`, `updateRequestStatus()` sets `signedDocumentRef` to the **original**
  `documentFileId` — i.e. the unsigned original is silently labelled as the signed document, and
  that reference is what the cross-app `SigningConcludedEvent` carries to consumers.

So the canonical `document-signing` spec claims that are **false at HEAD**:
- "the signature is embedded in the PDF document" (SES scenario)
- "the signed PDF is stored as a new file version in Nextcloud" (COMPLETED scenario)

Meanwhile the verification half already exists: `SigningVerificationService` reads a
`/DocuDesk-Signature(base64-json)` PDF marker and validates an HMAC-SHA256 over the document
content-hash using the server-held `signing_verification_secret`. The **verifier is present; only
the writer (marker embedding) and the provider↔request wiring are missing** — this is issue #304.

This change closes #304 by specifying the native SES **signature-embedding + storage** step so a
completed request actually yields a verifiable signed artifact, and — until any provider can
produce one — makes the request and the docs tell the truth instead of pointing consumers at the
unsigned original.

## What Changes

- **MODIFIED (`document-signing` / "Signature levels"):** the SES level is specified concretely —
  on the signature that completes a request, the active provider embeds the `/DocuDesk-Signature`
  marker (carrying the HMAC `mac` that `SigningVerificationService::verifyAssertion` validates)
  into the produced PDF. AdES/QES are honestly scoped to a configured external provider; the
  ValidSign integration is called out as incomplete rather than implied working.
- **ADDED (`document-signing`):** completion MUST produce a **verifiable signed artifact** —
  the signed file is stored as a **new Nextcloud file version** of the document, and the request's
  `signedDocumentRef` (and the `SigningConcludedEvent.signedDocumentRef`) reference **that
  artifact**, never the unsigned original. `SigningService` invokes the active provider to produce
  it as part of the terminal transition. Acceptance oracle: the stored artifact MUST pass
  `SigningVerificationService::verifyDocument()`.
- **ADDED (`document-signing`):** an **honest-completion gate** — when no configured provider can
  produce a signed artifact (native writer not yet wired, or external provider not configured/
  stubbed), the request MUST NOT report a signed artifact as available: `signedDocumentRef` stays
  null/flagged (never the original), and the failure surfaces loudly (the current `#304` throw is
  the correct interim behaviour) rather than silently marking the document signed.
- **ADDED (`document-signing`):** **documented readiness reflects reality** — `F-13` in
  `docs/GOVERNMENT-FEATURES.md` and the `docs/features.json` narrative MUST distinguish the shipped
  signing workflow + audit from signature embedding until the writer lands.

### Out of scope

- The cross-app delegated-signing event contract (`docudesk-signing-events`, already active) — this
  change only makes the artifact real; the conclusion event simply gains a truthful
  `signedDocumentRef`.
- OR approval-chain orchestration (`signing-via-or-approval-with-provider-plugins`) — unchanged;
  this change is the artifact-production step that spec deliberately leaves to providers.
- A full PAdES/CMS/eIDAS-crypto signature or PKIoverheid certificates (F-14, already "Gepland").
  The native level remains SES (marker + HMAC assertion), exactly as `SigningVerificationService`
  already models; qualified crypto is a separate future change.
- Completing the ValidSign provider (separate external-provider change) — this change only requires
  that an unfinished provider fail the honest-completion gate rather than mislabel the original.

## Capabilities

### Modified Capabilities

- `document-signing` — SES level specified as real marker embedding; completion produces a
  verifiable stored artifact; honest-completion gate; documented readiness corrected.

## Affected Projects

- [x] Project: `docudesk` — all implementation work is in this repo.
- Reference: `docudesk/openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md`
  (provider/chain contract this builds on).
- Reference: issue `ConductionNL/docudesk#304` (the wiring gap this closes).

## Success Criteria

- `openspec validate native-ses-signature-embedding --strict` exits 0.
- A native SES request whose signers all sign yields a stored signed file (a new NC file version)
  whose bytes carry a `/DocuDesk-Signature(...)` marker that `SigningVerificationService::
  verifyDocument()` reports `valid: true`.
- The request's `signedDocumentRef` and the emitted `SigningConcludedEvent.signedDocumentRef`
  reference the signed artifact, not the original `documentFileId`.
- When no provider can produce an artifact, the request does not report one (`signedDocumentRef`
  null/flagged), and enabling signing surfaces the gap loudly.
- `docs/GOVERNMENT-FEATURES.md` F-13 and `docs/features.json` no longer imply signature embedding
  is complete while it is not.
