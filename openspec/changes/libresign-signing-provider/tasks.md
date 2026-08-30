# Tasks: libresign-signing-provider

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Provider implementation

- [ ] 1.1 Implement `lib/Service/Signing/LibreSignProvider.php` implementing `SigningProviderInterface` (REQ-DDLSP-003, REQ-DDLSP-004)
  - `getIdentifier() = 'libresign'`; delegate initiate/status/download/cancel to a lazy `LibreSignClient` seam (design.md D3); `produceSignedArtifact()`/`downloadSignedDocument()` throw when LibreSign has not produced a verifiable signed file (honest-completion gate, D5) — never return the unsigned original.

- [ ] 1.2 Implement honest capability mapping `supportsLevel()` (REQ-DDLSP-002)
  - Config-driven (`filinq.libresign_qualified`, default false): true for SES/AdES always, QES only when qualified; `produceSignedArtifact()`/delegation re-checks `supportsLevel()` and throws on an unsupported level so no fallback can launder SES into a higher claim.

- [ ] 1.3 Implement the `LibreSignClient` seam + concrete OCS calls (REQ-DDLSP-003)
  - `interface LibreSignClient` (createRequest/getStatus/downloadSigned/validate) with one concrete impl (openconnector source or `IClientService`), resolved lazily so Filinq loads without LibreSign; endpoint paths confirmed against LibreSign's documented OCS API (RESOLVE the design.md Open Question before apply).

## 2. Registration + config

- [ ] 2.1 Register `libresign` in `SigningProviderFactory` behind an availability gate (REQ-DDLSP-001)
  - Register only when `IAppManager` reports LibreSign installed+enabled; absent → not in `getAvailableProviders()`, `getProvider('libresign')` throws; `signing_provider=libresign` while LibreSign is absent MUST fail closed with an explanatory error, never silently return native.

- [ ] 2.2 Admin settings: LibreSign section (REQ-DDLSP-001, REQ-DDLSP-002)
  - Provider selectable only when available; `libresign_qualified` toggle (default off) gating QES advertisement; LibreSign endpoint/source config; no signing keys stored by Filinq.

## 3. Round-trip

- [ ] 3.1 Artifact + audit round-trip into OpenRegister (REQ-DDLSP-004)
  - Signed PDF stored as a new document version via the existing OR-backed completion path; every provider action recorded through `SigningAuditService` → OR hash-chained `AuditTrailMapper`; `signingSession` carries `provider: libresign` + LibreSign `externalId`; no Filinq-local audit store.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for `LibreSignProvider` and the factory gate (REQ-DDLSP-001, REQ-DDLSP-002, REQ-DDLSP-004)
  - Availability gate (present/absent/configured-but-absent fail-closed); `supportsLevel` matrix incl. QES off-by-default; unsupported-level throws (no laundering); honest-completion throw on incomplete LibreSign result; audit written on each action — via fake `LibreSignClient`+`IAppManager`; min 75% on new code; run in the container (`docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`).

- [ ] 4.2 Playwright e2e `tests/e2e/spec-coverage/libresign-signing-provider.spec.ts` (REQ-DDLSP-001)
  - Provider appears in the signing picker only when LibreSign is enabled (gated by instance state); test through the UI; `@e2e exclude` the certificate-signing round-trip where no LibreSign instance is available, covered by PHPUnit.

- [ ] 4.3 i18n (EN + NL) for the admin section + picker label; docs `docs/features/libresign-signing-provider.md`; run `openspec validate libresign-signing-provider --strict`
  - Keys in English; document the availability gate, the honest supportsLevel/no-laundering rule, the signing-trust-rebuild dependency, the procest reuse, and the resolved LibreSign endpoints.
