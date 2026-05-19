## ADDED Requirements

### Requirement: Admin Warning When No Anonymiser Backend Is Available

DocuDesk MUST surface a non-blocking admin warning when entity recognition is operating in regex-only mode AND the admin viewing the page has not dismissed the warning.

#### Scenario: Admin opens DocuDesk admin settings with no backend configured
- **GIVEN** OpenRegister reports backend state `method = 'regex'`
- **AND** the current user is in the admin group
- **AND** the admin has not previously dismissed the warning
- **WHEN** the admin loads the DocuDesk admin settings page
- **THEN** a warning banner is shown at the top of the settings section
- **AND** the banner contains a deep link to the App Store entry for `openanonymiser_light`
- **AND** the banner contains a deep link to the App Store entry for `openanonymiser`
- **AND** the banner contains a link to OpenRegister settings for configuring a custom endpoint
- **AND** the banner contains a "Dismiss" action

#### Scenario: Admin opens DocuDesk dashboard with no backend configured
- **GIVEN** OpenRegister reports backend state `method = 'regex'`
- **AND** the current user is in the admin group
- **AND** the admin has not previously dismissed the warning
- **WHEN** the admin loads the DocuDesk dashboard
- **THEN** the warning banner is shown at the top of the dashboard

#### Scenario: Non-admin user opens DocuDesk dashboard with no backend configured
- **GIVEN** OpenRegister reports backend state `method = 'regex'`
- **AND** the current user is NOT in the admin group
- **WHEN** the user loads the DocuDesk dashboard
- **THEN** the warning banner is NOT shown

#### Scenario: Admin dismisses the warning banner
- **WHEN** the admin clicks "Dismiss" on the warning banner
- **THEN** the dismissal is persisted as a per-admin `IAppConfig` user value
- **AND** the banner is not shown again to this admin on subsequent page loads

#### Scenario: Admin re-enables the warning
- **GIVEN** the admin has previously dismissed the warning
- **WHEN** the admin enables "Show anonymiser backend warning" in DocuDesk admin settings
- **THEN** the dismissal record is cleared
- **AND** the banner is shown again on the next page load

#### Scenario: Backend becomes available
- **GIVEN** the warning was previously visible
- **WHEN** OpenRegister reports backend state changes to any non-`regex` method (e.g. `openanonymiser`, `presidio`, custom URL)
- **AND** the admin loads a DocuDesk admin page
- **THEN** the warning banner is NOT shown
- **AND** dismissal state remains intact (re-shown if backend later disappears)

#### Scenario: AppAPI is not installed
- **GIVEN** OpenRegister reports backend state `method = 'regex'`
- **AND** the `app_api` Nextcloud app is not installed or not enabled
- **WHEN** the admin loads the DocuDesk admin settings page
- **THEN** the warning banner additionally indicates that AppAPI must be installed first
- **AND** the deep-link CTAs to the ExApp entries remain visible

### Requirement: Deep Links Target App Store Entries

The warning banner's CTAs MUST link to Nextcloud App Store entries by app id, not to AppAPI internal admin pages.

#### Scenario: Click on "Install OpenAnonymiser Light"
- **WHEN** the admin clicks the OpenAnonymiser Light CTA
- **THEN** the browser navigates to `/settings/apps/discover/openanonymiser_light` on the current Nextcloud instance
- **AND** the Nextcloud App Store sidebar auto-opens with the app details and the "Download and enable" action
- **AND** no install action is triggered automatically — the admin must confirm install from the sidebar

### Requirement: Detection State Source

DocuDesk MUST NOT query AppAPI, `IAppManager`, or HTTP health endpoints directly to determine backend availability. All detection MUST be delegated to OpenRegister via the `AnonymisationBackendService::getState()` PHP service.

#### Scenario: DocuDesk delegates state lookup to OpenRegister
- **WHEN** DocuDesk needs to determine whether to show the warning
- **THEN** it calls `OCA\OpenRegister\Service\AnonymisationBackendService::getState()`
- **AND** it does not directly call `IAppManager::isEnabledForUser('openanonymiser_light')` or any AppAPI service
- **AND** if OpenRegister is not installed, DocuDesk treats this as a fatal install-time error consistent with its existing OpenRegister dependency
