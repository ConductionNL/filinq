## Why

DocuDesk needed a central entry point that gives users immediate situational awareness: how many consent records exist, which are pending vs. approved vs. objected, and what recent activity occurred — without navigating into the Consent Management view. Additionally, Nextcloud's Dashboard page (the home screen for all users) is the highest-traffic surface in a Nextcloud installation; registering DocuDesk widgets there provides passive visibility for document processing activity without requiring users to open the app. Without a dedicated dashboard, users had no at-a-glance summary and DocuDesk was invisible from the Nextcloud home screen.

## What Changes

- **NEW:** `DashboardIndex.vue` — default landing page showing consent statistics cards (Total, Pending, Approved, Objected) and a recent activity list of up to 10 most recent consent records, with an embedded quick-anonymization widget
- **NEW:** `AnonymizationWidget` PHP class (`lib/Dashboard/AnonymizationWidget.php`) — implements `IWidget` + `IIconWidget`, registered as Nextcloud Dashboard widget with ID `docudesk-anonymization`, title "Document Anonymization", order 20
- **NEW:** `FileEntitiesWidget` PHP class (`lib/Dashboard/FileEntitiesWidget.php`) — implements `IWidget` + `IIconWidget`, registered with ID `docudesk-file-entities`, title "File Entities", order 21
- **NEW:** `MainMenu.vue` — three-item SPA navigation sidebar: Dashboard (Finance icon), Anonymization (ShieldLock icon), Consent Management (AccountCheck icon); active state synchronized with Pinia `navigationStore`
- **NEW:** `Views.vue` — conditional view rendering driven by `navigationStore.selected`: dashboard → DashboardIndex, anonymization → AnonymizationWidget, consent → ConsentIndex, consentDetail → ConsentDetail
- **NEW:** `src/store/modules/navigation.ts` — Pinia store tracking selected view and active navigation item
- **NEW:** `src/dashboard.js` — webpack entry point for dashboard widget script bundle rendered on the Nextcloud Dashboard page
- **MODIFIED:** `lib/AppInfo/Application.php` — registers both dashboard widgets via `registerDashboardWidget()` in `Application::register()`
- **MODIFIED:** `lib/Controller/DashboardController.php` — serves the SPA shell via `page()` returning a `TemplateResponse`; dead code cleanup (removed `index()` method and `addAllowedConnectDomain('*')` CSP)

### Status badge mapping (implemented, used across all views)

| Status value | Label | Color |
|---|---|---|
| `pending` | Pending | dark |
| `consent_given` | Approved | green (success) |
| `objection_received` | Objected | red (error) |
| `no_response` | No Response | orange (warning) |
| `anonymized` | Anonymized | blue |

### Out of scope

- Persistent server-side dashboard state (dashboard is a pure read view over consent data)
- Per-user dashboard layout configuration
- Real-time WebSocket push updates (polling on page load is sufficient)
- Recursive folder statistics (not needed for consent summary)

## Capabilities

### New Capabilities

- `dashboard`: DocuDesk app landing page with consent statistics, recent activity, and quick anonymization access

### Modified Capabilities

- `anonymization`: AnonymizationWidget embedded on the dashboard provides drag-and-drop processing without navigating away

## Impact

- **New files:** `lib/Dashboard/AnonymizationWidget.php`, `lib/Dashboard/FileEntitiesWidget.php`, `src/views/dashboard/DashboardIndex.vue`, `src/views/Views.vue`, `src/navigation/MainMenu.vue`, `src/store/modules/navigation.ts`, `src/dashboard.js`
- **Modified files:** `lib/AppInfo/Application.php` (widget registration), `lib/Controller/DashboardController.php` (dead code removal)
- **API:** No new API endpoints. `GET /` returns a `TemplateResponse` (SPA shell). Dashboard reads consent data via Pinia `consentStore` which calls existing consent API endpoints.
- **Icons:** Two icon files serve different contexts — `app.svg` for Nextcloud top-bar navigation entry (from `info.xml`), `app-dark.svg` for dashboard widget icons (via `IIconWidget::getIconUrl()`) and admin settings section
- **No new database tables.** Dashboard is a read-only view; all data is owned by consent management and read via `consentStore`.
- **WCAG 2.1 AA:** Color-coded stat cards use NL Design System tokens via Nextcloud CSS variables (ADR-003) — no hardcoded color values.
