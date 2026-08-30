# Tasks: portal-signing-actions

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12.
     Acceptance criteria are plain bullets, not checkboxes. -->

## Prerequisites (apply-blockers)

- [ ] **BLOCKED (external, go-live only)**: Confirm the portaliq A6 amendment that forwards the resolved `signerEmail` scope claim in the `X-Portal-Subject` assertion (design.md Open Question 1, option a) is landed — OR the subjectRef→email callback (option b). The Filinq receiver fails closed `403` without a signer-identifying claim, so this gates go-live, not authoring — the receiver, verifier and entrypoint below are fully implemented and unit-tested against the frozen contract as specified.
- [x] Confirm the shared-secret sourcing (design.md Open Question 3): the verifier reads portaliq's `jwt_signing_secret` instance secret server-side (`IConfig::getAppValue('portaliq', 'jwt_signing_secret', '')`, falling back to `getSystemValue('secret', ...)`); fails closed `401` when unset or < 16 chars — unit-tested (`testUnusableSecretFailsClosed`, `testAppConfigSecretTakesPrecedence`, `testInstanceSecretFallback`).

## Implementation

- [x] Extend `lib/Portal/PortalContributionProvider.php` — signer manifest actions (REQ-DDPSA-001)
  - Added exactly `sign` (POST), `decline` (POST), `viewDocument` (GET) as `{id,label,endpoint,method,minTrust:'substantial'}`; endpoints are instance-local relative paths under `/apps/filinq/api/portal/signing/`. Class stays plain/dependency-free; `data-subject` `actions` stays `[]`. EUPL-1.2/SPDX docblock + `@spec` tags.

- [x] Ship `lib/Portal/PortalAssertionVerifier.php` (REQ-DDPSA-002)
  - Verifies HS256 vs the portaliq-managed shared secret; accepts `alg == HS256` ONLY (defeats `none`/alg-confusion); requires `iss=portaliq`, `use=assertion`, unexpired `exp`, and the frozen claim set; returns the derived claims or fails closed. No client input, no `Authorization` header.

- [x] Ship `lib/Controller/PortalSigningReceiverController.php` (REQ-DDPSA-003/004/006/007)
  - Three `#[PublicPage]` + `#[NoCSRFRequired]` routes; derives identity server-side (audience==signer, trust>=substantial, `signerEmail` from the verified assertion — never body); rejects a `signingRequestId` that is a URL/path (SSRF); resolves the invited `signerRecord` (email+signingRequestId) via OpenRegister; uniform not-authorised (no existence oracle); fails closed 401/403/404/502; never echoes raw exception text.

- [x] Add the verified-actor entrypoint to `lib/Service/SigningService.php` (REQ-DDPSA-005)
  - Accepts an already-resolved verified external `signerRecord` as actor; asserts against `signer['email']` instead of the Nextcloud uid; keeps the belongs-to-request C4 check, terminal-state status machine, MAC binding and audit write UNCHANGED; default (no actor) behaves exactly as before (byte-identical to the pre-change behaviour).

- [x] Wire `sign`/`decline`/`viewDocument` through the honest primitive + audit (REQ-DDPSA-005/006)
  - `sign`/`decline` call `SigningService` with the resolved actor; `decline` carries the client `reason`; `viewDocument` returns `{documentName,mimeType,contentBase64}` from `signingRequest.documentFileId`, IDOR-scoped identically; each act writes a signing-audit entry recording the portal email (namespaced `portal:<email>`) + assertion `jti`.

- [x] Register the three routes in `appinfo/routes.php`
  - Paths exactly match the manifest endpoints; each controller method carries `#[PublicPage]` + `#[NoCSRFRequired]`.

## Testing & quality

- [x] Unit tests `tests/unit/Portal/PortalContributionProviderTest.php` (extend)
  - Pins the three signer actions (ids, methods, `minTrust`, relative endpoints); asserts `data-subject` `actions` stays empty; register-drift pin covers `documentFileId`.

- [x] Unit tests `tests/unit/Portal/PortalAssertionVerifierTest.php` + `tests/unit/Controller/PortalSigningReceiverControllerTest.php`
  - Fail-closed matrix: missing/expired/wrong-`alg`/wrong-`iss`/wrong-`use`/bad-sig assertion → 401; no secret → 401; wrong audience / low trust / no signer claim → 403; cross-signer + non-existent request → identical result, `SigningService` never called; URL `signingRequestId` rejected; happy-path sign/decline/viewDocument; downstream failure → 502.

- [ ] **NOT DONE**: Newman receiver contract `tests/newman/portal-signing-actions.postman_collection.json` — no Newman collection was authored in this pass.

- [x] Pass the quality gates (re-confirmed 2026-07-24 after a second merge of origin/development, baseline = origin/development @ 6b4f69d5): `php -l` clean; PHPCS 0 errors fleet-wide (183 warnings vs baseline 196 — fewer, zero new) on every touched file; the full unit suite is green — **1053/1053** in the `nextcloud:34.0.0-apache` container vs baseline **998/998** (+55, zero regressions); PHPStan **2 pre-existing unrelated errors** identical to baseline; Psalm **1 pre-existing unrelated error** identical to baseline; route-auth/route-reachability satisfied (`#[PublicPage]`/`#[NoCSRFRequired]` on all 3 routes, routes.php entries match controller methods).

- [x] `openspec validate --type change portal-signing-actions --strict` → **valid** (re-run 2026-07-23).
- [ ] **NOT DONE**: closing Conduction/filinq#160 requires live-verify evidence — the A6 `signerEmail` claim forward from portaliq is still not landed (go-live blocker, not an authoring blocker; see Prerequisites above). The issue was NOT closed.

## Quality checklist

- Every MUST in the spec has a unit or Newman test; the fail-closed matrix and the no-cross-signer-IDOR guard are explicitly asserted (`SigningService` never called on a non-authorised path).
- Manifest labels ship in English source (i18n policy); portaliq owns portal-side translation.
- No register JSON change — every referenced property (`signerRecord.email/signingRequestId`, `signingRequest.documentFileId`) verified against HEAD.
- No Filinq UI ships (the signing frontend is portaliq's SPA) — no Playwright; receiver covered by PHPUnit + Newman.
- `openspec validate portal-signing-actions --strict` passes.
