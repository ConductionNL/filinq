# Design: Dashboard

## Architecture

### Backend
- `DashboardController::page()` renders the SPA template
- `AnonymizationWidget` (IWidget) for Nextcloud Dashboard
- `FileEntitiesWidget` (IWidget) for Nextcloud Dashboard
- `FileEntityStatsService` provides entity statistics

### Frontend
- `DashboardIndex.vue` is the default landing page
- Displays consent stats cards (Total, Pending, Approved, Objected)
- Shows recent consent activity (up to 10 most recent)
- Embeds `AnonymizationWidget` for quick anonymization
- `AnonymizationDashboardWidget.vue` and `FileEntitiesDashboardWidget.vue` for NC Dashboard

### Navigation
- SPA with Vue Router: Dashboard, Anonymization, Consent Management
- `MainMenu.vue` provides left sidebar navigation

## ADR Compliance
- ADR-003: Color-coded stats cards via CSS variables
- ADR-012: Uses NcEmptyContent, NcAppContent components
