## Tasks

### Backend
- [x] Add `OCA\DocuDesk\Service\AnonymiserBackendStateClient` that wraps a call to `OCA\OpenRegister\Service\AnonymisationBackendService::getState()`
- [x] Add `OCA\DocuDesk\Settings\DocuDeskAdmin` field for `anonymiser_warning_dismissed` (per-admin user value)
- [x] Add admin endpoint `POST /api/admin/anonymiser-warning/dismiss` and `POST /api/admin/anonymiser-warning/reset`
- [x] Add unit tests for the dismissal toggle and state-delegation behaviour

### Frontend
- [x] Add `AnonymiserBackendWarning` Vue component (NL Design System banner, dismissible)
- [x] Mount component on the DocuDesk admin settings page
- [x] Mount component on the DocuDesk dashboard, gated on admin group membership
- [x] Wire the dismiss action to the new admin endpoint
- [x] NL + EN translations per ADR-005

### Tests
- [x] Unit test: state query returns `regex` → banner data is exposed to the view
- [x] Unit test: state query returns `openanonymiser` → banner data is suppressed
- [x] Unit test: dismissal persists per-admin and survives logout
- [x] Component test: deep-link URLs match `/settings/apps/discover/{appid}` shape

### Documentation
- [~] Screenshot the banner in admin settings (NL + EN) → `docs/screenshots/anonymiser-warning-*.png` — DEFERRED: requires a live OpenRegister stack with the `AnonymisationBackendService::getState()` endpoint returning `regex` (the only state that exposes the banner) plus an admin session in both locales. Build container has no such stack; capture lives with the live-environment verification follow-up.
- [x] Update `docs/admin/anonymisation.md` with the four backend options and the install workflow
- [x] Cross-reference ADR-017 in the admin docs

### Quality Gates
- [x] `composer check:strict` passes
- [x] No new PHPCS/PHPMD/PHPStan warnings (fix any pre-existing in touched files per CLAUDE.md)
- [x] WCAG AA verified on the banner component (focus order, contrast, dismiss button keyboard-accessible)
