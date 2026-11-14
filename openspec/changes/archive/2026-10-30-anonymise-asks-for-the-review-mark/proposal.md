---
kind: code
---

# Proposal: anonymise-asks-for-the-review-mark

## Summary

Since #1140 (18 September 2026) `AnonymizationService::runAnonymize` refuses to write a redacted copy until a `redactionReviewMark` exists for the document's current detection run (REQ-RWB-02). The endpoint that records the mark, `POST /api/redaction/documents/{fileId}/checked`, was never called from the screens. Every interactive and folder anonymise was therefore refused with `never_checked`, and the controller turned the uncaught refusal into an HTTP 500 that said nothing useful.

This change puts the human check into the screens and makes the refusal speak:

- The reviewer marks the findings as checked ("Markeren als gecontroleerd") after looking at them. The screen then records the mark over exactly the entities it will send, and only after that can it anonymise.
- Folder and dossier runs write only the files a person checked, one by one, and list the files that still need a check. Nothing is ever marked without a click from a person.
- A refused anonymise answers 409 with a Dutch message ("Controleer eerst de gevonden gegevens") and the reason, never a 500.
- My documents shows per file whether it was checked, by whom and when.

The gate itself stays where it is, in filinq's service. Moving it to OpenRegister is a separate change.

## Why

Without this the anonymise feature does not work at all for a person using the screens, and the error they get blames the server. The Zuiddrecht demo surfaced it.

## What changes

- `lib/Controller/AnonymizationController.php`: `RedactionNotReviewedException` maps to 409 with `error`, `reviewRequired` (the gate's reason) and `document`. The short translated line comes from the gate (`RedactionReviewGate`, carried by the exception as `instruction`).
- `lib/Controller/RedactionOutputController.php`: `markChecked` checks that the caller can open the file before it records anything, and no longer leaks an exception message; new `checkedStatus` (`GET /api/redaction/documents/checked?fileIds=`) returns the latest mark of each file the caller can open.
- `lib/Service/Redaction/RedactionOutputGuard.php`: `marksFor()` reads who checked and when.
- `src/services/reviewMark.js`: one payload builder for the mark and the anonymise call, so the two always describe the same detection run.
- `src/components/ReviewMarkCard.vue`, `src/sidebars/FileViewerSidebar.vue`: the check card from board FqAnonimiseren ("Tweede controle"); the anonymise button waits for it; the dossier batch runs over checked files only and lists the rest.
- `src/store/modules/folderAnonymization.js`, `src/views/anonymization/FolderAnonymizationView.vue`: a check per file; unchecked files are skipped and named.
- `src/views/myDocuments/MyDocumentsIndex.vue`: the Status column (Gecontroleerd / Niet gecontroleerd) returns.

## Relation to other changes

`anonymization-review-workbench` REQ-DDARW-007 plans a `documentReview` object for the same gate. The gate that shipped is `redactionReviewMark` (REQ-RWB-02); this change wires that one and adds no second object.
