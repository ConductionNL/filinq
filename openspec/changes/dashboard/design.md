## Context

DocuDesk is a Nextcloud app for document anonymization and consent management. Before this change, opening DocuDesk took the user directly to the anonymization view with no summary of consent activity. The app was also completely absent from the Nextcloud Dashboard home screen. Users had to navigate to Consent Management and count records manually to understand their workload. The primary store of runtime data is the Pinia `consentStore`, which already fetched consent records from the OpenRegister backend — the dashboard needed only to read that store, not add any new data layer.

Two Nextcloud integration points are available for passive app visibility: the top-bar navigation entry (always visible, uses `app.svg`) and Dashboard widgets (opt-in by the user, higher real-estate, uses `app-dark.svg` for better visibility on the Dashboard's typically light background).

**Key constraint (ADR-001):** All data operations go through OpenRegister — no custom database tables. The dashboard is a pure consumer; it owns no schema objects.

**Key constraint (ADR-012):** Must use `@conduction/nextcloud-vue` components (`CnDashboardPage`) — do not build custom equivalents.

**Key constraint (ADR-003):** Color-coded stat cards must use NL Design System tokens via Nextcloud CSS variables. No hardcoded hex colors.

## Goals / Non-Goals

**Goals:**
- Landing page that shows consent statistics (Total, Pending, Approved, Objected) as color-coded stat cards
- Recent consent activity list (up to 10 most recent) with entity text and status badge
- Quick anonymization widget embedded on the dashboard (no navigation away required)
- Two Nextcloud Dashboard widgets registered so users can pin them to their home screen
- Three-item SPA navigation (Dashboard, Anonymization, Consent Management) with active state tracking
- DashboardController serving the SPA shell TemplateResponse with default Nextcloud CSP

**Non-Goals:**
- Server-side dashboard data aggregation endpoint (frontend reads Pinia store directly)
- Persistent per-user dashboard widget configuration beyond what Nextcloud Dashboard provides natively
- Real-time push updates (page-load fetch is sufficient)
- Recursive or cross-register statistics (total is scoped to the active consent register)

## Decisions

### D1. Dashboard reads Pinia consentStore — no new API endpoint

**Decision:** `DashboardIndex.vue` reads `consentStore.consentStats` and `consentStore.consents` directly. No dedicated dashboard summary endpoint.

**Alternatives considered:**
- **New `GET /api/dashboard/stats` endpoint:** Adds a server round-trip for data the store already has. Consent data is already paginated and cached in the store from the Consent Management view. Duplication without benefit.

**Rationale:** The store is the single source of truth for consent data in the SPA. Dashboard stats are a derived view (count by status) trivially computed on the frontend from `consentStore.consents`. Adding a backend endpoint would require a custom aggregate query that violates ADR-001's intent (data shape owned by OpenRegister schema, not custom queries).

### D2. Two separate Widget PHP classes (AnonymizationWidget + FileEntitiesWidget)

**Decision:** Each widget is its own PHP class implementing `IWidget` + `IIconWidget`. Both load the same `docudesk-dashboard` script bundle but render different Vue components.

**Alternatives considered:**
- **Single widget class with a type parameter:** Nextcloud's `IWidget` interface does not support parameterized instances. Widget IDs must be unique PHP class instances.

**Rationale:** Nextcloud's `registerDashboardWidget()` accepts a class name string. One class = one widget ID. Two separate classes is the only correct approach per Nextcloud platform conventions.

### D3. Active navigation state in Pinia navigationStore (not Vue Router)

**Decision:** View switching is driven by `navigationStore.selected` (a string enum), not a URL-based Vue Router. `Views.vue` renders components conditionally based on this value.

**Rationale:** DocuDesk is a single-page Nextcloud app served from `GET /`. Nextcloud's SPA convention uses Pinia stores for in-app navigation state rather than URL routing, to avoid conflicts with Nextcloud's own routing layer. This pattern is consistent with other Nextcloud apps.

### D4. app.svg vs app-dark.svg for different contexts

**Decision:** Navigation entry (`info.xml`) uses `app.svg`; dashboard widgets and admin settings use `app-dark.svg`.

**Rationale:** The Nextcloud top-bar navigation renders icons on a dark background (the app navigation sidebar), so `app.svg` (light-colored logo) is appropriate. The Nextcloud Dashboard page has a light background; `app-dark.svg` (dark-colored logo) provides better contrast. `IIconWidget::getIconUrl()` returns the icon path used by the Dashboard widget renderer.

### D5. Dead code cleanup in DashboardController

**Decision:** Remove `index()` method and `addAllowedConnectDomain('*')` CSP override. Keep `page()` with the unused `?string $getParameter` documented as dead code (not yet removed to avoid breaking route registrations that might pass the parameter).

**Rationale:** `addAllowedConnectDomain('*')` is a security regression (too-permissive CSP). The `index()` method duplicated `page()` with no route registered. Both are safe to remove. The `$getParameter` on `page()` is a method signature artifact; its route doesn't pass any parameter, but removing it is a trivial future cleanup that carries no risk.

## Architecture

### Backend

```
lib/
  Controller/
    DashboardController.php       # page() → TemplateResponse (SPA shell)
  Dashboard/
    AnonymizationWidget.php       # IWidget + IIconWidget, id: docudesk-anonymization, order: 20
    FileEntitiesWidget.php        # IWidget + IIconWidget, id: docudesk-file-entities, order: 21
  AppInfo/
    Application.php               # registerDashboardWidget() for both classes
```

### Frontend

```
src/
  views/
    dashboard/
      DashboardIndex.vue          # Consent stats cards + recent activity + quick anonymization
    Views.vue                     # Conditional rendering: dashboard|anonymization|consent|consentDetail
  navigation/
    MainMenu.vue                  # Three-item sidebar: Dashboard, Anonymization, Consent Management
  store/modules/
    navigation.ts                 # Pinia store: selected view, active nav item
  dashboard.js                    # Webpack entry point for NC Dashboard widget bundle
```

### Data flow

```
DashboardIndex.vue
  └── reads consentStore.consentStats    → Total / Pending / Approved / Objected counts
  └── reads consentStore.consents        → Recent 10 records (entityText + consentStatus)
  └── embeds AnonymizationWidget.vue     → drag-and-drop upload without leaving dashboard

MainMenu.vue
  └── writes navigationStore.selected   → 'dashboard' | 'anonymization' | 'consent' | 'consentDetail'

Views.vue
  └── reads navigationStore.selected    → conditionally renders the correct view component
```

## Seed Data

The dashboard consumes `PublicationConsent` objects from OpenRegister. Example records (Dutch values) that seed the dashboard stats and recent activity list:

```json
[
  {
    "uuid": "a1b2c3d4-0001-0000-0000-000000000001",
    "entityText": "Jan de Vries",
    "entityType": "PERSON",
    "documentId": "woo-besluit-2024-0042",
    "consentStatus": "pending",
    "notificationStatus": "sent",
    "publicationDecision": "pending",
    "objectionDeadline": "2026-06-17T00:00:00+02:00"
  },
  {
    "uuid": "a1b2c3d4-0002-0000-0000-000000000002",
    "entityText": "Gemeente Amsterdam",
    "entityType": "ORGANIZATION",
    "documentId": "woo-besluit-2024-0042",
    "consentStatus": "consent_given",
    "notificationStatus": "delivered",
    "publicationDecision": "publish_with_consent",
    "objectionDeadline": "2026-05-01T00:00:00+02:00"
  },
  {
    "uuid": "a1b2c3d4-0003-0000-0000-000000000003",
    "entityText": "Fatima El-Amrani",
    "entityType": "PERSON",
    "documentId": "avg-verzoek-2025-0117",
    "consentStatus": "objection_received",
    "notificationStatus": "delivered",
    "publicationDecision": "pending",
    "objectionDeadline": "2026-04-15T00:00:00+02:00",
    "objectionReason": "Ik ga niet akkoord met publicatie van mijn persoonsgegevens."
  },
  {
    "uuid": "a1b2c3d4-0004-0000-0000-000000000004",
    "entityText": "Hendrik Bakker",
    "entityType": "PERSON",
    "documentId": "woo-besluit-2024-0099",
    "consentStatus": "no_response",
    "notificationStatus": "sent",
    "publicationDecision": "publish_anonymized",
    "objectionDeadline": "2026-03-01T00:00:00+01:00"
  },
  {
    "uuid": "a1b2c3d4-0005-0000-0000-000000000005",
    "entityText": "Priya Ganpat",
    "entityType": "PERSON",
    "documentId": "avg-verzoek-2025-0203",
    "consentStatus": "anonymized",
    "notificationStatus": "delivered",
    "publicationDecision": "publish_anonymized",
    "objectionDeadline": "2026-02-10T00:00:00+01:00"
  }
]
```

With this seed data the dashboard stat cards would show: **Total: 5 · Pending: 1 · Approved: 1 · Objected: 1** (no_response and anonymized are counted in Total but not in the three highlighted categories).

## Open Questions

None — implementation is complete and decisions are confirmed by the running codebase.
