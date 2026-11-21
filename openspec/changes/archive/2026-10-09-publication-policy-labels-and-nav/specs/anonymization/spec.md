## ADDED Requirements

### Requirement: Publication-policy features are labelled "Publish always" / "Publish never"

The standing-consent feature MUST be presented to users as "Publish always" and the prohibition feature as "Publish never", at the main menu entry, the page title, and the add/edit dialog labels, with NL translations ("Altijd publiceren" / "Nooit publiceren"). Route names, component names, and store identifiers MUST remain unchanged (display labels only).

#### Scenario: Menu shows the renamed labels

- **WHEN** the main navigation is rendered
- **THEN** the standing-consent entry reads "Publish always" and the prohibition entry reads "Publish never"

#### Scenario: Page titles and add/edit dialogs use the new labels

- **WHEN** the Publish-always or Publish-never page is opened
- **THEN** its title uses the new label and its add/edit dialog reads "Add/Edit publish-always rule" / "Add/Edit publish-never rule"

### Requirement: The two publication-policy pages are in the navigation

The navigation MUST list "Publish always" and "Publish never" directly beneath Consent Management, each with a registered icon, so both pages open from the menu and not only by a typed URL. The earlier trim of Dashboard, Folder Analysis, Consent Management and Templates is superseded: those are core entries.

#### Scenario: Both policy pages open from the menu

- **WHEN** the main navigation is rendered
- **THEN** it lists "Publish always" and "Publish never" beneath Consent Management
- **AND** choosing either opens its page
