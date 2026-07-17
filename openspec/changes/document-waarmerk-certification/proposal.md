---
kind: code
---

# Proposal: document-waarmerk-certification

## Why

Dutch government buyers ask for proof that a published document is the
authentic, unaltered output of a controlled process — a **waarmerk** applied
with an *organisation* certificate (the eIDAS *electronic seal* concept,
Regulation 910/2014 Art. 3(25)/(27): a seal is attached by a legal person,
distinct from a natural person's signature):

- **De Connectie 391449** (market consultation, REQ2): anonymise **and
  waarmerk** documents before active publication.
- **Dordrecht/Drechtsteden 407973 VPB-11**: certification of published
  documents with an organisation certificate.
- **Zynyo** productises an embedded ETSI "Deed of Signing"; **Redactable**
  sells *redaction certificates* (what was redacted, when, by whom);
  ValidSign ships an "evidence summary" — evidence artifacts are a
  6-competitor feature theme (research-competitors.md theme #4).
- User-wishes #10 ranks "document certification/waarmerk + immutable audit"
  in the deduplicated top-10.

DocuDesk already produces the two artifact families worth certifying —
anonymized/published PDFs (`AnonymizationService`, which records every run as
an `anonymizationLink` object with real, verified replacement counts, closing
GH #286) and generated documents — and already owns a fail-closed
verification primitive (`SigningVerificationService::verifyDocument`, HMAC
over a canonicalised document, hardened after finding #284). What is missing
is the organisation-level seal: a cryptographic waarmerk with a visible
mark + QR, a public verification surface, processing certificates as
evidence artifacts, and admin-side organisation-certificate management. No
Nextcloud ecosystem app offers this (research-nc-ecosystem.md: LibreSign is
personal-signature-oriented; nobody does org sealing of processed
documents).

## What Changes

- **Organisation certificate management** in DocuDesk admin settings: upload/
  reference an X.509 organisation certificate for sealing. The private key is
  NEVER stored in a register schema or app config — the seal service resolves
  it at signing time via a `credentialRef` per the ADR-064 custody model
  (master-data authority = OpenRegister, secret custody = credential broker).
- **Waarmerk sealing**: certify a processed/published PDF with a detached
  CMS (PKCS#7) signature over the document hash, made by the organisation
  certificate; a visible waarmerk stamp page (mark + QR + verification code)
  is appended to the sealed PDF; a `waarmerk` record is stored via
  OpenRegister.
- **Processing certificates** as evidence artifacts: an anonymization
  certificate PDF generated from the existing `anonymizationLink` +
  anonymization-run data (entity-type counts, backend, timestamps, acting
  user — never entity values), itself sealed with the waarmerk.
- **Verification**: a public verification page + endpoint that validates a
  waarmerk by verification code and uploaded/recomputed document hash,
  fail-closed (mirroring the finding-#284 posture of the existing verify
  path). Existing `signing#verify` stays untouched; waarmerk verification is
  a separate, org-seal-specific surface.
- **Revocation**: a waarmerk can be revoked (with reason); verification of a
  revoked waarmerk reports revoked, never valid.

## Capabilities

### New Capabilities

- `document-waarmerk-certification`: organisation-certificate waarmerk
  (eIDAS electronic-seal concept) on processed/published PDFs — sealing with
  visible mark + QR, processing certificates as sealed evidence artifacts,
  public fail-closed verification, revocation, and admin certificate
  management with credentialRef key custody.

### Modified Capabilities

<!-- none — document-signing requirements are unchanged; the waarmerk is a
     separate organisational surface. SigningVerificationService is reused
     as a pattern, not modified in contract. -->

## Impact

- **Backend**: new `WaarmerkService` (hashing, CMS sealing via OpenSSL,
  stamp-page composition, QR), new `ProcessingCertificateService` (evidence
  PDF from anonymization data via the existing `PdfService`), new
  `WaarmerkController`; admin settings section for the org certificate.
- **Register**: new `certification` register in
  `lib/Settings/docudesk_register.json` with a `waarmerk` schema (record of
  each seal: document refs, hash, certificate fingerprint, verification
  code, status).
- **Routes**: seal/list/show/revoke endpoints (authenticated) + public
  verification endpoints (`#[PublicPage]`) in `appinfo/routes.php`.
- **Frontend**: waarmerk actions on document views, a public verification
  page, admin settings panel (ADR-012 Cn components).
- **Dependencies**: PHP OpenSSL extension (already required by Nextcloud);
  a QR code is rendered without new heavy deps (design.md D5). Credential
  custody depends on the ADR-064 broker contract (`credentialRef`); the
  design documents the dependency and the fail-closed behaviour when no key
  is resolvable.
- **GDPR**: certificates are data-minimised (AVG Art. 5(1)(c)) — entity
  *types and counts* only, never detected values; verification exposes no
  document content.
