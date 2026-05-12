## Why

When neither an OpenAnonymiser ExApp nor a custom anonymisation endpoint is configured, DocuDesk silently falls back to regex-only entity recognition. Admins do not know that recognition quality is degraded, and they have no in-app guidance toward the two supported backends. This change adds a non-blocking admin warning banner with deep links to the App Store entries for OpenAnonymiser Light (CPU) and OpenAnonymiser (GPU), and a reference to the custom-endpoint alternative configured in OpenRegister.

- ADR-017 establishes that the user-facing app owns the warning UX for missing AI ExApp backends. DocuDesk is the user-facing app for anonymisation; OpenRegister owns the backend selection but not the discovery prompt.
- The current behaviour matches Nextcloud's `context_chat` failure mode (silent or log-only) which is widely reported as confusing by admins.
- Regex-only mode is functional and privacy-safe — the banner is informational, not blocking.

## Scope

### In Scope
- Admin warning banner visible on DocuDesk admin settings and dashboard when the backend-detection check resolves to "no backend".
- Deep-link CTAs to App Store entries for `openanonymiser_light` and `openanonymiser`.
- Reference link to OpenRegister settings for configuring a custom endpoint.
- Banner dismissible per-admin via `IAppConfig` user value, with a "show again" path in DocuDesk admin settings.
- Detection state queried from OpenRegister (single source of truth — DocuDesk does not duplicate the AppAPI/IAppManager logic).

### Out of Scope
- The backend selection UI itself — that lives in OpenRegister (see companion change `anonymiser-backend-selection`).
- The two ExApp repositories themselves (`openanonymiser_light`, `openanonymiser`) — tracked as separate work.
- End-user (non-admin) messaging — anonymisation degradation is operator-facing only.
- Auto-installation or AppAPI registration — explicitly forbidden by ADR-017.

## Dependencies

- Companion change `openregister/anonymiser-backend-selection` must expose a backend-state query (PHP service or OCS endpoint) that DocuDesk reads.
- AppAPI must be present on the Nextcloud instance for the deep links to be actionable. If AppAPI is missing, the banner additionally instructs the admin to install AppAPI first.
