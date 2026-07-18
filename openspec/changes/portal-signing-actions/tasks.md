# Tasks: portal-signing-actions

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12.
     Acceptance criteria are plain bullets, not checkboxes. -->

## Prerequisites (apply-blockers)

- [ ] Confirm the portaliq A6 amendment that forwards the resolved `signerEmail` scope claim in the `X-Portal-Subject` assertion (design.md Open Question 1, option a) is landed — OR the subjectRef→email callback (option b). The DocuDesk receiver fails closed `403` without a signer-identifying claim, so this gates go-live, not authoring.
- [ ] Confirm the shared-secret sourcing (design.md Open Question 3): the verifier reads portaliq's `jwt_signing_secret` instance secret server-side; fail closed `401` when unset.

## Implementation

- [ ] Extend `lib/Portal/PortalContributionProvider.php` — signer manifest actions (REQ-DDPSA-001)
  - Add exactly `sign` (POST), `decline` (POST), `viewDocument` (GET) as `{id,label,endpoint,method,minTrust:'substantial'}`; endpoints are instance-local relative paths under `/apps/docudesk/api/portal/signing/`. Keep the class plain/dependency-free; `data-subject` `actions` stays `[]`. EUPL-1.2/SPDX docblock + `@spec` tags.

- [ ] Ship `lib/Portal/PortalAssertionVerifier.php` (REQ-DDPSA-002)
  - Verify HS256 vs the portaliq-managed shared secret; accept `alg == HS256` ONLY (defeat `none`/alg-confusion); require `iss=portaliq`, `use=assertion`, unexpired `exp`, and the frozen claim set; return the derived claims or fail closed. No client input, no `Authorization` header.

- [ ] Ship `lib/Controller/PortalSigningReceiverController.php` (REQ-DDPSA-003/004/006/007)
  - Three `#[PublicPage]` + `#[NoCSRFRequired]` routes; derive identity server-side (audience==signer, trust>=substantial, `signerEmail` from the verified assertion — never body); reject a `signingRequestId` that is a URL/path (SSRF); resolve the invited `signerRecord` (email+signingRequestId) via OpenRegister; uniform not-authorised (no existence oracle); fail closed 401/403/404/502; never echo raw exception text.

- [ ] Add the verified-actor entrypoint to `lib/Service/SigningService.php` (REQ-DDPSA-005)
  - Accept an already-resolved verified external `signerRecord` as actor; assert against `signer['email']` instead of the Nextcloud uid; keep the belongs-to-request C4 check, terminal-state status machine, MAC binding and audit write UNCHANGED; default (no actor) behaves exactly as today.

- [ ] Wire `sign`/`decline`/`viewDocument` through the honest primitive + audit (REQ-DDPSA-005/006)
  - `sign`/`decline` call `SigningService` with the resolved actor; `decline` carries the client `reason`; `viewDocument` returns `{documentName,mimeType,contentBase64}` from `signingRequest.documentFileId`, IDOR-scoped identically; each act writes a signing-audit entry recording the portal email + assertion `jti`.

- [ ] Register the three routes in `appinfo/routes.php`
  - Paths exactly matching the manifest endpoints; each controller method carries the correct auth attributes (route-auth + route-reachability gates).

## Testing & quality

- [ ] Unit tests `tests/unit/Portal/PortalContributionProviderTest.php` (extend)
  - Pin the three signer actions (ids, methods, `minTrust`, relative endpoints); assert `data-subject` `actions` stays empty; register-drift pin covers `documentFileId`.

- [ ] Unit tests `tests/unit/Portal/PortalAssertionVerifierTest.php` + `SigningReceiverControllerTest.php`
  - Fail-closed matrix: missing/expired/wrong-`alg`/wrong-`iss`/wrong-`use`/bad-sig assertion → 401; no secret → 401; wrong audience / low trust / no signer claim → 403; cross-signer + non-existent request → identical result, `SigningService` never called; URL `signingRequestId` rejected; happy-path sign/decline/viewDocument; assertion-as-bearer never a session; downstream failure → 502; audit entry asserted.

- [ ] Newman receiver contract `tests/newman/portal-signing-actions.postman_collection.json`
  - 401 (no/invalid assertion), 403 (not-invited / low trust), happy-path 200 sign+decline+viewDocument, 502 on downstream fault (stubbed).

- [ ] Pass the quality gates
  - `php -l`, `composer phpcs`, `phpstan`, `psalm`, the unit suite (php:8.3-cli / NC container per config.yaml) and the relevant Hydra gates (route-auth, route-reachability, no-admin-idor, security-change-has-tests, spec-coverage) pass with zero new violations.

- [ ] Validate + close the issue
  - `openspec validate portal-signing-actions --strict` exits 0; after apply + live-verify, close Conduction/docudesk#160 with fixing-commit evidence.

## Quality checklist

- Every MUST in the spec has a unit or Newman test; the fail-closed matrix and the no-cross-signer-IDOR guard are explicitly asserted (`SigningService` never called on a non-authorised path).
- Manifest labels ship in English source (i18n policy); portaliq owns portal-side translation.
- No register JSON change — every referenced property (`signerRecord.email/signingRequestId`, `signingRequest.documentFileId`) verified against HEAD.
- No DocuDesk UI ships (the signing frontend is portaliq's SPA) — no Playwright; receiver covered by PHPUnit + Newman.
- `openspec validate portal-signing-actions --strict` passes.
