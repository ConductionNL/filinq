## ADDED Requirements

### Requirement: Publication-policy features are labelled "Publish always" / "Publish never"

The standing-consent feature MUST be presented to users as "Publish always" and the prohibition feature as "Publish never", at the main menu entry, the page title, and the add/edit dialog labels, with NL translations ("Altijd publiceren" / "Nooit publiceren"). Route names, component names, and store identifiers MUST remain unchanged (display labels only).

#### Scenario: Menu shows the renamed labels

- **WHEN** the main navigation is rendered
- **THEN** the standing-consent entry reads "Publish always" and the prohibition entry reads "Publish never"

#### Scenario: Page titles and add/edit dialogs use the new labels

- **WHEN** the Publish-always or Publish-never page is opened
- **THEN** its title uses the new label and its add/edit dialog reads "Add/Edit publish-always rule" / "Add/Edit publish-never rule"

### Requirement: The main menu is trimmed to the focused workflow

The main navigation MUST NOT surface the Dashboard, Folder Analysis, Consent Management, or Templates entries. Their routes and components MUST remain so the pages stay reachable by direct URL (hiding is navigation-only, not removal).

#### Scenario: Hidden entries are absent from the menu

- **WHEN** the main navigation is rendered
- **THEN** it does not contain Dashboard, Folder Analysis, Consent Management, or Templates entries

#### Scenario: Hidden pages remain routable

- **GIVEN** a hidden page's route (e.g. `/dashboard`, `/templates`)
- **WHEN** it is navigated to directly
- **THEN** the page still loads (the route and component are retained)
