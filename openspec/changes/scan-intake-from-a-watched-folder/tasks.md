# Tasks: scan-intake-from-a-watched-folder

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11.
     Acceptance criteria are plain bullets, not checkboxes. -->

Supports Woo row 1.7. Decision D4. Wave 2. Build rules: `openspec/woo-build-rules.md`.

**Do not start before** `integriq/sources-sftp-adapter-intake-hand-over` is merged on integriq
`development`. Then read `lib/Event/WatchedFileArrivedEvent.php` and `docs/features/watched-folder.md`
there: the constructor, `accept()`, `getResult()` and its keys, and the accessors for
`synchronizationId`, `intakeApp`, `fileId`, `path` and `ownerUid`. integriq's spec names no
accessors; use the real ones and never guess. If it is not merged, stop and say so.

Every test named here fails on `development` today: no listener for the event exists and
`ScanBatchService::take()` does not exist. Show one failing line in the PR body.

## 1. One intake path

- [ ] 1.1 Add `ScanBatchService::take(File $file, array $profile): array` holding the find-or-receive-then-split flow of `ScanIntakeController::split()`, and make the controller call it (REQ-SCW-001, REQ-SCW-003). Files: `lib/Service/ScanBatchService.php`, `lib/Controller/ScanIntakeController.php`.
  - GIVEN a stored batch for the file WHEN taken THEN it is not received again.
  - Test: PHPUnit `ScanBatchServiceTest::testTakeReceivesOnceAndSplits`, `::testTakeReusesAStoredBatch`; new `ScanIntakeControllerTest::testSplitStillWorksThroughTake` with the controller calling `take()`, beside the existing controller tests, which stay green.

## 2. The listener

- [ ] 2.1 Add `lib/EventListener/WatchedFileArrivedListener.php` and register it for `\OCA\Integriq\Event\WatchedFileArrivedEvent::class` in the registrar that holds the other cross-app listeners (`lib/AppInfo/IntegrationLeafRegistrar.php` or its sibling), with the `class_exists` guard (REQ-SCW-001, REQ-SCW-003).
  - GIVEN an event for `intakeApp: filinq` and a PDF in a profile folder WHEN dispatched THEN a batch is taken and `accept()` gets its uuid.
  - GIVEN an event for another app WHEN dispatched THEN nothing is written and nothing accepted.
  - Test: PHPUnit `WatchedFileArrivedListenerTest::testABatchIsTakenAndAccepted`, `::testAnotherIntakesFileIsIgnored`, `::testAnUnrelatedEventIsIgnored`, constructing the REAL integriq event class (autoload it from integriq's source in the test bootstrap, or skip with a stated reason when integriq is not available, and say so in the PR body).
- [ ] 2.2 Fail closed and once per file (REQ-SCW-002).
  - Test: PHPUnit `WatchedFileArrivedListenerTest::testWithoutAProfileNothingIsAccepted`, `::testAFolderIdIsNotAccepted`, `::testAMissingFileIsNotAccepted`, `::testANonPdfIsNotAccepted`, `::testAStoreFailureIsNotAccepted`, `::testAFailedCutIsAcceptedWithItsBatch`, `::testASecondHandOverIsAcceptedWithTheSameBatch`. Double `IRootFolder` and `ScanBatchRepository` after reading their real signatures; `findByFile()` answers null, it does not throw.
- [ ] 2.3 Through the caller: dispatch the real event through a real `IEventDispatcher` wired by `Application::register()` (REQ-SCW-001).
  - Test: PHPUnit `tests/integration/WatchedFolderScanIntakeTest.php::testTheDispatcherReachesTheListener` asserts `getResult()` is `['accepted' => true, 'intakeApp' => 'filinq', 'reference' => <uuid>]`.
- [ ] 2.4 Contract test on filinq's side (REQ-SCW-001).
  - Test: PHPUnit `WatchedFileArrivedContractTest::testTheEventMatchesIntegriqsSpec` asserts the constructor parameter names and types, `accept(string)`, the three `getResult()` keys and the accessor names, as literals copied from integriq's class with its source line. Name integriq's `WatchedFileIngestJobTest::testAnAcceptedHandOverMarksTheFile` in the PR body.

## 3. Bookkeeping and docs

- [ ] 3.1 Tick task 3.2 of `scan-intake-with-separator-sheets` with a pointer to this change, and update its design D2 to say integriq's watcher replaces the `FolderExtractionJob` walk (D4). Add `docs/features/scan-intake.md` a section on the watched folder: set up a synchronization in integriq with `target: intake` and `intakeApp: filinq` on the profile's folder.
  - Test: `openspec validate scan-intake-with-separator-sheets --strict` and `openspec validate scan-intake-from-a-watched-folder --strict` pass.
- [ ] 3.2 Live after merge on the dev instance with integriq carrying the hand-over: configure the synchronization, drop a two-document batch with one separator sheet in the folder, and paste the `scanBatch`, the two intake documents and the synchronization log line (accepted, reference) in the PR body.
  - Test: the pasted evidence. Set `appstoreenabled=false` before any `occ upgrade` on a mounted clone.

## Verification

The building agent follows `openspec/woo-build-rules.md`:

- [ ] Own clone, `git checkout --no-track -b <branch> origin/development`, `TMPDIR` a sibling outside the clone.
- [ ] PHPUnit judged by the `Tests:` line, or with `--no-coverage`; a green suite exits 1 without a coverage driver. `run-hydra-gates.sh --base origin/development`, counting the gates that ran.
- [ ] Once before push: `COMPOSER_PROCESS_TIMEOUT=0 composer check:strict`, `npm run lint`, and every check `code-quality.yml` requires, read from `package.json`. The coverage guard needs a test for every added statement.
- [ ] One PR, `--base development`; merge `development` in, never rebase; no `Co-Authored-By` trailer. Done means merged on `development` with CI green; row 1.7 is `production` only once store releases of integriq and filinq carry it.
