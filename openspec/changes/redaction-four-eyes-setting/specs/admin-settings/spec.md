# admin-settings Specification (delta)

## ADDED Requirements

### Requirement: The admin chooses when four eyes are required (REQ-SET-12)

The Anonymisation section of filinq's admin page SHALL offer the setting "Vier ogen bij anonimiseren" with three choices: "Alleen bij Woo-zaken" (`woo`), "Altijd" (`always`) and "Uit" (`off`). The value SHALL be stored as app config key `redaction_four_eyes`, SHALL default to `woo` when the key is unset, and SHALL be writable only by an administrator through the settings API. An unknown value SHALL be refused with 400 and the stored value SHALL stay as it was.

#### Scenario: A fresh install requires four eyes for Woo cases

- **GIVEN** an install where `redaction_four_eyes` was never set
- **WHEN** an administrator opens the Anonymisation section
- **THEN** "Alleen bij Woo-zaken" is selected

#### Scenario: The administrator switches it off

- **GIVEN** the setting is `woo`
- **WHEN** an administrator picks "Uit" and saves
- **THEN** `redaction_four_eyes` is `off` and one officer may check and anonymise a Woo document

#### Scenario: An unknown value is refused

- **GIVEN** the setting is `woo`
- **WHEN** an administrator posts `redaction_four_eyes` = `sometimes`
- **THEN** the answer is 400 and the setting stays `woo`
- @e2e exclude API validation; covered by PHPUnit on SettingsService
