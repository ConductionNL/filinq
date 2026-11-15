---
kind: code
depends_on: []
---

# Proposal: object-files-read-through-the-record

## Why

The flat file list resolved every file through the reader's own Files
(`getUserFolder($uid)->getById()`). A file stored in an OpenRegister object's
folder lives in the openregister account's home, which no reader has a mount
on, so such a file never resolved and its record was left out for everyone.
On the demo set, case 2026-0110 "Verlichting fietspad Lindelaan" has three
documents in the folder of dossier "Fietspad Lindelaan"; `getById(21064)`
answered nothing for pieter and admin, and dossiq's Gekoppeld card stayed empty.

The case bundle copied files the same way, so the same files would have been
written to the manifest as missing.

## What changes

- A new `ReadableFileResolver` answers "the file behind this id, as the
  signed-in reader may read it": first the reader's own Files (owned, shared,
  group folder), then, for a file in an object's folder, through OpenRegister's
  `FileReadScope::mayReadFile()`, which applies the read rule of the object
  that owns the file (and any file restriction).
- The file is opened through OpenRegister's `FileService::getFileById()` only
  AFTER that check admits the reader. A file the reader may not read is never
  opened.
- `FlatFileListService` and `CaseArchiveWriter` both resolve files through it.
- Without OpenRegister, only the reader's own Files count, as before.
