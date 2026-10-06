---
kind: code
depends_on: [scan-intake-with-separator-sheets]
---

# Proposal: scan-intake-from-a-watched-folder

## Summary

filinq's scan intake takes the scanned batches integriq's watched folder hands over, by listening to `OCA\Integriq\Event\WatchedFileArrivedEvent` and accepting a file only once it is stored as a `scanBatch`.

- Rows: supporting, supports 1.7 "Records arrive from a watched folder or a file drop" (not statutory). integriq's `sources-sftp-adapter` chain closes the row; this is the scan intake half D4 names.
- Wave 2.
- Dependencies: `integriq/sources-sftp-adapter-intake-hand-over` (https://github.com/ConductionNL/integriq/issues/2551), which ships the event; `filinq/scan-intake-with-separator-sheets` (open, no issue; https://github.com/ConductionNL/filinq/tree/development/openspec/changes/scan-intake-with-separator-sheets), whose task 3.2 this change carries out.
- Decisions: D4 (integriq owns the folder watcher, filinq's scan intake consumes it).
- Build rules: openspec/woo-build-rules.md

## Why

Woo row 1.7 reads `partial`: nothing watches a folder and ingests what lands in it. Decision D4
(2026-10-05) puts the watcher in integriq, because a folder is a source, and names filinq's scan
intake as a consumer. integriq specs the hand-over in `sources-sftp-adapter-intake-hand-over`
(integriq spec PR #2536, REQ-SFTP-005): for a watched-folder synchronization with `target: intake`
and `intakeApp: filinq` it dispatches

```
new \OCA\Integriq\Event\WatchedFileArrivedEvent(string $synchronizationId, string $intakeApp,
    int $fileId, string $path, string $ownerUid)
```

with `accept(string $reference): void` and `getResult(): array` answering `accepted` (bool),
`intakeApp` (string) and `reference` (string or null). The result starts as not accepted, and only
an acceptance marks or moves the file; a hand-over nobody accepts stays in the folder as
`unclaimed`.

filinq's side does not exist. `scan-intake-with-separator-sheets` (5 of 9 tasks done) left task 3.2
open: "Point the watched-folder job at `ScanBatchService::receive()` for profile folders", noting
there is no watched-folder job in `lib/BackgroundJob/` to point. Today a batch reaches filinq only
through `ScanIntakeController::split()`, which finds or receives the `scanBatch`
(`ScanBatchRepository::findByFile()`, `ScanBatchService::receive(File $file, array $profile)`) and
then cuts it (`ScanBatchService::split(array $batch, array $profile, File $file, Folder $target)`).
D4 replaces design D2's plan to reuse `FolderExtractionJob`'s walk: integriq notices the file, filinq
takes it.

## What changes

1. A listener, `OCA\Filinq\EventListener\WatchedFileArrivedListener`, registered for
   `OCA\Integriq\Event\WatchedFileArrivedEvent`. It acts only when the event's `intakeApp` is
   `filinq`, and does nothing for any other intake.
2. One intake path for both entries. The find-or-receive-then-split flow of
   `ScanIntakeController::split()` moves into one service method,
   `OCA\Filinq\Service\ScanBatchService::take(File $file, array $profile): array`, which answers the
   batch (`split` or `failed`). The controller and the listener both call it.
3. The listener resolves the file by `fileId` in the owner's folder
   (`IRootFolder::getUserFolder($ownerUid)->getById($fileId)`), resolves the scan profile the way
   `ScanIntakeController::resolveProfile()` does for the file's path, calls `take()`, and then calls
   `accept()` with the batch's uuid.
4. Once per file. A file that already has a `scanBatch` (`findByFile()`) is accepted with that
   batch's uuid and is not received or cut again.

## Fail closed

filinq accepts only what it has stored. In every one of these cases it does not call `accept()`, so
the file stays where it was, unmarked, and integriq reports it `unclaimed`:

- the file id resolves to nothing, or to a folder, in the owner's folder;
- the file is not a PDF;
- no scan profile watches its folder;
- storing the batch throws.

A batch that is stored but cannot be cut is accepted: the `scanBatch` in `failed` with its
`lastError` now owns the file, and the inbox shows it, as it does for a batch uploaded by hand.

## App absent

- integriq absent: the event is never dispatched and nothing changes. Registering a listener for a
  class that does not exist is harmless in Nextcloud, and the listener checks
  `class_exists(WatchedFileArrivedEvent::class)` before it reads the event, as integriq's
  `docs/features/watched-folder.md` asks of a consumer. The controller upload keeps working.
- filinq absent: integriq's side (no listener accepts, the file is `unclaimed`).

## What the event does not name

integriq's spec names the constructor arguments, `accept()` and `getResult()`, but no accessors for
`synchronizationId`, `intakeApp`, `fileId`, `path` and `ownerUid`. The builder reads them from the
real class on integriq `development` and copies their names into the contract test with the source
line. If the class is not merged, the builder stops and says so; it does not guess the accessor names.

## Dependencies and wave

- `integriq/sources-sftp-adapter-intake-hand-over` (wave 1), merged on integriq `development`.
- `filinq/scan-intake-with-separator-sheets`: this change ticks its task 3.2 with a pointer here.
- Wave 2. Implements decision D4 for filinq. Done means merged on `development` with CI green; row
  1.7 reads `production` only once store releases of integriq and filinq carry it.
