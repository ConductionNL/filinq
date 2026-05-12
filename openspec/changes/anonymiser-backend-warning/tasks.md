## Tasks

### Backend
- [ ] Add `OCA\DocuDesk\Service\AnonymiserBackendStateClient` that wraps a call to `OCA\OpenRegister\Service\AnonymisationBackendService::getState()`
- [ ] Add `OCA\DocuDesk\Settings\DocuDeskAdmin` field for `anonymiser_warning_dismissed` (per-admin user value)
- [ ] Add admin endpoint `POST /api/admin/anonymiser-warning/dismiss` and `POST /api/admin/anonymiser-warning/reset`
- [ ] Add unit tests for the dismissal toggle and state-delegation behaviour

### Frontend
- [ ] Add `AnonymiserBackendWarning` Vue component (NL Design System banner, dismissible)
- [ ] Mount component on the DocuDesk admin settings page
- [ ] Mount component on the DocuDesk dashboard, gated on admin group membership
- [ ] Wire the dismiss action to the new admin endpoint
- [ ] NL + EN translations per ADR-005

### Tests
- [ ] Unit test: state query returns `regex` → banner data is exposed to the view
- [ ] Unit test: state query returns `openanonymiser` → banner data is suppressed
- [ ] Unit test: dismissal persists per-admin and survives logout
- [ ] Component test: deep-link URLs match `/settings/apps/installed/{appid}` shape

### Documentation
- [ ] Screenshot the banner in admin settings (NL + EN) → `docs/screenshots/anonymiser-warning-*.png`
- [ ] Update `docs/admin/anonymisation.md` with the four backend options and the install workflow
- [ ] Cross-reference ADR-017 in the admin docs

### Quality Gates
- [ ] `composer check:strict` passes
- [ ] No new PHPCS/PHPMD/PHPStan warnings (fix any pre-existing in touched files per CLAUDE.md)
- [ ] WCAG AA verified on the banner component (focus order, contrast, dismiss button keyboard-accessible)
