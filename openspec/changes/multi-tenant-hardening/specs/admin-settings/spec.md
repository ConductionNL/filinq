# admin-settings Specification (delta)

---
status: proposed
---

## Purpose

Extend the existing admin-settings capability (instance-global IAppConfig
settings, REQ-SET-01..11 and the signing settings requirements) with
per-organisation overrides for the settings that are policy per organisation:
the WOO consent/objection period, the WOO anonymization profile, and the
default huisstijl. Instance-global requirements are unchanged; this delta
only ADDs requirements. Introduced by the `multi-tenant-hardening` change.

## ADDED Requirements

### Requirement: Per-organisation settings are stored as organisationSettings objects (REQ-DDMTH-007)

Per-organisation setting overrides MUST be stored via OpenRegister as
`organisationSettings` objects (new schema in the `document` register of
`lib/Settings/docudesk_register.json`, additive register version bump): at
most one object per organisation, keyed by the object's OR envelope
organisation stamp (no organisation property in the schema, per
REQ-DDMTH-001). The schema MUST carry `name` (string), `consentPeriodDays`
(integer 1–365, nullable), `anonymizationProfile` (object with `anonymize[]`
and `keepVisible[]` entity-category lists, nullable), `defaultHuisstijl`
(string reference to a `huisstijl` object, nullable) and `notes` (string,
nullable); every override property MUST be nullable, where null means
"inherit the instance default". Writing an `organisationSettings` object MUST
require that the caller is an admin of that organisation
(`OrganisationService::isOrganisationAdmin()`) or an instance admin; other
members MUST be able to read but not write it. Instance-global settings
(OpenRegister wiring, enrichment toggles, signing provider configuration)
MUST remain in IAppConfig and MUST NOT gain per-organisation variants in this
change.

#### Scenario: Organisation admin sets a per-organisation objection period

- GIVEN an organisation admin of organisation A opens the DocuDesk settings surface
- WHEN they set the organisation objection period to 42 days and save
- THEN an `organisationSettings` object for organisation A stores `consentPeriodDays: 42`
- AND the instance IAppConfig `publication_objection_period_days` is unchanged
- @e2e tests/e2e/spec-coverage/organisation-settings.spec.ts

#### Scenario: Non-admin member cannot write organisation settings

- GIVEN a regular member of organisation A who is not an organisation admin
- WHEN they attempt to save a change to organisation A's `organisationSettings`
- THEN the write is rejected with 403
- AND the stored object is unchanged
- @e2e tests/e2e/spec-coverage/organisation-settings.spec.ts

### Requirement: Effective settings resolve organisation override, then instance default (REQ-DDMTH-008)

DocuDesk MUST resolve every per-organisation-capable setting through a single
resolution order: the caller's active organisation's `organisationSettings`
value (when the object exists and the property is non-null) → the instance
IAppConfig default → the code default. `SettingsService` MUST expose one
effective-settings helper implementing this order, and the consuming paths
MUST read through it: new consent records compute their objection deadline
from the effective consent period (existing REQ-SET-04 semantics, including
the WOO 4-week-minimum guidance, apply to the effective value); the WOO
anonymization profile used for entity pre-selection
(`WooProfileService` / batch-anonymization profiles) MUST be the effective
profile; document generation MUST use the effective default huisstijl (per
REQ-DDMTH-004). Organisations without an override MUST behave exactly as
before this change.

#### Scenario: Consent deadline uses the organisation override

- GIVEN organisation A has `consentPeriodDays: 42` and the instance default is 28
- WHEN a consent record is created by a user with active organisation A
- THEN its objection deadline is 42 days out
- @e2e tests/e2e/spec-coverage/organisation-settings.spec.ts

#### Scenario: Organisation without an override inherits the instance default

- GIVEN organisation B has no `organisationSettings` object
- WHEN a consent record is created by a user with active organisation B
- THEN its objection deadline uses the instance default of 28 days
- AND the effective anonymization profile equals the instance profile
- @e2e tests/e2e/spec-coverage/organisation-settings.spec.ts

#### Scenario: Effective anonymization profile drives entity pre-selection

- GIVEN organisation A's `anonymizationProfile` keeps `ORGANIZATION` visible but anonymizes `PERSON` and `BSN`
- WHEN a user in organisation A runs an anonymisation entity review
- THEN PERSON and BSN entities are pre-selected for anonymisation and ORGANIZATION entities are not
- @e2e tests/e2e/spec-coverage/organisation-settings.spec.ts
