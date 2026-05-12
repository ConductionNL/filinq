## Context

DocuDesk's anonymisation pipeline delegates entity recognition to OpenRegister's `EntityRecognitionHandler`, which selects a method from `regex | presidio | openanonymiser | llm`. When `openanonymiser` is configured but the endpoint is unreachable (or no method other than `regex` is selected at all), the handler silently falls back to regex. Admins discover the degradation only by inspecting logs or noticing low-quality output.

ADR-017 defines the responsibility split: the user-facing app (DocuDesk) owns the warning + deep links; the foundation app (OpenRegister) owns the backend selection UI and the actual HTTP call.

## Goals / Non-Goals

**Goals**
- Make backend-absent state visible to admins on first DocuDesk admin page load.
- Provide one-click navigation to each supported ExApp's App Store entry.
- Keep the banner dismissible to avoid nag fatigue once the admin has consciously chosen regex-only.

**Non-Goals**
- Implementing backend selection or endpoint configuration (OpenRegister's responsibility).
- Health-checking the ExApp (OpenRegister's responsibility — DocuDesk just reads the result).
- Auto-installing or attempting to register the ExApp (forbidden by ADR-017).
- Showing the banner to non-admin users.

## Decisions

1. **Detection source.** DocuDesk reads backend state from OpenRegister via a single PHP service call (`OCA\OpenRegister\Service\AnonymisationBackendService::getState()` — defined in the companion change). DocuDesk does not query `IAppManager` or AppAPI directly. This avoids two apps drifting on what "detected" means.

2. **Banner trigger condition.** Show the banner iff `state.method === 'regex'` AND `state.dismissed_by_admin !== true`. Any non-regex state (built-in detected, custom URL configured, or admin explicitly chose regex-only and dismissed) suppresses the banner.

3. **Banner placement.** Two surfaces:
   - DocuDesk admin settings page (top of section, persistent until dismissed).
   - DocuDesk dashboard (top of page, only for users in the admin group).
   Non-admin users never see it on either surface.

4. **Deep-link targets.** App Store URLs use `/settings/apps/discover/{appid}` — verified against `apps/appstore/src/router/routes.ts` (master) and `apps/settings/src/router/routes.ts` (stable30). The `:id` segment drives the App Store sidebar panel (`AppstoreSidebar.vue` reads `route.params.id`), which auto-opens with the app's details and the "Download and enable" action. `/settings/apps` redirects to `discover/...` by default when the App Store is enabled, so this URL also matches the admin's normal navigation pattern. We do not try to bypass the AppAPI install flow — the admin still clicks "Download and enable" from the sidebar.

5. **Dismissibility.** Per-admin (stored as `IAppConfig` user value with key `docudesk.anonymiser_warning_dismissed`). A separate setting in DocuDesk admin lets a previously-dismissed admin restore the banner.

6. **AppAPI-missing fallback.** If `app_api` is not installed/enabled, the banner adds a leading line: "AppAPI is not installed. Install it from the App Store before installing OpenAnonymiser." The deep links still target the ExApp entries (clicking them in that state surfaces the standard Nextcloud "AppAPI required" message).

7. **Copy.** Banner uses NL/EN per ADR-005. Dutch copy avoids the word "fout" — this is informational, not an error.

## Risks and Mitigations

- **Risk:** Banner persists after admin installs ExApp but before re-checking state. **Mitigation:** State query is cheap (single OCS or service call); page load always re-queries. No caching.
- **Risk:** Two admins disagree on dismissal. **Mitigation:** Dismissal is per-admin (`IAppConfig` user-scoped), not instance-wide.
- **Risk:** Deep-link URL pattern changes in future Nextcloud versions. **Mitigation:** Centralise URL construction in one helper; cover with one test that asserts the URL shape.

## Open Questions

- Should the banner also offer "Run OpenAnonymiser at Conduction Cloud" as a third CTA? Deferred — depends on whether/when a hosted offering exists.
- Should the dashboard banner be auto-collapsed (link-only) vs full banner? UX call — propose full banner for v1, downgrade if admins complain.
