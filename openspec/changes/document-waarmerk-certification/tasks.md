# Tasks: document-waarmerk-certification

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 14.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & data model

- [ ] 1.1 Add the `certification` register + `waarmerk` schema to `lib/Settings/filinq_register.json` (fields per design.md: document refs, `documentHash`, `sealType` enum, `cmsSignature`, certificate fingerprint/subject, `verificationCode`, `sealedAt`/`sealedBy`, `status` enum, revocation fields, optional `anonymizationLinkId` relation)
  - Additive import on boot; `tests/validate-manifest.js` passes; NO key material fields anywhere (ADR-064)

- [ ] 1.2 Seed data: demo `waarmerk` object (nil-UUID, Demostad, placeholder CMS/fingerprint per design.md Seed Data); test bootstrap generates a throwaway self-signed org certificate at runtime — no PEM/key fixture committed

## 2. Backend

- [ ] 2.1 Custody resolver + admin settings: certificate PEM storage (public material), fingerprint/expiry display, `credentialRef` storage; broker resolution with interim `ICrypto` local-custody mode behind the same interface, labelled in UI; expired-cert seal refusal; key never persisted or logged (REQ-DDWMK-001)

- [ ] 2.2 `WaarmerkService::seal()`: stamp-page composition via `PdfService` (Twig template, brandable) + local QR SVG, canonicalised sha256 over sealed artifact, detached CMS via `openssl_cms_sign` (pkcs7 fallback), write-once `waarmerk` record via ObjectService (REQ-DDWMK-002)
  - Stamp appended as final page only; fail-closed on unresolvable key (503, no artifact)

- [ ] 2.3 `ProcessingCertificateService`: anonymization certificate PDF from `anonymizationLink` + run entity-type counts + backend id + acting user; types/counts only — never entity values; sealed with `sealType: processing-certificate` + `anonymizationLinkId` (REQ-DDWMK-003)

- [ ] 2.4 Verification: `POST /api/waarmerk/verify` (`{code, sha256}` → `valid|hash-mismatch|revoked|unknown`, CMS validation via `openssl_cms_verify`, fail-closed, oracle-free response parity, rate-limited) and revocation endpoint with in-method admin-or-sealer guard, reason mandatory, irreversible (REQ-DDWMK-004/005)

## 3. Routes & controller

- [ ] 3.1 `WaarmerkController` + routes in `appinfo/routes.php`: seal, list/show, revoke (authenticated, explicit attributes); `GET /verify/{code}` page + `POST /api/waarmerk/verify` as `#[PublicPage]` with brute-force protection
  - route-auth/semantic-auth/no-admin-idor gates pass; public endpoints serve no document content

## 4. Frontend (ADR-012)

- [ ] 4.1 Document surfaces: "Waarmerk toevoegen" action + waarmerk badge on My Documents/anonymization results; anonymization-certificate action on runs; dialogs in `src/modals/`

- [ ] 4.2 Public verification page: standalone entry, client-side WebCrypto hash of a locally selected file (bytes never uploaded), status rendering incl. `certificateExpired` advisory; NL Design System tokens, WCAG AA (citizen-facing)

- [ ] 4.3 Admin settings panel (settings framework, NOT vue-router): PEM upload, fingerprint/expiry, `credentialRef`, custody-mode label, test-seal button

## 5. Quality, i18n, docs

- [ ] 5.1 Unit tests ≥75% on new code (seal/verify round-trip with runtime-generated cert, tamper detection, revocation dominance, write-once invariant, oracle parity, key-never-logged assertion); run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`

- [ ] 5.2 Playwright e2e `tests/e2e/spec-coverage/document-waarmerk-certification.spec.ts` (seal → QR page → verify valid / tampered / revoked); verify on Postgres (8080); test with nldesign theme enabled

- [ ] 5.3 i18n EN source + NL translations for all new strings, incl. the public page (citizen-facing Dutch first-class)

- [ ] 5.4 Docs in `docs/features/` (waarmerk, processing certificate, verification; explicit "organisation seal, not qualified seal" wording) with Playwright screenshots (ADR-010); `openspec validate document-waarmerk-certification --strict` passes

## Quality checklist

- ADR-064: no secret in schema/appconfig/logs — reviewer greps for PEM headers and `credentialRef` misuse
- Fail-closed verified by mutation-style tests (flip hash byte, strip CMS, revoke) — never `valid`
- `composer check:strict` green; hydra gates pass; end-to-end verified against OpenRegister on Postgres dev instance
