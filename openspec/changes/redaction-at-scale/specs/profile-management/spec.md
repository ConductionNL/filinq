## ADDED Requirements

### Requirement: Redaction profile definition and management

The system SHALL support creating and managing `RedactionProfile` entities with the following fields: name, description, `patterns[]` (array of pattern IDs), `entityTypes[]` (array of entity types to recognize), allowList[] (terms never to redact), denyList[] (terms always to redact), language, owner, sharedWith[] (list of users/groups with read-only access), and version (integer, incremented on update).

#### Scenario: Profile is created with all fields
- **WHEN** a user creates a profile with:
  ```json
  {
    "name": "Woo-Publicatie",
    "description": "Standard redaction for Woo publication",
    "patterns": ["pattern-bsn", "pattern-iban", "pattern-phone"],
    "entityTypes": ["PERSON", "ORG"],
    "allowList": ["Gemeente Utrecht"],
    "denyList": [],
    "language": "nl-NL",
    "owner": "alice"
  }
  ```
- **THEN** the profile is stored with `version: 1`
- **AND** subsequent requests return the same data

#### Scenario: Profile defaults apply when fields are omitted
- **WHEN** a user creates a minimal profile with only `name` and `description`
- **THEN** defaults are applied:
  - `patterns[]`: all standard patterns (BSN, IBAN, phone, email, postcode, kenteken)
  - `entityTypes[]`: ["PERSON", "ORG"]
  - `allowList[]`: [] (empty)
  - `denyList[]`: [] (empty)
  - `language`: system default (e.g., "nl-NL")
  - `owner`: requesting user
  - `sharedWith[]`: [] (not shared)

#### Scenario: Profiles can be listed and filtered
- **WHEN** a user requests `GET /api/redactions/profiles?owner=alice&language=nl-NL`
- **THEN** the response lists profiles matching the filters
- **AND** each profile includes `sharedWith[]` to show which users/groups have access

### Requirement: Profile versioning

Profiles are versioned. When a profile is updated (patterns, entity types, allow/deny lists), the version is incremented. Jobs record the profile UUID and version used, ensuring reproducibility.

#### Scenario: Updating a profile increments version
- **GIVEN** a profile with `version: 1`
- **WHEN** the owner updates the `allowList` to add "CIBG"
- **THEN** the profile `version` is set to `2`
- **AND** a new record is stored (not overwriting v1)
- **AND** subsequent calls to `GET /api/redactions/profiles/<profileId>` return v2

#### Scenario: Jobs record profile version
- **GIVEN** a profile with `version: 2`
- **WHEN** a job is created using this profile
- **THEN** the job stores `profileId` and `profileVersion: 2`
- **AND** if the profile is later updated to v3, the job remains bound to v2

#### Scenario: Profile version history
- **WHEN** a user requests `GET /api/redactions/profiles/<profileId>/versions`
- **THEN** the response lists all versions of the profile with timestamps and change summaries
- **AND** the user can view/restore prior versions (if permitted)

### Requirement: Profile sharing and read-only access

A profile owner can share a profile with other users or groups via the `sharedWith[]` field. Shared profiles are read-only for non-owners.

#### Scenario: Owner shares profile with group
- **GIVEN** a profile owned by alice
- **WHEN** alice updates `sharedWith: [{"type": "group", "id": "redaction-team"}]`
- **THEN** all members of the `redaction-team` group can read the profile and use it in jobs
- **AND** they cannot modify it

#### Scenario: Non-owner cannot modify shared profile
- **GIVEN** a profile owned by alice, shared with bob
- **WHEN** bob attempts `PATCH /api/redactions/profiles/<profileId>` with updated allowList
- **THEN** the response is 403 Forbidden
- **AND** the profile remains unchanged

#### Scenario: Owner can revoke access
- **GIVEN** a profile shared with bob
- **WHEN** the owner removes bob from `sharedWith[]`
- **THEN** bob can no longer access the profile via `GET` or use it in new jobs
- **AND** existing jobs that used this profile continue to work (history preserved)

### Requirement: Built-in profiles for common use cases

The system SHALL ship with at least three seed profiles for common Woo/AVG use cases:
1. **Woo-Publicatie**: All patterns + PERSON/ORG NLP, standard allow/deny lists, Dutch language.
2. **AVG-Inzageverzoek**: All patterns + PERSON/ORG NLP, higher NLP confidence, exclude requestor from redaction.
3. **Juridische Procedure**: BSN, IBAN, phone, email only (no NLP), minimal allow/deny.

#### Scenario: Seed profiles are available after install
- **WHEN** DocuDesk is installed with redaction enabled
- **THEN** `GET /api/redactions/profiles` returns at least three profiles
- **AND** each has a stable slug (e.g., "woo-publicatie", "avg-inzageverzoek", "juridische-procedure")

#### Scenario: Users can clone and customize seed profiles
- **WHEN** a user requests to clone "woo-publicatie"
- **THEN** a new profile is created with the same settings
- **AND** the owner is set to the requesting user
- **AND** `version: 1` for the clone

### Requirement: Pattern and entity type management within profiles

Profiles reference patterns by ID and allow/deny specific entity types. Users can add custom patterns to profiles.

#### Scenario: Pattern list is updated in profile
- **WHEN** a user removes "pattern-postcode" and adds a custom pattern "pattern-credit-card"
- **THEN** the profile's `patterns[]` array is updated
- **AND** `version` is incremented
- **AND** jobs using the new version will apply the updated patterns

#### Scenario: Allow list prevents specific terms
- **GIVEN** a profile with `allowList: ["Gemeente Utrecht", "RDW"]`
- **WHEN** a job runs with this profile
- **THEN** "Gemeente Utrecht" and "RDW" are never redacted even if detected by patterns or NLP
- **AND** other entities are redacted normally

#### Scenario: Deny list forces redaction of specific terms
- **GIVEN** a profile with `denyList: ["Top Secret Project Alpha"]`
- **WHEN** a job runs with this profile
- **THEN** "Top Secret Project Alpha" is always redacted, regardless of whether it matches patterns
- **AND** the annotation is marked with `originEntityType: "deny_list"`
