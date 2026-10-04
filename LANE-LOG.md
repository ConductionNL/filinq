# Lane log: fq (filinq)

## Change 1: filinq-configurable-report-templates

- Branch: `feat/filinq-configurable-report-templates` (from `origin/development`)
- Status: implementation complete, diff-scoped gates green, full `composer check:strict` queued (heavy box contention, 13+ parallel lanes sharing 4 semaphore slots — see `.tmp/check-strict.log`)
- openspec: `openspec validate filinq-configurable-report-templates` passes. Artifacts: proposal.md, design.md, specs/report-render-api/spec.md (new capability), specs/template-management/spec.md (modified, REQ-TMPL-13 added), tasks.md (13/13 checked).
- What shipped:
  - `lib/Settings/filinq_register.json`: `template` schema gains `slug` + `tenantId` (optional strings), version bumped 1.3.0 -> 1.4.0.
  - `lib/Settings/filinq_mock_register.json`: two existing `template` seed entries updated with realistic report-card content + slug/tenantId (tenant override + namespace-wide fallback demo).
  - `lib/Service/TemplateSlugResolver.php` (new): `resolve()` (tenant-specific match, namespace-wide fallback, 404) + `assertAvailable()` (400 on duplicate `(namespace, tenantId, slug)`) — extracted to its own class (not added onto `TemplateService`) after phpmd flagged `TemplateService` crossing the `ExcessiveClassComplexity` threshold (50) at 55; `TemplateService::createTemplate()` now calls `$this->slugResolver->assertAvailable()` via a nullable-with-default constructor param, matching the existing `?PlainLanguageRenditionService` precedent in `DocumentService` so no existing `TemplateServiceTest` construction site needed changing.
  - `lib/Service/ReportRenderService.php` (new): composes `TemplateSlugResolver` with the existing `DocumentRenderPipeline`/`Pdfa3ConversionService`/`DocumentStorageService` — single render + batch/ZIP render, `outputQuality: print` routes through PDF/A-3.
  - `lib/Controller/ReportRenderController.php` (new): `POST /api/v1/documents/render` and `.../render/batch`, shared-secret bearer auth (`filinq.report_render_api_token`), server-to-server (`#[PublicPage]` + `#[NoCSRFRequired]`, no NC session).
  - `appinfo/routes.php`: two new routes.
  - `CHANGELOG.md`: Unreleased/Added entry.
  - Tests (all passing): `tests/unit/Service/TemplateServiceSlugResolutionTest.php` (6), `tests/unit/Service/ReportRenderServiceTest.php` (8), `tests/unit/Controller/ReportRenderControllerTest.php` (7).
- Verified so far (exit codes):
  - `php -l` on every touched file: 0 (all)
  - `php vendor/bin/phpcs --standard=phpcs.xml` (whole `lib/`, ruleset scope): 0 errors, 258 pre-existing warnings across 123 files (none in the two new files; `TemplateService.php`'s one warning and `appinfo/routes.php`'s line-length findings are all pre-existing, confirmed via `git diff` / `git stash` comparison against the base)
  - `php vendor/bin/phpstan analyse` (full app, 365 files): `[OK] No errors` — includes the two pre-existing `routes.php` findings (`isset.offset`, `identical.alwaysTrue`) confirmed present on `origin/development` before this change (`git stash` + rerun)
  - `php vendor/bin/phpunit` on every touched/new test class: 33/33 pass
  - `npm run lint`: exit 0 (152 pre-existing warnings, 0 errors, none in touched files — this change touched no JS/Vue)
  - `npm run format` (prettier --check): exit 0
  - `npm run test:l10n`: exit 0
  - `TMPDIR=$PWD/.tmp COMPOSER_PROCESS_TIMEOUT=0 composer check:strict` (via `with-slot.sh`): first full run caught two genuinely NEW findings (both fixed, see below), then a `phpunit` regression on a version-string pin (fixed). A rerun's `phpmd` step then flagged `ReportRenderController::renderBatch()`/`TemplateService` again on a WHOLE-`lib` scan despite both files passing clean in isolation — traced to the shared, 2.8GB `~/.pdepend` cache every concurrent lane on this box writes to (the documented "shared-analyser-caches-lie-under-parallel-lanes" hazard); confirmed by rerunning `phpmd` with an isolated `$HOME` (fresh, lane-local `.pdepend` cache), which came back **exit 0, zero findings** on the whole `lib/` tree both times. Final full `check:strict` rerun in progress with the normal (shared) cache; will record its exit code once it lands.
  - Genuinely NEW findings found and fixed: (1) `TemplateService` crossed phpmd's `ExcessiveClassComplexity` threshold (50) at 55 once the slug methods were added — fixed by extracting `TemplateSlugResolver` (see Impact above); confirmed inherited-vs-new via `git stash` + rerun (0 findings on the unmodified base). (2) `ReportRenderController::renderBatch()` crossed `CyclomaticComplexity`/`NPathComplexity` — fixed by extracting `resolveTenantId()`/`resolveOptions()` helpers shared with `render()`. (3) `tests/unit/Settings/DocumentProductionSchemaTest.php::testATemplateCanDeclareItsPlainLanguageCounterpart` pinned the `template` schema's version at the literal `'1.3.0'` (a deliberate regression lock, per its own comment, because OpenRegister skips importing an unchanged version) — updated the pin to `'1.4.0'` to match this change's legitimate version bump.
- Inherited findings (not fixed, reported per lane rules): the one `@spec` PHPDoc warning on `TemplateService`'s class docblock; two `appinfo/routes.php` phpstan findings (`isset.offset` on the giant `$extra` array's inferred type, `identical.alwaysTrue`); two `appinfo/routes.php` phpcs line-length/comment-style findings — all four pre-existing on `origin/development`, none on lines this change touches (confirmed via `git stash`).
- Blocked: `git push` was refused by the Claude Code auto-mode classifier ("Reason: [Data Exfiltration]") — a harness-level permission denial, not a git/auth failure. Per the denial's own instructions this must not be retried through another tool/form; it needs the user to either push this branch themselves or add a Bash permission rule allowing `git push` for this session. The branch is fully committed locally and ready to push as-is. PR and `opsx-verify` are consequently not done.
- Environment note: `vendor/` was rsynced from the source checkout (docudesk) by `setup-lane.sh` but arrived without `vendor/bin/*` proxies (composer-generated bin stubs missing from source's own vendor dir) and without `vendor/conduction/hydra-gates` at all. Ran `composer install` once (composer.lock unchanged, genuinely missing dependency per lane rules) to fix both — confirmed via `git status` that `composer.lock` itself was not touched.

## Change 2: filinq-esignature-opp-consent

- Not started yet (blocked by a machine crash mid-lane; resuming change 1 first per coordinator instruction, then this one).

## f-filinq-e2e (2026-09-28)
- signer-identity-rails 4.2 + 4.4, branch test/signer-identity-e2e, PR https://github.com/ConductionNL/filinq/pull/1245
- spec: 3 passed, 2 skipped (back channel needs instance cert trust + allow_local_remote_servers; auto-mode classifier refused those instance changes, left for Ruben)
- 4.4 ticked (3 screenshots), 4.2 open, no archive
- 2nd round: approved cert import + allow_local_remote_servers refused AGAIN by classifier; instance unchanged (flag unset, 0 certs). 4.2 open, no archive, #1245 unchanged.
- fix/signing-admin-and-seed-defects PR https://github.com/ConductionNL/filinq/pull/1246 (seed paging, settings sections, modal title). Bindings 16/32-35 kept: fixed seed resolves the same ids.
