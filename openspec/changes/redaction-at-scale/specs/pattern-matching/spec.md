## ADDED Requirements

### Requirement: Pattern auto-detection with validation

The system SHALL implement pattern-based redaction matching for six standard patterns (BSN, IBAN, telefoonnummer, email, postcode, kenteken) using regex with optional validators (11-proef for BSN, MOD-97 for IBAN). Each `RedactionPattern` entity SHALL include name, regex, category, validator (optional), and replacement text. When a job runs with an auto-mask profile, the system MUST detect every match of every active pattern, validate matches where a validator is configured, and add a `RedactionAnnotation` with status `pending` or `applied` per profile setting.

#### Scenario: Pattern library is seeded on install
- **WHEN** DocuDesk is installed
- **THEN** six standard patterns are created: BSN (11-proef), IBAN (MOD-97), Telefoonnummer, Email, Postcode, Kenteken
- **AND** each pattern has the regex, category, validator (if applicable), and Dutch replacement text

#### Scenario: BSN validation with 11-proef
- **GIVEN** a pattern for BSN with validator `11-proef`
- **WHEN** the job runs and encounters a 9-digit string matching `\b\d{9}\b`
- **THEN** the system MUST apply the 11-proef checksum algorithm and only create an annotation if the checksum is valid
- **AND** invalid 9-digit strings are skipped (no false-positive annotations)

#### Scenario: IBAN validation with MOD-97
- **GIVEN** a pattern for IBAN with validator `MOD-97`
- **WHEN** the job encounters an IBAN-like string in the format `[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}`
- **THEN** the system MUST apply the MOD-97 checksum and only create an annotation if the checksum is valid

#### Scenario: Pattern matching at high throughput
- **GIVEN** a 50-page PDF document with text layer
- **WHEN** a job runs with the standard pattern library
- **THEN** the system MUST complete pattern matching at ≥50 pages per minute on standard worker tier
- **AND** all patterns are matched in a single pass (no re-scanning)

#### Scenario: Custom patterns can be added to profiles
- **WHEN** a user creates a custom pattern with category `custom`, regex, and replacement text
- **THEN** the pattern is stored and can be included in a profile
- **AND** the custom pattern is treated the same as standard patterns in matching

### Requirement: Regex caching for repeated pattern matching

Pattern regexes SHALL be compiled and cached in memory to avoid recompilation during bulk processing. The cache SHALL be keyed by pattern ID and regex text, with a maximum cache size and TTL configurable per job.

#### Scenario: Regex is compiled once per job
- **WHEN** a job processes multiple documents using the same profile
- **THEN** each pattern's regex is compiled only once and reused across documents
- **AND** subsequent documents process faster due to compiled regex cache

#### Scenario: Cache is cleared between jobs
- **GIVEN** a completed job with cached regexes
- **WHEN** a new job is started
- **THEN** the cache is cleared (or job-scoped) to prevent memory leaks across jobs
