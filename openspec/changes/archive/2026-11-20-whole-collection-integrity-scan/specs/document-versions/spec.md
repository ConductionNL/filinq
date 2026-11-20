# document-versions Specification (delta)

## ADDED Requirements

### Requirement: A scan finds missing, changed and orphaned files across the collection (REQ-DIS-01)

Filinq SHALL offer an integrity scan over all its records. The scan SHALL report a record whose file id no longer resolves as `missing`, a final document whose file no longer matches its frozen checksum as `changed`, and an anonymised copy whose source document is gone as `orphaned`. Each finding SHALL name the record, the file id and the kind. The scan SHALL only read: it SHALL NOT delete, move or change any file or record other than its own result. Each run SHALL be stored as an `integrityScan` object with its start time, end time, the number of records checked, the count per kind and the findings.

#### Scenario: A file of a final document was changed on storage

- **GIVEN** a final document whose file was edited outside filinq
- **WHEN** the integrity scan runs
- **THEN** the run lists that document as `changed` and the file is left as it is

#### Scenario: A dossier member was deleted

- **GIVEN** a dossier that lists a file which was deleted from storage
- **WHEN** the integrity scan runs
- **THEN** the run lists the dossier and the file id as `missing`

#### Scenario: An anonymised copy lost its source

- **GIVEN** an anonymised copy whose source document was deleted
- **WHEN** the integrity scan runs
- **THEN** the run lists the copy as `orphaned`

#### Scenario: A clean collection

- **GIVEN** a collection where every record resolves and every checksum matches
- **WHEN** the integrity scan runs
- **THEN** the run is stored with all three counts at zero

### Requirement: The scan runs nightly and on demand, and administrators see the result (REQ-DIS-02)

The scan SHALL run nightly as a background job and SHALL be startable by an administrator with `occ filinq:integrity:scan` and with "Nu controleren" in the "Integriteitscontrole" section of the admin page. Starting a scan from the page SHALL be refused for a non-administrator. The section SHALL show the last run's time and its count per kind, and each finding SHALL link to its record. A run with at least one `missing` or `changed` finding SHALL send one Nextcloud notification to the administrators.

#### Scenario: The administrator reads the last result

- **GIVEN** a nightly run that found two missing files
- **WHEN** an administrator opens the Integriteitscontrole section
- **THEN** it shows the time of that run, "2 ontbrekend" and a link per finding

#### Scenario: Only administrators start a scan

- **GIVEN** a signed-in user who is not an administrator
- **WHEN** that user posts to the start-scan endpoint
- **THEN** the answer is 403 and no scan runs
- @e2e exclude authorisation; covered by PHPUnit on the controller

#### Scenario: A run with findings notifies once

- **GIVEN** a run that finds three changed files
- **WHEN** the run ends
- **THEN** each administrator gets one notification for the run
- @e2e exclude background job; covered by PHPUnit on IntegrityScanJob
