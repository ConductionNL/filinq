---
kind: code
---

# Proposal: whole-collection-integrity-scan

## Summary

Filinq checks one document at a time: when someone opens a final document, `FinalDocumentService::verifyChecksum()` compares the frozen checksum with the file on storage and the Versies tab warns. Nothing looks across the collection, so a missing or changed file is found only when somebody happens to open it, and an orphaned file is never found. Ruben accepted on 30 October 2026 (DECISIONS row 268) that row op-integrity is partial and needs a plan for a whole-collection integrity scan.

## What changes

- A new `IntegrityScanService` walks filinq's records and reports three kinds of finding:
  - **missing**: a record points at a file id that no longer resolves (dossier membership, final documents, anonymisation links, correspondence);
  - **changed**: a final document whose file no longer matches its frozen checksum;
  - **orphaned**: an anonymised copy whose source document is gone, found through the anonymisation links.
- It runs nightly as a background job and on demand through `occ filinq:integrity:scan`. Each run is stored as an `integrityScan` object with counts and the findings, so the last result can be read without scanning again.
- The scan only reads. It never deletes, moves or repairs a file.
- Administrators see the last run in a new "Integriteitscontrole" section of the admin page, with the counts, the time and a "Nu controleren" button. A finding links to the record it belongs to.
- When a run finds a missing or changed file, the administrators get one Nextcloud notification per run, not one per file.

## Why

A document management system that loses or silently changes a file must say so before an auditor or a Woo requester finds it. Paperless-ngx ships this as its sanity checker; the row asks for the same.

## Impact

- New `lib/Service/IntegrityScanService.php`, `lib/BackgroundJob/IntegrityScanJob.php`, `lib/Command/IntegrityScanCommand.php`; `appinfo/info.xml` (job and command); `lib/Settings/filinq_register.json` (`integrityScan` schema).
- Reuses `FinalDocumentService::verifyChecksum()`, `DossierContextService::members()` and `AnonymizationLinkReader`.
- `lib/Controller/SettingsController.php` (read the last run, start a run), `src/views/settings/Settings.vue` (new section), `lib/Notification/IntegrityScanNotifier.php` (new, registered the way `LegalHoldNotifier` is).

## Screens

There is no drawn board for the admin section yet. FqVersies shows the per-document warning that already exists; the admin section follows FqInstellingen's layout until a board is drawn.
