# Design: work-document-notes

Kind: code, front end only. One shared component in three places, one
extra property in a listing. The store is Nextcloud's.

## Context

Read at development `2088cc1f`.

- `lib/Controller/RedactionOutputController.php:92` `markChecked()` takes a
  `$note` that is written with the redaction review mark. It is the only
  note in filinq.
- `src/views/fileViewer/FileViewerPage.vue` (264 lines) is the in-app
  viewer hosted on My documents (`src/manifest.json`, page `MyDocuments`).
- `src/views/dossier/DossierDetail.vue` shows a dossier's documents as a
  list beside an iframe viewer (:190); picking a document swaps the viewer
  through store state.
- `src/views/intake/IntakeIndex.vue` is a `CnIndexPage` over
  `intakeDocument` objects with row actions Assign (:67) and Reject (:73)
  and no preview. Each intake document names its file in `file`.
- `src/store/modules/myDocuments.js:321` lists My documents with a WebDAV
  `PROPFIND` asking for `resourcetype`, `getcontenttype`,
  `getcontentlength`, `getlastmodified`, `fileid` and `size` (:335 to
  :340).
- `@conduction/nextcloud-vue` 2.57.1 ships `CnNotesCard`: a notes card with
  add, delete-own and "show all", which fetches from
  `{apiBase}/objects/{registerId}/{schemaId}/{objectId}/notes`, that is
  OpenRegister's object notes. OpenRegister stores those as Nextcloud
  comments with object type `openregister`
  (`lib/Service/NoteService.php:80` in the read-only clone).
- Nextcloud keeps file comments under object type `files` and serves them
  at `/remote.php/dav/comments/files/{fileId}`, where it checks that the
  user can reach the file. The Files sidebar shows them, the comments app
  notifies a user named with @, and its search provider finds them.

New: the notes panel in three screens and the comment count on the row.

## Goals / Non-goals

Goals: a colleague reads what another wrote about a document, where she
opens it; no second place for notes; access that follows the file.

Non-goals: notes on records, a notes store in filinq, per-user switching
off, changes to the redaction check remark.

## Decisions

### D1. A note is a Nextcloud comment on the file

filinq writes and reads comments with object type `files` and the file id.
It keeps no copy and adds no schema.

Alternative considered: OpenRegister object notes on a filinq record
(`documentVersion`, `intakeDocument`). Rejected. A document in My
documents often has no record, so notes would split between file and
record. A note on the record would also not show in the Files sidebar,
where colleagues outside filinq look.

### D2. One component, fed by Nextcloud

The panel is `CnNotesCard` with a file as its source, once nextcloud-vue
accepts one. The card then calls the comments endpoint from the browser.
filinq adds no controller and no PHP (ADR-022), and no hand-built notes
list (ADR-012).

Alternative considered: a notes list of filinq's own over the same
endpoint. Rejected. `CnNotesCard` already has the add, delete and empty
states, and a second list would drift from it.

### D3. Where the panel sits

- My documents: beside the viewer in `FileViewerPage.vue`.
- A dossier: under the iframe viewer, for the selected document.
- Intake: a "Notes" row action opening a dialog in `src/dialogs/` with the
  intake document's file as source. Intake has no viewer to sit beside.

### D4. The count comes with the listing

The My documents `PROPFIND` also asks for `oc:comments-count`, and a row
with notes shows a note icon with the number. No extra request per row.

### D5. A note stays where it was written

A comment belongs to one file id. An anonymised copy, a merged PDF, a
share link, a bundle or a generated document is another file, and carries
none. filinq never copies notes onto those. This is what keeps a note
written about a person out of a Woo publication.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| storage | Nextcloud comments | exists, per file, with access checks |
| notification on @mention | Nextcloud's comments app | exists; filinq declares nothing |
| search | Nextcloud's comments search provider | exists; filinq registers no provider (REQ-DDUSP-003) |
| showing and adding | `CnNotesCard` in three screens | presentation only |

## Seed data

No schema. The demo seed adds one comment on a sample document through
the comments endpoint, so the panel is not empty on a demo.

## Risks / trade-offs

- A note is personal data when it names a person. It lives and dies with
  the file: Nextcloud deletes a file's comments with the file. The docs
  say so.
- Until nextcloud-vue ships the file source for `CnNotesCard`, the panel
  cannot be built without breaking ADR-012. The change waits for it.

## Open questions

- Should the intake dialog also show the notes of the message a document
  arrived with (`arrivedWith`)? Useful for attachments; left for the
  intake lane.
