# anonymisation-detector-honesty Specification (delta)

---
status: proposed
---

## Purpose

Filinq reports which entity-detection backend was in force for an
anonymisation run, and refuses to write an output file when none was. Today a
run with no detector produces a file whose bytes are the input's, reports
success, and cannot be told apart from a run that redacted everything it
found.

## ADDED Requirements

### Requirement: Filinq reads OpenRegister's real backend state (REQ-DDADH-001)

`AnonymiserBackendStateClient` MUST resolve OpenRegister's
`AnonymisationBackendService` at the class name OpenRegister declares, and
MUST return the state that service supplies, including
`entityRecognitionEnabled`, `activeMethod` and `effectiveMethod`. When the
service cannot be resolved or throws, the client MUST report that the state is
unknown. It MUST NOT report a specific backend it did not read.

#### Scenario: The state a caller reads is the state OpenRegister holds

- **GIVEN** OpenRegister reports an effective method of `presidio`
- **WHEN** filinq reads the backend state
- **THEN** the state reports `presidio`
- @e2e exclude Service-level cross-app read; covered by PHPUnit.

#### Scenario: An unreachable service is unknown, not regex

- **GIVEN** OpenRegister's backend service cannot be resolved
- **WHEN** filinq reads the backend state
- **THEN** the state reports that it is unknown
- **AND** it does not report `regex`
- @e2e exclude Service-level cross-app read; covered by PHPUnit.

### Requirement: An anonymisation run without a live detector is refused (REQ-DDADH-002)

`AnonymizationService` MUST establish the effective detection backend before
running an anonymisation. When entity recognition is disabled, or the
effective backend is unavailable, or the backend state is unknown, the run
MUST be refused with a message naming the reason. No output file MUST be
written and no anonymisation record MUST be created.

#### Scenario: Detection off means no file

- **GIVEN** entity recognition is disabled on the instance
- **WHEN** a caseworker anonymises a document
- **THEN** the run is refused with a message naming the disabled detector
- **AND** no output file exists
- @e2e exclude Backend refusal path; covered by PHPUnit and the API test.

#### Scenario: An unknown backend state is refused, not assumed

- **GIVEN** filinq cannot read the backend state
- **WHEN** a caseworker anonymises a document
- **THEN** the run is refused
- **AND** no output file exists
- @e2e exclude Backend refusal path; covered by PHPUnit.

### Requirement: A run reports the backend that looked (REQ-DDADH-003)

The result of an anonymisation MUST report the detection backend that was in
force for the run and the number of entities it redacted. A run where a live
backend found no entities MUST report that outcome distinctly from a run that
redacted at least one, and MUST name the backend that found nothing. That run
still writes its output file.

#### Scenario: A caller can tell an empty detection from a dead detector

- **GIVEN** a live detection backend that finds no entities in a document
- **WHEN** a caseworker anonymises it
- **THEN** the result names the backend, reports zero entities redacted, and
  says detection ran
- **AND** the output file exists
- @e2e exclude Backend result shape; covered by PHPUnit and the API test.

#### Scenario: A redacting run names its backend too

- **GIVEN** a live backend that finds four entities
- **WHEN** a caseworker anonymises the document
- **THEN** the result names the backend and reports four entities redacted
- @e2e exclude Backend result shape; covered by PHPUnit.

### Requirement: The admin warning says what is actually configured (REQ-DDADH-004)

The anonymisation settings surface MUST show the effective detection backend
read from OpenRegister and MUST warn only when that backend gives weaker
detection than the instance is configured to expect. The warning MUST name the
backend. It MUST NOT be derived from a value filinq failed to read.

#### Scenario: An admin sees the backend that will run

- **GIVEN** OpenRegister reports an effective method of `openanonymiser`
- **WHEN** an admin opens the anonymisation settings
- **THEN** the surface names `openanonymiser`
- **AND** no weak-detection warning is shown
- e2e: tests/e2e/spec-coverage/anonymisation-detector-honesty.spec.ts

#### Scenario: A regex-only instance is warned by name

- **GIVEN** OpenRegister reports an effective method of `regex`
- **WHEN** an admin opens the anonymisation settings
- **THEN** the warning names `regex` and says what it cannot detect
- e2e: tests/e2e/spec-coverage/anonymisation-detector-honesty.spec.ts
