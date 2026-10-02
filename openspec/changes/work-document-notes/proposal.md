---
kind: code
depends_on: []
---

# Proposal: work-document-notes

Matrix row `wk-notes` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Written in the OpenSpec pass of 27 September 2026.

## Why

A registrar takes a letter from the intake worklist and sees the amount
does not match the order. She wants to leave a line for the colleague who
handles it: "Check with finance before assigning." filinq has nowhere to
put it. She writes it in a chat, and the next person who opens the letter
never sees it.

The only note in filinq today is the remark a checker adds when marking a
redaction as checked (`RedactionOutputController::markChecked()`,
`lib/Controller/RedactionOutputController.php:92`), which belongs to that
review and nowhere else.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `wk-notes` | Add a note to a document for colleagues. | no: no note feature; the only `note` is the redaction check remark |

### Competitors rated yes

- ZyLAB ONE (docs read 2026-09-26): "'Opmerkingen plaatsen' remarks for
  other processors". Evidence:
  https://docs.zylab.com/01/ZyLAB/Woo-Dutch/050534.html
- Paperless-ngx (source read at v3.2.1): "src/documents/models.py:932 Note
  with text and user; src/documents/views.py:1849 notes endpoint ... notes
  are searchable". Evidence:
  https://github.com/paperless-ngx/paperless-ngx/blob/v3.2.1/src/documents/models.py

## What changes

- A notes panel beside the document on My documents, on a dossier's
  document view and on the intake worklist. Read the notes, add one, delete
  your own.
- A note is a Nextcloud comment on the file. The same note shows in the
  Files sidebar, is found by Nextcloud's search, and a colleague named
  with @ gets a notification, all without filinq code.
- Who can read or add a note follows who can open the file.
- A note stays with the file it was written on. An anonymised copy, a
  share link, a bundle or a publication does not carry it.
- My documents shows how many notes a document has.

## Capabilities

### New capabilities

- `document-notes`: notes for colleagues on a document, kept as Nextcloud
  file comments and shown on filinq's document screens.

### Modified capabilities

None.

## Impact

- `src/views/fileViewer/FileViewerPage.vue` (the viewer on My documents),
  `src/views/dossier/DossierDetail.vue` and `src/views/intake/IntakeIndex.vue`:
  a notes panel with `CnNotesCard`.
- `src/store/modules/myDocuments.js`: ask for the comment count in the
  existing WebDAV listing, and show it on the row.
- `docs/features/`: a section with a screenshot.

No PHP, no schema, no route.

## Out of scope

- Notes on records rather than files. OpenRegister's object notes serve
  that, and `CnNotesCard` already shows them.
- The redaction check remark. It stays part of the review mark.
- Turning notes off per user.

## Cross-app dependencies

- **nextcloud-vue**: `CnNotesCard` reads and writes only OpenRegister
  object notes (`/api/objects/{register}/{schema}/{id}/notes`). It must
  accept a Nextcloud file as its source, reading and writing that file's
  comments through `/remote.php/dav/comments/files/{fileId}`. This is an
  added prop, a minor release, not a major one.
