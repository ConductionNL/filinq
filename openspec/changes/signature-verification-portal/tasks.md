# Tasks: signature-verification-portal

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add the `signatureVerification` schema to the `document` register in `lib/Settings/docudesk_register.json` (REQ-DDSVP-003)
  - Properties per design.md D1 (`token`, `fileRef`, `contentHash`, `signatures[]` with `level`/`method`/`signerAsserted`/`integrityVerified`/`identityBound`, `waarmerkRef`, `createdAt`, `revoked`); register-i18n tags on user-facing strings; register version bump with changelog entry; one nil-token seed (design.md Seed Data); schema refs use slugs.

## 2. Backend

- [ ] 2.1 Implement `SignatureVerificationLinkService` (mint + lookup by token) (REQ-DDSVP-003)
  - High-entropy non-enumerable token (`bin2hex(random_bytes(16))`); mint on signing completion and on waarmerk sealing; store outcome (never the signing secret, never document bytes); OR ObjectService save/find on `signatureVerification`.

- [ ] 2.2 Add a record-based verify path to `SigningVerificationService` (REQ-DDSVP-001, REQ-DDSVP-002)
  - `verifyByRecord(array $record)` returning tri-state per-signature (`verified`/`unverifiable`/`invalid`) with separate `integrityVerified` and `identityBound` flags; no `userId`/file-read dependency; external `/Type /Sig` reported `unverifiable`, not `invalid`.

- [ ] 2.3 Implement `PublicVerificationController` with `verify/{token}` + `api/verify/{token}` as `#[PublicPage]` (REQ-DDSVP-001, REQ-DDSVP-004)
  - `#[PublicPage] #[NoCSRFRequired]` + brute-force/rate-limit keyed by token+IP; unknown/malformed token returns a `status:unknown` body identical in shape to a valid verdict (no existence oracle); serves file name only, no bytes, no download; consumes the waarmerk verification primitive (presence-gated) to include seal status.

- [ ] 2.4 Mint the verification record + stamp the QR on signing/seal completion (REQ-DDSVP-005)
  - Hook `SigningService` completion and the waarmerk seal path to mint a record and compose a footer QR encoding the absolute `verify/{token}` URL into the produced PDF bytes (dependency-free QR per waarmerk D5); a single QR/token when both a signature and a seal exist.

## 3. Frontend

- [ ] 3.1 Public portal page registered in the manifest V2 shell (REQ-DDSVP-001, REQ-DDSVP-002)
  - Register in `src/manifest.json` + `src/registry.js` (NOT `src/router/index.js`); ADR-012 Cn components, NL Design tokens; renders content-integrity vs signer-identity as distinct guarantees with an explicit "not cryptographically bound" badge while `identityBound` is false; tri-state per-signature chips; audit rollup; waarmerk seal status; optional WebCrypto no-upload re-hash of a locally selected file.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests: token non-enumerability, unknown-token non-oracle parity, record-based tri-state verify, honest identity boundary (identity never `verified` while `identityBound` false), presence-gated waarmerk status — min 75% on new code
  - Run in the container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`; include a mutation-style test that a rewritten signer field is NOT presented as verified identity.

- [ ] 4.2 Playwright e2e `tests/e2e/spec-coverage/signature-verification-portal.spec.ts` covering the `@e2e` scenarios anonymously (logged-out browser context) (REQ-DDSVP-001, REQ-DDSVP-004)
  - Scans/opens a QR-linked `verify/{token}`; asserts the honest-trust copy; asserts unknown token shows `unknown` not 404; nldesign-theme accessibility pass; test through the UI.

- [ ] 4.3 Assert the `#[PublicPage]` precondition and rate-limit (REQ-DDSVP-004)
  - Test the public routes are reachable logged-out; assert `info.xml` carries no group restriction (regression guard for the app-group-vs-PublicPage gotcha); assert brute-force throttling engages.

- [ ] 4.4 i18n (EN + NL) for all portal strings incl. the trust-boundary copy; docs `docs/features/signature-verification-portal.md` with MCP screenshots; run `openspec validate signature-verification-portal --strict`
  - Keys in English; document the MAC-defect boundary, the signing-trust-rebuild dependency, the waarmerk consumption, and the LibreSign#2617 positioning.
