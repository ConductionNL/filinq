# Design: share-document-links

Kind: code. One schema, one service behind a narrow share gateway, one
archive step, one dialog, one page, one background job.

## Context

Read at development `2088cc1f`.

- No share code: `grep -rn "IShare\|Share\\\\IManager\|OCP\\\\Share" lib`
  finds only a docblock. `lib/Service/DomainFolderGateway.php:37` records
  why: `OCP\Share\IManager` does not exist under
  `tests/bootstrap-unit.php`, so a class that calls it directly cannot be
  unit tested there.
- `lib/Service/CaseArchiveService.php` builds a bundle of every file on one
  object. `manifestFor()` (:142) takes the files in name order up to a
  ceiling and lists each left-out file with its reason (`ceiling` or
  `permission`). `record()` (:195) writes an `archiveJob` and swallows a
  failed write into a log line (:212). It takes a `fileId` for the archive
  "when one was written", but no code writes one: there is no
  `ZipArchive` or zip streamer in `lib/` outside office package I/O
  (`lib/Service/Editing/PackagePartIo.php:78`), and
  `DocumentProductionController::archiveManifest()` (:151) calls
  `record()` without a file id (:161).
- `archiveJob` (`lib/Settings/filinq_register.json:6067`) has `subject`,
  `requestedBy`, `manifest`, `fileId` and a lifecycle `pending`,
  `running`, `completed`, `failed`. Its authorization allows `create` to
  `admin` only, so a clerk's job write is refused and, through `record()`,
  lost without a trace.
- `lib/Service/DocumentDownloadRecorder.php:82` `record()` writes a
  download with a `linkId` when it came through a public link, and names
  the link rather than a person (`documents-in-and-out-of-the-building`,
  REQ-DIO-06).
- `src/views/myDocuments/MyDocumentsIndex.vue` has a bulk selection mode
  (`bulkSelect`, `selectedIds`, :30 and :307) whose only bulk action is
  delete (:119), and row actions Open, Download, Validate, Compare,
  Versions and Delete (:134 to :188).
- `lib/Service/FinalDocumentService.php:289` `isFinal()` tells whether a
  document is frozen.
- `lib/BackgroundJob/UploadFragmentReaperJob.php:89` names the user's
  filinq documents folder, `DOCUMENTS_FOLDER`.
- `src/manifest.json` already carries 14 main menu items.

New: `documentShare`, `DocumentShareService`, `LinkShareGateway` and its
Nextcloud implementation, `DocumentShareController`,
`ExpiredShareCloserJob`, `ShareByLinkDialog.vue`, `SharedLinksIndex.vue`.

## Goals / Non-goals

Goals: one link for one or several documents; an expiry on every link;
take a link back at any time; the record says what left, by whom, until
when.

Non-goals: sending the link by mail, recording downloads, sharing inside
the organisation, a public page of filinq's own.

## Decisions

### D1. The link is a Nextcloud public share

`DocumentShareService` creates a link share through `OCP\Share\IManager`
with read permission only, an expiry date and an optional password. Taking
it back deletes the share. Nextcloud serves the link, applies its brute
force protection, and shows the share in Files.

Alternative considered: a filinq link store with its own tokens and a
public controller. Rejected. It would be a second public surface to harden
(ADR-054, ADR-108) and a second token store, for what Nextcloud already
does.

### D2. A narrow gateway, so the service can be tested

`LinkShareGateway` has three methods: create a link on a node, delete a
share, and read a share's state. `NextcloudLinkShareGateway` implements it
over `IManager`. PHPUnit drives the service through a stub gateway, and
Newman drives the real one on the instance. This follows
`DomainFolderGateway`, which exists for the same reason.

### D3. One document is shared as itself

A single document gets a link on its own file. The share record keeps the
file version at the moment of sharing, so it can say which version left.

Alternative considered: always build an archive, even for one document.
Rejected. The receiver of one PDF should open it in the browser, not unzip
it.

### D4. Several documents become one archive, built by CaseArchiveService

For two or more documents, `CaseArchiveService` gains a manifest over a
chosen list of file ids beside its manifest over an object, with the same
ceiling and the same `permission` exclusions, and the step that writes the
archive with the manifest inside it. The archive goes in a
`Shared bundles` folder under the sharer's filinq documents folder, the
`archiveJob` records it with its `fileId`, and the link is made on the
archive. The archive is a snapshot: a later edit of a document does not
change what the receiver gets.

Alternative considered: a folder, either a new folder of copies or a link
on a folder. Rejected. A folder link lets the receiver browse without a
manifest, and a folder of copies is an archive without the manifest. The
chosen documents also often sit in different dossiers, so no existing
folder holds exactly them.

The write step also closes the gap in `record()`: a failed job write stops
the share instead of being logged and dropped, and `archiveJob.create` is
opened to authenticated users so a clerk's bundle can be recorded at all.

### D5. Every link has an expiry

The dialog asks for an expiry date, 14 days ahead by default. When
Nextcloud's sharing settings enforce a shorter maximum, that wins. A link
without an expiry is refused. When Nextcloud enforces link passwords, the
dialog asks for one.

### D6. The share is recorded, not the token

A `documentShare` object records the kind (`document` or `bundle`), the
files with their version, the Nextcloud share id, the `archiveJob` for a
bundle, the sharer, when it was made, when it expires, a note on who it is
for, and when and by whom it was taken back. It does not hold the token or
the URL. Those stay in Nextcloud, and a record readable by colleagues must
not be a way to open the link. A download through the link is recorded by
`DocumentDownloadRecorder` with the share id as `linkId`, so a reader can
go from a download to the share and its sharer.

Alternative considered: no filinq record, and read Nextcloud's share table
when asked. Rejected. A deleted share leaves nothing there, so a revoked
link would vanish from the history that must show it.

### D7. Taking back, and running out

"Take back" on the Shared links page deletes the Nextcloud share, deletes
the bundle archive, and moves the record to `revoked`. `ExpiredShareCloserJob`,
a timed job, moves records past their expiry to `expired` and deletes their
archives. Nextcloud already refuses an expired link on access.

### D8. What may be shared

The sharer must be allowed to share each file. Nextcloud's share manager
refuses otherwise, and the dialog shows its reason. When a file carries a
confidentiality label (`ConfidentialityLabelService::getLabelForFile()`,
`lib/Service/ConfidentialityLabelService.php:129`), the dialog names the
label and asks the sharer to confirm. The label and the confirmation go on
the share record.

Alternative considered: refuse any labelled file. Rejected for now. What a
label forbids is an organisation's policy, and filinq has no setting that
says so. A warning with a recorded confirmation is honest about that.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| share state (`active`, `expired`, `revoked`) | `x-openregister-lifecycle` on `documentShare` | a record with three states and two exits |
| making and deleting the link | imperative service through the gateway | a call into Nextcloud's share manager, no object behaviour |
| expiry | timed job | the lifecycle has no clock of its own |
| notifications | none | the sharer is the one acting |

## Seed data

New `documentShare` with properties `kind`, `files` (array of `fileId`,
`name`, `version`, `label`), `shareId`, `archiveJob`, `sharedBy`, `sharedAt`,
`expiresAt`, `note`, `revokedBy`, `revokedAt` and `status`, an
authorization cascade (create and read for authenticated users, update and
delete for the owner and admins), and a register version bump.
`archiveJob.create` opens to authenticated users (D4). The seed adds one
expired bundle share, so the Shared links page is not empty on a demo.

## Risks / trade-offs

- A link reaches whoever has it. The expiry, the optional password and the
  take-back are the controls; the dialog says so in one line.
- A bundle archive doubles storage until the link ends. The job deletes it
  then, and the ceiling bounds its size.
- Nextcloud's own share list in Files also shows the link. Deleting it
  there leaves the filinq record `active` until the job sees the share is
  gone; the job checks the share's state and closes the record as
  `revoked` with "removed in Files" as the reason.

## Open questions

- Should a bundle carry the redaction verdict of each anonymised copy in
  its manifest? It would help the receiver; it is left to the
  anonymisation lane.
