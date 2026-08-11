## ADDED Requirements

### Requirement: Schema names SHALL be English and SHALL name the concept they model

docudesk's `dossier` schema SHALL be renamed to an English name describing what it
actually models — a folder whose contents are anonymised under a legal basis — rather
than to a generic translation of the Dutch word.

#### Scenario: The redaction unit is named for what it is

- **WHEN** the schema describes a Nextcloud folder anonymised under one or more Woo
  Art. 5 grounds
- **THEN** it SHALL be renamed to `RedactionDossier`
- **AND** it SHALL carry a marker recording the Woo Art. 5 basis

#### Scenario: The apparent collision with another app's concept is resolved by evidence

- **WHEN** two Dutch words in different apps both translate naturally to "case"
- **THEN** each schema's own description SHALL decide whether they are one concept
- **AND** docudesk's redaction folder SHALL NOT be named `Case`

#### Scenario: Renaming a schema is treated as a data migration

- **WHEN** the schema is renamed
- **THEN** the count of stored objects SHALL be measured first, excluding soft-deleted
  rows and reading the per-schema shard table rather than the shared objects table
- **AND** the objects SHALL be migrated when that count is non-zero

### Requirement: Cross-app foreign keys SHALL move only in a coordinated window

`generatedDocument.zaakId` SHALL NOT be renamed in this change. It is a foreign key into
procest, which owns the name, and openconnector holds the same key — a unilateral rename
desynchronises the apps without failing any test in any of them.

#### Scenario: The key is held out of the app-local change

- **WHEN** the app-local rename lands
- **THEN** `zaakId` SHALL be unchanged
- **AND** the dependency on procest SHALL be recorded in the change

#### Scenario: The key moves with its owner

- **WHEN** procest renames its `Zaak` schema to `Case`
- **THEN** docudesk SHALL rename `zaakId` to `caseId` in the same window
- **AND** openconnector SHALL do the same

#### Scenario: A passing test suite is not accepted as evidence of safety

- **WHEN** docudesk's suite passes after a unilateral rename of `zaakId`
- **THEN** that result SHALL NOT be treated as evidence the rename is safe
- **AND** the consuming read sites SHALL be diffed explicitly, because consumers read
  with a null-coalescing default

### Requirement: Code identifiers SHALL be renamed across both the PHP and frontend layers

Dutch class, file and method names SHALL be renamed to English wherever they describe
docudesk's own behaviour, in `lib/` and in `src/` alike. A rename that stops at the PHP
boundary leaves the two layers naming one concept differently.

#### Scenario: A frontend identifier is included in scope

- **WHEN** a Dutch-named function such as `inferDossier` exists in the Vue store
- **THEN** it SHALL be renamed in the same change as the PHP identifiers
- **AND** the change SHALL NOT be considered complete while the layers disagree

#### Scenario: A legal-bases service is internationalised

- **WHEN** a service is named for *grondslagen*, the legal bases for redaction
- **THEN** it SHALL be renamed to name legal bases in English
- **AND** the rename SHALL follow the rule that freedom-of-information concepts are
  internationalised rather than preserved in Dutch

### Requirement: Published legal citations SHALL be preserved as values

Identifiers naming published law SHALL be preserved exactly, even where the surrounding
code identifiers are renamed. The code name is ours; the citation is not.

#### Scenario: A Woo article identifier survives the rename

- **WHEN** a redaction basis is recorded as a Woo article reference
- **THEN** that reference SHALL be byte-identical after the change
- **AND** only the surrounding identifiers SHALL be renamed

#### Scenario: The key and the value are classified separately

- **WHEN** a property holds a statutory citation
- **THEN** the property name SHALL be renamed to English
- **AND** the stored value SHALL be preserved
