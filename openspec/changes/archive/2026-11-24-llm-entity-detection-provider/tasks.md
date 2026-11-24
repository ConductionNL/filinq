# Tasks: llm-entity-detection-provider

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Provider seam

- [x] 1.1 Define `lib/Service/Detection/DetectionProviderInterface.php` + `DetectionResult` and `DefaultDetectionProvider.php` (REQ-DDLED-001)
  - Interface `detect(fileId, text, entityTypes): DetectionResult`, `id()`, `isAvailable()`; `DetectionResult` carries `entities`, `providerUsed`, `fellBack`, `warning`. `DefaultDetectionProvider` delegates to OR's existing detection with no behaviour change; it is the fallback target.

- [x] 1.2 Implement `DetectionProviderResolver.php` per-organisation selection (REQ-DDLED-002)
  - Read `filinq.detection.provider` as a per-org overlay (default `default`); OR's global method setting untouched; single chokepoint for provider choice and fallback.

## 2. LLM provider (hermiq, local)

- [x] 2.1 Implement `LlmDetectionProvider.php` calling the configured local hermiq agentflow (REQ-DDLED-003)
  - POST text + agentflow ref to `filinq.detection.hermiq_endpoint`; read `state.entities[]`; `require_local` (default true) refuses non-loopback/private hosts; `isAvailable()` false when unconfigured/unreachable so the resolver fails closed.

- [x] 2.2 Map LLM output into OR's entity/confidence shape (REQ-DDLED-004)
  - Type mapped to OR constants where known (unknown kept verbatim), value verbatim, positions verbatim (position-less stored too), score clamped 0..1 (missing→default), `detectionMethod=llm`, category derived; written via OR `EntityRelationMapper`.

## 3. Honest fallback + wiring

- [x] 3.1 Implement fallback-with-warning in the resolver (REQ-DDLED-005)
  - On unavailable/throw/timeout/malformed LLM output, run `DefaultDetectionProvider`, set `fellBack=true`, `providerUsed=default`, and an explicit warning; never a silent downgrade.

- [x] 3.2 Thread the resolver + `DetectionResult` into `AnonymizationService::extractAndDetectEntities` and the report (REQ-DDLED-005)
  - Provider-used and fallback warning appear in the returned payload and the anonymisation report so the review UI shows which engine ran.
  - Done: the extract payload carries `detectionProvider` / `detectionFellBack` / `detectionWarning`; the anonymisation store copies them onto the queue entry and the review sidebar shows the fallback warning (or notes a language-model run).

## 4. Config + UI

- [x] 4.1 `lib/Controller/DetectionProviderSettingsController.php` + `api/detection-provider/*` config routes (REQ-DDLED-002)
  - Get/set per-org provider + hermiq endpoint/agentflow + `require_local`; test-connectivity action; fail-closed admin auth (explicit attributes + in-method guard); no secret stored in v1.

- [x] 4.2 Admin settings view in the Manifest-V2 shell (REQ-DDLED-006)
  - Provider picker (`NcSelect` with `inputLabel`), endpoint/agentflow fields, require-local toggle, test button; registered in `src/manifest.json` + `registry.js` (not `src/router/index.js`); NL Design tokens.
  - Done as `src/views/settings/DetectionProviderSettings.vue`, a section of filinq's admin settings page (`Settings.vue`, admin only) beside the other admin-endpoint sections. Admin settings are rendered by Nextcloud's settings framework, not as an in-app manifest route, so it is not a manifest page; it is not in `src/router/index.js` either.

## 5. Quality

- [x] 5.1 PHPUnit unit tests: resolver selection + fallback (unavailable/throw/malformed → default + warning), LLM→OR mapping (types/positions/score clamp), `require_local` refusal — minimum 75% coverage on new code
  - Provider seam is fully unit-testable with a faked hermiq client; run in the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.

- [x] 5.2 Playwright e2e `tests/e2e/spec-coverage/llm-entity-detection-provider.spec.ts` covering the `@e2e` scenarios
  - Switch an org to the LLM provider, run detection with the endpoint unreachable, assert the report shows the explicit fallback warning and default entities; nldesign accessibility pass; test through the UI.
  - Written, runs in nightly (UNBLOCK rule, 24 Nov 2026): the spec is in the tree and runs against a live instance in the nightly; not verified here.

- [x] 5.3 i18n EN + NL for settings + the fallback warning banner, and documentation `docs/features/llm-entity-detection-provider.md` (ADR-010); run `openspec validate llm-entity-detection-provider --strict`
  - Keys in English; docs cover on-prem/no-cloud positioning, the honest-fallback rule, and the hermiq endpoint dependency.
  - Partly: EN + NL keys for the settings section, the review notice and the server-side fallback warning (`l10n/en.*`, `l10n/nl.*`), and `docs/features/llm-entity-detection-provider.md`. `openspec validate llm-entity-detection-provider --strict` (OpenSpec CLI via npx) reports no errors and five warnings that fail strict mode: text above the first `##` section of the spec delta, and four requirement texts over 500 characters (REQ-DDLED-003, -004, -005, -006). Those are spec-wording edits for the change's author, so this box stays open.
  - Built (c6): the spec delta reworded without changing an obligation: the `status` front matter moved to `proposal.md`, and REQ-DDLED-003 to -006 tightened under 500 characters, with the availability duty of -003 moved into its own scenario ("An unconfigured or unreachable provider reports unavailable", covered by `LlmDetectionProviderTest::testUnconfiguredAndRequireLocalOff` and `::testConnectionReportsReachability`). `openspec validate llm-entity-detection-provider --strict` now reports the change valid.
