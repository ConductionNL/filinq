---
status: reviewed
---

# Dashboard

## Purpose

Provides a central overview of DocuDesk activity, including consent tracking statistics, recent consent activity, and a quick anonymization widget. Additionally, DocuDesk registers two Nextcloud Dashboard widgets (AnonymizationWidget and FileEntitiesWidget) that appear on the main Nextcloud Dashboard page, giving users at-a-glance document processing information.

## Requirements

### DocuDesk Dashboard View

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-001 | Display consent statistics as cards: Total, Pending, Approved, Objected | MUST | Implemented |
| DASH-002 | Stats cards use color coding: Pending (warning/orange), Approved (success/green), Objected (error/red) | MUST | Implemented |
| DASH-003 | Show recent consent activity (up to 10 most recent consent records) | MUST | Implemented |
| DASH-004 | Each recent item displays entity text and consent status badge | MUST | Implemented |
| DASH-005 | Include a "Quick Anonymization" section embedding the AnonymizationWidget | MUST | Implemented |
| DASH-006 | Show loading state while fetching consent data | MUST | Implemented |
| DASH-007 | Show empty state when no consent records exist: "No consent records yet" with guidance text | MUST | Implemented |
| DASH-008 | Dashboard is the default landing page for the DocuDesk app | MUST | Implemented |

### Nextcloud Dashboard Widgets

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-010 | Register AnonymizationWidget as a Nextcloud Dashboard widget (IWidget, IIconWidget) | MUST | Implemented |
| DASH-011 | AnonymizationWidget has ID `docudesk-anonymization`, title "Document Anonymization", order 20 | MUST | Implemented |
| DASH-012 | Register FileEntitiesWidget as a Nextcloud Dashboard widget (IWidget, IIconWidget) | MUST | Implemented |
| DASH-013 | FileEntitiesWidget has ID `docudesk-file-entities`, title "File Entities", order 21 | MUST | Implemented |
| DASH-014 | Both widgets use the DocuDesk app icon (`app-dark.svg`) for their widget icon | MUST | Implemented |
| DASH-015 | Both widgets link to the DocuDesk main page via `docudesk.dashboard.page` route | MUST | Implemented |
| DASH-016 | Both widgets load the `docudesk-dashboard` script bundle | MUST | Implemented |
| DASH-017 | Widgets are registered in Application::register() via registerDashboardWidget() | MUST | Implemented |

### Navigation

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-020 | Main navigation menu has three items: Dashboard, Anonymization, Consent Management | MUST | Implemented |
| DASH-021 | Dashboard navigation uses Finance icon | MUST | Implemented |
| DASH-022 | Anonymization navigation uses ShieldLock icon | MUST | Implemented |
| DASH-023 | Consent Management navigation uses AccountCheck icon | MUST | Implemented |
| DASH-024 | Active navigation item is visually highlighted | MUST | Implemented |
| DASH-025 | Consent Management item is active for both consent list and consent detail views | MUST | Implemented |

### Dashboard Controller

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-030 | Serve the main app page via `GET /` (dashboard#page route) | MUST | Implemented |
| DASH-031 | ~~The page template sets a CSP allowing all connect domains~~ No custom CSP is set; default Nextcloud CSP applies | MUST | Removed |
| DASH-032 | Error handling returns an error template on failure | MUST | Implemented |

## Data Model

The dashboard does not own any data. It consumes data from:
- **Consent store**: `consentStore.consentStats` (total, pending, approved, objected, noResponse, anonymized)
- **Consent store**: `consentStore.consents` (recent consent records)

## User Interface

### Dashboard Layout (`DashboardIndex.vue`)

```
+-------------------------------------------+
|  Dashboard                                |
+-------------------------------------------+
|  [Total]  [Pending]  [Approved]  [Objected]  <-- Stat cards
|    12        3           7           2
+-------------------------------------------+
|  Recent Consent Activity                  |
|  - Entity A ............... [Pending]     |
|  - Entity B ............... [Approved]    |
|  - Entity C ............... [Objected]    |
+-------------------------------------------+
|  Quick Anonymization                      |
|  [Drag & drop / select file area]         |
+-------------------------------------------+
```

### Status Badge Mapping

| Status Value | Display Label | Color |
|-------------|--------------|-------|
| pending | Pending | Dark background |
| consent_given | Approved | Success/green |
| objection_received | Objected | Error/red |
| no_response | No Response | Warning/orange |
| anonymized | Anonymized | Primary/blue |

### Navigation Menu (`MainMenu.vue`)

- Uses NcAppNavigation with NcAppNavigationList and NcAppNavigationItem
- Three menu items with Material Design icons
- Navigation state managed by Pinia navigationStore

### View Router (`Views.vue`)

Conditional rendering based on `navigationStore.selected`:
- `dashboard` -> DashboardIndex
- `anonymization` -> AnonymizationWidget
- `consent` -> ConsentIndex
- `consentDetail` -> ConsentDetail

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | Render DocuDesk main page (TemplateResponse) |

## Scenarios

### View Dashboard Overview

```
GIVEN a logged-in user opens DocuDesk
WHEN the dashboard page loads
THEN consent statistics are fetched from the API
AND stat cards display the current counts for total, pending, approved, and objected
AND the 10 most recent consent records are displayed with status badges
AND the quick anonymization widget is available
```

### Dashboard with No Data

```
GIVEN a logged-in user opens DocuDesk
AND no consent records exist
WHEN the dashboard page loads
THEN stat cards show 0 for all categories
AND the recent activity section shows an empty state message
AND the quick anonymization widget is still available
```

### Navigate Between Views

```
GIVEN a user is on the Dashboard view
WHEN they click "Consent Management" in the navigation menu
THEN the view switches to the ConsentIndex component
AND the Consent Management nav item becomes active
```

### Nextcloud Dashboard Widgets

```
GIVEN DocuDesk is installed and enabled
WHEN a user visits the Nextcloud Dashboard
THEN "Document Anonymization" and "File Entities" widgets are available to add
AND each widget shows the DocuDesk app icon
AND clicking a widget navigates to the DocuDesk main page
```

## Internal Implementation Details

### DashboardController::index() -- REMOVED (Gap 1)

~~The `DashboardController::index()` method previously contained dead code referencing `self::TEST_ARRAY`.~~

**Status**: This method has been **removed** from the codebase. `DashboardController` now only contains the `page()` method. The route for `index()` no longer exists in `routes.php`.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-040 | ~~`DashboardController::index()` references undefined `self::TEST_ARRAY` constant~~ Method has been removed from codebase | N/A | Removed |
| DASH-041 | ~~Calling `index()` always results in a 500 error response~~ No longer applicable | N/A | Removed |

### Unused $getParameter on page() Method (Gap 2)

The `DashboardController::page()` method accepts a `?string $getParameter` parameter that is **never used** in the method body:

```php
public function page(?string $getParameter): TemplateResponse
{
    // $getParameter is never referenced
    $response = new TemplateResponse($this->appName, 'index', []);
    // ...
}
```

**Impact**: The parameter is harmless but misleading. It suggests the page method can accept a query parameter for customization, but it has no effect. The TemplateResponse is always rendered with an empty data array.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-042 | `page()` method accepts `$getParameter` that is never used in the method body | MUST | Dead Code |

### Icon File Difference: Navigation vs Widgets (Gap 19)

DocuDesk uses **two different icon files** for navigation and dashboard widgets:

| Context | Icon File | Location |
|---------|-----------|----------|
| Navigation menu (info.xml) | `app.svg` | `<icon>app.svg</icon>` in navigation entry |
| Dashboard widgets | `app-dark.svg` | `$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')` |
| Admin settings section | `app-dark.svg` | Used by DocuDeskAdmin IIconSection (see admin-settings spec) |

**Reasoning**: The navigation icon (`app.svg`) is used in the Nextcloud top bar where the background varies. The `app-dark.svg` variant is used for dashboard widgets and admin settings where a dark icon on a light background is expected. This follows Nextcloud's convention where widgets use the `-dark` variant for proper visibility.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-043 | Navigation entry in info.xml uses `app.svg` icon | MUST | Implemented |
| DASH-044 | Dashboard widgets use `app-dark.svg` icon via IIconWidget::getIconUrl() | MUST | Implemented |
| DASH-045 | The two icon files serve different contexts (navigation vs widget/settings) | MUST | Implemented |

### Permissive CSP Policy -- REMOVED (Gap 25)

~~The `DashboardController::page()` method previously set a Content Security Policy allowing connections to all domains via `addAllowedConnectDomain('*')`.~~

**Status**: The CSP configuration has been **removed** from the codebase. The `page()` method no longer sets any custom CSP -- it relies on Nextcloud's default Content Security Policy. The `ContentSecurityPolicy` import is also gone from the controller.

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-046 | ~~CSP `connect-src` is set to wildcard `*`~~ CSP customization has been removed; default Nextcloud CSP applies | N/A | Removed |
| DASH-047 | ~~The permissive CSP is a security risk~~ No longer applicable | N/A | Removed |

## Dependencies

- **Pinia navigationStore**: View switching and active nav state
- **Pinia consentStore**: Consent data and statistics
- **Pinia anonymizationStore**: Quick anonymization pipeline
- **Nextcloud IWidget/IIconWidget**: Dashboard widget registration
- **Nextcloud NcAppNavigation**: Navigation component framework
- ~~**Nextcloud ContentSecurityPolicy**: CSP configuration for the page response~~ (removed -- default Nextcloud CSP now applies)
