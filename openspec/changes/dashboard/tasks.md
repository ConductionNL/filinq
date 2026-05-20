## 1. DashboardController — SPA shell and dead code cleanup

- [x] 1.1 Create `lib/Controller/DashboardController.php` with `page(?string $getParameter): TemplateResponse` method. Route: `GET /` (`dashboard#page`). Return `new TemplateResponse('docudesk', 'index')` on success; return error template on caught exception.
- [x] 1.2 Remove `addAllowedConnectDomain('*')` CSP override — rely on default Nextcloud CSP. Document security rationale in commit message.
- [x] 1.3 Remove `index()` method if present — only `page()` should remain. The `?string $getParameter` signature is preserved but marked as dead code in a inline comment.

## 2. Nextcloud Dashboard widgets — PHP registration

- [x] 2.1 Create `lib/Dashboard/AnonymizationWidget.php` implementing `IWidget` + `IIconWidget`. Properties: `getId()` → `'docudesk-anonymization'`, `getName()` → `'Document Anonymization'`, `getOrder()` → `20`, `getIconUrl()` → app URL for `img/app-dark.svg`, `getUrl()` → `docudesk.dashboard.page` route URL, `getWidgetScript()` → `'docudesk-dashboard'`.
- [x] 2.2 Create `lib/Dashboard/FileEntitiesWidget.php` implementing `IWidget` + `IIconWidget`. Properties: `getId()` → `'docudesk-file-entities'`, `getName()` → `'File Entities'`, `getOrder()` → `21`, `getIconUrl()` → app URL for `img/app-dark.svg`, `getUrl()` → `docudesk.dashboard.page` route URL, `getWidgetScript()` → `'docudesk-dashboard'`.
- [x] 2.3 Register both widgets in `lib/AppInfo/Application.php` inside `register()`: `$context->registerDashboardWidget(AnonymizationWidget::class)` and `$context->registerDashboardWidget(FileEntitiesWidget::class)`.

## 3. Pinia navigation store

- [x] 3.1 Create `src/store/modules/navigation.ts` as a Pinia store. State: `selected: string` (default `'dashboard'`). Actions: `setSelected(view: string)`. The store is imported in `MainMenu.vue` and `Views.vue`.

## 4. MainMenu.vue — three-item sidebar navigation

- [x] 4.1 Create `src/navigation/MainMenu.vue` using `NcAppNavigation` + `NcAppNavigationItem`. Three items: Dashboard (Finance MDI icon, sets `navigationStore.selected = 'dashboard'`), Anonymization (ShieldLock MDI icon, sets `'anonymization'`), Consent Management (AccountCheck MDI icon, sets `'consent'`).
- [x] 4.2 Apply active state: item is active when `navigationStore.selected === item.key` OR (for Consent Management) when `navigationStore.selected === 'consent' || navigationStore.selected === 'consentDetail'`.

## 5. Views.vue — conditional view rendering

- [x] 5.1 Create `src/views/Views.vue`. Use `v-if`/`v-else-if` to render:
  - `navigationStore.selected === 'dashboard'` → `<DashboardIndex />`
  - `navigationStore.selected === 'anonymization'` → `<AnonymizationWidget />`
  - `navigationStore.selected === 'consent'` → `<ConsentIndex />`
  - `navigationStore.selected === 'consentDetail'` → `<ConsentDetail />`

## 6. DashboardIndex.vue — consent stats and recent activity

- [x] 6.1 Create `src/views/dashboard/DashboardIndex.vue` using `CnDashboardPage` (ADR-012). On `mounted()`, call `consentStore.fetchConsents()` if not already loaded.
- [x] 6.2 Render four stat cards: Total (neutral), Pending (NL Design System warning/orange token), Approved (success/green token), Objected (error/red token). Values sourced from `consentStore.consentStats`. Cards use CSS variables — no hardcoded hex colors (ADR-003).
- [x] 6.3 Render recent activity list: show up to 10 most recent `consentStore.consents` entries. Each row: `entityText` and a status badge component.
- [x] 6.4 Status badge component: map `consentStatus` to label + CSS color class. Mapping: `pending` → "Pending" (dark), `consent_given` → "Approved" (success/green), `objection_received` → "Objected" (error/red), `no_response` → "No Response" (warning/orange), `anonymized` → "Anonymized" (info/blue). Same badge component is reused in ConsentIndex.vue for consistency.
- [x] 6.5 Loading state: show `NcLoadingIcon` while `consentStore.loading === true`.
- [x] 6.6 Empty state: show `NcEmptyContent` with title "No consent records yet" and guidance text when `consentStore.consents.length === 0` and not loading.
- [x] 6.7 Quick Anonymization section: embed `<AnonymizationWidget />` component below the recent activity list.

## 7. Dashboard widget script bundle

- [x] 7.1 Create `src/dashboard.js` as the webpack entry point for the `docudesk-dashboard` bundle. Import and register `AnonymizationDashboardWidget.vue` and `FileEntitiesDashboardWidget.vue` as custom elements (or mount via `OCA.Dashboard.register()`).
- [x] 7.2 Ensure `webpack.config.js` (or equivalent) includes `dashboard` as an entry point producing `js/docudesk-dashboard.js`.

## 8. i18n — Dutch and English translations (ADR-005)

- [x] 8.1 Add English translation strings (in `l10n/en.json` or `src/l10n/`): "Dashboard", "Anonymization", "Consent Management", "No consent records yet", "Total", "Pending", "Approved", "Objected", "No Response", "Anonymized", "Quick Anonymization", "Document Anonymization", "File Entities".
- [x] 8.2 Add Dutch translation strings: "Dashboard", "Anonimisering", "Toestemmingsbeheer", "Nog geen toestemmingsrecords", "Totaal", "In behandeling", "Goedgekeurd", "Bezwaar gemaakt", "Geen reactie", "Geanonimiseerd", "Snel anonimiseren", "Document anonimisering", "Bestandsentiteiten".

## 9. Unit tests (ADR-009 — minimum 75% coverage for new code)

- [x] 9.1 Create `tests/Unit/Controller/DashboardControllerTest.php`: test `page()` returns TemplateResponse with correct template name; test error path returns error template; verify no CSP override is set (`addAllowedConnectDomain` not called).
- [x] 9.2 Create `tests/Unit/Dashboard/AnonymizationWidgetTest.php`: test `getId()` returns `'docudesk-anonymization'`; test `getName()` returns `'Document Anonymization'`; test `getOrder()` returns `20`; test `getIconUrl()` returns URL containing `app-dark.svg`; test `getWidgetScript()` returns `'docudesk-dashboard'`.
- [x] 9.3 Create `tests/Unit/Dashboard/FileEntitiesWidgetTest.php`: same structure, ID `'docudesk-file-entities'`, title `'File Entities'`, order `21`.
- [x] 9.4 Verify widget registration in `tests/Unit/AppInfo/ApplicationTest.php`: confirm both widget class names are registered via `registerDashboardWidget()`.
- [x] 9.5 Run unit tests inside the Nextcloud container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml` (tests written; run manually if Docker unavailable in current environment).

## 10. Documentation with screenshots (ADR-010)

- [x] 10.1 Create `docs/features/dashboard.md` documenting: the DocuDesk dashboard overview (stats cards, recent activity, quick anonymization), the two Nextcloud Dashboard widgets (how to add them, what they show), and the navigation menu. Include Playwright screenshots of the dashboard page and both NC Dashboard widgets using browser MCP.
- [x] 10.2 Update `docs/features/README.md` (or index) to include a link to the new dashboard feature doc.
