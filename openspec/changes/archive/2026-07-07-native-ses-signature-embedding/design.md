# Design: native-ses-signature-embedding

## Context

Verified against HEAD (`origin/development`). The signing feature is a **workflow + audit** that
never touches the document bytes:

| Layer | HEAD behaviour |
|-------|----------------|
| `SigningService::createRequest()` | Creates request + signer records + OR `ApprovalChain`; records audit. Works. |
| `SigningService::sign()` | Authorizes signer, sets `SignerRecord.status=SIGNED`, records OR `AuditTrail` entry, calls `updateRequestStatus()`. **Never invokes a provider.** |
| `SigningService::updateRequestStatus()` | On all-signed sets request `COMPLETED`; passes the **original** `documentFileId` as `signedDocumentRef` to the conclusion event. **No embedding, no new version.** |
| `NativeSigningProvider::initiateSigning()` / `downloadSignedDocument()` | **Throw** "not yet integrated — docudesk#304". |
| `ValidSignProvider` | **Stub** — download "always throws — not yet implemented". |
| `SigningVerificationService::verifyDocument()` | **Real.** Reads `/DocuDesk-Signature(base64-json)` marker; `verifyAssertion()` recomputes `hash_hmac('sha256', sha256(pdf-without-mac), signing_verification_secret)` and `hash_equals` against the marker's `mac`. |

The asymmetry is the whole story: **the verifier exists, the writer does not.** The marker format,
the HMAC-over-content-hash assertion, and the `signing_verification_secret` app-config are all
already defined by the verifier. This change specifies the missing writer + wiring so the
`document-signing` spec's SES claims become true.

## Goals / Non-goals

- **Goal:** a completed native SES request produces a stored, verifiable signed artifact; the
  request/event reference that artifact; until then, no request mislabels the original as signed.
- **Goal:** the documented readiness (F-13, features.json) matches the implementation boundary.
- **Non-goal:** PAdES/CMS/eIDAS-qualified crypto or PKIoverheid (F-14 stays "Gepland"). SES here
  means the existing marker+HMAC assertion model, not certificate-based cryptography.
- **Non-goal:** completing the ValidSign external provider (separate change). This change only
  requires an unfinished provider to fail the honest-completion gate.

## Decisions

### Decision 1 — reuse the existing marker + HMAC contract as the SES artifact

The signed artifact is the produced PDF carrying a `/DocuDesk-Signature(base64-json)` marker whose
`mac` is `hash_hmac('sha256', sha256(pdf-content-without-mac), signing_verification_secret)`. The
acceptance oracle is the already-shipped `SigningVerificationService::verifyDocument()` returning
`valid: true`. This keeps writer and verifier symmetric and avoids inventing a second format.

### Decision 2 — the provider produces the artifact; the service stores it as a new NC file version

On the signature that completes the request, `SigningService` resolves the active provider via
`SigningProviderFactory` and asks it to produce the signed bytes, then writes them as a **new
version of the existing Nextcloud file** (NC `files_versions`), not a sibling `_signed` file. This
matches the `document-signing` COMPLETED scenario ("signed PDF stored as a new file version") and
keeps a single canonical document with its version history. The provider owns byte production; the
service owns storage + status + the conclusion event.

### Decision 3 — `signedDocumentRef` references the artifact, or nothing

`updateRequestStatus()` MUST set `signedDocumentRef` to the stored signed artifact's reference
(file id + version), never the original `documentFileId`. If the provider cannot produce an
artifact, `signedDocumentRef` is null/flagged and the request MUST NOT present as having a signed
document. The cross-app `SigningConcludedEvent` inherits this truthful reference (its contract is
otherwise unchanged — `docudesk-signing-events`).

### Decision 4 — honest-completion gate keeps the loud failure as correct interim behaviour

The current `NativeSigningProvider` `#304` throw is the *right* behaviour under the gate: better a
loud failure than a silently-unsigned "completed" document. The gate is specified so that when the
writer lands the throw is removed, and so that an operator enabling signing with a stubbed/
unconfigured provider gets the same loud failure rather than a false completion.

### Decision 5 — correct the documented readiness

`docs/GOVERNMENT-FEATURES.md` F-13 and `docs/features.json` are corrected as tasks; the spec
asserts the invariant that documented signing readiness distinguishes the shipped workflow+audit
from signature embedding until a provider produces a verifiable artifact.

## Risks

- **PDF writability.** Embedding a marker requires a writeable PDF pipeline (mPDF re-render or a
  PDF cross-reference append). Non-PDF or encrypted inputs must be converted/rejected — the
  existing `DocumentValidationService` `pdf-encrypted` check and `PdfConversionService` are the
  reuse points. Captured as a task, not a new engine.
- **Secret management.** `signing_verification_secret` must be set for verification to succeed;
  the writer must fail the honest-completion gate (not emit an unverifiable artifact) when it is
  unset.
- **Scope discipline.** This is explicitly *not* eIDAS-crypto; the spec keeps SES = marker+HMAC so
  the claim stays honest and the change stays small.
