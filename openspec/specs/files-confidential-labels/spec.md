# files-confidential-labels Specification

## Purpose
TBD - created by archiving change files-confidential-labels. Update Purpose after archive.
## Requirements
### Requirement: Read a file's confidentiality label, availability-guarded (REQ-DDFCL-001)

The app MUST provide `ConfidentialityLabelService::getLabelForFile(int
$fileId): ?ConfidentialityLabel` returning the file's confidentiality label and a
normalised level when `files_confidential` is installed and the file carries a
label matching the configured vocabulary, and returning `null` otherwise. The
service MUST guard on `files_confidential` presence via
`IAppManager::getInstalledApps()` before reading, MUST read labels through
Nextcloud's public system-tag API (`ISystemTagObjectMapper` /
`ISystemTagManager`) rather than any `files_confidential` internal, MUST match tag
names against the admin-configured `docudesk.confidentiality.label_vocabulary`
returning the highest-level match, and MUST NOT throw — any tag-API failure MUST
be treated as "no label" (`null`). DocuDesk MUST load and function normally when
`files_confidential` is absent.

#### Scenario: Labelled file returns its label and level

- GIVEN `files_confidential` is installed and a file is tagged "Confidential" which the vocabulary maps to level 2
- WHEN `getLabelForFile(fileId)` is called
- THEN it returns a label "Confidential" with level 2
- @e2e exclude service-level read — covered by PHPUnit (tests/unit/Service/ConfidentialityLabelServiceTest.php::testLabelledFileReturnsLabel)

#### Scenario: Absent app yields no signal, no crash

- GIVEN `files_confidential` is not installed
- WHEN `getLabelForFile(fileId)` is called
- THEN it returns null and DocuDesk's anonymisation flow proceeds unchanged
- @e2e exclude availability guard — covered by PHPUnit (tests/unit/Service/ConfidentialityLabelServiceTest.php::testAbsentAppReturnsNull)

#### Scenario: Tag-API failure degrades to no label

- GIVEN the system-tag API throws while resolving a file's tags
- WHEN `getLabelForFile(fileId)` is called
- THEN it returns null (the exception is caught) and never propagates into the anonymisation path
- @e2e exclude fault-injection on the tag API — covered by PHPUnit (tests/unit/Service/ConfidentialityLabelServiceTest.php::testTagApiFailureReturnsNull)

### Requirement: Surface the label in the document report and entity-review context (REQ-DDFCL-002)

The appraisal result MUST carry the confidentiality signal when a label resolves: the result returned by `AnonymizationService::extractAndDetectEntities()` MUST include
`confidentialityLabel` (display string) and `confidentialityLevel` (normalised
integer) alongside the existing `entities` and `riskLevel`, and the entity-review
surface MUST show it as a read-only confidentiality chip next to the risk
indicator (NL Design tokens, no hardcoded colour). When no label resolves, the
result MUST omit or null those fields and the review surface MUST show no
confidentiality chip. The signal MUST be informational only — it MUST NOT trigger
any block, redaction, gate or enforcement.

#### Scenario: Review context shows the confidentiality chip

- GIVEN a file tagged "Confidential" is opened for entity review
- WHEN the entity-review context loads
- THEN the appraisal result carries `confidentialityLabel: "Confidential"` and `confidentialityLevel: 2`, and the review surface shows a read-only confidentiality chip beside the risk chip
- @e2e tests/e2e/spec-coverage/files-confidential-labels.spec.ts

#### Scenario: Unlabelled file shows no chip and no fields

- GIVEN a file with no confidentiality label (or `files_confidential` absent)
- WHEN the entity-review context loads
- THEN the appraisal result omits the confidentiality fields and no confidentiality chip is shown, with entities and risk rendered as before
- @e2e exclude negative rendering permutation — covered by component tests and PHPUnit result-merge assertions

### Requirement: Optionally suggest batch/folder analysis priority (REQ-DDFCL-003)

The app MUST expose an admin flag
`docudesk.confidentiality.prioritise_analysis` defaulting to off. When off,
batch/folder analysis ordering MUST be identical to current behaviour. When on,
the analysis enumerator MUST use the normalised confidentiality level (unlabelled
= 0) as a secondary, tie-breaking sort key so higher-confidentiality files are
analysed sooner. The flag MUST only reorder work — it MUST NOT skip, block,
redact or otherwise change what analysis does to any file.

#### Scenario: Flag off leaves ordering unchanged

- GIVEN `prioritise_analysis` is off (default)
- WHEN a folder of mixed-confidentiality files is analysed
- THEN the analysis order is identical to the pre-change behaviour
- @e2e exclude ordering equivalence — covered by PHPUnit (tests/unit/Service/…::testPriorityOffOrderingUnchanged)

#### Scenario: Flag on prioritises higher confidentiality

- GIVEN `prioritise_analysis` is on and a folder contains a "Secret" (level 3) and an unlabelled (level 0) file
- WHEN the folder is analysed
- THEN the "Secret" file is ordered ahead of the unlabelled file as a secondary sort key, and neither file's analysis outcome is otherwise changed
- @e2e exclude secondary-sort ordering — covered by PHPUnit (tests/unit/Service/…::testPriorityOnOrdersByLevel)

