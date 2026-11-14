# print-preview Specification (delta)

## ADDED Requirements

### Requirement: A handler sends letters to print and follows the job (REQ-PJA-001)

The app MUST let a handler send one letter or a selection of letters from
Correspondence to print as one job, and MUST list the handler's own print jobs
with their status and a download of the PDF. Job state MUST be stored as an
OpenRegister object and the PDF as a file, never as app configuration.

#### Scenario: Three letters go to print as one job

- GIVEN a handler with three letters selected in Correspondence
- WHEN they choose Send to print
- THEN one job is created with three items and appears on the Print jobs page as queued
- @e2e tests/e2e/spec-coverage/print-jobs.spec.ts

#### Scenario: The print service reports back

- GIVEN a queued job
- WHEN the print service reports it printed
- THEN the Print jobs page shows it as printed with the time
- @e2e tests/e2e/spec-coverage/print-jobs.spec.ts

#### Scenario: Somebody else's jobs stay out of the list

- GIVEN two handlers who each sent a job
- WHEN one opens the Print jobs page
- THEN only their own job is listed
- @e2e exclude a second session is needed, covered by PHPUnit (tests/unit/Controller/PrintJobControllerTest.php)

#### Scenario: No PDF in the app configuration

- GIVEN a job was created
- WHEN the app configuration is read
- THEN it holds no print job entry
- @e2e exclude storage check with no UI surface, covered by PHPUnit (tests/unit/Service/PrintJobServiceTest.php)
