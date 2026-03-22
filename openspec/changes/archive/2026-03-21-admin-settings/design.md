# Design: Admin Settings

## Architecture

### Backend
- `DocuDeskAdmin` (ISettings) in `lib/Settings/` renders the settings form template
- `DocuDeskAdmin` (IIconSection) in `lib/Sections/` registers the admin section with icon
- `SettingsController` provides REST API: `GET/POST /api/settings`
- `SettingsService` handles persistence via `IAppConfig`
- `SettingsInitializer` imports `docudesk_register.json` on boot via `ConfigurationService::importFromApp()`
- `RegisterDiscoveryService` fetches available registers/schemas from OpenRegister

### Frontend
- `Settings.vue` in `src/views/settings/` renders the admin panel
- Entry point: `settings.js` registered via `info.xml` admin settings
- Uses `NcSettingsSection` components for Consent Settings, Metadata Enrichment, Data Storage

### Data Flow
- Settings stored in `oc_appconfig` table via `IAppConfig`
- Feature toggles: `enable_language_detection`, `enable_keyword_extraction`, `enable_topic_classification`
- Consent period: `publication_objection_period_days` (default 28)
- Register/schema config: `publicationConsent_register`, `publicationConsent_schema`, `template_register`, `template_schema`

## ADR Compliance
- ADR-001: All data via OpenRegister (no custom tables)
- ADR-003: NL Design System tokens via CSS variables
- ADR-008: Controller -> Service -> Mapper layering
