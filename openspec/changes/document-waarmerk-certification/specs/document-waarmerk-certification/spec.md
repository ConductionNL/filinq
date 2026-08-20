# document-waarmerk-certification Specification (delta)

---
status: proposed
---

## Purpose

Certify processed/published documents with an organisation certificate — a
cryptographic **waarmerk** (eIDAS electronic-seal concept, Regulation
910/2014 Art. 3(25)/(27): attached by a legal person, distinct from a
personal signature). Covers sealing with a visible mark + QR, processing
certificates as data-minimised evidence artifacts grounded in the recorded
anonymization data, a public fail-closed verification surface, revocation,
and admin-side organisation-certificate management with ADR-064 key custody.

## ADDED Requirements

### Requirement: Organisation certificate is managed in admin settings with credentialRef key custody (REQ-DDWMK-001)

The app MUST provide an admin-settings section to configure the waarmerk
organisation certificate: upload/paste the X.509 certificate (PEM, public
material), display its subject, fingerprint, and expiry, and store a
`credentialRef` reference to the private key. Private key material MUST
NEVER be stored in a register schema, in plain app config, or written to
logs (ADR-064 custody model); the key MUST be resolved through a custody
resolver at seal time only and held only in memory. When no interim local
custody is configured, the resolver MUST use the fleet credential broker via
the stored `credentialRef`; an interim encrypted-at-rest local custody mode
(Nextcloud `ICrypto`) MAY be offered behind the same resolver interface and
MUST be visibly labelled as local custody in the admin UI. Sealing with an
expired certificate MUST be refused.

#### Scenario: Admin configures the organisation certificate

- GIVEN a Nextcloud admin on the DocuDesk admin settings page
- WHEN they upload the organisation certificate PEM and set the private-key `credentialRef`
- THEN the panel shows the certificate subject, fingerprint, and expiry
- AND no private-key material appears in any register object, app-config value, or log line
- @e2e tests/e2e/spec-coverage/document-waarmerk-certification.spec.ts

#### Scenario: Unresolvable key fails sealing closed

- GIVEN a configured certificate whose `credentialRef` cannot be resolved
- WHEN a user attempts to seal a document
- THEN the request fails with a clear service-unavailable error naming key resolution
- AND no unsealed or partially sealed artifact is produced
- @e2e exclude broker-outage fault injection is not browser-drivable; covered by PHPUnit (tests/unit/Service/WaarmerkServiceTest.php)

### Requirement: A processed PDF can be sealed with a waarmerk (REQ-DDWMK-002)

The app MUST let an authenticated user with access to a processed/published
PDF apply a waarmerk via `POST /api/waarmerk/seal`: compose the sealed
artifact by appending a visible stamp page (waarmerk mark, organisation
name, `sealedAt`, verification code, and a QR encoding the public
verification URL — QR generated locally, no external service), compute the
sha256 over the sealed artifact (verification-code field canonicalised), and
sign that hash as a **detached CMS (PKCS#7)** structure with the
organisation certificate via OpenSSL. A `waarmerk` record MUST be stored via
OpenRegister in the new `certification` register carrying the document
references, `documentHash`, `sealType`, the CMS signature, certificate
fingerprint and subject, a high-entropy `verificationCode`, `sealedAt`,
`sealedBy`, and `status: active`. All evidence fields MUST be write-once:
after creation only the revocation fields of REQ-DDWMK-005 may change. The
stamp page MUST be appended as a final page and MUST NOT overlay or alter
existing pages. The UI and documentation MUST describe the waarmerk as an
organisation seal (advanced level at most) and MUST NOT claim a qualified
seal.

#### Scenario: WOO officer seals an anonymized PDF

- GIVEN an anonymized PDF the user can access
- WHEN they trigger "Waarmerk toevoegen" on the document
- THEN a sealed PDF is produced whose final page shows the waarmerk mark, organisation name, verification code, and QR
- AND a `waarmerk` object exists with `status: active`, the sha256 of the sealed artifact, and the detached CMS signature
- @e2e tests/e2e/spec-coverage/document-waarmerk-certification.spec.ts

#### Scenario: Evidence fields are write-once

- GIVEN an existing `waarmerk` record
- WHEN any API attempts to modify `documentHash`, `cmsSignature`, `verificationCode`, or `sealedAt`
- THEN the modification is rejected
- AND the stored evidence fields are byte-identical to their values at creation
- @e2e exclude immutability contract is an API/service invariant; covered by PHPUnit (tests/unit/Service/WaarmerkServiceTest.php)

### Requirement: Anonymization runs can be certified with a sealed processing certificate (REQ-DDWMK-003)

The app MUST generate an anonymization **processing certificate** as a PDF
evidence artifact from already-recorded data only: the `anonymizationLink`
fields (source and anonymized file name/path, `outputFormat`,
`replacementCount`, `runCount`, `anonymizedAt`, `anonymizedBy`), the
per-entity-type detection/redaction counts of the run, and the configured
detection-backend identifier. The certificate MUST contain entity **types
and counts only — never detected entity values or document text** (AVG Art.
5(1)(c) data minimisation; Art. 5(2) accountability). The certificate PDF
MUST itself be sealed through REQ-DDWMK-002 with `sealType:
processing-certificate` and the `waarmerk` record MUST reference the
`anonymizationLinkId`. Nothing SHALL be re-derived from document content at
certificate time — the certificate attests the recorded run.

#### Scenario: Processing certificate for an anonymization run

- GIVEN a completed anonymization with an `anonymizationLink` record
- WHEN the user requests an anonymization certificate for it
- THEN a sealed certificate PDF is produced stating what was detected/redacted per entity type (counts), when, by which backend, and by whom
- AND the certificate contains no entity values and no document text
- AND its `waarmerk` record carries `sealType: processing-certificate` and the `anonymizationLinkId`
- @e2e tests/e2e/spec-coverage/document-waarmerk-certification.spec.ts

### Requirement: A waarmerk is publicly verifiable, fail-closed (REQ-DDWMK-004)

The app MUST expose a public verification surface requiring no Nextcloud
account: `GET /verify/{code}` (human page showing status, organisation,
seal timestamp, and document name only) and `POST /api/waarmerk/verify`
accepting `{code, sha256}` where the sha256 is computed by the verifier —
the verification page MUST compute it client-side (WebCrypto) from a
locally selected file so document bytes never leave the verifier's machine.
The server MUST compare the submitted hash with the recorded
`documentHash` AND validate the stored CMS signature against the stored
certificate, returning exactly one of `valid`, `hash-mismatch`, `revoked`,
`unknown`. Verification MUST be fail-closed: any unverifiable state
(unknown code, broken CMS, certificate mismatch) MUST NOT report `valid`.
Public endpoints MUST be rate-limited against brute force; verification
codes MUST be high-entropy and non-enumerable, and response shape for
non-matching codes MUST NOT act as an existence oracle. The page MUST serve
no document content and offer no file download. A seal made while the
certificate was valid but verified after its expiry MUST report `valid`
with a `certificateExpired: true` advisory.

#### Scenario: Citizen verifies a sealed document via the QR

- GIVEN a citizen holding a sealed PDF and scanning its QR code
- WHEN they open the verification page and select their local copy of the file
- THEN the page computes the hash locally and reports `valid` with the organisation name and seal timestamp
- AND the file is not uploaded to the server
- @e2e tests/e2e/spec-coverage/document-waarmerk-certification.spec.ts

#### Scenario: Tampered document is detected

- GIVEN a sealed document whose bytes were modified after sealing
- WHEN it is verified against its waarmerk code
- THEN the result is `hash-mismatch`
- AND the result is never `valid`
- @e2e tests/e2e/spec-coverage/document-waarmerk-certification.spec.ts

#### Scenario: Unknown code yields no oracle

- GIVEN a syntactically plausible but non-existent verification code
- WHEN verification is attempted
- THEN the result is `unknown` with a response shape identical to other non-valid outcomes
- AND repeated attempts are rate-limited
- @e2e exclude timing/oracle parity and throttling assertions are not reliably browser-testable; covered by PHPUnit (tests/unit/Controller/WaarmerkControllerTest.php)

### Requirement: A waarmerk can be revoked and revocation dominates verification (REQ-DDWMK-005)

The app MUST allow revoking a waarmerk via `POST /api/waarmerk/{id}/revoke`
with a mandatory reason. Revocation MUST be restricted by an in-method
authorization guard to admins or the original sealer. Revocation sets only
`status: revoked`, `revokedAt`, `revokedBy`, `revocationReason`; every
other field stays immutable. Verification of a revoked waarmerk MUST return
`revoked` regardless of hash match and MUST NEVER return `valid`.
Revocation MUST be irreversible via the API.

#### Scenario: Revoked seal never verifies as valid

- GIVEN a waarmerk revoked with reason "document withdrawn after objection"
- WHEN the (unmodified) sealed document is verified with the correct code and hash
- THEN the result is `revoked`
- AND the human page shows the revocation date but not internal notes beyond the recorded reason
- @e2e tests/e2e/spec-coverage/document-waarmerk-certification.spec.ts

#### Scenario: Non-sealer non-admin cannot revoke

- GIVEN an authenticated user who is neither an admin nor the sealer of a waarmerk
- WHEN they call the revoke endpoint for it
- THEN the request is rejected with 403
- AND the waarmerk remains `active`
- @e2e exclude authorization-guard negative path; covered by PHPUnit (tests/unit/Controller/WaarmerkControllerTest.php)
