## ADDED Requirements

### Requirement: filinq takes a scanned batch that integriq's watched folder hands over (REQ-SCW-001)

filinq SHALL register `OCA\Filinq\EventListener\WatchedFileArrivedListener` for
`OCA\Integriq\Event\WatchedFileArrivedEvent`. For an event whose `intakeApp` is `filinq`, the
listener SHALL resolve the file by its `fileId` in the folder of `ownerUid`, resolve the scan
profile for the file's path as `ScanIntakeController::resolveProfile()` does, pass both to
`ScanBatchService::take(File $file, array $profile): array`, and then call `accept()` with the uuid
of the stored `scanBatch`. For an event naming any other `intakeApp` the listener SHALL do nothing.
`take()` SHALL be the one find-or-receive-then-split path, called by `ScanIntakeController::split()`
as well.

#### Scenario: A scanned batch from the watched folder becomes a scan batch
- GIVEN a scan profile watching `Scans/post` and a watched-folder synchronization with `target: intake` and `intakeApp: filinq` on that folder
- WHEN integriq dispatches `WatchedFileArrivedEvent` for a PDF that landed there
- THEN one `scanBatch` exists for that file id, it is cut into its segments, and `getResult()` answers `accepted: true`, `intakeApp: filinq` and `reference` the batch's uuid
- @e2e exclude a background hand-over between two apps; covered by PHPUnit `WatchedFileArrivedListenerTest::testABatchIsTakenAndAccepted`, dispatching the real integriq event class through a real `IEventDispatcher`

#### Scenario: Another intake's file is left alone
- GIVEN an event with `intakeApp: dossiq`
- WHEN filinq's listener receives it
- THEN no `scanBatch` is written and the result stays not accepted
- @e2e exclude a backend listener; covered by PHPUnit `WatchedFileArrivedListenerTest::testAnotherIntakesFileIsIgnored`

### Requirement: filinq accepts only what it stored, and each file once (REQ-SCW-002)

The listener SHALL NOT call `accept()` when the file id resolves to nothing or to a folder, when the
file is not a PDF, when no scan profile watches its folder, or when storing the batch throws. When
the file already has a `scanBatch` (`ScanBatchRepository::findByFile()`), the listener SHALL accept
with that batch's uuid and SHALL NOT receive or cut it again. A batch that is stored but fails to cut
SHALL be accepted, because its `failed` `scanBatch` now owns the file.

#### Scenario: No profile, no acceptance
- GIVEN a PDF in a folder no scan profile watches
- WHEN the event arrives with `intakeApp: filinq`
- THEN no `scanBatch` is written and the result stays not accepted, so integriq leaves the file and reports it unclaimed
- @e2e exclude a fail-closed path; covered by PHPUnit `WatchedFileArrivedListenerTest::testWithoutAProfileNothingIsAccepted`

#### Scenario: The same file twice
- GIVEN a file already taken as batch `b-1`
- WHEN the event arrives again for the same file id
- THEN no second `scanBatch` is written, the batch is not cut again, and the result is accepted with reference `b-1`
- @e2e exclude idempotency of a backend path; covered by PHPUnit `WatchedFileArrivedListenerTest::testASecondHandOverIsAcceptedWithTheSameBatch`

### Requirement: Without integriq the scan intake works as today (REQ-SCW-003)

When integriq is not installed the listener SHALL never run, and the listener SHALL check
`class_exists` for the event class before reading it. `ScanIntakeController::split()` SHALL keep its
behaviour, now through `take()`.

#### Scenario: No integriq
- GIVEN integriq is not installed
- WHEN a clerk uploads a batch and asks the controller to split it
- THEN the batch is received and cut exactly as before this change
- @e2e exclude an app-absent path; covered by PHPUnit `ScanIntakeControllerTest::testSplitStillWorksThroughTake` and `WatchedFileArrivedListenerTest::testAnUnrelatedEventIsIgnored`
