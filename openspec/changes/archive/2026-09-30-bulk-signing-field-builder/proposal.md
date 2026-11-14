---
kind: code
depends_on: [signing-trust-rebuild]
---

# Proposal: bulk-signing-field-builder

## Why

Three signing workflow features are table stakes in every serious e-signature
product and absent from Filinq (competitor evidence verified in the
intelligence DB, `competitor_features` app_slug `filinq`):

- **Bulk send** — DocuSeal: "Bulk send via CSV/XLSX — mass signature
  campaigns"; DocuSign eSignature: "Envelope-based bulk workflows — templates,
  bulk send". Municipalities send the same declaration/contract to dozens of
  recipients at once; today that is N manual `SigningRequestForm` submissions.
- **Signature field placement** — DocuSeal: "WYSIWYG field-placement builder —
  12 field types, conditional fields and formulas". Filinq's native artifact
  is an appended marker with no visible signature block where the reader
  expects one; there is no way to say "signer 2 initials page 3, bottom
  right".
- **Envelope grouping** — LibreSign (the native Nextcloud eIDAS-aligned
  signing leaf, per the ecosystem insight): "Multi-document envelopes — single
  signing flow across documents; order and roles"; Docupilot: "Docgen +
  eSignature bundle — generation flows straight into signature envelopes". A
  dossier (contract + annexes + processing agreement) must be signable in one
  ceremony, not three parallel requests with three notifications.

This is could-have (competitive parity, no tender hard-requirement verified),
scoped after the must-have `signing-trust-rebuild`: bulk multiplication of a
dishonest pipeline would multiply the defect, so this change hard-depends on
the rebuild (`depends_on`) and builds only on its honest completion gates.
The existing `document-signing` "Bulk signing" requirement covers bulk
*signing* (one signer, N pending requests); this change adds the send side —
bulk *sending* (one document, N recipients) — plus placement and envelopes.

## What Changes

- **Bulk send**: upload a CSV/XLSX recipient list against one document (or a
  template from `template-management`), get a validation report (parse errors,
  invalid emails, unknown users, duplicates), then create one signing request
  per accepted row as a tracked batch (`bulkSigningBatch` object) with
  progress, per-row error isolation, and batch cancel of still-pending
  requests. Reuses the existing per-request creation path — every request
  passes the same validation, level floors and honest-completion gates as a
  single request.
- **Signature field placement**: a visual placement editor on the request form
  stores per-signer field positions (`fieldPlacements`: page, x/y/w/h in
  page-relative coordinates, type `signature|initials|date|text|checkbox`) on
  the signing request; the native provider renders visible field blocks at
  those positions into the signed artifact (in addition to the tamper-evident
  v2 marker, which remains the cryptographic truth). Conditional fields and
  formulas (DocuSeal parity) are explicitly future scope.
- **Envelope grouping**: a `signingEnvelope` object groups N documents into a
  single signing ceremony — one notification and one signing view per signer
  covering all documents, per-document artifacts and per-document audit
  trails, envelope status rolled up from member requests (LibreSign parity).

## Capabilities

### New Capabilities

- `bulk-signing-field-builder`: CSV/XLSX bulk send creating tracked batches of
  signing requests, per-signer visual signature field placement rendered into
  the signed artifact, and multi-document envelope grouping with a single
  signing ceremony.

### Modified Capabilities

<!-- none — the existing document-signing requirements (incl. "Bulk signing" =
     bulk-sign-as-signer) are unchanged; batch/envelope/placement are additive
     objects and flows that reuse the existing request lifecycle. -->

## Out of Scope

- Conditional fields, formulas and computed field types (DocuSeal's 12-type
  builder) — future follow-up; only the five static types ship.
- Bulk send to *external* (accountless) recipients — recipient resolution in
  this change targets Nextcloud users/groups + known emails resolvable to
  signer records; the accountless external flow arrives with
  `signer-identity-rails` + `portal-contribution` maturity.
- Template merge-field authoring itself (`office-template-authoring`,
  wave 1) and batch correspondence generation (`letter-correspondence-generation`)
  — bulk send may reference a template for per-recipient document generation
  but does not change template capabilities.
- Envelope-level single artifact (one merged signed PDF) — per-document
  artifacts only; a merged "signing book" export is a follow-up idea.

## Success Criteria

- `openspec validate bulk-signing-field-builder --strict` exits 0.
- A 50-row CSV with 3 bad rows yields a validation report naming the 3 rows,
  then a batch of 47 requests; one failing row at creation time does not abort
  the remaining rows — live-verified on Postgres 8080.
- A placed signature field for signer 2 on page 3 appears as a visible block
  at that position in the completed artifact, and the artifact still passes
  v2 MAC verification.
- An envelope of 3 documents notifies each signer once, is signable in one
  ceremony, produces 3 verifiable artifacts and 3 complete audit trails, and
  reports COMPLETED only when all member requests are COMPLETED.
- All batch/envelope/placement objects are OpenRegister objects (ADR-001);
  no Filinq-local tables.
