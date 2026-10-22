---
kind: code
depends_on: []
---

# Proposal: share-document-links

Matrix rows `sh-bundle`, `sh-link` and `sh-revoke` in filinq's
`openspec/parity/capabilities.json`, all rated no, built.state none.
Written in the OpenSpec pass of 27 September 2026.

## Why

A clerk has to send an advisor the four documents of a dossier. filinq
offers no way to do it. There is no share code at all: `grep -ri
'IShare|ShareManager|shareLink' lib src` finds nothing. So the clerk leaves
filinq, opens Files, shares each document by hand, and pastes four links
into a mail. Nothing in filinq then says the documents left, who sent them
or until when the links work. Taking a link back means finding it again in
Files.

The bundle row carries demand. The single link and the revoke come along
because a bundle may hold one document, and a link you cannot take back is
not a time-limited link.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `sh-bundle` | Share several documents at once as one bundle behind one link. | no: no share code; the only ZIP code is office package I/O (`lib/Service/Editing/PackagePartIo.php:78`) |
| `sh-link` | Share a document with an outside party through a time-limited link. | no: no share link, time limit or public share anywhere in `lib/` or `src/` |
| `sh-revoke` | Revoke access to a document that was shared. | no: nothing to revoke, and no revoke endpoint in `lib/Controller` or `lib/Service` |

### Demand

- changelog: https://github.com/paperless-ngx/paperless-ngx/pull/11682
  (row `sh-bundle`)

### Competitors rated yes

- Paperless-ngx, `sh-bundle` (source read at v3.2.1): "src/documents/models.py:1032
  ShareLinkBundle (status pending/ready, expiry) ... tasks.py:770
  build_share_link_bundle builds the archive, :862 cleans up expired ones".
  Evidence: https://github.com/paperless-ngx/paperless-ngx/pull/11682
- Paperless-ngx, `sh-link` (source read at v3.2.1): "share links per
  document with optional expiration (src/documents/models.py:972-1000),
  served unauthenticated at /share/<slug>". Evidence:
  https://github.com/paperless-ngx/paperless-ngx/blob/v3.2.1/src/documents/models.py
- Paperless-ngx, `sh-revoke` (source read at v3.2.1): "ShareLinkViewSet
  includes DestroyModelMixin (src/documents/views.py:4679-4683) and the
  share links dialog deletes a link". Evidence:
  https://github.com/paperless-ngx/paperless-ngx/blob/v3.2.1/src/documents/views.py

## What changes

- "Share by link" on one document, and on a selection of documents, in My
  documents.
- One document is shared as itself. Several documents become one archive
  with a manifest, built the way filinq already builds a dossier bundle,
  and that archive is shared.
- The link is a Nextcloud public link with an expiry date, and optionally
  a password. filinq keeps no tokens of its own.
- Every share is recorded in OpenRegister: what was shared, by whom, when,
  until when, and when it was taken back. So the record can say what left
  the building.
- A "Shared links" page lists your links. You take one back there, before
  it runs out.

## Capabilities

### New capabilities

- `document-share-links`: share one or several documents with an outside
  party through one time-limited link, record it, and take it back.

### Modified capabilities

None. `documents-in-and-out-of-the-building` records each download through
a public link (REQ-DIO-06); this change records the link itself and does
not record downloads again.

## Impact

- New schema `documentShare` in `lib/Settings/filinq_register.json`, with
  a lifecycle and a register version bump.
- New `lib/Service/DocumentShareService.php`, a narrow
  `lib/Service/Share/LinkShareGateway.php` interface and its Nextcloud
  implementation, a controller with three routes, and a background job
  that closes expired shares.
- `lib/Service/CaseArchiveService.php`: a manifest over chosen files beside
  the existing one over an object, and the step that writes the archive.
- `src/views/myDocuments/MyDocumentsIndex.vue`: the row action and the bulk
  action. New dialog in `src/modals/`. New page "Shared links" reached from
  My documents, not a menu item.
- `docs/features/`: a section with screenshots.

## Out of scope

- Sending the link by e-mail. The clerk copies it; mail stays with the
  mail apps.
- Recording downloads. `documents-in-and-out-of-the-building` owns that.
- Sharing with a colleague inside the organisation. Nextcloud's own
  sharing does that already, and `multi-tenant-hardening` keeps
  cross-organisation sharing out.
- A public landing page of filinq's own. Nextcloud serves the link.
