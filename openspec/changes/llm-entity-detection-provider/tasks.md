# Tasks: llm-entity-detection-provider

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Provider seam

- [ ] 1.1 Define `lib/Service/Detection/DetectionProviderInterface.php` + `DetectionResult` and `DefaultDetectionProvider.php` (REQ-DDLED-001)
  - Interface `detect(fileId, text, entityTypes): DetectionResult`, `id()`, `isAvailable()`; `DetectionResult` carries `entities`, `providerUsed`, `fellBack`, `warning`. `DefaultDetectionProvider` delegates to OR's existing detection with no behaviour change; it is the fallback target.

- [ ] 1.2 Implement `DetectionProviderResolver.php` per-organisation selection (REQ-DDLED-002)
  - Read `docudesk.detection.provider` as a per-org overlay (default `default`); OR's global method setting untouched; single chokepoint for provider choice and fallback.

## 2. LLM provider (hermiq, local)

- [ ] 2.1 Implement `LlmDetectionProvider.php` calling the configured local hermiq agentflow (REQ-DDLED-003)
  - POST text + agentflow ref to `docudesk.detection.hermiq_endpoint`; read `state.entities[]`; `require_local` (default true) refuses non-loopback/private hosts; `isAvailable()` false when unconfigured/unreachable so the resolver fails closed.

- [ ] 2.2 Map LLM output into OR's entity/confidence shape (REQ-DDLED-004)
  - Type mapped to OR constants where known (unknown kept verbatim), value verbatim, positions verbatim (position-less stored too), score clamped 0..1 (missing→default), `detectionMethod=llm`, category derived; written via OR `EntityRelationMapper`.

## 3. Honest fallback + wiring

- [ ] 3.1 Implement fallback-with-warning in the resolver (REQ-DDLED-005)
  - On unavailable/throw/timeout/malformed LLM output, run `DefaultDetectionProvider`, set `fellBack=true`, `providerUsed=default`, and an explicit warning; never a silent downgrade.

- [ ] 3.2 Thread the resolver + `DetectionResult` into `AnonymizationService::extractAndDetectEntities` and the report (REQ-DDLED-005)
  - Provider-used and fallback warning appear in the returned payload and the anonymisation report so the review UI shows which engine ran.

## 4. Config + UI

- [ ] 4.1 `lib/Controller/DetectionProviderSettingsController.php` + `api/detection-provider/*` config routes (REQ-DDLED-002)
  - Get/set per-org provider + hermiq endpoint/agentflow + `require_local`; test-connectivity action; fail-closed admin auth (explicit attributes + in-method guard); no secret stored in v1.

- [ ] 4.2 Admin settings view in the Manifest-V2 shell (REQ-DDLED-006)
  - Provider picker (`NcSelect` with `inputLabel`), endpoint/agentflow fields, require-local toggle, test button; registered in `src/manifest.json` + `registry.js` (not `src/router/index.js`); NL Design tokens.

## 5. Quality

- [ ] 5.1 PHPUnit unit tests: resolver selection + fallback (unavailable/throw/malformed → default + warning), LLM→OR mapping (types/positions/score clamp), `require_local` refusal — minimum 75% coverage on new code
  - Provider seam is fully unit-testable with a faked hermiq client; run in the container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.

- [ ] 5.2 Playwright e2e `tests/e2e/spec-coverage/llm-entity-detection-provider.spec.ts` covering the `@e2e` scenarios
  - Switch an org to the LLM provider, run detection with the endpoint unreachable, assert the report shows the explicit fallback warning and default entities; nldesign accessibility pass; test through the UI.

- [ ] 5.3 i18n EN + NL for settings + the fallback warning banner, and documentation `docs/features/llm-entity-detection-provider.md` (ADR-010); run `openspec validate llm-entity-detection-provider --strict`
  - Keys in English; docs cover on-prem/no-cloud positioning, the honest-fallback rule, and the hermiq endpoint dependency.
