---
status: draft
---
# eIDAS Qualified Electronic Signature (QES)

## Purpose

A Qualified Electronic Signature under Regulation (EU) 910/2014 (eIDAS) is the only category of electronic signature that the regulation declares to be legally equivalent to a handwritten signature across all EU member states. It is the signature type required for high-assurance use cases that docudesk customers regularly face: notarial deeds, employment contracts with statutory written-form requirements, government decisions that need to survive judicial review, contracts with consumers in member states with national written-form rules, and any cross-border instrument where the receiving party will not accept lesser AES (Advanced Electronic Signature) levels.

Today docudesk can attach a basic electronic signature artefact to a document. It cannot run a QES flow: it does not integrate with a Qualified Trust Service Provider (QTSP), it does not provide the user-controlled Qualified Signature Creation Device (QSCD) ceremony that QES requires, it does not produce signatures in long-term-validation (LTV) form, and it cannot orchestrate a multi-party signing flow with eIDAS-grade authentication of every signer. This brief defines what those capabilities look like, with explicit support for the four QTSPs that cover the Dutch and Benelux market — QuoVadis, KPN ID, Digidentity, and Connectis — through a pluggable adapter pattern so additional QTSPs (Itsme, Signicat, Namirial, etc.) can be onboarded later without re-architecting the flow.

The brief is scoped to QES specifically, with AES included as a fall-through tier when the customer explicitly accepts the lower assurance level. Simple electronic signatures (SES, e.g. a typed name or a drawn squiggle) are out of scope: the existing docudesk basic-signature feature continues to serve that use case.

QES introduces three legal requirements that drive most of the architecture. First, the signature must be created with the signer's sole control — the private key cannot be held by the relying party (docudesk) and must live inside a QSCD; in practice for cloud signing this means a remote-QSCD operated by the QTSP, accessed by the signer through a strong-authentication ceremony. Second, the signer's identity must be verified to a Substantial or High eIDAS LoA before the certificate can be issued (the QTSP does this, typically through an in-person ceremony, video-identification, or eID like DigiD-Substantieel or itsme). Third, the signed document must remain verifiable for the full retention period — which for archival records can be decades — and that requires LTV: every certificate in the chain plus timestamps plus revocation information is embedded into the signature container so verification works even after the original CA has stopped responding.

## Data Model

The signing flow is modelled as a state machine on a `signature_request` object plus per-signer `signature_invitation` objects plus an immutable `signature_artefact` object per completed signature. All three live in a `docudesk_signatures` register on OpenRegister.

`signature_request` fields: `id` (UUID), `documentId` (UUID, the document being signed — frozen at request creation, see REQ-SIG-002), `documentVersion` (string, the immutable docudesk version pointer), `documentHash` (string, SHA-256 of the bytes being signed — the contract content), `previewUrl` (string, expires after signing), `assuranceLevel` (enum: `qes`, `aes`), `signatureFormat` (enum: `pades-b-lta`, `xades-b-lta`, `cades-b-lta` — defaults to `pades-b-lta` for PDFs), `signingOrder` (enum: `parallel`, `sequential`), `qtspId` (string, the configured QTSP adapter to use), `state` (enum: `draft`, `awaitingSigners`, `partiallySigned`, `completed`, `cancelled`, `failed`, `expired`), `expiresAt` (ISO timestamp), `createdBy`, `createdAt`, `completedAt`, `auditChain` (array of event references — see audit-log section).

`signature_invitation` fields: `id`, `signatureRequestId`, `signerName` (string), `signerEmail` (string), `signerLoa` (enum: `substantial`, `high` — the minimum LoA required of this signer; QES needs at least Substantial), `signerOrder` (integer — meaningful only when request is `sequential`), `role` (string, free-form e.g. `gemeentesecretaris`, `wethouder`, `inwoner`), `state` (enum: `pending`, `notified`, `viewed`, `authenticated`, `signed`, `declined`, `expired`), `qtspSessionId` (string, the QTSP's session/transaction id for this signer), `signedAt` (ISO timestamp), `declinedReason` (string).

`signature_artefact` fields: `id`, `signatureRequestId`, `invitationId`, `signerName`, `signerCertificateSubject` (full DN), `signerCertificateFingerprint` (SHA-256 hex), `signatureFormat`, `assuranceLevel`, `signedAt`, `qualifiedTimestamp` (object: `tsa` — issuer name, `tsaCertificateFingerprint`, `timestampedAt`, `nonce`), `revocationInfo` (object: `crl` — array of distribution points captured at sign time, `ocsp` — OCSP response captured at sign time), `signatureBytes` (binary blob — the actual signature value, embedded in the signed document container for PAdES; stored separately for XAdES detached signatures), `signedDocumentRef` (UUID — the docudesk file that holds the document with the embedded signature, with its own SHA-256 captured), `ltvSealedAt` (ISO timestamp — when LTV embedding was completed), `verificationStatus` (enum: `valid`, `valid-ltv`, `unknown`, `invalid` — refreshed by a background verifier).

A `qtsp_configuration` object describes a QTSP adapter as installed on the instance: `id`, `qtspId` (`quovadis`, `kpnid`, `digidentity`, `connectis`, ...), `displayName`, `adapterVersion`, `openconnectorSourceId` (UUID — credentials and base URL live in OpenConnector), `supportedFormats` (array of pades/xades/cades variants the QTSP can produce), `supportedLoa` (array — `substantial`, `high`), `defaultSignatureFormat`, `userJourney` (enum: `embedded`, `redirect`, `popup` — how the signer reaches the QTSP signing ceremony from the docudesk UI), `enabled`.

An `audit_event` row is written on every state transition with: `id`, `signatureRequestId`, `invitationId` (nullable), `eventType` (enum: `requestCreated`, `invitationSent`, `invitationViewed`, `signerAuthenticated`, `signatureCompleted`, `signatureFailed`, `requestCancelled`, `ltvSealed`, `verificationRefreshed`), `timestamp` (qualified-timestamped where available), `actor` (signer email or system identifier), `ipAddress` (where applicable), `userAgent`, `evidence` (JSON — QTSP response payload excerpt, hash of any artefact produced).

The bewaartermijn for signature artefacts inherits from the underlying document's TMLO/MDTO bewaartermijn (see sibling brief): an artefact is retained at least as long as its document, and the LTV refresh job extends embedded revocation info on a schedule so signature validity does not lapse before the document's retention does.

## Requirements

### REQ-SIG-001 — Pluggable QTSP adapter for QuoVadis, KPN ID, Digidentity, Connectis

The system SHALL expose every QTSP integration as an `IQtspAdapter` implementation registered through OpenConnector, SHALL ship adapters for QuoVadis, KPN ID, Digidentity, and Connectis, and SHALL allow additional adapters to be installed without modifying core docudesk code.

- GIVEN an instance with the four shipped QTSPs configured
  WHEN a signing request is created with `qtspId=kpnid`
  THEN the request SHALL be routed to the KPN ID adapter and SHALL fail validation if KPN ID's configured `supportedFormats` does not include the requested `signatureFormat`.
- GIVEN a third-party QTSP adapter installed as an app
  WHEN the adapter registers a new `qtspId`
  THEN it SHALL appear in the QTSP selector for new signing requests with no core changes required.

### REQ-SIG-002 — Document freeze at request creation

The system SHALL freeze the document content (capturing its immutable version pointer and SHA-256 hash) at the moment the signing request is created, and SHALL refuse to start the QTSP signing ceremony if the document's current hash no longer matches the frozen hash.

- GIVEN a signing request created with `documentHash=H1`
  WHEN the document is edited after request creation and before any signer signs
  THEN the request SHALL be moved to `failed` state with reason `documentChanged` and all pending invitations SHALL be cancelled.
- GIVEN a signing request whose frozen hash still matches the document's current hash
  WHEN a signer initiates the QTSP ceremony
  THEN the ceremony SHALL proceed and the QTSP SHALL receive the frozen-hash content for signing.

### REQ-SIG-003 — Preview → invitation → ceremony signing flow

The system SHALL support a three-step signing flow: requester previews and confirms the document and the signer list; invitations are dispatched; each signer authenticates to the QTSP at their required LoA and completes the signing ceremony on the QSCD.

- GIVEN a requester confirming a draft signing request
  WHEN they submit
  THEN every invitation SHALL be created in `pending` state, the request SHALL move to `awaitingSigners`, and each invitation SHALL be dispatched as an email containing a one-time signing link.
- GIVEN a signer clicking their signing link
  WHEN the link is valid and not expired
  THEN the signer SHALL be redirected to the configured QTSP user journey (embedded/redirect/popup per config) and the invitation SHALL move to `viewed`.
- GIVEN a signer who has authenticated at their required LoA and completed the QSCD ceremony
  WHEN the QTSP posts the completion callback to the docudesk webhook
  THEN the invitation SHALL move to `signed`, a `signature_artefact` SHALL be persisted, and the request SHALL advance per `signingOrder` (next signer for sequential, or `completed` if all signed for parallel).

### REQ-SIG-004 — Sequential and parallel multi-party signing

The system SHALL support both `sequential` and `parallel` multi-signer flows, SHALL enforce signer order in `sequential` mode, and SHALL allow any signer to sign at any time in `parallel` mode.

- GIVEN a sequential request with signers ordered Alice, Bob, Carol
  WHEN Bob's invitation link is clicked before Alice has signed
  THEN the QTSP ceremony SHALL NOT start, the user SHALL see a "waiting for Alice" message, and Bob's invitation SHALL remain `pending` until Alice's signature completes.
- GIVEN a parallel request with three signers
  WHEN any signer completes their signature
  THEN the request SHALL move to `partiallySigned` (or `completed` if all are done) and the other signers SHALL remain able to sign concurrently.
- GIVEN a sequential request
  WHEN any signer declines
  THEN the request SHALL move to `cancelled` and remaining signers SHALL receive no invitation.

### REQ-SIG-005 — Long-Term Validation (LTV) embedding

The system SHALL produce signatures in an LTV-capable format (PAdES-B-LTA, XAdES-B-LTA, or CAdES-B-LTA per request) with every certificate in the trust chain, the qualified timestamp, and CRL+OCSP responses embedded at sign time; and SHALL refresh embedded revocation info on a periodic background job so the signature remains LTV-valid through the document's retention period.

- GIVEN a completed signing request with `signatureFormat=pades-b-lta`
  WHEN the signed PDF is verified by an external eIDAS-aware validator
  THEN the validator SHALL report `LTV enabled` with all chain certificates, the qualified timestamp, and revocation info present and valid.
- GIVEN an existing PAdES-B-LTA signature approaching the end of its embedded revocation window
  WHEN the LTV refresh background job runs
  THEN a new document timestamp SHALL be embedded extending the validity window, and the artefact's `ltvSealedAt` SHALL be updated.

### REQ-SIG-006 — Qualified timestamp from a qualified TSA

The system SHALL obtain a qualified timestamp from a qualified Trust Service Provider Time-Stamping Authority for every completed signature, and SHALL refuse to mark a request `completed` if the timestamp cannot be obtained.

- GIVEN a signing ceremony where the QTSP returns the signature value but the TSA is unreachable
  WHEN the artefact-build step runs
  THEN the artefact SHALL be retried up to three times with exponential backoff; on permanent failure the invitation SHALL be moved to `failed` with `reason=timestampUnavailable` and the request SHALL stay `partiallySigned` or `awaitingSigners`.
- GIVEN a successfully timestamped signature
  WHEN the artefact is persisted
  THEN `qualifiedTimestamp.tsa`, `qualifiedTimestamp.tsaCertificateFingerprint`, and `qualifiedTimestamp.timestampedAt` SHALL all be populated.

### REQ-SIG-007 — Immutable audit log with qualified timestamps

The system SHALL write an immutable audit-log entry for every state transition, every signer authentication outcome, and every signature artefact creation; entries SHALL be qualified-timestamped where the source event is signature-related, and SHALL be retrievable per request and per document for the document's full retention period.

- GIVEN any state transition on a signing request
  WHEN the transition completes
  THEN an `audit_event` row SHALL be written with `eventType`, `timestamp`, `actor`, `ipAddress` (where applicable), and an evidence excerpt; the row SHALL be append-only.
- GIVEN a signature completion event
  WHEN the audit row is written
  THEN it SHALL include the same qualified timestamp as the artefact and SHALL be cross-referenced from `signature_artefact.qualifiedTimestamp`.

### REQ-SIG-008 — Signer authentication enforced at declared LoA

The system SHALL require each signer to authenticate at or above their invitation's `signerLoa` before the QSCD ceremony can begin, and SHALL refuse to accept a signature whose returned authentication context falls below the required level.

- GIVEN an invitation with `signerLoa=high`
  WHEN the QTSP returns a completion callback with `authContext.loa=substantial`
  THEN the artefact SHALL NOT be persisted, the invitation SHALL move to `failed` with `reason=insufficientLoa`, and the audit log SHALL capture the mismatch.
- GIVEN an invitation with `signerLoa=substantial`
  WHEN the signer authenticates at Substantial or High
  THEN the ceremony SHALL proceed normally.

### REQ-SIG-009 — Request cancellation and expiry

The system SHALL allow the requester (or a user with cancel permission) to cancel a signing request at any point before `completed`, SHALL automatically expire requests past `expiresAt`, and SHALL invalidate all outstanding invitations on either event.

- GIVEN a signing request in `awaitingSigners` state
  WHEN the requester cancels it
  THEN the request SHALL move to `cancelled`, every `pending`/`viewed` invitation SHALL be invalidated, and clicking any invitation link SHALL show a "request cancelled" page rather than starting a QTSP ceremony.
- GIVEN a signing request whose `expiresAt` is now past
  WHEN the expiry background job runs
  THEN the request SHALL move to `expired` and outstanding invitations SHALL be invalidated.

### REQ-SIG-010 — Verification refresh and validity reporting

The system SHALL re-verify every completed signature artefact on a configurable schedule (default monthly), SHALL update `verificationStatus`, and SHALL alert via the docudesk notification channel when a signature drops from `valid-ltv` to `unknown` or `invalid`.

- GIVEN a completed artefact with `verificationStatus=valid-ltv`
  WHEN the verification refresh job runs and the embedded chain still verifies
  THEN `verificationStatus` SHALL remain `valid-ltv` and the refresh timestamp SHALL be updated.
- GIVEN a completed artefact whose chain no longer verifies (e.g. embedded OCSP cannot be revalidated because the issuing CA's certificate has expired and LTV refresh failed)
  WHEN the verification refresh runs
  THEN `verificationStatus` SHALL move to `unknown` or `invalid` per the failure mode and a notification SHALL be dispatched to the request's owner with the failure detail.

## Standards & Sources

The legal anchor is Regulation (EU) 910/2014 (eIDAS) and its implementing acts, in particular Commission Implementing Decision (EU) 2015/1506 on the formats of advanced electronic signatures recognised by public-sector bodies. Article 25(2) is the basis for "QES is legally equivalent to a handwritten signature"; Article 25(3) requires cross-border recognition. The Dutch implementation lives in the Uitvoeringswet eIDAS-verordening and the Algemene wet bestuursrecht as amended for electronic signatures.

The technical signature formats are defined by ETSI:
- ETSI EN 319 142 (PAdES — PDF Advanced Electronic Signatures) for PDFs; the B-LTA profile is the LTV-enabled profile required for long-term validity.
- ETSI EN 319 132 (XAdES — XML Advanced Electronic Signatures) for XML payloads; B-LTA is again the LTV profile.
- ETSI EN 319 122 (CAdES — CMS Advanced Electronic Signatures) for opaque binary content; B-LTA for LTV.
- ETSI EN 319 102-1 is the umbrella signature-creation-and-validation procedure document.
- ETSI EN 319 401 (general TSP policy) and ETSI EN 319 411-2 (qualified-certificate-specific TSP policy) describe the QTSP obligations the docudesk adapter relies on.
- ETSI EN 319 422 specifies qualified timestamping.

QSCD (Qualified Signature Creation Device) requirements follow Annex II of eIDAS plus EN 419 241-2 (remote-QSCD requirements), which is the relevant standard since modern cloud-signing relies on a server-side HSM operated by the QTSP rather than a smart card in the signer's hand. EN 419 211 covers smart-card QSCDs for the cases where a customer still uses one.

LoA (Levels of Assurance — Low, Substantial, High) follow Commission Implementing Regulation (EU) 2015/1502. For QES, the certificate-issuance identity proofing must be at Substantial or High; the signer-authentication-at-signing requirement is typically captured per QTSP and is reflected in the `signerLoa` field on the invitation.

The four shipped adapters target the four QTSPs that dominate the Dutch/Benelux market: QuoVadis (Belgian-headquartered, widely used in Dutch enterprise), KPN ID (Dutch telco's identity service), Digidentity (Dutch QTSP with strong public-sector footprint), and Connectis (Dutch identity broker also operating QTSP services). Each publishes a remote-signing API; the adapter layer normalises authentication, signing-session orchestration, and certificate-attestation handling.

The qualified-trust-list source for verification is the EU Trusted List browser (`webgate.ec.europa.eu/tl-browser/`) which publishes the national trust lists per member state — docudesk's LTV refresh job consumes the Dutch TSL plus any neighbouring TSLs configured by the operator to cover cross-border signers.

Audit-log immutability and qualified-timestamping of audit events follow ISO/IEC 27001 controls 8.15 (logging) and 8.17 (clock synchronisation), and align with the NEN-7510 controls for healthcare-context deployments.

## Cross-app integration

- **docudesk base**: signing requests are attached to a specific document version; on completion the signed PDF (with embedded PAdES container) is stored as a new docudesk file version with the artefact metadata linked to it. Document-edit operations are blocked while a signing request is live (see REQ-SIG-002 — edits invalidate the request).
- **openconnector**: every QTSP integration is a `Source` in OpenConnector; credentials and base URLs live in the OpenConnector credential vault, not in docudesk config. The four shipped adapters ship as OpenConnector source templates with documented config schemas. Webhook callbacks from QTSPs are received via OpenConnector's inbound endpoint and dispatched to the docudesk signing-orchestration handler.
- **openregister**: signing requests, invitations, artefacts, audit events, and QTSP configurations all live in the `docudesk_signatures` register so they inherit ACL, audit, retention, search, and the computed-fields capability (e.g. `verificationStatus` is recomputed by the refresh job and surfaced consistently).
- **tmlo-mdto-metadata** (sibling brief): a completed signature artefact appears as a `relatie` of type `hasSignature` on the document's TMLO/MDTO metadata, so the archival record carries the signature provenance and the LTV-extension schedule respects the document's bewaartermijn.
- **widget-alerting** (mydash brief): a dashboard widget showing "signatures pending > 7 days" or "signatures failing verification" can attach an alert rule that pings the legal/contracts on-call channel; no new docudesk API needed.
- **AI Chat Companion (ADR-034)**: the companion MAY help a requester construct a signing request ("send this to Alice as gemeentesecretaris and Bob as wethouder, both at LoA Substantial") but SHALL NOT initiate the QSCD ceremony — the signer's authentication is by definition outside the LLM's reach.

## Target users

- **Bestuurders / wethouders / gemeentesecretarissen** sign documents through the QTSP-provided strong-authentication ceremony from the docudesk UI; the embedded/redirect flow is configured per QTSP so they see a familiar branded experience.
- **Juristen / contractmanagers** create the signing requests, select signers, pick the assurance level, and own the audit-log review when something goes wrong; the preview-and-confirm step before invitation dispatch is built for their workflow.
- **Inwoners and external counterparties** receive invitations by email and complete the signing ceremony using their personal eID (DigiD-Substantieel, itsme, eHerkenning, eIDAS-notified national eID); they do not need a docudesk account.
- **Compliance officers and auditors** consult the immutable audit log and the verification-refresh history to demonstrate signature validity at any point during retention; the LTV refresh schedule plus alerting on verification regressions gives them confidence that signatures stay valid for the long haul.
- **DIV / archief-medewerkers** treat the signed document version as the archival record; the artefact metadata is part of the TMLO/MDTO record and travels with it to the e-Depot at the end of retention.
- **Platform administrators** configure which QTSPs are enabled on the instance, which is the default, and which signature format is the default per document-type; per-QTSP credentials live in OpenConnector and rotate independently.
