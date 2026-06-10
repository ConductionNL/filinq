# Tasks: document-creatie-sjablonen

## Task 1: Core Implementation
- [x] Implement service classes — shipped: `lib/Service/TemplateService.php`, `lib/Service/TemplateVersionService.php`, `lib/Service/TemplatePreviewService.php`, `lib/Service/TemplateRenderer.php`, plus `lib/Resources/templates/` Twig collection for grondslagen + eml + signing.
- [x] Add API endpoints — shipped: `lib/Controller/TemplatesController.php` + `lib/Controller/TemplateRequestHandler.php`; routes registered in `appinfo/routes.php` under `templates#*`.
- [x] Add configuration settings — `docudesk.templates.lock_timeout_minutes` (default 15) is read by `TemplateService::isLockExpired()` per the canonical manifest-declared key (see `docudesk-adopt-or-abstractions` task 10 implementation).

## Task 2: Testing
- [x] Unit tests — `tests/unit/Service/TemplateServiceTest.php` + `TemplateVersionServiceTest.php` ship with the template-service surface.
- [~] Integration tests — DEFERRED: Newman/Playwright runs against the dev container; the unit tests cover the contract.

## Task 3: Documentation
- [x] API documentation — `docs/features/template-management.md` + `docs/features/document-creatie-sjablonen.md` + `docs/features/advanced-template-management.md` describe the public surface; endpoint reference lives in `appinfo/openapi.json`.
- [~] Admin guide — DEFERRED: a dedicated `docs/admin/template-management.md` ships alongside the templates admin-UI iteration; the feature docs above cover end-user use.
