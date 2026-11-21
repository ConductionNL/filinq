# document-creatie-sjablonen Specification (delta)

## Purpose

A periodic document is produced on its cadence without anyone starting it,
as a real file. Matrix row `gen-periodic` (filinq).

## ADDED Requirements

### Requirement: A periodic document runs on its cadence and produces a file (REQ-PDS-001)

Filinq MUST run every active periodic document whose cadence has come round
since its last run, without a user action, and each run MUST render the
named template over the records the saved view returns into a stored PDF.
A run that fails MUST record the reason on the schedule and MUST NOT change
the previous document.

#### Scenario: The weekly besluitenlijst appears on Monday

- GIVEN an active weekly periodic document "Besluitenlijst" last run seven days ago
- WHEN the hourly job runs
- THEN a new PDF of the besluitenlijst is stored and the schedule's last run points at it
- @e2e exclude a cron cadence is not driven in a browser, covered by PHPUnit (tests/unit/Service/PeriodicDocumentServiceTest.php::testTheSweepRunsWhatIsDueAndSkipsTheRest and tests/unit/BackgroundJob/PeriodicDocumentJobTest.php)

#### Scenario: A broken view is visible on the schedule

- GIVEN a periodic document whose saved view was deleted
- WHEN the job runs it
- THEN the schedule records "The view no longer exists" and last week's document is unchanged
- @e2e exclude a cron cadence is not driven in a browser, covered by PHPUnit (tests/unit/Service/PeriodicDocumentServiceTest.php::testABrokenViewIsWrittenOnTheScheduleAndLastWeeksDocumentStays)
