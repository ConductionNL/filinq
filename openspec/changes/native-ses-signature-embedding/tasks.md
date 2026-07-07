# Tasks: native-ses-signature-embedding

Closes `ConductionNL/docudesk#304`. Adds the missing native SES artifact writer + provider↔request
wiring so a completed request yields a verifiable, stored signed document, and corrects the
documented readiness. Reuses the existing `SigningVerificationService` marker/HMAC contract as the
acceptance oracle. Unit tests run inside the Nextcloud container (per repo convention).

## [docudesk] Native SES artifact writer

### T-1. Embed the `/DocuDesk-Signature` marker (M)

- [ ] T-1.1 Implement, in `NativeSigningProvider`, production of a signed PDF that embeds a
  `/DocuDesk-Signature(base64-json)` marker binding signer identity, timestamp, and IP, with the
  `mac` field set to `hash_hmac('sha256', sha256(pdf-without-mac), signing_verification_secret)` —
  the exact assertion `SigningVerificationService::verifyAssertion()` validates. Remove the
  `#304` interim throw once the writer is in place.
  - **Acceptance:** the produced bytes pass `SigningVerificationService::verifyDocument()`
    (`valid: true`); when `signing_verification_secret` is unset the writer fails the
    honest-completion gate (T-3) instead of emitting an unverifiable artifact.
- [ ] T-1.2 Route non-PDF or encrypted inputs through the existing `PdfConversionService` /
  `DocumentValidationService` `pdf-encrypted` check before embedding; reject what cannot be made
  writeable rather than silently skipping the marker.
  - **Acceptance:** an encrypted or non-convertible input fails loudly; a convertible input is
    converted then signed.

## [docudesk] Wire the provider into completion

### T-2. Produce + store the artifact on the completing signature (M)

- [ ] T-2.1 In `SigningService`, on the signature that transitions a request to COMPLETED, resolve
  the active provider via `SigningProviderFactory`, obtain the signed bytes, and store them as a
  **new Nextcloud file version** of the original document (via `files_versions`).
  - **Acceptance:** a completed native SES request results in a new file version whose bytes verify.
- [ ] T-2.2 In `updateRequestStatus()`, set `signedDocumentRef` to the stored artifact reference
  (file id + version), **never** the original `documentFileId`. The `SigningConcludedEvent`
  inherits this truthful reference (contract otherwise unchanged).
  - **Acceptance:** `signedDocumentRef` on a completed request points at the signed version; a
    delegated request's `SigningConcludedEvent.signedDocumentRef` matches.

## [docudesk] Honest-completion gate

### T-3. Fail loudly when no artifact can be produced (S)

- [ ] T-3.1 Ensure that when the active provider cannot produce an artifact (native writer
  unavailable, secret unset, or external provider unconfigured/stubbed), the completing signature
  fails with a descriptive error and the request is NOT marked COMPLETED with a signed reference;
  `signedDocumentRef` stays null/flagged.
  - **Acceptance:** with the native writer disabled the request does not falsely complete; with a
    stubbed ValidSign provider a QES request does not present the original as signed.

## [docudesk] Documentation

### T-4. Correct the documented readiness (S)

- [ ] T-4.1 In `docs/GOVERNMENT-FEATURES.md`, adjust F-13 so it presents the signing workflow +
  audit trail as available and signature embedding as in progress (reference `#304`) rather than a
  bare "Beschikbaar".
- [ ] T-4.2 In `docs/features.json`, adjust the signing summary so it no longer states signature
  embedding / "legally meaningful signature process" is complete while the writer is pending.
  - **Acceptance:** neither doc implies embedding is done while `#304` is open.

## [docudesk] Verify

### T-5. Tests + validate (M)

- [ ] T-5.1 PHPUnit: a native SES request whose signers all sign produces a stored artifact that
  `SigningVerificationService::verifyDocument()` reports valid; `signedDocumentRef` references the
  artifact; the honest-completion gate holds when the writer/secret is unavailable. Run inside the
  container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
- [ ] T-5.2 `openspec validate native-ses-signature-embedding --strict` exits 0.
- [ ] T-5.3 `php -l` passes on every changed PHP file; self-check the key hydra gates (SPDX headers,
  no forbidden debug helpers, no stubs left in the native provider, notification dialect unchanged).
  No new user-facing English l10n key beyond any required signing-error string (add EN + NL if so).
