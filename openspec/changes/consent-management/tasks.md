## Tasks

- [ ] 1. **Enable RBAC and multitenancy — `ConsentService`** — locate all `ObjectService` calls in `lib/Service/ConsentService.php` (`createConsentRequest`, `updateConsentStatus`, `getConsentsByDocument`); change `_rbac: false` → `_rbac: true` and `_multitenancy: false` → `_multitenancy: true` on every call; grep for `_rbac` and `_multitenancy` across ALL files in `lib/` to catch any instance missed by a targeted search (CONS-044, CONS-045).

- [ ] 2. **Enable RBAC and multitenancy — `ConsentCrudService` and `ConsentController`** — apply the same flag change to `lib/Service/ConsentCrudService.php` and to the direct ObjectService calls in `lib/Controller/ConsentController.php::show()` and `index()`; confirm no other controller method queries ObjectService without the flags (CONS-046).

- [ ] 3. **Delegate ObjectService resolution — `ConsentService`** — inject `SettingsService` into `ConsentService` if not already present; remove the private `getObjectService()` method; replace all internal calls to `$this->getObjectService()` with `$this->settingsService->getObjectService()`; verify constructor DI uses `private readonly` per ADR-003 (CONS-053).

- [ ] 4. **Delegate ObjectService resolution — `ObjectionDeadlineChecker`** — inject `SettingsService` into `lib/Service/ObjectionDeadlineChecker.php`; remove its private `getObjectService()` method; replace internal calls with `$this->settingsService->getObjectService()`; confirm no third private duplicate remains elsewhere in `lib/` by grepping for `getInstalledApps()` (CONS-054).

- [ ] 5. **Delegate objection period config reading** — in `ConsentService::getObjectionPeriodDays()`, replace the direct `IAppConfig::getValueString('publication_objection_period_days', default: '28')` call with `(int)($this->settingsService->getAllSettings()['publicationObjectionPeriodDays'] ?? 28)`; remove `IAppConfig` from `ConsentService` constructor if it is no longer used for any other purpose; run `grep -rn 'publication_objection_period_days' lib/` and confirm only `SettingsService` reads the key directly (CONS-051, CONS-052).

- [ ] 6. **Add `POST /api/consents` route** — in `appinfo/routes.php`, add a POST route for `consents` pointing to `ConsentController::create()`; place the route BEFORE any `{id}` wildcard routes per ADR-003 route-ordering rule (CONS-048).

- [ ] 7. **Implement `ConsentController::create()`** — validate required fields from the request body (`documentId`, `entityType`, `entityText`); return HTTP 400 with a descriptive message if any required field is missing; call `ConsentService::createConsentRequest($documentId, $entityType, $entityText, $extraData)`; enforce admin-only access via `IGroupManager::isAdmin()` per ADR-015; return HTTP 201 with the created object; NEVER return `$e->getMessage()` — use a static error string per ADR-015 error response pattern (CONS-047, CONS-050).

- [ ] 8. **Add `@spec` PHPDoc tags** — add `@spec openspec/changes/consent-management/tasks.md#task-N` to the file-level docblock and every public method docblock in: `ConsentController.php`, `ConsentService.php`, `ConsentCrudService.php`, `ObjectionDeadlineChecker.php`, `ConsentUpdateHandler.php` — per ADR-003 spec traceability requirement.

- [ ] 9. **Unit tests — `ConsentServiceTest`** — update `tests/unit/Service/ConsentServiceTest.php`: mock `SettingsService::getObjectService()` instead of a private method (tasks 3–4); verify objection period is read from `SettingsService::getAllSettings()` (task 5); verify `_rbac: true` and `_multitenancy: true` are passed on `createConsentRequest()` and `updateConsentStatus()` (tasks 1–2); all existing test scenarios from the archived consent-management spec must continue to pass.

- [ ] 10. **Unit tests — `ConsentControllerTest`** — update `tests/unit/Controller/ConsentControllerTest.php`: add POST endpoint test: valid body → 201 + created object; missing required field → 400; non-admin user → 403; `_rbac: true` passed on `index()` and `show()` (tasks 2, 7).

- [ ] 11. **SPDX headers** — run `grep -rL 'SPDX-License-Identifier' lib/Controller/ConsentController.php lib/Service/ConsentService.php lib/Service/ConsentCrudService.php lib/Service/ObjectionDeadlineChecker.php lib/Service/ConsentUpdateHandler.php`; add `// SPDX-License-Identifier: EUPL-1.2` after `<?php` on every file that is missing it per ADR-014 and ADR-015 (all new files touched in this change must have the header).

- [ ] 12. **Integration test — Newman** — add a Newman collection test for `POST /api/consents`: authenticated as admin with valid body → 201; missing `entityType` → 400; authenticated as non-admin → 403; `GET /api/consents/{id}` on the created record → 200 with matching fields.

- [ ] 13. **Documentation and CHANGELOG** — update `docs/features/consent-management.md` to reflect the new `POST /api/consents` endpoint and the RBAC behavior change; add CHANGELOG entries: **Added** — `POST /api/consents` endpoint for creating consent records; **Security** — enabled RBAC and multitenancy on all consent ObjectService calls; **Behavior change** — operators upgrading to this version must verify RBAC group configuration; consent records are now tenant-scoped in multi-tenant deployments.

- [ ] 14. **Quality verification** — `composer check:strict` clean on all touched files; run pre-commit checklist from ADR-015: SPDX headers, ObjectService 3-arg calls, no `$e->getMessage()` in controllers, admin auth on POST/PUT/DELETE, no `@nextcloud/vue` imports (must use `@conduction/nextcloud-vue`), all `t()` calls use English keys; confirm `openspec validate consent-management` returns clean.
