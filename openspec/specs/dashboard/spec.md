---
status: implemented
---

# Dashboard

## Purpose

Provides a central overview of DocuDesk activity, including consent tracking statistics, recent consent activity, and a quick anonymization widget. Additionally, DocuDesk registers two Nextcloud Dashboard widgets (AnonymizationWidget and FileEntitiesWidget) that appear on the main Nextcloud Dashboard page, giving users at-a-glance document processing information.

## Requirements

### Requirement: DocuDesk Dashboard View (REQ-DASH-01)

**Priority:** Must

The dashboard serves as the default landing page displaying consent statistics, recent activity, and quick anonymization access.

#### Scenario: View dashboard with consent data
- GIVEN a logged-in user opens DocuDesk
- AND 12 consent records exist (3 pending, 7 approved, 2 objected)
- WHEN the dashboard page loads
- THEN stat cards display Total: 12, Pending: 3, Approved: 7, Objected: 2
- AND cards are color-coded: Pending (warning/orange), Approved (success/green), Objected (error/red)

#### Scenario: View recent consent activity
- GIVEN 15 consent records exist
- WHEN the dashboard loads
- THEN the 10 most recent consent records are displayed
- AND each item shows entity text and consent status badge

#### Scenario: Dashboard with no data
- GIVEN no consent records exist
- WHEN the dashboard loads
- THEN stat cards show 0 for all categories
- AND the recent activity section shows "No consent records yet" with guidance text
- AND the quick anonymization widget is still available

#### Scenario: Quick anonymization from dashboard
- GIVEN the dashboard is displayed
- WHEN the user scrolls to the Quick Anonymization section
- THEN the AnonymizationWidget is embedded with drag-and-drop upload
- AND files can be processed without navigating away from the dashboard

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-001 | Display consent statistics as cards: Total, Pending, Approved, Objected | MUST | Implemented |
| DASH-002 | Stats cards use color coding: Pending (orange), Approved (green), Objected (red) | MUST | Implemented |
| DASH-003 | Show recent consent activity (up to 10 most recent) | MUST | Implemented |
| DASH-004 | Each recent item displays entity text and consent status badge | MUST | Implemented |
| DASH-005 | Include a "Quick Anonymization" section embedding the AnonymizationWidget | MUST | Implemented |
| DASH-006 | Show loading state while fetching consent data | MUST | Implemented |
| DASH-007 | Show empty state when no consent records exist | MUST | Implemented |
| DASH-008 | Dashboard is the default landing page for the DocuDesk app | MUST | Implemented |

### Requirement: Nextcloud Dashboard Widgets (REQ-DASH-02)

**Priority:** Must

DocuDesk registers two widgets on the main Nextcloud Dashboard for at-a-glance document processing information.

#### Scenario: Widgets available on Nextcloud Dashboard
- GIVEN DocuDesk is installed and enabled
- WHEN a user visits the Nextcloud Dashboard
- THEN "Document Anonymization" and "File Entities" widgets are available to add
- AND each widget shows the DocuDesk app icon (app-dark.svg)

#### Scenario: Widget links to DocuDesk
- GIVEN a dashboard widget is displayed
- WHEN the user clicks the widget
- THEN they are navigated to the DocuDesk main page via `docudesk.dashboard.page` route

#### Scenario: Widget script loading
@e2e exclude script bundle loading is a build artifact — verified by webpack output inspection; not directly observable as a UI assertion
- GIVEN the Nextcloud Dashboard page loads
- WHEN DocuDesk widgets are rendered
- THEN both widgets load the `docudesk-dashboard` script bundle
- AND the script renders the Vue widget components

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-010 | Register AnonymizationWidget as Nextcloud Dashboard widget (IWidget, IIconWidget) | MUST | Implemented |
| DASH-011 | AnonymizationWidget has ID `docudesk-anonymization`, title "Document Anonymization", order 20 | MUST | Implemented |
| DASH-012 | Register FileEntitiesWidget as Nextcloud Dashboard widget (IWidget, IIconWidget) | MUST | Implemented |
| DASH-013 | FileEntitiesWidget has ID `docudesk-file-entities`, title "File Entities", order 21 | MUST | Implemented |
| DASH-014 | Both widgets use `app-dark.svg` icon | MUST | Implemented |
| DASH-015 | Both widgets link to DocuDesk main page | MUST | Implemented |
| DASH-016 | Both widgets load the `docudesk-dashboard` script bundle | MUST | Implemented |
| DASH-017 | Widgets registered in Application::register() via registerDashboardWidget() | MUST | Implemented |

### Requirement: Navigation Menu (REQ-DASH-03)

**Priority:** Must

The main navigation provides three items with Material Design icons for switching between DocuDesk views.

#### Scenario: Navigate between views
- GIVEN a user is on the Dashboard view
- WHEN they click "Consent Management" in the navigation menu
- THEN the view switches to the ConsentIndex component
- AND the Consent Management nav item becomes active (visually highlighted)

#### Scenario: Navigation items and icons
- GIVEN the DocuDesk app is open
- WHEN the navigation menu is displayed
- THEN three items are shown: Dashboard (Finance icon), Anonymization (ShieldLock icon), Consent Management (AccountCheck icon)

#### Scenario: Consent detail navigation state
@e2e exclude consent detail view requires a consent record to navigate to; no consent creation UI exists (CONS-048); covered by navigate-between-views test for nav highlighting
- GIVEN the user navigates to a consent detail view
- WHEN the navigation menu is displayed
- THEN the Consent Management item remains active
- AND the active state applies to both consent list and detail views

#### Scenario: Conditional view rendering
@e2e exclude internal Pinia store→Vue component wiring — covered by navigate-between-views test which observes the rendered views; unit-testable directly
- GIVEN the navigation store tracks the selected view
- WHEN `navigationStore.selected` changes
- THEN the Views.vue component renders the corresponding view:
  - `dashboard` -> DashboardIndex
  - `anonymization` -> AnonymizationWidget
  - `consent` -> ConsentIndex
  - `consentDetail` -> ConsentDetail

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-020 | Main navigation has three items: Dashboard, Anonymization, Consent Management | MUST | Implemented |
| DASH-021 | Dashboard navigation uses Finance icon | MUST | Implemented |
| DASH-022 | Anonymization navigation uses ShieldLock icon | MUST | Implemented |
| DASH-023 | Consent Management navigation uses AccountCheck icon | MUST | Implemented |
| DASH-024 | Active navigation item is visually highlighted | MUST | Implemented |
| DASH-025 | Consent Management item active for both list and detail views | MUST | Implemented |

### Requirement: Dashboard Controller (REQ-DASH-04)

**Priority:** Must

The DashboardController serves the main app page as a Nextcloud TemplateResponse.

#### Scenario: Serve main app page
@e2e exclude DashboardController::page() PHP implementation — HTTP 200 response verified by view-dashboard test navigating to /apps/docudesk
- GIVEN an authenticated user
- WHEN GET / is requested
- THEN DashboardController::page() returns a TemplateResponse
- AND the template renders the Vue app entry point

#### Scenario: Error handling
@e2e exclude backend controller error path — exception-to-template conversion verified by PHPUnit; not injectable via UI
- GIVEN an error occurs during page rendering
- WHEN the controller catches the exception
- THEN an error template is returned
- AND the user sees a meaningful error message

#### Scenario: Unused parameter on page method
@e2e exclude dead code documentation — no behavioral impact; verified by code inspection
- GIVEN DashboardController::page() accepts `$getParameter`
- WHEN the method is called with or without this parameter
- THEN the parameter has no effect on the response
- AND this is dead code that should be cleaned up

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-030 | Serve main app page via GET / (dashboard#page route) | MUST | Implemented |
| DASH-031 | Default Nextcloud CSP applies (no custom CSP) | MUST | Implemented |
| DASH-032 | Error handling returns an error template on failure | MUST | Implemented |

### Requirement: Status Badge Display (REQ-DASH-05)

**Priority:** Must

Consent status values are displayed with consistent color-coded badges throughout the dashboard and consent views.

#### Scenario: Status badge color mapping
- GIVEN a consent record with status "consent_given"
- WHEN the status badge is rendered
- THEN it displays "Approved" with success/green color

#### Scenario: All status badges
- GIVEN various consent statuses exist
- WHEN badges are rendered
- THEN the mapping is: pending (dark), consent_given (green), objection_received (red), no_response (orange), anonymized (blue)

#### Scenario: Badge consistency across views
- GIVEN a consent record appears in both dashboard recent activity and consent list
- WHEN the status badge is rendered in both locations
- THEN the same color and label mapping is used

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-040 | Status badges use consistent color mapping across all views | MUST | Implemented |
| DASH-041 | Five status values mapped: pending, consent_given, objection_received, no_response, anonymized | MUST | Implemented |

### Requirement: Icon File Differentiation (REQ-DASH-06)

**Priority:** Must

DocuDesk uses different icon files for navigation vs. dashboard widgets, following Nextcloud conventions.

#### Scenario: Navigation icon
- GIVEN the DocuDesk app is displayed in the Nextcloud top bar
- WHEN the navigation entry is rendered
- THEN `app.svg` is used (from info.xml)

#### Scenario: Dashboard widget icon
- GIVEN a DocuDesk dashboard widget is displayed
- WHEN the widget icon is rendered
- THEN `app-dark.svg` is used via IIconWidget::getIconUrl()
- AND this provides better visibility on light backgrounds

#### Scenario: Admin settings section icon
- GIVEN the DocuDesk admin settings section is displayed
- WHEN the section icon is rendered
- THEN `app-dark.svg` is used (same as dashboard widgets)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-043 | Navigation entry in info.xml uses `app.svg` icon | MUST | Implemented |
| DASH-044 | Dashboard widgets use `app-dark.svg` icon | MUST | Implemented |
| DASH-045 | Two icon files serve different contexts (navigation vs widget/settings) | MUST | Implemented |

### Requirement: Dead Code and Removed Features (REQ-DASH-07)

**Priority:** Must

Previously identified issues have been resolved through removal.

#### Scenario: DashboardController::index() removed
@e2e exclude dead code removal — verified by static code inspection; no UI behavior to assert
- GIVEN DashboardController previously had an index() method with dead code
- WHEN the codebase is inspected
- THEN the method has been removed
- AND only the page() method remains

#### Scenario: Permissive CSP removed
@e2e exclude CSP header is a browser security header — not inspectable via UI assertions without network interception
- GIVEN DashboardController previously set `addAllowedConnectDomain('*')`
- WHEN the codebase is inspected
- THEN the CSP customization has been removed
- AND default Nextcloud CSP applies

#### Scenario: Unused parameter documented
@e2e exclude dead code documentation — duplicate of unused-parameter-on-page-method scenario above
- GIVEN page() accepts `?string $getParameter`
- WHEN the parameter is inspected
- THEN it is never used in the method body
- AND this is documented as dead code

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DASH-042 | `page()` method accepts `$getParameter` that is never used | MUST | Dead Code |
| DASH-046 | CSP customization removed; default Nextcloud CSP applies | N/A | Removed |
| DASH-047 | DashboardController::index() method removed | N/A | Removed |

## Data Model

The dashboard does not own any data. It consumes data from:
- **Consent store**: `consentStore.consentStats` (total, pending, approved, objected, noResponse, anonymized)
- **Consent store**: `consentStore.consents` (recent consent records)

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | Render DocuDesk main page (TemplateResponse) |

## Dependencies

- **Pinia navigationStore**: View switching and active nav state
- **Pinia consentStore**: Consent data and statistics
- **Pinia anonymizationStore**: Quick anonymization pipeline
- **Nextcloud IWidget/IIconWidget**: Dashboard widget registration
- **Nextcloud NcAppNavigation**: Navigation component framework

### Current Implementation Status
- **Fully implemented** with file paths:
  - `lib/Controller/DashboardController.php` -- page() serving TemplateResponse
  - `lib/Dashboard/AnonymizationWidget.php` -- Nextcloud dashboard widget, order 20
  - `lib/Dashboard/FileEntitiesWidget.php` -- Nextcloud dashboard widget, order 21
  - `lib/AppInfo/Application.php` -- registers both widgets
  - `src/views/dashboard/DashboardIndex.vue` -- consent stats and recent activity
  - `src/views/Views.vue` -- conditional view rendering
  - `src/navigation/MainMenu.vue` -- three-item navigation
  - `src/store/modules/navigation.ts` -- Pinia navigation store
  - `src/dashboard.js` -- dashboard widget script bundle

### Standards & References
- **WCAG 2.1 AA**: Dashboard UI accessibility (color contrast, keyboard navigation)
- **Nextcloud IWidget/IIconWidget**: Standard Dashboard widget API
